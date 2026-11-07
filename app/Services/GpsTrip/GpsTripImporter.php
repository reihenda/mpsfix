<?php

namespace App\Services\GpsTrip;

use App\Models\GpsGeofence;
use App\Models\GpsTrip;
use App\Models\GpsTripDibuang;
use App\Models\GpsTripImport;
use App\Models\OperatorGtm;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GpsTripImporter
{
    private const KATA_BADAN_USAHA = ['pt', 'pd', 'cv', 'ud', 'p', 'tbk'];

    public function __construct(private GpsTripBuilder $builder)
    {
    }

    /**
     * Simpan trip hasil parsing ke database (idempoten: upload ulang file yang sama tidak menggandakan).
     *
     * Aturan upload ulang, per trip (kunci: nopol + waktu mulai):
     *  - belum ada            -> dibuat
     *  - ada, belum diedit    -> dihitung ulang, driver yang sudah dipilih dipertahankan
     *  - ada, sudah diedit    -> dibiarkan apa adanya
     *  - ada & lengkap, tapi di file ini terbaca belum selesai (file berakhir lebih awal) -> dibiarkan
     *  Pencocokan memakai kunci_waktu_mulai (waktu mulai asli GPS), jadi trip yang jam mulainya diedit
     *  tetap dikenali.
     *  - ada di DB tapi tidak lagi terdeteksi, belum diedit & belum ada driver -> dihapus
     */
    /**
     * @param array $atributBaru atribut tambahan HANYA untuk trip yang baru dibuat (mis. sumber, divalidasi)
     */
    public function import(array $parsed, string $namaFile, ?int $userId, array $atributBaru = []): array
    {
        $geofence = GpsGeofence::all()->keyBy(fn ($g) => GpsTripParser::normalisasiNama($g->nama));
        $batasTrip = function (string $nama) use ($geofence): bool {
            $g = $geofence->get(GpsTripParser::normalisasiNama($nama));

            return $g ? $g->isBatasTrip() : self::sarankanTipe($nama) === GpsGeofence::TIPE_CUSTOMER;
        };

        $operators = OperatorGtm::select('id', 'nama')->get();
        $stat = ['baru' => 0, 'diperbarui' => 0, 'dilewati' => 0, 'dihapus' => 0, 'dibuang' => 0, 'dipertahankan' => 0, 'total' => 0, 'kendaraan' => 0];

        DB::transaction(function () use ($parsed, $namaFile, $userId, $batasTrip, $operators, $atributBaru, &$stat) {
            $import = GpsTripImport::create([
                'nama_file' => $namaFile,
                'periode_mulai' => $parsed['periode'][0],
                'periode_selesai' => $parsed['periode'][1],
                'user_id' => $userId,
            ]);

            foreach ($parsed['kendaraan'] as $nopol => $kendaraan) {
                $trips = $this->builder->bangun($kendaraan['segmen'], $batasTrip);
                $stat['kendaraan']++;

                $batasAwal = min(array_column($kendaraan['segmen'], 'mulai'));
                $batasAkhir = max(array_column($kendaraan['segmen'], 'selesai'));

                // Cocokkan dengan waktu mulai ASLI dari GPS (kunci_waktu_mulai), bukan waktu_mulai yang
                // bisa sudah diedit admin, supaya trip yang diedit tidak dibuat ganda.
                $ada = GpsTrip::where('nopol', $nopol)
                    ->whereBetween('kunci_waktu_mulai', [$batasAwal, $batasAkhir])
                    ->get()
                    ->keyBy(fn ($t) => ($t->kunci_waktu_mulai ?? $t->waktu_mulai)->format('Y-m-d H:i:s'));

                // Trip noise yang pernah dihapus admin tidak dibuat lagi
                $dibuang = GpsTripDibuang::where('nopol', $nopol)
                    ->whereBetween('waktu_mulai', [$batasAwal, $batasAkhir])
                    ->pluck('waktu_mulai')
                    ->map(fn ($w) => Carbon::parse($w)->format('Y-m-d H:i:s'))
                    ->flip();

                $terdeteksi = [];

                foreach ($trips as $trip) {
                    if (isset($dibuang[$trip['waktu_mulai']])) {
                        $stat['dibuang']++;
                        continue;
                    }

                    $terdeteksi[$trip['waktu_mulai']] = true;
                    $stat['total']++;
                    $operatorId = $this->cocokkanDriver($trip['driver_gps'], $operators);

                    $data = $trip + [
                        'nopol' => $nopol,
                        'model_kendaraan' => $kendaraan['model'],
                        'gps_trip_import_id' => $import->id,
                    ];

                    $lama = $ada->get($trip['waktu_mulai']);

                    if (!$lama) {
                        // Hindari bentrok unique (nopol + waktu_mulai) bila trip lain yang sudah diedit
                        // kebetulan memakai waktu mulai ini
                        $bentrok = GpsTrip::where('nopol', $nopol)->where('waktu_mulai', $trip['waktu_mulai'])->exists();
                        if ($bentrok) {
                            $stat['dilewati']++;
                            continue;
                        }

                        GpsTrip::create($atributBaru + $data + [
                            'kunci_waktu_mulai' => $trip['waktu_mulai'],
                            'operator_gtm_id' => $operatorId,
                        ]);
                        $stat['baru']++;
                    } elseif ($lama->diedit) {
                        $stat['dilewati']++;
                    } elseif ($lama->status === 'selesai' && $trip['status'] === 'tidak_lengkap') {
                        // File ini berakhir sebelum trip selesai (periode lebih pendek dari upload sebelumnya):
                        // jangan turunkan trip yang sudah lengkap menjadi "tidak lengkap".
                        $stat['dipertahankan']++;
                    } else {
                        if ($lama->operator_gtm_id === null && $operatorId) {
                            $data['operator_gtm_id'] = $operatorId;
                        }
                        $lama->update($data + ['kunci_waktu_mulai' => $trip['waktu_mulai']]);
                        $stat['diperbarui']++;
                    }
                }

                foreach ($ada as $waktu => $lama) {
                    if (!isset($terdeteksi[$waktu]) && !$lama->diedit && $lama->operator_gtm_id === null) {
                        $lama->delete();
                        $stat['dihapus']++;
                    }
                }
            }

            $import->update([
                'jumlah_kendaraan' => $stat['kendaraan'],
                'jumlah_trip' => $stat['total'],
            ]);
        });

        return $stat;
    }

    /**
     * Tebakan awal klasifikasi geofence dari namanya.
     */
    public static function sarankanTipe(string $nama): string
    {
        if (preg_match('/^\s*spbg\b/i', $nama)) {
            return GpsGeofence::TIPE_SPBG;
        }

        if (preg_match('/workshop|bengkel|pool|garasi|kantor|depo/i', $nama)) {
            return GpsGeofence::TIPE_LAINNYA;
        }

        return GpsGeofence::TIPE_CUSTOMER;
    }

    /**
     * Tebak customer terdaftar dari nama geofence.
     * Cocok bila tiap kata geofence adalah awalan dari kata di nama customer
     * ("PT DIMA BEVERAGE INTERN" ~ "PT Dima Beverages International"),
     * atau singkatan ("RRC" ~ "PT Raja Roti Cemerlang"). Hanya dikembalikan bila tepat satu kandidat.
     *
     * @param Collection<int, User> $customers
     */
    public static function sarankanCustomer(string $nama, Collection $customers): ?int
    {
        $kataGeofence = self::kata($nama);
        if (empty($kataGeofence)) {
            return null;
        }

        $kandidat = [];
        foreach ($customers as $customer) {
            $kataCustomer = self::kata($customer->name);
            if (empty($kataCustomer)) {
                continue;
            }

            $cocok = true;
            foreach ($kataGeofence as $kata) {
                $ketemu = false;
                foreach ($kataCustomer as $kc) {
                    if (strlen($kata) >= 3 && str_starts_with($kc, $kata)) {
                        $ketemu = true;
                        break;
                    }
                }
                if (!$ketemu) {
                    $cocok = false;
                    break;
                }
            }

            if (!$cocok && count($kataGeofence) === 1) {
                $singkatan = implode('', array_map(fn ($k) => $k[0], $kataCustomer));
                $cocok = strlen($kataGeofence[0]) >= 2 && $kataGeofence[0] === $singkatan;
            }

            if ($cocok) {
                $kandidat[] = $customer->id;
            }
        }

        return count($kandidat) === 1 ? $kandidat[0] : null;
    }

    /**
     * Cocokkan nama driver di laporan GPS ke Operator GTM: nama sama persis (tanpa beda huruf besar/kecil),
     * atau hanya selisih satu huruf bila kandidatnya tunggal. Bila driver lebih dari satu orang -> kosong.
     */
    public function cocokkanDriver(?string $driverGps, Collection $operators): ?int
    {
        $driverGps = trim((string) $driverGps);
        if ($driverGps === '' || str_contains($driverGps, ',')) {
            return null;
        }

        $target = mb_strtolower($driverGps);

        foreach ($operators as $op) {
            if (mb_strtolower(trim($op->nama)) === $target) {
                return $op->id;
            }
        }

        if (strlen($target) < 4) {
            return null;
        }

        $mirip = [];
        foreach ($operators as $op) {
            $nama = mb_strtolower(trim($op->nama));
            if (strlen($nama) >= 4 && levenshtein($nama, $target) <= 1) {
                $mirip[] = $op->id;
            }
        }

        return count($mirip) === 1 ? $mirip[0] : null;
    }

    /**
     * Normalisasi nopol untuk pembandingan: huruf besar, tanpa spasi/tanda, huruf O dianggap angka 0.
     */
    public static function kunciNopol(?string $nopol): string
    {
        return str_replace('O', '0', strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $nopol)));
    }

    private static function kata(string $nama): array
    {
        $kata = preg_split('/[^a-z0-9]+/', mb_strtolower($nama), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter($kata, fn ($k) => !in_array($k, self::KATA_BADAN_USAHA, true)));
    }
}
