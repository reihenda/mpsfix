<?php

namespace App\Http\Controllers;

use App\Models\DataPencatatan;
use App\Models\RekapPengambilan;
use App\Models\User;
use App\Models\NomorPolisi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FobController extends Controller
{
    // Helper function to ensure data is always an array
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

    /**
     * Fungsi untuk auto-sinkronisasi data rekap pengambilan ke data pencatatan FOB
     * Fungsi ini akan dijalankan setiap kali halaman fob-detail diakses
     */
    /**
     * Metode untuk menjalankan sinkronisasi data manual dengan pemeriksaan integritas
     */
    public function syncData(User $customer)
    {
        // Verifikasi bahwa customer adalah FOB
        if (!$customer->isFOB()) {
            Log::warning('Attempt to sync data for non-FOB user', ['user_id' => $customer->id, 'role' => $customer->role]);
            return redirect()->back()->with('error', 'User yang dipilih bukan FOB');
        }

        // Verifikasi user memiliki izin (admin atau superadmin)
        if (!Auth::user()->isAdmin() && !Auth::user()->isSuperAdmin()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk melakukan operasi ini');
        }

        try {
            DB::beginTransaction();
            
            // Ambil SEMUA rekap pengambilan dan data pencatatan yang ada
            $rekapData = RekapPengambilan::where('customer_id', $customer->id)->get();

            // Lakukan sinkronisasi data dengan metode yang lebih agresif
            $newDataCount = $this->forceSyncRekapPengambilanData($customer, $rekapData);
            
            // Langkah 1: Rekalkulasi total pembelian
            $userController = new UserController();
            $newTotalPurchases = $userController->rekalkulasiTotalPembelianFob($customer);
            
            // Log hasil rekalkulasi
            Log::info("Hasil rekalkulasi total pembelian setelah sync", [
                'customer_id' => $customer->id,
                'old_total_purchases' => $customer->total_purchases, 
                'new_total_purchases' => $newTotalPurchases,
                'difference' => $newTotalPurchases - $customer->total_purchases
            ]);
            
            // Langkah 2: Verifikasi dan perbaiki konsistensi data deposit
            $depositHistory = $this->ensureArray($customer->deposit_history);
            $calculatedTotalDeposit = 0;
            
            foreach ($depositHistory as $deposit) {
                if (isset($deposit['amount'])) {
                    $calculatedTotalDeposit += floatval($deposit['amount']);
                }
            }
            
            // Jika ada perbedaan dalam total deposit, perbaiki
            if (abs($calculatedTotalDeposit - $customer->total_deposit) > 0.01) {
                Log::warning("Perbedaan total deposit terdeteksi", [
                    'customer_id' => $customer->id,
                    'stored_total_deposit' => $customer->total_deposit,
                    'calculated_total_deposit' => $calculatedTotalDeposit,
                    'difference' => $calculatedTotalDeposit - $customer->total_deposit
                ]);
                
                // Update total deposit ke nilai yang benar
                $customer->total_deposit = $calculatedTotalDeposit;
                $customer->save();
                
                Log::info("Total deposit dikoreksi", [
                    'customer_id' => $customer->id,
                    'new_total_deposit' => $customer->total_deposit
                ]);
            }
            
            // Langkah 3: Reset dan perbarui monthly balances dengan data yang sudah dikoreksi
            // Hapus monthly_balances yang ada dan buat ulang dari awal
            $customer->monthly_balances = [];
            $customer->save();
            
            // Perbarui monthly_balances dari awal (4 tahun ke belakang)
            $fourYearsAgo = Carbon::now()->subYears(4)->startOfMonth()->format('Y-m');
            $updateResult = $customer->updateMonthlyBalances($fourYearsAgo);
            
            Log::info("Monthly balances diperbarui setelah sync", [
                'customer_id' => $customer->id,
                'success' => $updateResult ? 'true' : 'false',
                'start_from' => $fourYearsAgo
            ]);
            
            // Langkah 4: Verifikasi final keseluruhan proses
            // Reload customer dari database untuk memastikan data terbaru
            $customer = User::findOrFail($customer->id);
            
            // Validasi saldo akhir sesuai dengan total_deposit - total_purchases
            $expectedBalance = $customer->total_deposit - $customer->total_purchases;
            $currentYearMonth = Carbon::now()->format('Y-m');
            $latestBalance = $customer->monthly_balances[$currentYearMonth] ?? null;
            
            if ($latestBalance !== null && abs($expectedBalance - $latestBalance) > 0.01) {
                Log::warning("Saldo akhir masih tidak konsisten setelah sync", [
                    'customer_id' => $customer->id,
                    'expected_balance' => $expectedBalance,
                    'latest_monthly_balance' => $latestBalance,
                    'difference' => $expectedBalance - $latestBalance
                ]);
                
                // Koreksi saldo bulan terakhir jika masih tidak konsisten
                $monthlyBalances = $customer->monthly_balances;
                $monthlyBalances[$currentYearMonth] = $expectedBalance;
                $customer->monthly_balances = $monthlyBalances;
                $customer->save();
                
                Log::info("Saldo bulan terakhir dikoreksi manual", [
                    'customer_id' => $customer->id,
                    'current_month' => $currentYearMonth,
                    'corrected_balance' => $expectedBalance
                ]);
            }
            
            DB::commit();
            
            if ($newDataCount > 0) {
                return redirect()->route('data-pencatatan.fob-detail', ['customer' => $customer->id])
                    ->with('success', "$newDataCount data berhasil disinkronkan dan saldo sudah dikoreksi dengan akurat!");
            } else {
                return redirect()->route('data-pencatatan.fob-detail', ['customer' => $customer->id])
                    ->with('info', "Tidak ada data baru, tetapi semua saldo telah diverifikasi dan dikoreksi jika diperlukan.");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error saat sinkronisasi data", [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('data-pencatatan.fob-detail', ['customer' => $customer->id])
                ->with('error', "Terjadi kesalahan: {$e->getMessage()}");
        }
    }

    /**
     * Fungsi untuk force-sinkronisasi data rekap pengambilan (DIPERBAIKI)
     * Mencegah duplikasi data dengan validasi yang lebih ketat
     */
    private function forceSyncRekapPengambilanData(User $customer, $rekapData = null)
    {
        // Pastikan ini adalah user FOB
        if (!$customer->isFOB()) {
            return 0;
        }

        // PERBAIKAN: Gunakan DB transaction untuk memastikan konsistensi
        DB::beginTransaction();
        
        try {
            // Ambil rekap pengambilan untuk FOB ini jika tidak disediakan
            if (!$rekapData) {
                $rekapData = RekapPengambilan::where('customer_id', $customer->id)->get();
            }

            // PERBAIKAN: Ambil data pencatatan yang sudah ada dengan indexing yang lebih baik
            $existingRecords = DB::table('data_pencatatan')
                ->where('customer_id', $customer->id)
                ->get()
                ->keyBy(function($item) {
                    $dataInput = json_decode($item->data_input, true) ?? [];
                    if (!empty($dataInput['waktu'])) {
                        try {
                            return Carbon::parse($dataInput['waktu'])->format('Y-m-d');
                        } catch (\Exception $e) {
                            return null;
                        }
                    }
                    return null;
                })
                ->filter(); // Remove null keys

            $importedCount = 0;
            $skippedCount = 0;
            $duplicateCount = 0;

            // Log untuk debugging
            Log::info('Memulai force sync untuk FOB dengan validasi duplikasi', [
                'customer_id' => $customer->id,
                'rekap_count' => $rekapData->count(),
                'existing_records_count' => $existingRecords->count(),
                'existing_dates' => $existingRecords->keys()->toArray()
            ]);

            foreach ($rekapData as $rekap) {
                $tanggalKey = Carbon::parse($rekap->tanggal)->format('Y-m-d');
                
                // PERBAIKAN: Skip jika sudah ada data untuk tanggal ini
                if ($existingRecords->has($tanggalKey)) {
                    $skippedCount++;
                    Log::info("Tanggal $tanggalKey sudah ada, dilewati", [
                        'rekap_id' => $rekap->id,
                        'existing_record_id' => $existingRecords[$tanggalKey]->id
                    ]);
                    continue;
                }

                // PERBAIKAN: Cek duplikasi berdasarkan kombinasi tanggal + volume + customer
                $existingWithSameData = DB::table('data_pencatatan')
                    ->where('customer_id', $customer->id)
                    ->where('data_input', 'LIKE', '%"waktu":"' . Carbon::parse($rekap->tanggal)->format('Y-m-d') . '%')
                    ->where('data_input', 'LIKE', '%"volume_sm3":' . $rekap->volume . '%')
                    ->exists();

                if ($existingWithSameData) {
                    $duplicateCount++;
                    Log::warning('Skipping potential duplicate record', [
                        'rekap_id' => $rekap->id,
                        'tanggal' => $tanggalKey,
                        'volume' => $rekap->volume
                    ]);
                    continue;
                }

                // Lanjutkan proses impor jika tidak ada duplikasi
                $volumeSm3 = floatval($rekap->volume);
                $rekap_date = Carbon::parse($rekap->tanggal);
                $rekap_yearMonth = $rekap_date->format('Y-m');
                $pricingInfo = $customer->getPricingForYearMonth($rekap_yearMonth, $rekap_date);
                
                $hargaPerM3 = floatval($pricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
                $hargaFinal = $volumeSm3 * $hargaPerM3;

                $dataInput = [
                    'waktu' => $rekap_date->format('Y-m-d H:i:s'),
                    'volume_sm3' => $volumeSm3,
                    'keterangan' => $rekap->keterangan,
                    'alamat_pengambilan' => $rekap->alamat_pengambilan
                ];

                // Buat data pencatatan baru
                $dataPencatatan = new DataPencatatan();
                $dataPencatatan->customer_id = $rekap->customer_id;
                $dataPencatatan->data_input = json_encode($dataInput);
                $dataPencatatan->nama_customer = $customer->name;
                $dataPencatatan->status_pembayaran = 'belum_lunas';
                $dataPencatatan->harga_final = $hargaFinal;
                $dataPencatatan->created_at = $rekap->created_at;
                $dataPencatatan->updated_at = $rekap->updated_at;
                $dataPencatatan->save();

                // Update existing records untuk mencegah duplikasi selanjutnya
                $existingRecords[$tanggalKey] = (object)['id' => $dataPencatatan->id];

                $importedCount++;
                Log::info("Berhasil mengimpor data rekap pengambilan ID {$rekap->id}", [
                    'data_pencatatan_id' => $dataPencatatan->id,
                    'tanggal' => $tanggalKey,
                    'harga_final' => $hargaFinal
                ]);
            }

            // Rekalkulasi total pembelian jika ada data yang diimpor
            if ($importedCount > 0) {
                $userController = new UserController();
                $userController->rekalkulasiTotalPembelianFob($customer);
                
                // PERBAIKAN: Lakukan sinkronisasi realtime setelah import data
                $this->syncRealtimeCalculationsToTotal($customer);
                
                Log::info("Rekalkulasi total pembelian setelah import", [
                    'customer_id' => $customer->id,
                    'imported_count' => $importedCount
                ]);
            }

            DB::commit();
            
            Log::info("Force sync selesai", [
                'customer_id' => $customer->id,
                'imported' => $importedCount,
                'skipped' => $skippedCount,
                'duplicates_prevented' => $duplicateCount
            ]);
            
            return $importedCount;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in forceSyncRekapPengambilanData: ' . $e->getMessage(), [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 0;
        }
    }
    /**
     * Fungsi untuk auto-sinkronisasi data rekap pengambilan ke data pencatatan FOB
     * Fungsi ini akan dijalankan setiap kali halaman fob-detail diakses
     * Versi yang ditingkatkan dengan deteksi lebih kuat
     */
    private function syncRekapPengambilanData(User $customer)
    {
        // Pastikan ini adalah user FOB
        if (!$customer->isFOB()) {
            return 0;
        }

        // Ambil rekap pengambilan untuk FOB ini
        $rekapData = RekapPengambilan::where('customer_id', $customer->id)->get();

        // Ambil data pencatatan yang sudah ada
        $existingPencatatanData = DataPencatatan::where('customer_id', $customer->id)->get();

        // Siapkan array untuk melacak tanggal dari data pencatatan yang sudah ada
        $existingDates = [];
        foreach ($existingPencatatanData as $data) {
            $dataInput = json_decode($data->data_input, true) ?? [];

            if (!empty($dataInput['waktu'])) {
                try {
                    $date = Carbon::parse($dataInput['waktu'])->format('Y-m-d');
                    $existingDates[$date] = true;
                } catch (\Exception $e) {
                    // Ignore errors
                }
            }
        }

        // Log existingDates untuk debugging
        Log::info('Existing dates for sync', [
            'customer_id' => $customer->id,
            'count' => count($existingDates),
            'dates' => array_keys($existingDates)
        ]);

        $importedCount = 0;

        // Batasi maksimal jumlah data yang diimpor per kali akses halaman (untuk performa)
        $maxImportPerVisit = 5;
        $currentImport = 0;

        foreach ($rekapData as $rekap) {
            try {
                // Format tanggal untuk pencarian
                $tanggalYmd = Carbon::parse($rekap->tanggal)->format('Y-m-d');

                // Jika sudah ada di data pencatatan, skip
                if (isset($existingDates[$tanggalYmd])) {
                    continue;
                }

                // Batasi jumlah data yang diimpor per kali akses
                if ($currentImport >= $maxImportPerVisit) {
                    break;
                }

                // Hitung harga
                $volumeSm3 = floatval($rekap->volume);
                
                // Ambil pricing info berdasarkan tanggal spesifik rekap
                $rekap_date = Carbon::parse($rekap->tanggal);
                $rekap_yearMonth = $rekap_date->format('Y-m');
                $pricingInfo = $customer->getPricingForYearMonth($rekap_yearMonth, $rekap_date);
                
                // Gunakan harga per meter kubik yang sesuai dengan periode
                $hargaPerM3 = floatval($pricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
                $hargaFinal = $volumeSm3 * $hargaPerM3;

                // Format data untuk FOB
                $dataInput = [
                    'waktu' => Carbon::parse($rekap->tanggal)->format('Y-m-d H:i:s'),
                    'volume_sm3' => $volumeSm3,
                    'keterangan' => $rekap->keterangan,
                    'alamat_pengambilan' => $rekap->alamat_pengambilan
                ];
                
                // Log detail pricing untuk debugging
                Log::info('Pricing info for sync FOB record', [
                    'rekap_id' => $rekap->id,
                    'tanggal' => $tanggalYmd,
                    'volume' => $volumeSm3,
                    'harga_per_m3' => $hargaPerM3,
                    'harga_final' => $hargaFinal,
                    'pricing_info' => $pricingInfo
                ]);

                DB::beginTransaction();

                // Buat data pencatatan baru
                $dataPencatatan = new DataPencatatan();
                $dataPencatatan->customer_id = $rekap->customer_id;
                $dataPencatatan->data_input = json_encode($dataInput);
                $dataPencatatan->nama_customer = $customer->name;
                $dataPencatatan->status_pembayaran = 'belum_lunas'; // Default status
                $dataPencatatan->harga_final = $hargaFinal;
                $dataPencatatan->created_at = $rekap->created_at; // Gunakan created_at yang sama
                $dataPencatatan->updated_at = $rekap->updated_at; // Gunakan updated_at yang sama
                $dataPencatatan->save();

                DB::commit();

                // Tambahkan ke tanggal yang sudah ada
                $existingDates[$tanggalYmd] = true;

                $importedCount++;
                $currentImport++;
                
                Log::info("Auto-sync: Berhasil mengimpor data rekap ID {$rekap->id}", [
                    'tanggal' => $tanggalYmd,
                    'data_pencatatan_id' => $dataPencatatan->id,
                    'harga_final' => $hargaFinal
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Error saat memproses rekap ID {$rekap->id}: " . $e->getMessage(), [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
        }

        // Jika ada data yang diimpor, rekalkulasi total pembelian
        if ($importedCount > 0) {
        try {
        $userController = new UserController();
        $userController->rekalkulasiTotalPembelianFob($customer);
        
        // PERBAIKAN: Lakukan sinkronisasi realtime setelah auto-sync
        $this->syncRealtimeCalculationsToTotal($customer);
        
            Log::info("Auto-sync: Rekalkulasi total pembelian sukses", [
            'customer_id' => $customer->id,
                'customer_name' => $customer->name
                ]);
                } catch (\Exception $e) {
                    Log::error("Error saat merekalkulasi total pembelian FOB {$customer->name}: " . $e->getMessage());
                }
            }

        return $importedCount;
    }

    // Fungsi untuk menghitung informasi tahunan FOB
    private function calculateYearlyData(User $customer, $tahun)
    {
        // Ambil semua data pencatatan
        $allData = $customer->dataPencatatan()->get();

        // Filter hanya data dari tahun yang dipilih dengan metode yang ditingkatkan
        $yearlyData = $allData->filter(function ($item) use ($tahun) {
            $dataInput = $this->ensureArray($item->data_input);

            // Jika data input kosong, skip
            if (empty($dataInput)) {
                return false;
            }

            // Coba berbagai format dan kunci data
            $matchFound = false;

            // 1. Cek format standard 'waktu' (string datetime)
            if (!empty($dataInput['waktu']) && is_string($dataInput['waktu'])) {
                try {
                    $waktu = Carbon::parse($dataInput['waktu']);
                    $dataYear = $waktu->format('Y');
                    $matchFound = ($dataYear === $tahun);

                    if ($matchFound) {
                        return true;
                    }
                } catch (\Exception $e) {
                    // Ignore errors and try next format
                }
            }

            // 2. Cek format tanggal (string date only)
            if (!empty($dataInput['tanggal']) && is_string($dataInput['tanggal'])) {
                try {
                    $tanggal = Carbon::parse($dataInput['tanggal']);
                    $dataYear = $tanggal->format('Y');
                    $matchFound = ($dataYear === $tahun);

                    if ($matchFound) {
                        return true;
                    }
                } catch (\Exception $e) {
                    // Ignore errors and try next format
                }
            }

            // 3. Cek format pembacaan_awal.waktu (nested object)
            if (!empty($dataInput['pembacaan_awal']['waktu']) && is_string($dataInput['pembacaan_awal']['waktu'])) {
                try {
                    $waktu = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
                    $dataYear = $waktu->format('Y');
                    $matchFound = ($dataYear === $tahun);

                    if ($matchFound) {
                        return true;
                    }
                } catch (\Exception $e) {
                    // Ignore errors and try next format
                }
            }

            // 4. Cek format created_at dari item (fallback)
            try {
                if ($item->created_at) {
                    $createdAt = Carbon::parse($item->created_at);
                    $dataYear = $createdAt->format('Y');
                    $matchFound = ($dataYear === $tahun);

                    if ($matchFound) {
                        return true;
                    }
                }
            } catch (\Exception $e) {
                // Ignore errors
            }

            return false; // No match found in any format
        });

        // Hitung total pemakaian dengan penanganan berbagai format
        $totalPemakaianTahunan = 0;
        foreach ($yearlyData as $item) {
            $dataInput = $this->ensureArray($item->data_input);

            // Ambil volume SM3 dari berbagai format yang mungkin
            $volumeSm3 = 0;

            // Format FOB standard menggunakan volume_sm3 langsung
            if (isset($dataInput['volume_sm3'])) {
                $volumeSm3 = floatval($dataInput['volume_sm3']);
            }
            // Format dari rekap_pengambilan menggunakan volume
            else if (isset($dataInput['volume'])) {
                $volumeSm3 = floatval($dataInput['volume']);
            }
            // Format customer biasa menggunakan volume_flow_meter dan koreksi
            else if (isset($dataInput['volume_flow_meter'])) {
                $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
                $koreksiMeter = floatval($customer->koreksi_meter ?? 1.0);
                $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
            }

            // Log volume info
            Log::info('FOB volume calculation', [
                'id' => $item->id,
                'data_input' => $dataInput,
                'has_volume_sm3' => isset($dataInput['volume_sm3']),
                'has_volume' => isset($dataInput['volume']),
                'has_volume_flow_meter' => isset($dataInput['volume_flow_meter']),
                'calculated_volume' => $volumeSm3
            ]);

            $totalPemakaianTahunan += $volumeSm3;
        }

        // Hitung total pembelian berdasarkan volume Sm3 dan harga per meter kubik
        $totalPembelianTahunan = 0;
        foreach ($yearlyData as $item) {
            // Jika harga_final sudah tersedia, gunakan itu (lebih akurat)
            if ($item->harga_final > 0) {
                $totalPembelianTahunan += floatval($item->harga_final);
                continue;
            }
            
            // Jika tidak ada harga_final, kita perlu menghitung berdasarkan volume dan harga
            $dataInput = $this->ensureArray($item->data_input);
            $volumeSm3 = floatval($dataInput['volume_sm3'] ?? 0);

            // Ambil waktu untuk mendapatkan pricing yang tepat - dengan penanganan berbagai format
            $waktuYearMonth = null;

            // Coba berbagai format dan kunci data
            if (!empty($dataInput['waktu']) && is_string($dataInput['waktu'])) {
                try {
                    $waktuYearMonth = Carbon::parse($dataInput['waktu'])->format('Y-m');
                } catch (\Exception $e) {
                    // Ignore errors and try next format
                }
            }

            if (!$waktuYearMonth && !empty($dataInput['tanggal']) && is_string($dataInput['tanggal'])) {
                try {
                    $waktuYearMonth = Carbon::parse($dataInput['tanggal'])->format('Y-m');
                } catch (\Exception $e) {
                    // Ignore errors and try next format
                }
            }

            if (!$waktuYearMonth && !empty($dataInput['pembacaan_awal']['waktu']) && is_string($dataInput['pembacaan_awal']['waktu'])) {
                try {
                    $waktuYearMonth = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m');
                } catch (\Exception $e) {
                    // Ignore errors and try next format
                }
            }

            // Fallback ke created_at jika semua format gagal
            if (!$waktuYearMonth && $item->created_at) {
                try {
                    $waktuYearMonth = Carbon::parse($item->created_at)->format('Y-m');
                } catch (\Exception $e) {
                    // Use current date as last resort
                    $waktuYearMonth = Carbon::now()->format('Y-m');
                }
            }

            // Fallback terakhir jika masih null
            if (!$waktuYearMonth) {
                $waktuYearMonth = Carbon::now()->format('Y-m');
            }
            
            // Dapatkan tanggal yang tepat untuk pricing
            $recordDate = null;
            if (!empty($dataInput['waktu'])) {
                $recordDate = Carbon::parse($dataInput['waktu']);
            } elseif (!empty($dataInput['tanggal'])) {
                $recordDate = Carbon::parse($dataInput['tanggal']);
            } elseif (!empty($dataInput['pembacaan_awal']['waktu'])) {
                $recordDate = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
            } elseif ($item->created_at) {
                $recordDate = $item->created_at;
            } else {
                $recordDate = Carbon::now();
            }
            
            $pricingInfo = $customer->getPricingForYearMonth($waktuYearMonth, $recordDate);

            // Gunakan harga yang sesuai untuk periode ini
            $hargaPerMeterKubik = floatval($pricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
            $pembelian = $volumeSm3 * $hargaPerMeterKubik;

            $totalPembelianTahunan += $pembelian;
        }

        // Log untuk debugging
        Log::info("FOB {$customer->name} - Tahunan ($tahun): Pemakaian: $totalPemakaianTahunan Sm³, Pembelian: Rp " . number_format($totalPembelianTahunan, 0));
        
        // DEBUG: Log detail untuk troubleshooting
        Log::info('FOB calculateYearlyData - Detail', [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'tahun' => $tahun,
            'total_records_all' => $allData->count(),
            'total_records_yearly_filtered' => $yearlyData->count(),
            'calculated_total_pemakaian_tahunan' => $totalPemakaianTahunan,
            'calculated_total_pembelian_tahunan' => $totalPembelianTahunan,
            'database_total_purchases_current' => $customer->total_purchases,
            'yearly_vs_total_purchases_diff' => $totalPembelianTahunan - $customer->total_purchases
        ]);

        return [
            'totalPemakaianTahunan' => round($totalPemakaianTahunan, 2),
            'totalPembelianTahunan' => round($totalPembelianTahunan, 0) // Bulatkan ke angka bulat untuk Rupiah
        ];
    }

    // Menampilkan form untuk membuat data pencatatan FOB
    public function create()
    {
        // Ambil daftar FOB untuk dipilih
        $fobs = User::where('role', User::ROLE_FOB)->get();
        $nomorPolisList = NomorPolisi::orderBy('nopol')->get();
        return view('data-pencatatan.fob.fob-create', compact('fobs', 'nomorPolisList'));
    }

    // Menampilkan form untuk membuat data pencatatan FOB dengan FOB yang sudah dipilih
    public function createWithFob($fobId)
    {
        // Ambil daftar FOB untuk dipilih
        $fobs = User::where('role', User::ROLE_FOB)->get();
        $selectedCustomer = User::findOrFail($fobId);
        $nomorPolisList = NomorPolisi::orderBy('nopol')->get();

        return view('data-pencatatan.fob.fob-create', compact('fobs', 'selectedCustomer', 'nomorPolisList'));
    }

    // Proses penyimpanan data pencatatan FOB
    public function store(Request $request)
    {

        $validatedData = $request->validate([
            'customer_id' => 'required|exists:users,id',
            'data_input' => 'required|array',
            'nopol' => 'required|string|max:20',
            'alamat_pengambilan' => 'nullable|string|max:500'
        ]);

        $customer = User::findOrFail($validatedData['customer_id']);

        // Verifikasi bahwa customer adalah FOB
        if (!$customer->isFOB()) {
            return redirect()->back()->with('error', 'User yang dipilih bukan FOB')->withInput();
        }

        // Validasi data input FOB
        $this->validateFobInput($validatedData['data_input']);
        
        // Ambil waktu untuk mendapatkan pricing yang tepat
        $waktuDateTime = Carbon::parse($validatedData['data_input']['waktu']);
        $waktuYearMonth = $waktuDateTime->format('Y-m');
        
        // Ambil pricing info berdasarkan tanggal spesifik
        $pricingInfo = $customer->getPricingForYearMonth($waktuYearMonth, $waktuDateTime);

        // Hitung harga dengan pricing yang sesuai periode
        $volumeSm3 = floatval($validatedData['data_input']['volume_sm3']);
        $hargaPerM3 = floatval($pricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
        $hargaFinal = $volumeSm3 * $hargaPerM3;
        
        // Simpan alamat pengambilan di data_input juga
        $validatedData['data_input']['alamat_pengambilan'] = $validatedData['alamat_pengambilan'] ?? null;
        
        // Log detail pricing untuk debugging
        Log::info('Creating new FOB data with pricing', [
            'customer_id' => $customer->id,
            'waktu' => $waktuDateTime->format('Y-m-d H:i:s'),
            'volume_sm3' => $volumeSm3,
            'harga_per_m3' => $hargaPerM3,
            'harga_final' => $hargaFinal,
            'pricing_info' => $pricingInfo,
            'alamat_pengambilan' => $validatedData['alamat_pengambilan'] ?? null
        ]);

        // Buat data pencatatan baru
        $dataPencatatan = new DataPencatatan();
        $dataPencatatan->customer_id = $validatedData['customer_id'];
        $dataPencatatan->data_input = json_encode($validatedData['data_input']);
        $dataPencatatan->nama_customer = $customer->name;
        $dataPencatatan->status_pembayaran = 'belum_lunas'; // Default status
        $dataPencatatan->harga_final = $hargaFinal;
        $dataPencatatan->save();

        // Update total pembelian customer
        $customer->recordPurchase($hargaFinal);
        $userController = new UserController();
        $userController->rekalkulasiTotalPembelianFob($customer);
        
        // PERBAIKAN: Lakukan sinkronisasi realtime setelah menambah data
        $this->syncRealtimeCalculationsToTotal($customer);

        // Tambahkan data ke rekap pengambilan
        $rekapPengambilan = new RekapPengambilan();
        $rekapPengambilan->customer_id = $validatedData['customer_id'];
        $rekapPengambilan->tanggal = $validatedData['data_input']['waktu'];
        $rekapPengambilan->nopol = $validatedData['nopol'];
        $rekapPengambilan->volume = $volumeSm3;
        $rekapPengambilan->alamat_pengambilan = $validatedData['alamat_pengambilan'] ?? null;
        $rekapPengambilan->keterangan = $validatedData['data_input']['keterangan'] ?? null;
        $rekapPengambilan->save();

        return redirect()->route('data-pencatatan.fob-detail', ['customer' => $validatedData['customer_id']])
            ->with('success', 'Data FOB berhasil disimpan');
    }

    // Validasi input FOB
    private function validateFobInput($dataInput)
    {
        // Validasi waktu
        if (empty($dataInput['waktu'])) {
            throw new \InvalidArgumentException('Tanggal dan waktu harus diisi');
        }

        // Validasi volume SM3
        if (!isset($dataInput['volume_sm3']) || floatval($dataInput['volume_sm3']) < 0) {
            throw new \InvalidArgumentException('Volume Sm³ tidak valid');
        }
    }

    /**
     * Fungsi untuk melakukan sinkronisasi realtime antara perhitungan periode dengan total customer
     * Memastikan $customer->total_purchases selalu sama dengan total dari semua periode
     */
    private function syncRealtimeCalculationsToTotal(User $customer)
    {
        try {
            // PERBAIKAN: Hitung ulang total purchases berdasarkan semua data dengan pricing realtime
            $allRekapPengambilan = RekapPengambilan::where('customer_id', $customer->id)->get();
            
            $calculatedTotalPurchases = 0;
            $calculatedTotalVolume = 0;
            
            foreach ($allRekapPengambilan as $item) {
                $volumeSm3 = floatval($item->volume);
                $calculatedTotalVolume += $volumeSm3;
                
                // Ambil pricing berdasarkan tanggal item (sama seperti perhitungan periode)
                $itemDate = Carbon::parse($item->tanggal);
                $itemYearMonth = $itemDate->format('Y-m');
                $itemPricingInfo = $customer->getPricingForYearMonth($itemYearMonth, $itemDate);
                
                $hargaPerM3 = floatval($itemPricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
                $calculatedTotalPurchases += ($volumeSm3 * $hargaPerM3);
            }
            
            // PERBAIKAN: Bandingkan dengan total yang tersimpan di database
            $currentTotalPurchases = $customer->total_purchases;
            $difference = abs($calculatedTotalPurchases - $currentTotalPurchases);
            
            // Jika ada perbedaan signifikan (lebih dari 1 rupiah), update
            if ($difference > 1) {
                Log::info('FOB Realtime Sync: Perbedaan total purchases terdeteksi', [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'calculated_total' => $calculatedTotalPurchases,
                    'current_total' => $currentTotalPurchases,
                    'difference' => $difference
                ]);
                
                // Update total_purchases dengan perhitungan realtime
                $customer->total_purchases = $calculatedTotalPurchases;
                $customer->save();
                
                Log::info('FOB Realtime Sync: Total purchases berhasil disinkronkan', [
                    'customer_id' => $customer->id,
                    'new_total_purchases' => $calculatedTotalPurchases
                ]);
                
                return [
                    'updated' => true,
                    'old_total' => $currentTotalPurchases,
                    'new_total' => $calculatedTotalPurchases,
                    'difference' => $difference,
                    'total_volume' => $calculatedTotalVolume
                ];
            }
            
            return [
                'updated' => false,
                'total_purchases' => $calculatedTotalPurchases,
                'total_volume' => $calculatedTotalVolume
            ];
            
        } catch (\Exception $e) {
            Log::error('Error dalam syncRealtimeCalculationsToTotal', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'updated' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * PERBAIKAN: Method helper untuk menghitung saldo periode tertentu
     * Method ini menggantikan perhitungan yang ada di View
     * Memastikan konsistensi antara "Saldo Bulan Sebelumnya" dan "Sisa Saldo Periode Bulan Ini"
     */
    private function calculatePeriodBalance(User $customer, $tahun, $bulan)
    {
        try {
            $startDate = Carbon::createFromDate($tahun, $bulan, 1)->startOfDay();
            $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth()->endOfDay();
            
            // Ambil semua rekap pengambilan untuk customer ini
            $allRekapPengambilan = RekapPengambilan::where('customer_id', $customer->id)
                ->orderBy('tanggal', 'asc')
                ->get();
            
            // Ambil semua deposit history
            $depositHistory = $this->ensureArray($customer->deposit_history);
            
            // Hitung deposits sampai SEBELUM periode ini dimulai (untuk saldo awal)
            $depositsBeforePeriod = 0;
            foreach ($depositHistory as $deposit) {
                if (isset($deposit['date'])) {
                    $depositDate = Carbon::parse($deposit['date']);
                    if ($depositDate < $startDate) {
                        $amount = floatval($deposit['amount'] ?? 0);
                        $keterangan = $deposit['keterangan'] ?? 'penambahan';
                        
                        if ($keterangan === 'pengurangan') {
                            $depositsBeforePeriod -= abs($amount);
                        } else {
                            $depositsBeforePeriod += $amount;
                        }
                    }
                }
            }
            
            // Hitung purchases sampai SEBELUM periode ini dimulai (untuk saldo awal)
            $purchasesBeforePeriod = 0;
            foreach ($allRekapPengambilan as $item) {
                $itemDate = Carbon::parse($item->tanggal);
                if ($itemDate < $startDate) {
                    $volumeSm3 = floatval($item->volume);
                    $itemYearMonth = $itemDate->format('Y-m');
                    $itemPricingInfo = $customer->getPricingForYearMonth($itemYearMonth, $itemDate);
                    $hargaPerM3 = floatval($itemPricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
                    $purchasesBeforePeriod += ($volumeSm3 * $hargaPerM3);
                }
            }
            
            // Saldo awal periode = deposits sebelum periode - purchases sebelum periode
            $saldoAwalPeriode = $depositsBeforePeriod - $purchasesBeforePeriod;
            
            // Hitung deposits DALAM periode ini
            $depositsInPeriod = 0;
            foreach ($depositHistory as $deposit) {
                if (isset($deposit['date'])) {
                    $depositDate = Carbon::parse($deposit['date']);
                    if ($depositDate->between($startDate, $endDate)) {
                        $amount = floatval($deposit['amount'] ?? 0);
                        $keterangan = $deposit['keterangan'] ?? 'penambahan';
                        
                        if ($keterangan === 'pengurangan') {
                            $depositsInPeriod -= abs($amount);
                        } else {
                            $depositsInPeriod += $amount;
                        }
                    }
                }
            }
            
            // Hitung purchases DALAM periode ini
            $purchasesInPeriod = 0;
            $volumeInPeriod = 0;
            $dataPencatatanInPeriod = [];
            
            foreach ($allRekapPengambilan as $item) {
                $itemDate = Carbon::parse($item->tanggal);
                if ($itemDate->between($startDate, $endDate)) {
                    $volumeSm3 = floatval($item->volume);
                    $itemYearMonth = $itemDate->format('Y-m');
                    $itemPricingInfo = $customer->getPricingForYearMonth($itemYearMonth, $itemDate);
                    $hargaPerM3 = floatval($itemPricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
                    
                    $purchasesInPeriod += ($volumeSm3 * $hargaPerM3);
                    $volumeInPeriod += $volumeSm3;
                    $dataPencatatanInPeriod[] = $item;
                }
            }
            
            // Saldo akhir periode = saldo awal + deposits dalam periode - purchases dalam periode
            $saldoAkhirPeriode = $saldoAwalPeriode + $depositsInPeriod - $purchasesInPeriod;
            
            // Log untuk debugging
            Log::info('Perhitungan Saldo FOB Periode', [
                'customer_id' => $customer->id,
                'periode' => $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT),
                'saldo_awal_periode' => $saldoAwalPeriode,
                'deposits_in_period' => $depositsInPeriod,
                'purchases_in_period' => $purchasesInPeriod,
                'volume_in_period' => $volumeInPeriod,
                'saldo_akhir_periode' => $saldoAkhirPeriode,
                'jumlah_transaksi' => count($dataPencatatanInPeriod)
            ]);
            
            return [
                'saldo_awal_periode' => $saldoAwalPeriode, // Ini adalah "Saldo Bulan Sebelumnya"
                'deposits_periode' => $depositsInPeriod,
                'purchases_periode' => $purchasesInPeriod,
                'volume_periode' => $volumeInPeriod,
                'saldo_akhir_periode' => $saldoAkhirPeriode, // Ini adalah "Sisa Saldo Periode Bulan Ini"
                'data_pencatatan' => collect($dataPencatatanInPeriod)->sortBy('tanggal'),
                'jumlah_transaksi' => count($dataPencatatanInPeriod)
            ];
            
        } catch (\Exception $e) {
            Log::error('Error dalam calculatePeriodBalance', [
                'customer_id' => $customer->id,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return default values jika error
            return [
                'saldo_awal_periode' => 0,
                'deposits_periode' => 0,
                'purchases_periode' => 0,
                'volume_periode' => 0,
                'saldo_akhir_periode' => 0,
                'data_pencatatan' => collect([]),
                'jumlah_transaksi' => 0
            ];
        }
    }

    // Menampilkan detail pencatatan untuk FOB tertentu
    public function fobDetail(User $customer, Request $request)
    {
        // Verifikasi bahwa customer adalah FOB
        if (!$customer->isFOB()) {
            Log::warning('Attempt to access FOB detail for non-FOB user', ['user_id' => $customer->id, 'role' => $customer->role]);
            return redirect()->back()->with('error', 'User yang dipilih bukan FOB');
        }

        // PERBAIKAN: Lakukan sinkronisasi realtime sebelum menampilkan data
        $syncResult = $this->syncRealtimeCalculationsToTotal($customer);
        
        // Reload customer jika ada update
        if ($syncResult['updated'] ?? false) {
            $customer = User::findOrFail($customer->id);
            
            // Tampilkan notifikasi jika ada perbaikan otomatis
            session()->flash('info', 
                '✅ Data total berhasil disinkronkan dengan perhitungan realtime. ' .
                'Selisih yang diperbaiki: Rp ' . number_format($syncResult['difference'], 0)
            );
        }

        // Get filter parameters
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');

        // Default to current month and year if not specified
        if (!$bulan) {
            $bulan = date('m');
        }
        if (!$tahun) {
            $tahun = date('Y');
        }
        
        // PERBAIKAN: Gunakan helper method untuk menghitung saldo periode
        $periodBalance = $this->calculatePeriodBalance($customer, $tahun, $bulan);

        // PERBAIKAN: Gunakan data dari RekapPengambilan untuk konsistensi dengan halaman rekap-pengambilan
        $selectedBulan = $bulan;
        $selectedTahun = $tahun;
        
        // Format filter untuk query
        $yearMonth = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT);

        // PERBAIKAN: Ambil data dari helper method (sudah terfilter dan tersortir)
        $dataPencatatan = $periodBalance['data_pencatatan'];
        
        // PERBAIKAN: Buat mapping untuk memudahkan akses ID rekap pengambilan
        $rekapMapping = [];
        foreach ($dataPencatatan as $item) {
            $tanggalKey = Carbon::parse($item->tanggal)->format('Y-m-d');
            $volumeKey = floatval($item->volume);
            $rekapMapping[$tanggalKey][$volumeKey] = $item->id;
        }

        // Get pricing info for selected month
        $pricingInfo = $customer->getPricingForYearMonth($yearMonth);

        // PERBAIKAN: Calculate total volume SM3 untuk SEMUA WAKTU dari hasil sinkronisasi
        $totalVolumeSm3 = $syncResult['total_volume'] ?? RekapPengambilan::where('customer_id', $customer->id)->sum('volume');

        // PERBAIKAN: Ambil data dari helper method
        $filteredVolumeSm3 = $periodBalance['volume_periode'];
        $filteredTotalPurchases = $periodBalance['purchases_periode'];
        $filteredTotalDeposits = $periodBalance['deposits_periode'];
        $prevMonthBalance = $periodBalance['saldo_awal_periode']; // Ini adalah saldo bulan sebelumnya
        $currentMonthBalance = $periodBalance['saldo_akhir_periode']; // Ini adalah sisa saldo periode bulan ini

        // Calculate yearly data menggunakan rekap pengambilan dengan pricing realtime
        $allRekapPengambilan = RekapPengambilan::where('customer_id', $customer->id)->get();
        $yearlyRekapData = $allRekapPengambilan->filter(function ($item) use ($tahun) {
            $itemDate = Carbon::parse($item->tanggal);
            return $itemDate->year == $tahun;
        });
        
        $totalPemakaianTahunan = $yearlyRekapData->sum('volume');
        
        $totalPembelianTahunan = 0;
        foreach ($yearlyRekapData as $item) {
            $volumeSm3 = floatval($item->volume);
            $itemDate = Carbon::parse($item->tanggal);
            $itemYearMonth = $itemDate->format('Y-m');
            $itemPricingInfo = $customer->getPricingForYearMonth($itemYearMonth, $itemDate);
            $hargaPerM3 = floatval($itemPricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
            $totalPembelianTahunan += ($volumeSm3 * $hargaPerM3);
        }

        // PERBAIKAN: Reload customer terbaru untuk memastikan data total_purchases sudah tersinkronisasi
        $customer = User::findOrFail($customer->id);
        
        // PERBAIKAN: Log untuk debugging konsistensi saldo
        Log::info('FOB Detail - Data yang dikirim ke view', [
            'customer_id' => $customer->id,
            'periode' => $yearMonth,
            'prevMonthBalance' => $prevMonthBalance,
            'currentMonthBalance' => $currentMonthBalance,
            'filteredTotalDeposits' => $filteredTotalDeposits,
            'filteredTotalPurchases' => $filteredTotalPurchases,
            'calculation_check' => [
                'expected_current' => $prevMonthBalance + $filteredTotalDeposits - $filteredTotalPurchases,
                'actual_current' => $currentMonthBalance,
                'is_consistent' => abs(($prevMonthBalance + $filteredTotalDeposits - $filteredTotalPurchases) - $currentMonthBalance) < 0.01
            ]
        ]);

        // Ambil bulan-bulan yang belum diinput harganya
        $monthsWithoutPricing = $customer->getMonthsWithoutPricing();

        return view('data-pencatatan.fob.fob-detail', [
            'customer' => $customer,
            'dataPencatatan' => $dataPencatatan, // Menggunakan data dari helper method
            'rekapMapping' => $rekapMapping,
            'depositHistory' => $this->ensureArray($customer->deposit_history),
            'totalDeposit' => $customer->total_deposit,
            'totalPurchases' => $customer->total_purchases,
            'currentBalance' => $customer->getCurrentBalance(),
            'selectedBulan' => $selectedBulan,
            'selectedTahun' => $selectedTahun,
            'pricingInfo' => $pricingInfo,
            'totalVolumeSm3' => $totalVolumeSm3,
            'filteredVolumeSm3' => $filteredVolumeSm3,
            'filteredTotalPurchases' => $filteredTotalPurchases,
            'filteredTotalDeposits' => $filteredTotalDeposits,
            'prevMonthBalance' => $prevMonthBalance, // PERBAIKAN: Dari helper method
            'currentMonthBalance' => $currentMonthBalance, // PERBAIKAN: Saldo akhir periode
            'totalPemakaianTahunan' => $totalPemakaianTahunan,
            'totalPembelianTahunan' => $totalPembelianTahunan,
            // PERBAIKAN: Tambahkan info sinkronisasi untuk debugging
            'syncInfo' => $syncResult,
            'monthsWithoutPricing' => $monthsWithoutPricing // Notifikasi bulan tanpa harga
        ]);
    }

    // Filter data pencatatan FOB berdasarkan bulan dan tahun
    public function filterByMonthYear(Request $request, User $customer)
    {
        $validatedData = $request->validate([
            'bulan' => 'required|numeric|between:1,12',
            'tahun' => 'required|numeric|between:2000,2100'
        ]);

        // Gunakan with() untuk menyimpan data dalam session flash
        // ini akan memastikan variabel $depositHistory tersedia di view
        return redirect()->route('data-pencatatan.fob-detail', [
            'customer' => $customer->id,
            'bulan' => $validatedData['bulan'],
            'tahun' => $validatedData['tahun']
        ]);
    }
    
    /**
     * Fungsi untuk force-reset monthly balances dan menghitung ulang seluruh saldo
     * Digunakan untuk memperbaiki data yang tidak konsisten
     */
    public function resetAndRecalculateBalance(User $customer)
    {
        try {
            DB::beginTransaction();
            
            // Verifikasi bahwa user memiliki izin (admin atau superadmin)
            if (!Auth::user()->isAdmin() && !Auth::user()->isSuperAdmin()) {
                return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk melakukan operasi ini');
            }
            
            // Log operasi reset
            Log::info('Memulai reset dan rekalkulasi saldo untuk customer', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
            ]);
            
            // 1. Reset total_purchases dan hitung ulang dari semua data pencatatan
            $userController = new UserController();
            if ($customer->isFOB()) {
                $newTotalPurchases = $userController->rekalkulasiTotalPembelianFob($customer);
            } else {
                $newTotalPurchases = $userController->rekalkulasiTotalPembelian($customer);
            }
            
            // 2. Reset total_deposit dan hitung ulang dari deposit_history
            $depositHistory = $this->ensureArray($customer->deposit_history);
            $newTotalDeposit = 0;
            
            foreach ($depositHistory as $deposit) {
                $newTotalDeposit += floatval($deposit['amount'] ?? 0);
            }
            
            // Update total_deposit jika berbeda
            if (abs($newTotalDeposit - $customer->total_deposit) > 0.01) {
                Log::info('Memperbarui total_deposit', [
                    'customer_id' => $customer->id,
                    'old_total_deposit' => $customer->total_deposit,
                    'new_total_deposit' => $newTotalDeposit,
                    'difference' => $newTotalDeposit - $customer->total_deposit
                ]);
                
                $customer->total_deposit = $newTotalDeposit;
                $customer->save();
            }
            
            // 3. Hapus monthly_balances dan hitung ulang dari awal secara menyeluruh
            // Reset monthly_balances ke array kosong
            $customer->monthly_balances = [];
            $customer->save();
            
            // Jalankan updateMonthlyBalances dengan waktu awal 4 tahun ke belakang untuk memastikan semua data tercakup
            $fourYearsAgo = Carbon::now()->subYears(4)->startOfMonth()->format('Y-m');
            Log::info('Menghitung ulang monthly_balances dari 4 tahun lalu', [
                'start_month' => $fourYearsAgo
            ]);
            
            $customer->updateMonthlyBalances($fourYearsAgo);
            
            // 4. Log hasil rekalkulasi
            $monthlyBalances = $this->ensureArray($customer->monthly_balances);
            Log::info('Reset dan rekalkulasi saldo selesai', [
                'customer_id' => $customer->id,
                'monthly_balances_count' => count($monthlyBalances),
                'new_total_deposit' => $customer->total_deposit,
                'new_total_purchases' => $customer->total_purchases,
                'new_balance' => $customer->getCurrentBalance()
            ]);
            
            DB::commit();
            
            return redirect()->route('data-pencatatan.fob-detail', ['customer' => $customer->id])
                ->with('success', 'Reset dan rekalkulasi saldo berhasil dilakukan.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saat reset dan rekalkulasi saldo: ' . $e->getMessage(), [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat melakukan reset dan rekalkulasi saldo: ' . $e->getMessage());
        }
    }

    /**
     * Method untuk menganalisis data FOB untuk debugging
     */
    public function analyzeFobData(User $customer)
    {
        if (!$customer->isFOB()) {
            return response()->json(['error' => 'User bukan FOB'], 400);
        }

        // Ambil semua data pencatatan
        $allRecords = $customer->dataPencatatan()->get();
        $rekapRecords = RekapPengambilan::where('customer_id', $customer->id)->get();
        
        // Analisis duplikasi
        $duplicates = [];
        $uniqueRecords = [];
        
        foreach ($allRecords as $record) {
            $dataInput = $this->ensureArray($record->data_input);
            
            if (!empty($dataInput['waktu']) && isset($dataInput['volume_sm3'])) {
                $dateKey = Carbon::parse($dataInput['waktu'])->format('Y-m-d');
                $volumeKey = $dataInput['volume_sm3'];
                $uniqueKey = $dateKey . '_' . $volumeKey;

                if (isset($uniqueRecords[$uniqueKey])) {
                    $duplicates[] = [
                        'original_id' => $uniqueRecords[$uniqueKey]->id,
                        'duplicate_id' => $record->id,
                        'date' => $dateKey,
                        'volume' => $volumeKey,
                        'original_harga' => $uniqueRecords[$uniqueKey]->harga_final,
                        'duplicate_harga' => $record->harga_final
                    ];
                } else {
                    $uniqueRecords[$uniqueKey] = $record;
                }
            }
        }
        
        // Analisis konsistensi total
        $manualTotal = $allRecords->sum('harga_final');
        $storedTotal = $customer->total_purchases;
        $difference = abs($manualTotal - $storedTotal);
        
        // Analisis data yang tidak ter-sync
        $rekapDates = $rekapRecords->pluck('tanggal')->map(function($date) {
            return Carbon::parse($date)->format('Y-m-d');
        })->toArray();
        
        $pencatatanDates = $allRecords->map(function($record) {
            $dataInput = $this->ensureArray($record->data_input);
            if (!empty($dataInput['waktu'])) {
                return Carbon::parse($dataInput['waktu'])->format('Y-m-d');
            }
            return null;
        })->filter()->toArray();
        
        $missingFromPencatatan = array_diff($rekapDates, $pencatatanDates);
        $extraInPencatatan = array_diff($pencatatanDates, $rekapDates);
        
        // Analisis data tanpa harga_final
        $recordsWithoutHarga = $allRecords->filter(function($record) {
            return $record->harga_final <= 0;
        });
        
        $analysis = [
            'summary' => [
                'total_pencatatan_records' => $allRecords->count(),
                'total_rekap_records' => $rekapRecords->count(),
                'duplicates_found' => count($duplicates),
                'records_without_harga' => $recordsWithoutHarga->count(),
                'missing_from_pencatatan' => count($missingFromPencatatan),
                'extra_in_pencatatan' => count($extraInPencatatan)
            ],
            'totals' => [
                'manual_total' => $manualTotal,
                'stored_total' => $storedTotal,
                'difference' => $difference,
                'is_consistent' => $difference < 0.01
            ],
            'duplicates' => $duplicates,
            'missing_dates' => $missingFromPencatatan,
            'extra_dates' => $extraInPencatatan,
            'records_without_harga' => $recordsWithoutHarga->map(function($record) {
                $dataInput = $this->ensureArray($record->data_input);
                return [
                    'id' => $record->id,
                    'date' => $dataInput['waktu'] ?? 'unknown',
                    'volume' => $dataInput['volume_sm3'] ?? 0,
                    'harga_final' => $record->harga_final
                ];
            })->toArray()
        ];
        
        if (request()->expectsJson()) {
            return response()->json($analysis);
        }
        
        return view('debug.fob-analysis', compact('customer', 'analysis'));
    }

    /**
     * Method untuk membersihkan data duplikat FOB (dengan response)  
     */
    public function cleanDuplicateFobData(User $customer)
    {
        if (!$customer->isFOB()) {
            return false;
        }

        DB::beginTransaction();
        
        try {
            // Ambil semua data pencatatan FOB
            $allRecords = $customer->dataPencatatan()->get();
            $duplicateIds = [];
            $uniqueRecords = [];

            foreach ($allRecords as $record) {
                $dataInput = $this->ensureArray($record->data_input);
                
                if (!empty($dataInput['waktu']) && isset($dataInput['volume_sm3'])) {
                    $dateKey = Carbon::parse($dataInput['waktu'])->format('Y-m-d');
                    $volumeKey = $dataInput['volume_sm3'];
                    $uniqueKey = $dateKey . '_' . $volumeKey;

                    if (isset($uniqueRecords[$uniqueKey])) {
                        // Ini adalah duplikat, tandai untuk dihapus
                        $duplicateIds[] = $record->id;
                        Log::info('Found duplicate FOB record', [
                            'original_id' => $uniqueRecords[$uniqueKey]->id,
                            'duplicate_id' => $record->id,
                            'date' => $dateKey,
                            'volume' => $volumeKey
                        ]);
                    } else {
                        $uniqueRecords[$uniqueKey] = $record;
                    }
                }
            }

            // Hapus data duplikat
            if (!empty($duplicateIds)) {
                DataPencatatan::whereIn('id', $duplicateIds)->delete();
                Log::info('Cleaned duplicate FOB records', [
                    'customer_id' => $customer->id,
                    'deleted_count' => count($duplicateIds),
                    'deleted_ids' => $duplicateIds
                ]);
            }

            DB::commit();
            return count($duplicateIds);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error cleaning duplicate data: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Method untuk memvalidasi konsistensi total pembelian FOB
     */
    public function validateFobTotalConsistency(User $customer)
    {
        if (!$customer->isFOB()) {
            return false;
        }

        // Hitung manual total dari semua harga_final
        $manualTotal = $customer->dataPencatatan()->sum('harga_final');
        
        // Bandingkan dengan total_purchases di customer
        $storedTotal = $customer->total_purchases;
        $difference = abs($manualTotal - $storedTotal);

        Log::info('FOB total consistency check', [
            'customer_id' => $customer->id,
            'manual_total' => $manualTotal,
            'stored_total' => $storedTotal,
            'difference' => $difference,
            'is_consistent' => $difference < 0.01
        ]);

        // Jika perbedaan signifikan, update total_purchases
        if ($difference > 0.01) {
            $customer->total_purchases = $manualTotal;
            $customer->save();
            
            Log::warning('Fixed inconsistent FOB total', [
                'customer_id' => $customer->id,
                'old_total' => $storedTotal,
                'new_total' => $manualTotal
            ]);
            
            return $difference;
        }

        return 0;
    }

    /**
     * Debug dan perbaiki perhitungan FOB
     */
    public function debugAndFixCalculations(User $customer)
    {
        if (!$customer->isFOB()) {
            return response()->json(['error' => 'User bukan FOB'], 400);
        }

        try {
            DB::beginTransaction();
            
            // 1. Ambil semua data
            $allData = $customer->dataPencatatan()->get();
            
            // 2. Hitung manual total volume dan total pembelian
            $manualTotalVolume = 0;
            $manualTotalPurchases = 0;
            $recordCount = 0;
            
            foreach ($allData as $item) {
                $dataInput = $this->ensureArray($item->data_input);
                $volumeSm3 = floatval($dataInput['volume_sm3'] ?? 0);
                
                if ($volumeSm3 > 0) {
                    $manualTotalVolume += $volumeSm3;
                    
                    // Hitung pembelian berdasarkan harga_final yang tersimpan
                    $manualTotalPurchases += floatval($item->harga_final ?? 0);
                    $recordCount++;
                }
            }
            
            // 3. Bandingkan dengan database
            $dbTotalPurchases = $customer->total_purchases;
            $dbTotalDeposit = $customer->total_deposit;
            $currentBalance = $dbTotalDeposit - $dbTotalPurchases;
            
            // 4. Perbaiki jika ada perbedaan
            $fixed = false;
            if (abs($manualTotalPurchases - $dbTotalPurchases) > 0.01) {
                $customer->total_purchases = $manualTotalPurchases;
                $customer->save();
                $fixed = true;
            }
            
            // 5. Update monthly balances
            $customer->updateMonthlyBalances();
            
            DB::commit();
            
            $result = [
                'customer_info' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'role' => $customer->role
                ],
                'calculations' => [
                    'total_records' => $recordCount,
                    'manual_total_volume' => $manualTotalVolume,
                    'manual_total_purchases' => $manualTotalPurchases,
                    'db_total_purchases_before' => $dbTotalPurchases,
                    'db_total_purchases_after' => $customer->fresh()->total_purchases,
                    'db_total_deposit' => $dbTotalDeposit,
                    'current_balance' => $currentBalance,
                    'fixed' => $fixed,
                    'difference_before_fix' => $manualTotalPurchases - $dbTotalPurchases
                ],
                'status' => 'success'
            ];
            
            if (request()->expectsJson()) {
                return response()->json($result);
            }
            
            return redirect()->route('data-pencatatan.fob-detail', $customer->id)
                ->with('success', 'Debug dan perbaikan selesai. ' . ($fixed ? 'Data telah diperbaiki.' : 'Tidak ada yang perlu diperbaiki.'));
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in debugAndFixCalculations: ' . $e->getMessage());
            
            if (request()->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
    /**
     * Halaman print terpisah untuk FOB
     */
    public function printPage(User $customer, Request $request)
    {
        // Verifikasi bahwa customer adalah FOB
        if (!$customer->isFOB()) {
            abort(404, 'Customer tidak ditemukan');
        }

        // Get filter parameters
        $bulan = $request->input('bulan', date('m'));
        $tahun = $request->input('tahun', date('Y'));

        // PERBAIKAN: Gunakan logic yang sama dengan fobDetail method
        // Definisi periode filter
        $startDate = Carbon::createFromDate($tahun, $bulan, 1)->startOfDay();
        $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth()->endOfDay();

        // PERBAIKAN: Ambil data dari RekapPengambilan (sama seperti di fobDetail)
        $allRekapPengambilan = RekapPengambilan::where('customer_id', $customer->id)->get();
        
        // Filter data berdasarkan periode (sama logic dengan fobDetail)
        $dataPencatatan = $allRekapPengambilan->filter(function ($item) use ($startDate, $endDate) {
            $tanggalItem = Carbon::parse($item->tanggal);
            return $tanggalItem->between($startDate, $endDate);
        });

        // Urutkan data berdasarkan tanggal (ascending)
        $dataPencatatan = $dataPencatatan->sortBy('tanggal');

        // PERBAIKAN: Hitung total volume dari RekapPengambilan
        $totalVolume = $dataPencatatan->sum('volume');

        $printData = [
            'customer' => $customer,
            'dataPencatatan' => $dataPencatatan, // Menggunakan data RekapPengambilan
            'selectedBulan' => $bulan,
            'selectedTahun' => $tahun,
            'totalVolume' => $totalVolume,
            'jumlahData' => $dataPencatatan->count(),
            'tanggalCetak' => now()->format('d F Y H:i'),
            'periode' => Carbon::createFromDate($tahun, $bulan, 1)->format('F Y')
        ];

        return view('data-pencatatan.fob.print-page', $printData);
    }

    private function performAutomaticDataValidation(User $customer)
    {
        try {
            // 1. Validasi dan perbaiki harga_final yang kosong
            $recordsWithoutHargaFinal = $customer->dataPencatatan()
                ->where(function($query) {
                    $query->whereNull('harga_final')
                          ->orWhere('harga_final', '<=', 0);
                })
                ->get();

            $fixedRecords = 0;
            foreach ($recordsWithoutHargaFinal as $record) {
                $dataInput = $this->ensureArray($record->data_input);
                $volumeSm3 = floatval($dataInput['volume_sm3'] ?? 0);

                if ($volumeSm3 > 0) {
                    // Ambil tanggal untuk pricing
                    $recordDate = null;
                    if (!empty($dataInput['waktu'])) {
                        $recordDate = Carbon::parse($dataInput['waktu']);
                    } elseif ($record->created_at) {
                        $recordDate = $record->created_at;
                    } else {
                        continue;
                    }

                    $yearMonth = $recordDate->format('Y-m');
                    $pricingInfo = $customer->getPricingForYearMonth($yearMonth, $recordDate);
                    $hargaPerM3 = floatval($pricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
                    $calculatedPrice = $volumeSm3 * $hargaPerM3;

                    // Update harga_final
                    $record->harga_final = round($calculatedPrice, 2);
                    $record->save();
                    $fixedRecords++;
                }
            }

            if ($fixedRecords > 0) {
                Log::info("Auto-fixed records without harga_final", [
                    'customer_id' => $customer->id,
                    'fixed_count' => $fixedRecords
                ]);
            }

            // 2. Validasi konsistensi total deposit
            $depositHistory = $this->ensureArray($customer->deposit_history);
            $calculatedTotalDeposit = 0;
            
            foreach ($depositHistory as $deposit) {
                if (isset($deposit['amount'])) {
                    $calculatedTotalDeposit += floatval($deposit['amount']);
                }
            }
            
            // Perbaiki jika ada perbedaan
            if (abs($calculatedTotalDeposit - $customer->total_deposit) > 0.01) {
                Log::info("Auto-correcting total deposit", [
                    'customer_id' => $customer->id,
                    'old_total' => $customer->total_deposit,
                    'new_total' => $calculatedTotalDeposit
                ]);
                
                $customer->total_deposit = $calculatedTotalDeposit;
                $customer->save();
            }

            // 3. Validasi dan update monthly_balances jika diperlukan
            $currentYearMonth = Carbon::now()->format('Y-m');
            $expectedBalance = $customer->total_deposit - $customer->total_purchases;
            $monthlyBalances = $this->ensureArray($customer->monthly_balances);
            $currentMonthBalance = $monthlyBalances[$currentYearMonth] ?? null;

            // Jika saldo bulan ini tidak ada atau berbeda signifikan
            if ($currentMonthBalance === null || abs($expectedBalance - $currentMonthBalance) > 1) {
                Log::info("Auto-updating monthly balances due to inconsistency", [
                    'customer_id' => $customer->id,
                    'expected_balance' => $expectedBalance,
                    'current_month_balance' => $currentMonthBalance
                ]);
                
                // Update monthly balances
                $customer->updateMonthlyBalances();
            }

        } catch (\Exception $e) {
            Log::error("Error in automatic data validation", [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Print deposit history for FOB customer
     */
    public function printDepositHistory(Request $request, User $customer)
    {
        // Verify that customer is FOB
        if (!$customer->isFOB()) {
            return redirect()->back()->with('error', 'User yang dipilih bukan FOB');
        }

        // Get filter parameters
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalAkhir = $request->input('tanggal_akhir');

        // Validate dates
        if (!$tanggalMulai || !$tanggalAkhir) {
            return redirect()->back()->with('error', 'Tanggal mulai dan tanggal akhir harus diisi');
        }

        // Parse dates
        $startDate = Carbon::parse($tanggalMulai)->startOfDay();
        $endDate = Carbon::parse($tanggalAkhir)->endOfDay();

        // Get deposit history - use the same method as regular customers
        $depositHistory = $this->ensureArray($customer->deposit_history);

        // Add keterangan to deposit history items
        $depositHistory = collect($depositHistory)->map(function ($deposit) {
            return [
                'date' => $deposit['date'] ?? '',
                'amount' => $deposit['amount'] ?? 0,
                'keterangan' => $deposit['keterangan'] ?? 'penambahan',
                'deskripsi' => $deposit['deskripsi'] ?? ($deposit['description'] ?? '-'),
            ];
        })->toArray();

        // Filter deposit by date range
        $filteredDeposits = collect($depositHistory)->filter(function($deposit) use ($startDate, $endDate) {
            if (empty($deposit['date'])) {
                return false;
            }

            $depositDate = Carbon::parse($deposit['date']);
            return $depositDate->between($startDate, $endDate);
        })->sortBy('date')->values();

        // Calculate totals within date range
        $totalDepositInRange = 0;
        $totalPurchaseInRange = 0;

        foreach ($filteredDeposits as $deposit) {
            $amount = floatval($deposit['amount'] ?? 0);
            if ($deposit['keterangan'] === 'penambahan') {
                $totalDepositInRange += $amount;
            } else {
                $totalPurchaseInRange += abs($amount);
            }
        }

        // Calculate balance
        $saldoTersisa = ($customer->total_deposit ?? 0) - ($customer->total_purchases ?? 0);

        // Prepare data for view
        $data = [
            'customer' => $customer,
            'depositHistory' => $filteredDeposits,
            'tanggalMulai' => $startDate->format('d F Y'),
            'tanggalAkhir' => $endDate->format('d F Y'),
            'totalDepositOverall' => $customer->total_deposit ?? 0,
            'totalPurchaseOverall' => $customer->total_purchases ?? 0,
            'saldoTersisa' => $saldoTersisa,
            'totalDepositInRange' => $totalDepositInRange,
            'tanggalCetak' => Carbon::now()->format('d F Y H:i'),
        ];

        // Return print view - use the same view as regular customers
        return view('data-pencatatan.print-deposit-history', $data);
    }
}
