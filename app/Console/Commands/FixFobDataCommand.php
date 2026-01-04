<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Http\Controllers\FobController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Log;

class FixFobDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fob:fix-data {--customer-id= : ID customer FOB tertentu} {--dry-run : Jalankan tanpa melakukan perubahan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perbaiki data duplikat dan inkonsistensi pada FOB';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔧 Memulai perbaikan data FOB...');
        
        $isDryRun = $this->option('dry-run');
        $customerId = $this->option('customer-id');
        
        if ($isDryRun) {
            $this->warn('⚠️ Mode DRY RUN - Tidak ada perubahan yang akan disimpan');
        }

        // Ambil customer FOB
        if ($customerId) {
            $fobCustomers = User::where('id', $customerId)->where('role', 'fob')->get();
            if ($fobCustomers->isEmpty()) {
                $this->error("Customer dengan ID {$customerId} tidak ditemukan atau bukan FOB");
                return 1;
            }
        } else {
            $fobCustomers = User::where('role', 'fob')->get();
        }

        if ($fobCustomers->isEmpty()) {
            $this->warn('Tidak ada customer FOB yang ditemukan');
            return 0;
        }

        $this->info("Ditemukan {$fobCustomers->count()} customer FOB");
        
        $fobController = new FobController();
        $userController = new UserController();
        
        $totalFixed = 0;
        $totalDuplicatesRemoved = 0;
        $totalInconsistenciesFixed = 0;
        
        foreach ($fobCustomers as $customer) {
            $this->info("🔍 Memproses FOB: {$customer->name} (ID: {$customer->id})");
            
            try {
                // 1. Analisis data
                $this->line('   📊 Menganalisis data...');
                $analysis = $fobController->analyzeFobData($customer);
                
                if ($analysis instanceof \Illuminate\Http\JsonResponse) {
                    $analysisData = $analysis->getData(true);
                } else {
                    continue; // Skip jika tidak bisa dianalisis
                }
                
                $this->table([
                    'Metrik', 'Nilai'
                ], [
                    ['Total Records Pencatatan', $analysisData['summary']['total_pencatatan_records']],
                    ['Total Records Rekap', $analysisData['summary']['total_rekap_records']],
                    ['Duplikat Ditemukan', $analysisData['summary']['duplicates_found']],
                    ['Records Tanpa Harga', $analysisData['summary']['records_without_harga']],
                    ['Data Hilang dari Pencatatan', $analysisData['summary']['missing_from_pencatatan']],
                    ['Data Extra di Pencatatan', $analysisData['summary']['extra_in_pencatatan']],
                    ['Total Manual', 'Rp ' . number_format($analysisData['totals']['manual_total'], 2)],
                    ['Total Tersimpan', 'Rp ' . number_format($analysisData['totals']['stored_total'], 2)],
                    ['Selisih', 'Rp ' . number_format($analysisData['totals']['difference'], 2)],
                    ['Konsisten', $analysisData['totals']['is_consistent'] ? '✅ Ya' : '❌ Tidak']
                ]);

                if (!$isDryRun) {
                    // 2. Bersihkan duplikat
                    if ($analysisData['summary']['duplicates_found'] > 0) {
                        $this->line('   🧹 Membersihkan duplikat...');
                        $duplicatesRemoved = $fobController->cleanDuplicateFobData($customer);
                        if ($duplicatesRemoved > 0) {
                            $this->info("   ✅ Berhasil menghapus {$duplicatesRemoved} data duplikat");
                            $totalDuplicatesRemoved += $duplicatesRemoved;
                        }
                    }

                    // 3. Validasi dan perbaiki konsistensi
                    if (!$analysisData['totals']['is_consistent']) {
                        $this->line('   🔧 Memperbaiki konsistensi total...');
                        $inconsistency = $fobController->validateFobTotalConsistency($customer);
                        if ($inconsistency > 0) {
                            $this->info("   ✅ Berhasil memperbaiki inkonsistensi sebesar Rp " . number_format($inconsistency, 2));
                            $totalInconsistenciesFixed++;
                        }
                    }

                    // 4. Rekalkulasi dan sinkronisasi
                    $this->line('   🔄 Melakukan rekalkulasi final...');
                    $userController->rekalkulasiTotalPembelianFob($customer);
                    $userController->syncBalanceSilent($customer);
                    
                    $totalFixed++;
                    $this->info("   ✅ Perbaikan selesai untuk {$customer->name}");
                } else {
                    $this->line('   ⏭️ Dilewati (dry-run mode)');
                }
                
                $this->line('');
                
            } catch (\Exception $e) {
                $this->error("   ❌ Error saat memproses {$customer->name}: {$e->getMessage()}");
                Log::error("Error in FixFobDataCommand for customer {$customer->id}: " . $e->getMessage());
            }
        }
        
        // Summary
        $this->info('📋 RINGKASAN PERBAIKAN:');
        $this->table([
            'Metrik', 'Jumlah'
        ], [
            ['Total FOB yang Diproses', $fobCustomers->count()],
            ['Total FOB yang Diperbaiki', $totalFixed],
            ['Total Duplikat yang Dihapus', $totalDuplicatesRemoved],
            ['Total Inkonsistensi yang Diperbaiki', $totalInconsistenciesFixed]
        ]);
        
        if ($isDryRun) {
            $this->warn('⚠️ Ini adalah DRY RUN - Jalankan tanpa --dry-run untuk menerapkan perubahan');
        } else {
            $this->info('✅ Perbaikan data FOB selesai!');
        }
        
        return 0;
    }
}
