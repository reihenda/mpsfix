<?php

namespace App\Services\GpsTrip;

use App\Models\GpsTrip;
use App\Models\OperatorGtmLembur;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Menyusun data lembur operator (sesi 1-5 per tanggal) dari trip GPS yang sudah punya driver.
 *
 * Aturan penyusunan:
 *  - Hanya trip dengan dihitung=true, selesai, dan sudah ada driver.
 *  - Trip dipecah menjadi "blok kerja". Trip hasil import dipecah di jeda antar segmen > AMBANG_JEDA
 *    (kendaraan parkir lama di titik tak terbaca tidak dihitung sebagai jam kerja).
 *    Trip yang sudah diedit manual dipakai apa adanya (waktu mulai - selesai hasil edit).
 *  - Blok yang bertumpuk (satu driver, dua kendaraan, jam sama) digabung agar jam tidak terhitung ganda.
 *  - Blok dikelompokkan per tanggal mulai. Lebih dari 5 blok sehari: gabungkan blok dengan jeda terkecil.
 *  - Tiap blok = satu sesi; jeda di antara sesi tidak dihitung (sama seperti pengelompokan Absen Trip).
 */
class GpsTripLemburService
{
    public const MAKS_SESI = 5;

    /**
     * @return array{baris: array<int, array>, tanpa_driver: int, belum_validasi: int}
     */
    public function susun(Carbon $awal, Carbon $akhir): array
    {
        // Hanya trip yang sudah divalidasi yang ikut hitungan lembur. Trip Excel lama otomatis
        // berstatus tervalidasi; trip dari Cartrack baru masuk setelah divalidasi staff operasional/admin.
        $trips = GpsTrip::with('operator')
            ->where('dihitung', true)
            ->where('divalidasi', true)
            ->whereNotNull('waktu_selesai')
            ->whereBetween('waktu_mulai', [$awal, $akhir])
            ->orderBy('waktu_mulai')
            ->get();

        $belumValidasi = GpsTrip::where('dihitung', true)
            ->where('divalidasi', false)
            ->whereNotNull('waktu_selesai')
            ->whereBetween('waktu_mulai', [$awal, $akhir])
            ->count();

        $tanpaDriver = $trips->whereNull('operator_gtm_id')->count();
        $baris = [];

        foreach ($trips->whereNotNull('operator_gtm_id')->groupBy('operator_gtm_id') as $operatorId => $tripOperator) {
            $operator = $tripOperator->first()->operator;
            if (!$operator) {
                continue;
            }

            $perTanggal = [];
            foreach ($this->gabungkanTumpang($this->kumpulkanBlok($tripOperator)) as $blok) {
                $perTanggal[date('Y-m-d', $blok['mulai'])][] = $blok;
            }

            foreach ($perTanggal as $tanggal => $bloks) {
                $bloks = $this->ringkasKeMaksimal($bloks);

                $sesi = array_map(fn ($b) => [
                    'masuk' => $this->jam($b['mulai']),
                    'keluar' => $this->jam($b['selesai']),
                ], $bloks);

                $totalMenit = 0;
                foreach ($sesi as $s) {
                    $totalMenit += OperatorGtmLembur::calculateSessionDuration($s['masuk'], $s['keluar']);
                }
                $hitung = OperatorGtmLembur::calculateUpahLembur($totalMenit, $operator->jam_kerja);

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
                $lembur->save();

                GpsTrip::whereIn('id', $baris['trip_ids'])->update(['operator_gtm_lembur_id' => $lembur->id]);

                $hasil[$baru ? 'dibuat' : 'diperbarui']++;
            }
        });

        return $hasil;
    }

    /**
     * @return array<int, array{mulai:int, selesai:int, trip_ids:int[], nopol:string[]}>
     */
    private function kumpulkanBlok($trips): array
    {
        $bloks = [];

        foreach ($trips as $trip) {
            $rentang = [];

            if (!$trip->diedit && !empty($trip->segmen)) {
                $segmen = $trip->segmen;
                usort($segmen, fn ($a, $b) => strcmp($a['mulai'], $b['mulai']));

                $mulai = strtotime($segmen[0]['mulai']);
                $selesai = strtotime($segmen[0]['selesai']);
                foreach (array_slice($segmen, 1) as $seg) {
                    $segMulai = strtotime($seg['mulai']);
                    if ($segMulai - $selesai > GpsTrip::AMBANG_JEDA_DETIK) {
                        $rentang[] = [$mulai, $selesai];
                        $mulai = $segMulai;
                    }
                    $selesai = max($selesai, strtotime($seg['selesai']));
                }
                $rentang[] = [$mulai, $selesai];
            } else {
                $rentang[] = [$trip->waktu_mulai->getTimestamp(), $trip->waktu_selesai->getTimestamp()];
            }

            foreach ($rentang as [$m, $s]) {
                $bloks[] = ['mulai' => $m, 'selesai' => $s, 'trip_ids' => [$trip->id], 'nopol' => [$trip->nopol]];
            }
        }

        return $bloks;
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
