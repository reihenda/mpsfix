<?php

namespace App\Services\GpsTrip;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

/**
 * Membaca laporan "Standard Summary Trip Report" dari GPS tracking.
 *
 * Struktur laporan: per kendaraan ada blok berisi baris "Registration:" lalu nopol,
 * header kolom (Driver, Start Time, End Time, ..., Start Geofence, End Geofence, ...),
 * kemudian baris-baris segmen "ignition on -> ignition off".
 *
 * Hasil: ['periode' => [mulai, selesai], 'kendaraan' => [nopol => ['model' => .., 'segmen' => [...]]]]
 * Tanggal disimpan sebagai string 'Y-m-d H:i:s' agar aman di-serialize ke JSON.
 */
class GpsTripParser
{
    /** Urutan kolom default bila header tidak ditemukan */
    private const KOLOM_DEFAULT = [
        'driver' => 0,
        'mulai' => 1,
        'selesai' => 2,
        'lokasi_mulai' => 3,
        'lokasi_selesai' => 4,
        'geofence_mulai' => 5,
        'geofence_selesai' => 6,
        'jarak' => 7,
    ];

    public function parse(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $rows = $reader->load($path)->getActiveSheet()->toArray(null, true, false, false);

        $kolom = self::KOLOM_DEFAULT;
        $kendaraan = [];
        $nopol = null;
        $periode = [null, null];

        foreach ($rows as $i => $row) {
            $a = $this->teks($row[0] ?? null);

            if (preg_match('/^Date:\s*(\d{4}-\d{2}-\d{2}).*?-\s*(\d{4}-\d{2}-\d{2})/', $a, $m)) {
                $periode = [$m[1], $m[2]];
                continue;
            }

            if (strcasecmp($a, 'Registration:') === 0) {
                $nopol = null;
                // Baris nopol = baris pertama yang terisi setelah label "Registration:"
                for ($j = $i + 1; $j < min($i + 5, count($rows)); $j++) {
                    $kandidat = $this->teks($rows[$j][0] ?? null);
                    if ($kandidat !== '') {
                        $nopol = strtoupper(preg_replace('/\s+/', '', $kandidat));
                        $kendaraan[$nopol]['model'] = $this->teks($rows[$j][2] ?? null) ?: null;
                        $kendaraan[$nopol]['segmen'] ??= [];
                        break;
                    }
                }
                continue;
            }

            // Baris header kolom: petakan posisi kolom berdasarkan nama
            if (strcasecmp($a, 'Driver') === 0 && $this->teks($row[1] ?? null) !== '') {
                $kolom = $this->petakanKolom($row) + self::KOLOM_DEFAULT;
                continue;
            }

            if ($nopol === null) {
                continue;
            }

            $mulai = $this->waktu($row[$kolom['mulai']] ?? null);
            $selesai = $this->waktu($row[$kolom['selesai']] ?? null);
            if (!$mulai || !$selesai) {
                continue; // baris judul / total / alert
            }

            $kendaraan[$nopol]['segmen'][] = [
                'driver' => $this->teks($row[$kolom['driver']] ?? null),
                'mulai' => $mulai->format('Y-m-d H:i:s'),
                'selesai' => $selesai->format('Y-m-d H:i:s'),
                'lokasi_mulai' => $this->teks($row[$kolom['lokasi_mulai']] ?? null),
                'lokasi_selesai' => $this->teks($row[$kolom['lokasi_selesai']] ?? null),
                'geofence_mulai' => $this->namaGeofence($row[$kolom['geofence_mulai']] ?? null),
                'geofence_selesai' => $this->namaGeofence($row[$kolom['geofence_selesai']] ?? null),
                'jarak_km' => (float) str_replace(',', '.', (string) ($row[$kolom['jarak']] ?? 0)),
            ];
        }

        $kendaraan = array_filter($kendaraan, fn ($k) => !empty($k['segmen']));

        if (empty($kendaraan)) {
            throw new RuntimeException(
                'Tidak ada data perjalanan yang terbaca. Pastikan file adalah "Standard Summary Trip Report" dari GPS.'
            );
        }

        ksort($kendaraan);

        return ['periode' => $periode, 'kendaraan' => $kendaraan];
    }

    /**
     * Semua nama geofence unik di laporan beserta jumlah kemunculannya.
     */
    public static function hitungGeofence(array $parsed): array
    {
        $hitung = [];
        foreach ($parsed['kendaraan'] as $k) {
            foreach ($k['segmen'] as $seg) {
                foreach (['geofence_mulai', 'geofence_selesai'] as $f) {
                    if ($seg[$f] !== '') {
                        $hitung[$seg[$f]] = ($hitung[$seg[$f]] ?? 0) + 1;
                    }
                }
            }
        }
        ksort($hitung, SORT_NATURAL | SORT_FLAG_CASE);

        return $hitung;
    }

    public static function normalisasiNama(?string $nama): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $nama)));
    }

    private function petakanKolom(array $header): array
    {
        $peta = [
            'driver' => 'driver',
            'start time' => 'mulai',
            'end time' => 'selesai',
            'start location' => 'lokasi_mulai',
            'end location' => 'lokasi_selesai',
            'start geofence' => 'geofence_mulai',
            'end geofence' => 'geofence_selesai',
            'trip distance' => 'jarak',
        ];

        $hasil = [];
        foreach ($header as $idx => $nama) {
            $kunci = mb_strtolower($this->teks($nama));
            if (isset($peta[$kunci])) {
                $hasil[$peta[$kunci]] = $idx;
            }
        }

        return $hasil;
    }

    private function teks($nilai): string
    {
        return trim((string) ($nilai ?? ''));
    }

    private function namaGeofence($nilai): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) ($nilai ?? '')));
    }

    private function waktu($nilai): ?Carbon
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        if (is_numeric($nilai)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $nilai));
        }

        $teks = preg_replace('/\s+/', ' ', trim((string) $nilai));
        if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $teks)) {
            return null;
        }

        return Carbon::parse($teks);
    }
}
