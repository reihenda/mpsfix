<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\DataPencatatan;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RegenerateMonthlyBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balance:regenerate {customer_id?} {--all} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerasi monthly_balances untuk customer yang hilang datanya';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $customerId = $this->argument('customer_id');
        $all = $this->option('all');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - Tidak ada data yang akan diubah');
        }

        // Tentukan customer yang akan diproses
        if ($customerId) {
            $customers = User::where('id', $customerId)->whereIn('role', ['customer', 'fob'])->get();
        } elseif ($all) {
            // Hanya customer yang monthly_balances-nya hilang atau kosong
            $customers = User::whereIn('role', ['customer', 'fob'])
                ->where(function($query) {
                    $query->whereNull('monthly_balances')
                          ->orWhere('monthly_balances', '{}')
                          ->orWhere('monthly_balances', '')
                          ->orWhere('monthly_balances', 'null');
                })
                ->get();
        } else {
            $this->error('❌ Gunakan --all untuk semua customer atau berikan customer_id');
            return;
        }

        if ($customers->isEmpty()) {
            $this->error('❌ Tidak ada customer yang perlu diperbaiki');
            return;
        }

        $this->info("🚀 Memulai regenerasi monthly_balances untuk " . $customers->count() . " customer");

        foreach ($customers as $customer) {
            $this->regenerateCustomerBalances($customer, $dryRun);
        }

        $this->info('✅ Regenerasi selesai!');
    }

    private function regenerateCustomerBalances($customer, $dryRun = false)
    {
        $this->info("📋 Memproses: {$customer->name} (ID: {$customer->id}, Role: {$customer->role})");

        try {
            if (!$dryRun) {
                DB::beginTransaction();
            }

            // 1. Kumpulkan semua bulan yang ada aktivitas
            $monthsWithActivity = $this->getMonthsWithActivity($customer);
            $this->info("   📅 Ditemukan aktivitas di " . count($monthsWithActivity) . " bulan");

            // 2. Hitung saldo untuk setiap bulan secara kronologis
            $monthlyBalances = [];
            $runningBalance = 0;

            foreach ($monthsWithActivity as $yearMonth) {
                // Hitung deposit bulan ini
                $monthDeposits = $this->getDepositsForMonth($customer, $yearMonth);
                
                // Hitung pembelian bulan ini
                $monthPurchases = $this->getPurchasesForMonth($customer, $yearMonth);
                
                // Update running balance
                $runningBalance += $monthDeposits - $monthPurchases;
                $monthlyBalances[$yearMonth] = round($runningBalance, 2);

                $this->info("   💰 {$yearMonth}: Deposit +{$monthDeposits}, Purchase -{$monthPurchases}, Balance: {$runningBalance}");
            }

            // 3. Update database
            if (!$dryRun && !empty($monthlyBalances)) {
                $customer->updateQuietly([
                    'monthly_balances' => $monthlyBalances
                ]);

                $this->info("   ✅ Monthly balances berhasil diregenerasi untuk " . count($monthlyBalances) . " periode");
            } elseif ($dryRun) {
                $this->info("   🔍 DRY RUN: Akan regenerasi " . count($monthlyBalances) . " periode");
                foreach ($monthlyBalances as $month => $balance) {
                    $this->info("       {$month}: Rp " . number_format($balance, 0));
                }
            }

            if (!$dryRun) {
                DB::commit();
            }

        } catch (\Exception $e) {
            if (!$dryRun) {
                DB::rollBack();
            }
            $this->error("   ❌ Error: " . $e->getMessage());
        }
    }

    private function ensureArray($data)
    {
        if (is_string($data)) {
            return json_decode($data, true) ?? [];
        }

        if (is_array($data)) {
            return $data;
        }

        return [];
    }

    private function getMonthsWithActivity($customer)
    {
        $months = [];
        
        // Get months from deposit history
        $depositHistory = $this->ensureArray($customer->deposit_history);
        foreach ($depositHistory as $deposit) {
            if (!empty($deposit['date'])) {
                $months[] = Carbon::parse($deposit['date'])->format('Y-m');
            }
        }
        
        // Get months from data pencatatan
        if ($customer->isFOB()) {
            // Untuk FOB, gunakan RekapPengambilan sebagai sumber utama
            $rekapData = $customer->rekapPengambilan()->get();
            foreach ($rekapData as $rekap) {
                $months[] = Carbon::parse($rekap->tanggal)->format('Y-m');
            }
        } else {
            // Untuk customer reguler, gunakan DataPencatatan
            $dataPencatatan = $customer->dataPencatatan()->get();
            foreach ($dataPencatatan as $item) {
                $dataInput = $this->ensureArray($item->data_input);
                
                if (!empty($dataInput['pembacaan_awal']['waktu'])) {
                    $months[] = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m');
                } elseif ($item->created_at) {
                    $months[] = $item->created_at->format('Y-m');
                }
            }
        }
        
        // Remove duplicates and sort
        $months = array_unique($months);
        sort($months);
        
        return $months;
    }

    private function getDepositsForMonth($customer, $yearMonth)
    {
        $depositHistory = $this->ensureArray($customer->deposit_history);
        $totalDeposits = 0;
        
        foreach ($depositHistory as $deposit) {
            if (!empty($deposit['date'])) {
                $depositDate = Carbon::parse($deposit['date']);
                if ($depositDate->format('Y-m') === $yearMonth) {
                    $amount = floatval($deposit['amount'] ?? 0);
                    $keterangan = $deposit['keterangan'] ?? 'penambahan';
                    
                    if ($keterangan === 'pengurangan') {
                        $totalDeposits -= abs($amount);
                    } else {
                        $totalDeposits += $amount;
                    }
                }
            }
        }
        
        return $totalDeposits;
    }

    private function getPurchasesForMonth($customer, $yearMonth)
    {
        if ($customer->isFOB()) {
            // Untuk FOB, hitung dari RekapPengambilan
            $rekapData = $customer->rekapPengambilan()
                ->whereYear('tanggal', substr($yearMonth, 0, 4))
                ->whereMonth('tanggal', substr($yearMonth, 5, 2))
                ->get();
            
            $totalPurchases = 0;
            foreach ($rekapData as $rekap) {
                $volumeSm3 = floatval($rekap->volume);
                $rekap_date = Carbon::parse($rekap->tanggal);
                $rekap_yearMonth = $rekap_date->format('Y-m');
                
                // Ambil pricing yang sesuai periode
                $pricingInfo = $customer->getPricingForYearMonth($rekap_yearMonth, $rekap_date);
                $hargaPerM3 = floatval($pricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
                
                $totalPurchases += ($volumeSm3 * $hargaPerM3);
            }
            
            return $totalPurchases;
        } else {
            // Untuk customer reguler, hitung dari DataPencatatan
            $dataPencatatan = $customer->dataPencatatan()->get();
            $totalPurchases = 0;
            
            foreach ($dataPencatatan as $item) {
                $dataInput = $this->ensureArray($item->data_input);
                $itemDate = null;
                
                // Determine item date
                if (!empty($dataInput['pembacaan_awal']['waktu'])) {
                    $itemDate = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
                } elseif ($item->created_at) {
                    $itemDate = $item->created_at;
                }
                
                if ($itemDate && $itemDate->format('Y-m') === $yearMonth) {
                    if ($item->harga_final > 0) {
                        $totalPurchases += $item->harga_final;
                    } else {
                        // Hitung manual jika harga_final tidak ada
                        $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
                        $pricingInfo = $customer->getPricingForYearMonth($yearMonth, $itemDate);
                        $koreksiMeter = floatval($pricingInfo['koreksi_meter'] ?? $customer->koreksi_meter);
                        $hargaPerM3 = floatval($pricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
                        
                        $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
                        $totalPurchases += ($volumeSm3 * $hargaPerM3);
                    }
                }
            }
            
            return $totalPurchases;
        }
    }
}
