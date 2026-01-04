<?php

namespace App\Http\Controllers;

use App\Models\RekapPengambilan;
use App\Models\DataPencatatan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DataSyncController extends Controller
{
    /**
     * Helper function to ensure data is always an array
     */
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
     * Analisis dan perbaiki data yang tidak sinkron antara rekap_pengambilan dan data_pencatatan
     */
    public function analyzeAndFixFobDataSync(User $customer)
    {
        // Verifikasi bahwa customer adalah FOB
        if (!$customer->isFOB()) {
            return response()->json(['error' => 'Customer bukan FOB'], 400);
        }

        try {
            DB::beginTransaction();

            // 1. Ambil semua data rekap_pengambilan
            $rekapData = RekapPengambilan::where('customer_id', $customer->id)
                ->orderBy('tanggal')
                ->get();

            // 2. Ambil semua data_pencatatan
            $pencatatanData = DataPencatatan::where('customer_id', $customer->id)
                ->orderBy('created_at')
                ->get();

            // 3. Analisis data
            $analysis = [
                'rekap_count' => $rekapData->count(),
                'pencatatan_count' => $pencatatanData->count(),
                'missing_pencatatan' => [],
                'orphaned_pencatatan' => [],
                'mismatched_data' => [],
                'fixed_relations' => 0,
                'created_pencatatan' => 0,
                'deleted_orphaned' => 0
            ];

            // 4. Cek data rekap yang tidak ada pencatatannya
            foreach ($rekapData as $rekap) {
                $rekapDate = Carbon::parse($rekap->tanggal)->format('Y-m-d');
                
                // Cari data pencatatan yang matching
                $matchingPencatatan = $pencatatanData->filter(function($pencatatan) use ($rekapDate, $rekap) {
                    $dataInput = $this->ensureArray($pencatatan->data_input);
                    
                    if (empty($dataInput['waktu'])) {
                        return false;
                    }
                    
                    $pencatatanDate = Carbon::parse($dataInput['waktu'])->format('Y-m-d');
                    $pencatatanVolume = floatval($dataInput['volume_sm3'] ?? 0);
                    
                    return $pencatatanDate === $rekapDate && 
                           abs($pencatatanVolume - $rekap->volume) < 0.01;
                })->first();

                if (!$matchingPencatatan) {
                    // Data rekap tidak ada pencatatannya, buat baru
                    $newPencatatan = $this->createDataPencatatanFromRekap($rekap);
                    if ($newPencatatan) {
                        $analysis['created_pencatatan']++;
                        $analysis['missing_pencatatan'][] = [
                            'rekap_id' => $rekap->id,
                            'tanggal' => $rekapDate,
                            'volume' => $rekap->volume,
                            'action' => 'created_new_pencatatan',
                            'pencatatan_id' => $newPencatatan->id
                        ];
                    }
                } else {
                    // Update relasi jika belum ada
                    if (!$matchingPencatatan->rekap_pengambilan_id) {
                        $matchingPencatatan->rekap_pengambilan_id = $rekap->id;
                        $matchingPencatatan->save();
                        $analysis['fixed_relations']++;
                    }
                }
            }

            // 5. Cek data pencatatan yang tidak ada rekapnya (orphaned)
            foreach ($pencatatanData as $pencatatan) {
                $dataInput = $this->ensureArray($pencatatan->data_input);
                
                if (empty($dataInput['waktu'])) {
                    continue;
                }
                
                $pencatatanDate = Carbon::parse($dataInput['waktu'])->format('Y-m-d');
                $pencatatanVolume = floatval($dataInput['volume_sm3'] ?? 0);
                
                // Cari rekap yang matching
                $matchingRekap = $rekapData->filter(function($rekap) use ($pencatatanDate, $pencatatanVolume) {
                    $rekapDate = Carbon::parse($rekap->tanggal)->format('Y-m-d');
                    return $rekapDate === $pencatatanDate && 
                           abs($rekap->volume - $pencatatanVolume) < 0.01;
                })->first();

                if (!$matchingRekap) {
                    // Data pencatatan tidak ada rekapnya, hapus
                    $analysis['orphaned_pencatatan'][] = [
                        'pencatatan_id' => $pencatatan->id,
                        'tanggal' => $pencatatanDate,
                        'volume' => $pencatatanVolume,
                        'harga_final' => $pencatatan->harga_final,
                        'action' => 'deleted_orphaned'
                    ];
                    
                    $pencatatan->delete();
                    $analysis['deleted_orphaned']++;
                }
            }

            // 6. Rekalkulasi total pembelian setelah perbaikan
            $userController = new UserController();
            $newTotalPurchases = $userController->rekalkulasiTotalPembelianFob($customer);

            // 7. Update monthly balances
            $customer->updateMonthlyBalances();

            DB::commit();

            $analysis['new_total_purchases'] = $newTotalPurchases;
            $analysis['status'] = 'success';

            Log::info('FOB Data Sync Analysis and Fix Completed', [
                'customer_id' => $customer->id,
                'analysis' => $analysis
            ]);

            return response()->json($analysis);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in analyzeAndFixFobDataSync: ' . $e->getMessage(), [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Buat data pencatatan dari rekap pengambilan
     */
    private function createDataPencatatanFromRekap(RekapPengambilan $rekap)
    {
        try {
            $customer = $rekap->customer;
            
            // Ambil waktu untuk mendapatkan pricing yang tepat
            $waktuDateTime = Carbon::parse($rekap->tanggal);
            $waktuYearMonth = $waktuDateTime->format('Y-m');
            
            // Ambil pricing info berdasarkan tanggal spesifik
            $pricingInfo = $customer->getPricingForYearMonth($waktuYearMonth, $waktuDateTime);
            
            // Hitung harga dengan pricing yang tepat untuk periode ini
            $volumeSm3 = floatval($rekap->volume);
            $hargaPerM3 = floatval($pricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
            $hargaFinal = $volumeSm3 * $hargaPerM3;

            // Format data untuk FOB
            $dataInput = [
                'waktu' => $waktuDateTime->format('Y-m-d H:i:s'),
                'volume_sm3' => $volumeSm3,
                'alamat_pengambilan' => $rekap->alamat_pengambilan,
                'keterangan' => $rekap->keterangan
            ];

            // Buat data pencatatan baru
            $dataPencatatan = new DataPencatatan();
            $dataPencatatan->customer_id = $rekap->customer_id;
            $dataPencatatan->rekap_pengambilan_id = $rekap->id; // Set relasi
            $dataPencatatan->data_input = json_encode($dataInput);
            $dataPencatatan->nama_customer = $customer->name;
            $dataPencatatan->status_pembayaran = 'belum_lunas';
            $dataPencatatan->harga_final = $hargaFinal;
            $dataPencatatan->created_at = $rekap->created_at;
            $dataPencatatan->updated_at = $rekap->updated_at;
            $dataPencatatan->save();

            return $dataPencatatan;

        } catch (\Exception $e) {
            Log::error('Error creating DataPencatatan from Rekap: ' . $e->getMessage(), [
                'rekap_id' => $rekap->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Metode untuk matching data yang lebih presisi
     */
    private function findMatchingPencatatan($rekapData, $pencatatanData, $targetDate, $targetVolume)
    {
        return $pencatatanData->filter(function($pencatatan) use ($targetDate, $targetVolume) {
            $dataInput = $this->ensureArray($pencatatan->data_input);
            
            if (empty($dataInput['waktu'])) {
                return false;
            }
            
            try {
                $pencatatanDate = Carbon::parse($dataInput['waktu'])->format('Y-m-d');
                $pencatatanVolume = floatval($dataInput['volume_sm3'] ?? 0);
                
                // Match berdasarkan tanggal dan volume dengan toleransi
                return $pencatatanDate === $targetDate && 
                       abs($pencatatanVolume - $targetVolume) < 0.01;
            } catch (\Exception $e) {
                return false;
            }
        })->first();
    }

    /**
     * Method untuk debugging - lihat data yang tidak sinkron
     */
    public function debugFobDataSync(User $customer)
    {
        if (!$customer->isFOB()) {
            return response()->json(['error' => 'Customer bukan FOB'], 400);
        }

        $rekapData = RekapPengambilan::where('customer_id', $customer->id)->get();
        $pencatatanData = DataPencatatan::where('customer_id', $customer->id)->get();

        $debug = [
            'customer_info' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'total_purchases' => $customer->total_purchases,
                'total_deposit' => $customer->total_deposit
            ],
            'rekap_data' => $rekapData->map(function($rekap) {
                return [
                    'id' => $rekap->id,
                    'tanggal' => $rekap->tanggal->format('Y-m-d H:i:s'),
                    'volume' => $rekap->volume,
                    'has_pencatatan' => $rekap->dataPencatatan ? true : false,
                    'pencatatan_id' => $rekap->dataPencatatan ? $rekap->dataPencatatan->id : null
                ];
            }),
            'pencatatan_data' => $pencatatanData->map(function($pencatatan) {
                $dataInput = $this->ensureArray($pencatatan->data_input);
                return [
                    'id' => $pencatatan->id,
                    'rekap_pengambilan_id' => $pencatatan->rekap_pengambilan_id,
                    'waktu' => $dataInput['waktu'] ?? 'N/A',
                    'volume_sm3' => $dataInput['volume_sm3'] ?? 0,
                    'harga_final' => $pencatatan->harga_final
                ];
            })
        ];

        return response()->json($debug);
    }
}
