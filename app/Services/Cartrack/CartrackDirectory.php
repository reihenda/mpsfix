<?php

namespace App\Services\Cartrack;

use App\Models\OperatorGtm;
use App\Services\GpsTrip\GpsTripImporter;
use Illuminate\Support\Facades\Cache;

/**
 * Daftar kendaraan & driver di Cartrack (di-cache sebentar) dan pencocokan dengan data kita.
 */
class CartrackDirectory
{
    private const TTL_DETIK = 600;

    public function __construct(private CartrackClient $client)
    {
    }

    public function lupakan(): void
    {
        Cache::forget('cartrack.kendaraan');
        Cache::forget('cartrack.driver');
    }

    /** @return array<int, array{registration:string, model:?string}> */
    public function kendaraan(): array
    {
        return Cache::remember('cartrack.kendaraan', self::TTL_DETIK, function () {
            return array_values(array_map(fn ($v) => [
                'registration' => (string) ($v['registration'] ?? ''),
                'model' => $v['model'] ?? null,
            ], $this->client->vehicles()));
        });
    }

    /** @return array<string, array{id:string, nama:string, status:?string}> dikunci driver_id */
    public function driver(): array
    {
        return Cache::remember('cartrack.driver', self::TTL_DETIK, function () {
            $hasil = [];
            foreach ($this->client->drivers() as $d) {
                $id = (string) ($d['driver_id'] ?? '');
                if ($id === '') {
                    continue;
                }
                $hasil[$id] = [
                    'id' => $id,
                    'nama' => trim(($d['first_name'] ?? '').' '.($d['last_name'] ?? '')),
                    'status' => $d['status'] ?? null,
                ];
            }

            return $hasil;
        });
    }

    /**
     * Registrasi persis seperti tertulis di Cartrack untuk nopol kita (spasi/huruf O vs angka 0 diabaikan).
     */
    public function registrasiUntuk(?string $nopol): ?string
    {
        $kunci = GpsTripImporter::kunciNopol($nopol);
        if ($kunci === '') {
            return null;
        }

        foreach ($this->kendaraan() as $v) {
            if (GpsTripImporter::kunciNopol($v['registration']) === $kunci) {
                return $v['registration'];
            }
        }

        return null;
    }

    public function namaDriver(?string $driverId): ?string
    {
        return $driverId ? ($this->driver()[$driverId]['nama'] ?? null) : null;
    }

    /**
     * Saran driver Cartrack untuk seorang operator berdasarkan nama. Hanya dikembalikan bila
     * kandidatnya tunggal: nama persis, atau nama operator = awalan nama driver (mis. "Bagus" ->
     * "Bagus Arif Sucipto"), atau selisih satu huruf pada kata pertama ("Pahtoni" ~ "Fahtoni").
     *
     * @param array<int, string> $sudahDipakai driver_id yang sudah dipetakan ke operator lain
     */
    public function sarankanDriver(OperatorGtm $operator, array $sudahDipakai = []): ?string
    {
        $nama = self::norm($operator->nama);
        if ($nama === '') {
            return null;
        }

        $kandidat = [];
        foreach ($this->driver() as $id => $d) {
            if (in_array($id, $sudahDipakai, true) || ($d['status'] ?? 'Active') !== 'Active') {
                continue;
            }
            $namaDriver = self::norm($d['nama']);
            $kataPertama = explode(' ', $namaDriver)[0] ?? '';
            $kataOperator = explode(' ', $nama)[0];

            $cocok = $namaDriver === $nama
                || str_starts_with($namaDriver.' ', $nama.' ')
                || (strlen($kataOperator) >= 4 && levenshtein($kataPertama, $kataOperator) <= 1 && count(explode(' ', $nama)) === 1);

            if ($cocok) {
                $kandidat[] = $id;
            }
        }

        return count($kandidat) === 1 ? $kandidat[0] : null;
    }

    private static function norm(?string $s): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $s)));
    }
}
