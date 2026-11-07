<?php

namespace App\Console\Commands;

use App\Services\Cartrack\CartrackClient;
use Illuminate\Console\Command;
use Throwable;

class CartrackTest extends Command
{
    protected $signature = 'cartrack:test {--raw : tampilkan 1 contoh data mentah kendaraan & driver}';

    protected $description = 'Tes koneksi ke Cartrack Fleet API (read-only): daftar kendaraan & driver';

    public function handle(CartrackClient $client): int
    {
        $this->info('Base URL: '.$client->baseUrl());

        try {
            $vehicles = $client->vehicles();
            $this->info('Kendaraan: '.count($vehicles));
            $this->table(
                ['registration', 'nama', 'model'],
                collect($vehicles)->map(fn ($v) => [
                    $v['registration'] ?? '-',
                    $v['vehicle_name'] ?? ($v['client_vehicle_description'] ?? '-'),
                    trim(($v['make'] ?? '').' '.($v['model'] ?? '')) ?: '-',
                ])->all()
            );

            $drivers = $client->drivers();
            $this->info('Driver: '.count($drivers));
            $this->table(
                ['driver_id', 'nama', 'employee_number', 'status'],
                collect($drivers)->map(fn ($d) => [
                    $d['driver_id'] ?? ($d['id'] ?? '-'),
                    trim(($d['first_name'] ?? '').' '.($d['last_name'] ?? '')),
                    $d['employee_number'] ?? '-',
                    $d['driver_status'] ?? ($d['status'] ?? '-'),
                ])->all()
            );

            if ($this->option('raw')) {
                $this->line('--- contoh kendaraan ---');
                $this->line(json_encode($vehicles[0] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $this->line('--- contoh driver ---');
                $this->line(json_encode($drivers[0] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
