<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Kurs USD -> IDR per bulan dari kurs referensi harian European Central Bank (ECB),
 * lewat Frankfurter (https://frankfurter.dev, gratis, tanpa API key).
 *
 * Tidak ada angka cadangan: bila sumber gagal, lempar exception supaya user tahu
 * dan bisa mengisi manual (jangan sampai angka palsu tersimpan sebagai "real-time").
 */
class KursHistorisService
{
    public const SUMBER_LABEL = 'kurs referensi ECB via frankfurter.dev';

    /**
     * @param string $periode Y-m
     * @param string $metode  'rata' (rata-rata harian dalam bulan) | 'akhir' (kurs hari kerja terakhir)
     * @return array{kurs: float, keterangan: string, metode: string, tanggal_awal: string, tanggal_akhir: string, jumlah_hari: int}
     */
    public function untukBulan(string $periode, string $metode = 'rata'): array
    {
        $awal = Carbon::createFromFormat('Y-m-d', $periode . '-01')->startOfDay();
        $hariIni = now()->startOfDay();
        if ($awal->gt($hariIni)) {
            throw new \RuntimeException('Bulan ' . $this->labelBulan($awal) . ' belum berjalan, kursnya belum ada.');
        }
        $akhir = $awal->copy()->endOfMonth()->startOfDay();
        $bulanBerjalan = $akhir->gt($hariIni);
        if ($bulanBerjalan) {
            $akhir = $hariIni;
        }

        $kunciCache = "kurs_ecb:{$periode}:{$metode}";
        // Bulan yang sudah lewat tidak berubah: cache lama. Bulan berjalan: cache 1 jam.
        $ttl = $bulanBerjalan ? now()->addHour() : now()->addDays(30);

        return Cache::remember($kunciCache, $ttl, function () use ($awal, $akhir, $metode, $bulanBerjalan) {
            $harian = $this->ambilHarian($awal, $akhir);
            if (!$harian) {
                throw new \RuntimeException('Belum ada kurs ECB untuk ' . $this->labelBulan($awal) . ' (hari kerja belum ada).');
            }

            $tanggal = array_keys($harian);
            $pertama = Carbon::parse(reset($tanggal));
            $terakhir = Carbon::parse(end($tanggal));

            if ($metode === 'akhir') {
                $kurs = end($harian);
                $keterangan = 'Kurs ' . $this->labelTanggal($terakhir)
                    . ($bulanBerjalan ? ' (terbaru, bulan berjalan)' : ' (hari kerja terakhir)')
                    . ' · ' . self::SUMBER_LABEL;
            } else {
                $kurs = array_sum($harian) / count($harian);
                $keterangan = 'Rata-rata ' . count($harian) . ' kurs harian '
                    . $pertama->format('j') . '–' . $this->labelTanggal($terakhir)
                    . ($bulanBerjalan ? ' (s.d. hari ini, bulan berjalan)' : '')
                    . ' · ' . self::SUMBER_LABEL;
            }

            return [
                'kurs' => round($kurs, 2),
                'keterangan' => $keterangan,
                'metode' => $metode,
                'tanggal_awal' => $pertama->toDateString(),
                'tanggal_akhir' => $terakhir->toDateString(),
                'jumlah_hari' => count($harian),
            ];
        });
    }

    /** @return array<string, float> [Y-m-d => kurs], hanya tanggal di dalam rentang */
    private function ambilHarian(Carbon $awal, Carbon $akhir): array
    {
        $url = 'https://api.frankfurter.dev/v1/' . $awal->toDateString() . '..' . $akhir->toDateString();
        $response = Http::timeout(15)->acceptJson()->get($url, ['base' => 'USD', 'symbols' => 'IDR']);

        if (!$response->successful()) {
            throw new \RuntimeException('Sumber kurs ECB (frankfurter.dev) tidak merespons (HTTP ' . $response->status() . ').');
        }

        $hasil = [];
        foreach ((array) $response->json('rates', []) as $tanggal => $nilai) {
            // API bisa menyertakan hari kerja sebelum tanggal awal; buang
            if ($tanggal < $awal->toDateString() || $tanggal > $akhir->toDateString()) {
                continue;
            }
            if (isset($nilai['IDR']) && is_numeric($nilai['IDR'])) {
                $hasil[$tanggal] = (float) $nilai['IDR'];
            }
        }
        ksort($hasil);

        return $hasil;
    }

    private function labelBulan(Carbon $tgl): string
    {
        return $tgl->locale('id')->translatedFormat('F Y');
    }

    private function labelTanggal(Carbon $tgl): string
    {
        return $tgl->locale('id')->translatedFormat('j M Y');
    }
}
