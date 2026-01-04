<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\RekapPengambilan;
use App\Models\DataPencatatan;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncFobData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fob:sync-data {customer_id?} {--all} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi data dari rekap_pengambilan ke data_pencatatan untuk customer FOB';

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
            $customers = User::where('id', $customerId)->where('role', 'fob')->get();
        } elseif ($all) {
            $customers = User::where('role', 'fob')->get();
        } else {
            $this->error('❌ Gunakan --all untuk semua customer FOB atau berikan customer_id');
            return;
        }

        if ($customers->isEmpty()) {
            $this->error('❌ Tidak ada customer FOB yang ditemukan');
            return;
        }

        $this->info("🚀 Memulai sinkronisasi untuk " . $customers->count() . " customer FOB");

        foreach ($customers as $customer) {
            $this->syncCustomerData($customer, $dryRun);
        }

        $this->info('✅ Sinkronisasi selesai!');
    }

    private function syncCustomerData($customer, $dryRun = false)
    {
        $this->info("📋 Memproses: {$customer->name} (ID: {$customer->id})");

        try {
            if (!$dryRun) {
                DB::beginTransaction();
            }

            // 1. Ambil data rekap pengambilan
            $rekapData = RekapPengambilan::where('customer_id', $customer->id)->get();
            $pencatatanData = DataPencatatan::where('customer_id', $customer->id)->get();

            $this->info("   📊 Data Rekap: {$rekapData->count()}, Data Pencatatan: {$pencatatanData->count()}");

            $created = 0;
            $updated = 0;
            $deleted = 0;

            // 2. Buat data pencatatan dari rekap yang belum ada
            foreach ($rekapData as $rekap) {
                // Cek apakah sudah ada data pencatatan untuk rekap ini
                $existing = $pencatatanData->firstWhere('rekap_pengambilan_id', $rekap->id);
                
                if (!$existing) {
                    // Cek berdasarkan tanggal dan volume
                    $rekapDate = Carbon::parse($rekap->tanggal)->format('Y-m-d');
                    $matchingByDateVolume = $pencatatanData->filter(function($pencatatan) use ($rekapDate, $rekap) {
                        $dataInput = json_decode($pencatatan->data_input, true) ?? [];
                        if (empty($dataInput['waktu'])) return false;
                        
                        $pencatatanDate = Carbon::parse($dataInput['waktu'])->format('Y-m-d');
                        $pencatatanVolume = floatval($dataInput['volume_sm3'] ?? 0);
                        
                        return $pencatatanDate === $rekapDate && 
                               abs($pencatatanVolume - $rekap->volume) < 0.01;
                    })->first();

                    if ($matchingByDateVolume && !$matchingByDateVolume->rekap_pengambilan_id) {
                        // Update relasi
                        if (!$dryRun) {
                            $matchingByDateVolume->rekap_pengambilan_id = $rekap->id;
                            $matchingByDateVolume->save();
                        }
                        $updated++;
                        $this->info("   🔗 Updated relasi untuk tanggal: {$rekapDate}");
                    } elseif (!$matchingByDateVolume) {
                        // Buat data pencatatan baru
                        if (!$dryRun) {
                            $this->createDataPencatatanFromRekap($rekap, $customer);
                        }
                        $created++;
                        $this->info("   ➕ Created data pencatatan untuk tanggal: {$rekapDate}");
                    }
                }
            }

            // 3. Hapus data pencatatan yang orphaned
            foreach ($pencatatanData as $pencatatan) {
                if (!$pencatatan->rekap_pengambilan_id) {
                    $dataInput = json_decode($pencatatan->data_input, true) ?? [];
                    if (empty($dataInput['waktu'])) continue;
                    
                    $pencatatanDate = Carbon::parse($dataInput['waktu'])->format('Y-m-d');
                    $pencatatanVolume = floatval($dataInput['volume_sm3'] ?? 0);
                    
                    // Cari rekap yang matching
                    $matchingRekap = $rekapData->filter(function($rekap) use ($pencatatanDate, $pencatatanVolume) {
                        $rekapDate = Carbon::parse($rekap->tanggal)->format('Y-m-d');
                        return $rekapDate === $pencatatanDate && 
                               abs($rekap->volume - $pencatatanVolume) < 0.01;
                    })->first();

                    if (!$matchingRekap) {
                        // Data orphaned, hapus
                        if (!$dryRun) {
                            $pencatatan->delete();
                        }
                        $deleted++;
                        $this->warn("   🗑️  Deleted orphaned data untuk tanggal: {$pencatatanDate}");
                    }
                }
            }

            // 4. Rekalkulasi total pembelian
            if (!$dryRun && ($created > 0 || $updated > 0 || $deleted > 0)) {
                $userController = new \App\Http\Controllers\UserController();
                $newTotal = $userController->rekalkulasiTotalPembelianFob($customer);
                $this->info("   💰 Total pembelian diperbarui: Rp " . number_format($newTotal, 0));
            }

            if (!$dryRun) {
                DB::commit();
            }

            $this->info("   ✅ Selesai - Created: {$created}, Updated: {$updated}, Deleted: {$deleted}");

        } catch (\Exception $e) {
            if (!$dryRun) {
                DB::rollBack();
            }
            $this->error("   ❌ Error: " . $e->getMessage());
        }
    }

    private function createDataPencatatanFromRekap($rekap, $customer)
    {
        // Ambil pricing info berdasarkan tanggal
        $waktuDateTime = Carbon::parse($rekap->tanggal);
        $waktuYearMonth = $waktuDateTime->format('Y-m');
        $pricingInfo = $customer->getPricingForYearMonth($waktuYearMonth, $waktuDateTime);
        
        // Hitung harga
        $volumeSm3 = floatval($rekap->volume);
        $hargaPerM3 = floatval($pricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
        $hargaFinal = $volumeSm3 * $hargaPerM3;

        // Format data input
        $dataInput = [
            'waktu' => $waktuDateTime->format('Y-m-d H:i:s'),
            'volume_sm3' => $volumeSm3,
            'alamat_pengambilan' => $rekap->alamat_pengambilan,
            'keterangan' => $rekap->keterangan
        ];

        // Buat data pencatatan
        $dataPencatatan = new DataPencatatan();
        $dataPencatatan->customer_id = $rekap->customer_id;
        $dataPencatatan->rekap_pengambilan_id = $rekap->id;
        $dataPencatatan->data_input = json_encode($dataInput);
        $dataPencatatan->nama_customer = $customer->name;
        $dataPencatatan->status_pembayaran = 'belum_lunas';
        $dataPencatatan->harga_final = $hargaFinal;
        $dataPencatatan->created_at = $rekap->created_at;
        $dataPencatatan->updated_at = $rekap->updated_at;
        $dataPencatatan->save();

        return $dataPencatatan;
    }
}
