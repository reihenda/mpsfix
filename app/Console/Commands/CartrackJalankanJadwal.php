<?php

namespace App\Console\Commands;

use App\Services\Cartrack\JadwalPerjalananService;
use Illuminate\Console\Command;

class CartrackJalankanJadwal extends Command
{
    protected $signature = 'cartrack:jalankan-jadwal';

    protected $description = 'Proses jadwal perjalanan: link driver ke Cartrack saat mulai, lepas saat selesai, tarik trip';

    public function handle(JadwalPerjalananService $service): int
    {
        $hasil = $service->jalankan();

        if ($hasil['diproses'] || $hasil['galat']) {
            $this->info("Jadwal diproses: {$hasil['diproses']}, galat: {$hasil['galat']}");
        }

        return $hasil['galat'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
