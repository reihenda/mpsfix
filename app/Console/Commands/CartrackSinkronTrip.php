<?php

namespace App\Console\Commands;

use App\Services\Cartrack\CartrackSinkronService;
use App\Services\GpsTrip\PeriodeLembur;
use Illuminate\Console\Command;

class CartrackSinkronTrip extends Command
{
    protected $signature = 'cartrack:sinkron-trip
                            {--periode= : Paksa tarik penuh satu periode lembur 26-25 (YYYY-MM, label = bulan tanggal 25)}
                            {--hari= : Paksa tarikan ringan sekian hari ke belakang}
                            {--nopol= : Batasi tarikan ringan ke satu kendaraan}';

    protected $description = 'Tarik trip semua kendaraan Cartrack ke halaman Trip GPS (tanpa menunggu jadwal perjalanan)';

    public function handle(CartrackSinkronService $service): int
    {
        if (! config('services.cartrack.sinkron_aktif', true)) {
            $this->warn('Sinkron trip Cartrack dinonaktifkan (CARTRACK_SINKRON_AKTIF=false).');

            return self::SUCCESS;
        }

        $hariBawaan = (int) config('services.cartrack.sinkron_hari', 3);

        if ($periode = $this->option('periode')) {
            if (! PeriodeLembur::valid($periode)) {
                $this->error('Format --periode harus YYYY-MM, mis. 2026-11.');

                return self::INVALID;
            }
            $rencana = ['jenis' => 'periode', 'bulan' => $periode];
        } elseif ($this->option('hari') || $this->option('nopol')) {
            $rencana = ['jenis' => 'ringan', 'hari' => (int) ($this->option('hari') ?: $hariBawaan)];
        } else {
            // Dipanggil penjadwal tiap menit: jalan hanya bila ada permintaan manual atau sudah waktunya
            $rencana = CartrackSinkronService::rencana();
            if ($rencana === null) {
                return self::SUCCESS;
            }
        }

        if ($rencana['jenis'] === 'periode') {
            $hasil = $service->sinkronPeriode($rencana['bulan']);
            $cakupan = 'periode '.PeriodeLembur::label($rencana['bulan']);
        } else {
            $hari = $rencana['hari'] ?? $hariBawaan;
            $hasil = $service->sinkron($hari, null, $this->option('nopol') ?: null);
            $cakupan = "{$hari} hari terakhir";
        }

        $this->info("Sinkron {$cakupan}: {$hasil['kendaraan']} kendaraan, {$hasil['baru']} trip baru, {$hasil['diperbarui']} diperbarui, {$hasil['tertaut']} tertaut ke jadwal, {$hasil['noise_dibuang']} noise dibuang");
        foreach ($hasil['galat'] as $kendaraan => $pesan) {
            $this->error("{$kendaraan}: {$pesan}");
        }

        return $hasil['galat'] ? self::FAILURE : self::SUCCESS;
    }
}
