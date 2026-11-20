<?php

namespace App\Services\GpsTrip;

use App\Models\GpsHentiBukti;
use App\Models\GpsTrip;
use App\Models\OperatorGtmLembur;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Menyusun data lembur operator (sesi 1-5 per tanggal) dari trip GPS yang sudah punya driver.
 *
 * Aturan penyusunan:
 *  - Hanya trip dengan dihitung=true, selesai, dan sudah ada driver.
 *  - Tiap trip = satu "blok kerja" dari waktu mulai (meninggalkan customer) sampai waktu selesai
 *    (tiba di customer). Jeda di tengah trip (mobil dimatikan menunggu antrean gas di SPBG, dll.)
 *    tetap dihitung sebagai jam kerja. Satu pengecualian: berhenti lebih dari 1 jam di titik yang sama
 *    di luar geofence mana pun (mogok / di tengah jalan) dipotong, lihat GpsTrip::hentiDiJalan(),
 *    asalkan kejadian itu sudah punya foto bukti (GpsHentiBukti).
 *  - Blok yang bertumpuk (satu driver, dua kendaraan, jam sama) digabung agar jam tidak terhitung ganda.
 *  - Blok dikelompokkan per tanggal mulai. Lebih dari 5 blok sehari: gabungkan blok dengan jeda terkecil.
 *  - Tiap blok = satu sesi; jeda di antara sesi tidak dihitung (sama seperti pengelompokan Absen Trip).
 */
class GpsTripLemburService
{
    public const MAKS_SESI = 5;

    /** Kejadian berhenti lama yang sudah punya foto bukti (kunci => jumlah foto); diisi di susun(). */
    private array $bukti = [];

    /**
     * @return array{baris: array<int, array>, tanpa_driver: int, belum_validasi: int}
     */
    public function susun(Carbon $awal, Carbon $akhir, ?int $operatorId = null): array
    {
        // Hanya trip yang sudah divalidasi yang ikut hitungan lembur. Trip Excel lama otomatis
        // berstatus tervalidasi; trip dari Cartrack baru masuk setelah divalidasi staff operasional/admin.
        $this->bukti = GpsHentiBukti::himpunan($awal->copy()->subDay(), $akhir->copy()->addDay());

        // Mulai sehari lebih awal: trip yang melewati tengah malam dari hari sebelumnya ikut mengisi hari pertama
        $trips = GpsTrip::with('operator')
            ->where('dihitung', true)
            ->where('divalidasi', true)
            ->whereNotNull('waktu_selesai')
            ->whereBetween('waktu_mulai', [$awal->copy()->subDay(), $akhir])
            ->when($operatorId, fn ($q) => $q->where('operator_gtm_id', $operatorId))
            ->orderBy('waktu_mulai')
            ->get();

        $belumValidasi = GpsTrip::where('dihitung', true)
            ->where('divalidasi', false)
            ->whereNotNull('waktu_selesai')
            ->whereBetween('waktu_mulai', [$awal, $akhir])
            ->count();

        $tanpaDriver = $trips->whereNull('operator_gtm_id')->filter(fn ($t) => $t->waktu_mulai->gte($awal))->count();
        $tglAwal = $awal->toDateString();
        $tglAkhir = $akhir->toDateString();
        $baris = [];

        foreach ($trips->whereNotNull('operator_gtm_id')->groupBy('operator_gtm_id') as $operatorId => $tripOperator) {
            $operator = $tripOperator->first()->operator;
            if (!$operator) {
                continue;
            }

            $perTanggal = [];
            // Blok yang melewati tengah malam dipecah per hari: 20:00-04:00 -> 20:00-00:00 (hari ini) + 00:00-04:00 (besok)
            foreach ($this->potongTengahMalam($this->gabungkanTumpang($this->kumpulkanBlok($tripOperator))) as $blok) {
                $perTanggal[date('Y-m-d', $blok['mulai'])][] = $blok;
            }

            foreach ($perTanggal as $tanggal => $bloks) {
                if ($tanggal < $tglAwal || $tanggal > $tglAkhir) {
                    continue;
                }
                $bloks = $this->ringkasKeMaksimal($bloks);

                $sesi = array_map(fn ($b) => [
                    'masuk' => $this->jam($b['mulai']),
                    // Satu hari penuh (00:00 s/d 00:00 besok) tidak bisa ditulis sebagai jam: pakai 23:59
                    'keluar' => $b['selesai'] - $b['mulai'] >= 86400 ? '23:59' : $this->jam($b['selesai']),
                ], $bloks);

                $totalMenit = 0;
                foreach ($sesi as $s) {
                    $totalMenit += OperatorGtmLembur::calculateSessionDuration($s['masuk'], $s['keluar']);
                }
                $hitung = OperatorGtmLembur::calculateUpahLembur($totalMenit, $operator->jam_kerja);

                $dipotong = [];
                foreach (array_unique(array_merge(...array_column($bloks, 'trip_ids'))) as $tripId) {
                    $trip = $tripOperator->firstWhere('id', $tripId);
                    $hariMulai = strtotime($tanggal.' 00:00:00');
                    $hariAkhir = strtotime($tanggal.' 00:00:00 +1 day');
                    foreach ($trip ? $trip->hentiDenganBukti($this->bukti) : [] as $h) {
                        if ($h['selesai'] > $hariMulai && $h['mulai'] < $hariAkhir) {
                            $dipotong[] = $h + ['nopol' => $trip->nopol];
                        }
                    }
                }

                $existing = OperatorGtmLembur::where('operator_gtm_id', $operatorId)->where('tanggal', $tanggal)->first();
                $sesiExisting = $existing ? $this->sesiDari($existing) : null;

                $baris[] = [
                    'kunci' => "{$operatorId}|{$tanggal}",
                    'operator_id' => (int) $operatorId,
                    'operator' => $operator->nama,
                    'jam_kerja' => $operator->jam_kerja ?: 8,
                    'tanggal' => $tanggal,
                    'sesi' => $sesi,
                    'trip_ids' => array_values(array_unique(array_merge(...array_column($bloks, 'trip_ids')))),
                    'nopol' => array_values(array_unique(array_merge(...array_column($bloks, 'nopol')))),
                    'dipotong' => $dipotong,
                    'total_menit' => $totalMenit,
                    'lembur_menit' => $hitung['total_jam_lembur'],
                    'upah' => $hitung['upah_lembur'],
                    'status' => $existing === null ? 'baru' : ($sesiExisting === $sesi ? 'sama' : 'berbeda'),
                    'existing' => $existing ? [
                        'sesi' => $sesiExisting,
                        'total_menit' => (int) $existing->total_jam_kerja,
                        'lembur_menit' => (int) $existing->total_jam_lembur,
                    ] : null,
                ];
            }
        }

        usort($baris, fn ($a, $b) => [$a['operator'], $a['tanggal']] <=> [$b['operator'], $b['tanggal']]);

        return ['baris' => $baris, 'tanpa_driver' => $tanpaDriver, 'belum_validasi' => $belumValidasi];
    }

    /**
     * Simpan baris terpilih ke operator_gtm_lembur. Data dihitung ulang di server dari trip,
     * bukan dipercaya dari form. Baris berstatus 'sama' dilewati.
     *
     * @param string[] $kunci daftar "operatorId|Y-m-d"
     */
    public function terapkan(Carbon $awal, Carbon $akhir, array $kunci): array
    {
        $hasil = ['dibuat' => 0, 'diperbarui' => 0, 'dilewati' => 0];
        $pilih = array_flip($kunci);

        DB::transaction(function () use ($awal, $akhir, $pilih, &$hasil) {
            foreach ($this->susun($awal, $akhir)['baris'] as $baris) {
                if (!isset($pilih[$baris['kunci']])) {
                    continue;
                }
                if ($baris['status'] === 'sama') {
                    $hasil['dilewati']++;
                    continue;
                }

                $baru = $this->tulis($baris);

                $hasil[$baru ? 'dibuat' : 'diperbarui']++;
            }
        });

        return $hasil;
    }

    /**
     * Terapkan otomatis ke data lembur untuk hari-hari yang tersentuh perubahan trip (validasi, batal validasi,
     * ganti driver, edit / hapus trip, tambah / hapus foto bukti). Tiap hari dihitung ulang dari SEMUA trip
     * driver itu yang valid di hari tersebut, jadi hasilnya sama dengan menerapkan lewat halaman Terapkan ke Lembur.
     *
     * Data lembur yang diisi manual (tidak berasal dari trip GPS) TIDAK ditimpa; hari itu dilaporkan supaya
     * bisa dicek di halaman Terapkan ke Lembur. Hari yang tidak lagi punya trip valid dan datanya berasal dari
     * trip GPS dihapus dari data lembur.
     *
     * @param iterable<array{0:int|null, 1:string}> $hari          pasangan [operator_gtm_id, 'Y-m-d']
     * @param int[]                                 $lemburDariGps id data lembur yang diketahui berasal dari trip GPS
     *                                                             (ditangkap sebelum trip diubah / dihapus)
     * @return array{diterapkan:int, dihapus:int, manual:array<int, string>}
     */
    public function terapkanHari(iterable $hari, array $lemburDariGps = []): array
    {
        $perOperator = [];
        foreach ($hari as [$operatorId, $tanggal]) {
            if ($operatorId) {
                $perOperator[(int) $operatorId][$tanggal] = true;
            }
        }

        $hasil = ['diterapkan' => 0, 'dihapus' => 0, 'manual' => []];
        if (!$perOperator) {
            return $hasil;
        }
        $dariGps = array_flip(array_filter($lemburDariGps));

        DB::transaction(function () use ($perOperator, $dariGps, &$hasil) {
            foreach ($perOperator as $operatorId => $tanggal) {
                $daftar = array_keys($tanggal);
                sort($daftar);
                // Diperlebar sehari di kedua sisi: blok kerja bisa jatuh di tanggal lain dari mulai trip
                $baris = collect($this->susun(
                    Carbon::parse($daftar[0])->subDay()->startOfDay(),
                    Carbon::parse(end($daftar))->addDay()->endOfDay(),
                    $operatorId
                )['baris'])->keyBy('tanggal');

                foreach ($daftar as $tgl) {
                    $ada = OperatorGtmLembur::where('operator_gtm_id', $operatorId)->where('tanggal', $tgl)->first();
                    $asalGps = $ada && ($ada->sumber_gps || isset($dariGps[$ada->id]) || GpsTrip::where('operator_gtm_lembur_id', $ada->id)->exists());

                    if ($ada && !$asalGps) {
                        $hasil['manual'][] = ($ada->operator->nama ?? 'Operator #'.$operatorId).' '.Carbon::parse($tgl)->format('d/m');
                        $hasil['manual_detail'][] = [
                            'label' => ($ada->operator->nama ?? 'Operator #'.$operatorId).' '.Carbon::parse($tgl)->format('d/m'),
                            'bulan' => PeriodeLembur::bulanUntuk(Carbon::parse($tgl)),
                        ];
                        continue;
                    }

                    if ($b = $baris->get($tgl)) {
                        $this->tulis($b);
                        if ($b['status'] !== 'sama') {
                            $hasil['diterapkan']++;
                        }
                    } elseif ($ada) {
                        GpsTrip::where('operator_gtm_lembur_id', $ada->id)->update(['operator_gtm_lembur_id' => null]);
                        $ada->delete();
                        $hasil['dihapus']++;
                    }
                }
            }
        });

        // Tampilkan peringatan (dengan tautan ke halaman pembanding) di halaman berikutnya
        if (!empty($hasil['manual_detail']) && app()->bound('session')) {
            session()->flash('lembur_manual', $hasil['manual_detail']);
        }

        return $hasil;
    }

    /** Pasangan [operator, tanggal] yang disentuh sebuah trip (tanggal mulai & selesai). */
    public static function hariTrip(GpsTrip $trip, ?int $operatorId = null): array
    {
        $op = $operatorId ?? $trip->operator_gtm_id;
        if (!$op || !$trip->waktu_mulai) {
            return [];
        }
        $hari = [[$op, $trip->waktu_mulai->toDateString()]];
        if ($trip->waktu_selesai && $trip->waktu_selesai->toDateString() !== $trip->waktu_mulai->toDateString()) {
            $hari[] = [$op, $trip->waktu_selesai->toDateString()];
        }

        return $hari;
    }

    /** Kalimat ringkas hasil terapkanHari() untuk pesan ke admin. */
    public static function ringkasan(array $hasil): string
    {
        $bagian = [];
        if ($hasil['diterapkan']) {
            $bagian[] = "data lembur {$hasil['diterapkan']} hari diperbarui";
        }
        if ($hasil['dihapus']) {
            $bagian[] = "{$hasil['dihapus']} hari dihapus dari data lembur (tidak ada lagi trip valid)";
        }
        $teks = $bagian ? ' '.ucfirst(implode(', ', $bagian)).'.' : '';
        if ($hasil['manual']) {
            $teks .= ' '.count($hasil['manual']).' hari tidak diubah karena sudah ada input lembur manual (lihat peringatan kuning).';
        }

        return $teks;
    }

    /**
     * Tulis satu baris hasil susun() ke operator_gtm_lembur dan tautkan ulang trip-tripnya.
     *
     * @return bool true bila data lembur baru dibuat
     */
    private function tulis(array $baris): bool
    {
        $lembur = OperatorGtmLembur::firstOrNew([
            'operator_gtm_id' => $baris['operator_id'],
            'tanggal' => $baris['tanggal'],
        ]);
        $baru = !$lembur->exists;

        for ($i = 1; $i <= self::MAKS_SESI; $i++) {
            $s = $baris['sesi'][$i - 1] ?? null;
            $lembur->{"jam_masuk_sesi_{$i}"} = $s['masuk'] ?? null;
            $lembur->{"jam_keluar_sesi_{$i}"} = $s['keluar'] ?? null;
        }
        $lembur->total_jam_kerja = $baris['total_menit'];
        $lembur->total_jam_lembur = $baris['lembur_menit'];
        $lembur->upah_lembur = $baris['upah'];
        $lembur->sumber_gps = true;
        $lembur->save();

        // Satu trip hanya bisa tertaut ke satu data lembur: tautkan ke hari MULAI trip. Trip yang melewati
        // tengah malam tetap mengisi hari berikutnya (lewat sesi), tanpa ditautkan ke data lembur hari itu.
        $tautkan = GpsTrip::whereIn('id', $baris['trip_ids'])->whereDate('waktu_mulai', $baris['tanggal'])->pluck('id')->all();
        GpsTrip::where('operator_gtm_lembur_id', $lembur->id)->whereNotIn('id', $tautkan)->update(['operator_gtm_lembur_id' => null]);
        GpsTrip::whereIn('id', $tautkan)->update(['operator_gtm_lembur_id' => $lembur->id]);

        return $baru;
    }

    /**
     * @return array<int, array{mulai:int, selesai:int, trip_ids:int[], nopol:string[]}>
     */
    private function kumpulkanBlok($trips): array
    {
        $bloks = [];

        foreach ($trips as $trip) {
            // Berhenti > 1 jam di titik yang sama di luar geofence (mogok / di tengah jalan) dipotong
            // dari jam kerja, TETAPI hanya bila kejadian itu sudah punya foto bukti: trip dipecah menjadi
            // beberapa blok di sekitar waktu berhenti itu.
            $mulai = $trip->waktu_mulai->getTimestamp();
            foreach ($trip->hentiDenganBukti($this->bukti) as $henti) {
                if (!$henti['terbukti']) {
                    continue;
                }
                $bloks[] = ['mulai' => $mulai, 'selesai' => $henti['mulai'], 'trip_ids' => [$trip->id], 'nopol' => [$trip->nopol]];
                $mulai = $henti['selesai'];
            }
            $bloks[] = ['mulai' => $mulai, 'selesai' => $trip->waktu_selesai->getTimestamp(), 'trip_ids' => [$trip->id], 'nopol' => [$trip->nopol]];
        }

        return $bloks;
    }

    /** Pecah blok yang melewati tengah malam menjadi potongan per hari kalender. */
    private function potongTengahMalam(array $bloks): array
    {
        $hasil = [];
        foreach ($bloks as $b) {
            $mulai = $b['mulai'];
            while ($mulai < $b['selesai']) {
                $tengahMalam = strtotime(date('Y-m-d', $mulai).' 00:00:00 +1 day');
                $selesai = min($b['selesai'], $tengahMalam);
                $hasil[] = array_merge($b, ['mulai' => $mulai, 'selesai' => $selesai]);
                $mulai = $selesai;
            }
        }

        return $hasil;
    }

    private function gabungkanTumpang(array $bloks): array
    {
        usort($bloks, fn ($a, $b) => $a['mulai'] <=> $b['mulai']);

        $hasil = [];
        foreach ($bloks as $blok) {
            $akhir = count($hasil) - 1;
            if ($akhir >= 0 && $blok['mulai'] <= $hasil[$akhir]['selesai']) {
                $hasil[$akhir] = $this->gabung($hasil[$akhir], $blok);
            } else {
                $hasil[] = $blok;
            }
        }

        return $hasil;
    }

    private function ringkasKeMaksimal(array $bloks): array
    {
        while (count($bloks) > self::MAKS_SESI) {
            $indeks = 0;
            $jedaMin = PHP_INT_MAX;
            for ($i = 0; $i < count($bloks) - 1; $i++) {
                $jeda = $bloks[$i + 1]['mulai'] - $bloks[$i]['selesai'];
                if ($jeda < $jedaMin) {
                    $jedaMin = $jeda;
                    $indeks = $i;
                }
            }
            array_splice($bloks, $indeks, 2, [$this->gabung($bloks[$indeks], $bloks[$indeks + 1])]);
        }

        return $bloks;
    }

    private function gabung(array $a, array $b): array
    {
        return [
            'mulai' => min($a['mulai'], $b['mulai']),
            'selesai' => max($a['selesai'], $b['selesai']),
            'trip_ids' => array_merge($a['trip_ids'], $b['trip_ids']),
            'nopol' => array_merge($a['nopol'], $b['nopol']),
        ];
    }

    private function jam(int $timestamp): string
    {
        return Carbon::createFromTimestamp($timestamp, config('app.timezone'))->format('H:i');
    }

    private function sesiDari(OperatorGtmLembur $lembur): array
    {
        $sesi = [];
        for ($i = 1; $i <= self::MAKS_SESI; $i++) {
            $masuk = $lembur->{"jam_masuk_sesi_{$i}"};
            $keluar = $lembur->{"jam_keluar_sesi_{$i}"};
            if ($masuk && $keluar) {
                $sesi[] = ['masuk' => substr($masuk, 0, 5), 'keluar' => substr($keluar, 0, 5)];
            }
        }

        return $sesi;
    }
}
