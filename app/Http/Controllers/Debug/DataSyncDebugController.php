<?php

namespace App\Http\Controllers\Debug;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DataSyncDebugController extends Controller
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
     * Compare data between customer detail and billing logic
     */
    public function compareCustomerBillingData(Request $request)
    {
        $customerId = $request->input('customer_id');
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));

        if (!$customerId) {
            return response()->json(['error' => 'Customer ID is required'], 400);
        }

        $customer = User::findOrFail($customerId);
        $yearMonth = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);

        // Get all raw data from database
        $allDataRaw = $customer->dataPencatatan()->get();
        
        // Apply the same filtering logic as both controllers
        $filteredData = $allDataRaw->filter(function ($item) use ($yearMonth) {
            $dataInput = $this->ensureArray($item->data_input);

            // Jika data input kosong atau tidak ada waktu awal, skip
            if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                return false;
            }

            // Convert the timestamp to year-month format for comparison
            $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m');

            // Filter by year-month
            return $waktuAwal === $yearMonth;
        });

        // Detailed analysis
        $analysis = [
            'customer_info' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'role' => $customer->role
            ],
            'period' => [
                'month' => $month,
                'year' => $year,
                'yearMonth' => $yearMonth
            ],
            'data_counts' => [
                'total_raw_records' => $allDataRaw->count(),
                'filtered_records' => $filteredData->count()
            ],
            'detailed_records' => [],
            'summary' => [
                'total_volume_flow_meter' => 0,
                'total_volume_sm3' => 0,
                'total_biaya' => 0
            ]
        ];

        // Process each filtered record
        $recordsForDisplay = []; // Records yang akan ditampilkan di tabel (volume > 0)
        $recordsForCalculation = []; // Semua records untuk perhitungan total
        
        foreach ($filteredData as $item) {
            $dataInput = $this->ensureArray($item->data_input);
            
            if (empty($dataInput['pembacaan_awal']['waktu'])) {
                continue;
            }

            $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
            $waktuAwalYearMonth = $waktuAwal->format('Y-m');
            
            // Get pricing info for this specific item
            $itemPricingInfo = $customer->getPricingForYearMonth($waktuAwalYearMonth, $waktuAwal);
            
            $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
            $koreksiMeter = floatval($itemPricingInfo['koreksi_meter'] ?? $customer->koreksi_meter);
            $hargaPerM3 = floatval($itemPricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
            $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
            $biayaPemakaian = $volumeSm3 * $hargaPerM3;

            $recordDetail = [
                'id' => $item->id,
                'waktu_awal' => $waktuAwal->format('Y-m-d H:i:s'),
                'waktu_awal_date_only' => $waktuAwal->format('Y-m-d'),
                'year_month' => $waktuAwalYearMonth,
                'volume_flow_meter' => $volumeFlowMeter,
                'koreksiMeter' => $koreksiMeter,
                'harga_per_m3' => $hargaPerM3,
                'volume_sm3' => $volumeSm3,
                'biaya_pemakaian' => $biayaPemakaian,
                'pembacaan_awal_volume' => floatval($dataInput['pembacaan_awal']['volume'] ?? 0),
                'pembacaan_akhir_volume' => floatval($dataInput['pembacaan_akhir']['volume'] ?? 0),
                'will_be_displayed_in_table' => $volumeFlowMeter > 0,
                'raw_data_input' => $dataInput
            ];

            // Semua record masuk ke perhitungan total
            $recordsForCalculation[] = $recordDetail;
            $analysis['summary']['total_volume_flow_meter'] += $volumeFlowMeter;
            $analysis['summary']['total_volume_sm3'] += $volumeSm3;
            $analysis['summary']['total_biaya'] += $biayaPemakaian;
            
            // Hanya record dengan volume > 0 yang akan ditampilkan di tabel billing
            if ($volumeFlowMeter > 0) {
                $recordsForDisplay[] = $recordDetail;
            }
        }
        
        $analysis['detailed_records'] = $recordsForCalculation;
        $analysis['records_for_display'] = $recordsForDisplay;
        $analysis['data_counts']['records_for_calculation'] = count($recordsForCalculation);
        $analysis['data_counts']['records_for_display'] = count($recordsForDisplay);
        $analysis['data_counts']['zero_volume_records'] = count($recordsForCalculation) - count($recordsForDisplay);

        // Check deposits for the period
        $depositHistory = $this->ensureArray($customer->deposit_history);
        $depositData = [];
        $totalDeposit = 0;

        foreach ($depositHistory as $deposit) {
            if (isset($deposit['date'])) {
                $depositDate = Carbon::parse($deposit['date']);
                if ($depositDate->format('Y-m') === $yearMonth) {
                    $depositAmount = floatval($deposit['amount'] ?? 0);
                    $depositData[] = [
                        'date' => $depositDate->format('Y-m-d H:i:s'),
                        'amount' => $depositAmount,
                        'description' => $deposit['description'] ?? ''
                    ];
                    $totalDeposit += $depositAmount;
                }
            }
        }

        $analysis['deposits'] = [
            'deposit_records' => $depositData,
            'total_deposit' => $totalDeposit
        ];

        // Log this analysis
        Log::info('Data Sync Debug Analysis', $analysis);

        return response()->json($analysis);
    }

    /**
     * Identify and analyze duplicate date records
     */
    public function findDuplicateDates(Request $request)
    {
        $customerId = $request->input('customer_id');
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));

        if (!$customerId) {
            return response()->json(['error' => 'Customer ID is required'], 400);
        }

        $customer = User::findOrFail($customerId);
        $yearMonth = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);

        // Get all data for the period
        $allData = $customer->dataPencatatan()->get();
        
        // Filter by period
        $filteredData = $allData->filter(function ($item) use ($yearMonth) {
            $dataInput = $this->ensureArray($item->data_input);
            if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                return false;
            }
            $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m');
            return $waktuAwal === $yearMonth;
        });

        // Analyze duplicates
        $dateRecords = [];
        foreach ($filteredData as $item) {
            $dataInput = $this->ensureArray($item->data_input);
            if (!empty($dataInput['pembacaan_awal']['waktu'])) {
                $tanggal = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m-d');
                $volume = floatval($dataInput['volume_flow_meter'] ?? 0);
                
                if (!isset($dateRecords[$tanggal])) {
                    $dateRecords[$tanggal] = [];
                }
                
                $dateRecords[$tanggal][] = [
                    'id' => $item->id,
                    'volume' => $volume,
                    'waktu_lengkap' => $dataInput['pembacaan_awal']['waktu'],
                    'pembacaan_awal' => $dataInput['pembacaan_awal'] ?? [],
                    'pembacaan_akhir' => $dataInput['pembacaan_akhir'] ?? [],
                    'created_at' => $item->created_at->format('Y-m-d H:i:s')
                ];
            }
        }

        // Find duplicates
        $duplicates = [];
        $uniqueDates = [];
        
        foreach ($dateRecords as $date => $records) {
            if (count($records) > 1) {
                $duplicates[$date] = [
                    'count' => count($records),
                    'records' => $records,
                    'recommendation' => $this->getDuplicateRecommendation($records)
                ];
            } else {
                $uniqueDates[$date] = $records[0];
            }
        }

        $result = [
            'customer_id' => $customerId,
            'customer_name' => $customer->name,
            'period' => $yearMonth,
            'total_records' => $filteredData->count(),
            'unique_dates' => count($uniqueDates),
            'duplicate_dates' => count($duplicates),
            'duplicates_detail' => $duplicates,
            'unique_dates_detail' => $uniqueDates
        ];

        Log::info('Duplicate Dates Analysis', $result);

        return response()->json($result);
    }

    /**
     * Compare balance calculation between billing and customer detail
     */
    public function compareBalanceCalculation(Request $request)
    {
        $customerId = $request->input('customer_id');
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));

        if (!$customerId) {
            return response()->json(['error' => 'Customer ID is required'], 400);
        }

        $customer = User::findOrFail($customerId);
        $yearMonth = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
        
        // Update monthly balances to ensure they're current
        $prevDate = \Carbon\Carbon::createFromDate($year, $month, 1)->subMonth();
        $prevMonthYear = $prevDate->format('Y-m');
        $customer->updateMonthlyBalances($prevMonthYear);
        
        // Reload customer
        $customer = User::findOrFail($customerId);
        
        $analysis = [
            'customer_info' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'total_deposit' => $customer->total_deposit,
                'total_purchases' => $customer->total_purchases
            ],
            'period_info' => [
                'current_period' => $yearMonth,
                'prev_period' => $prevMonthYear
            ],
            'monthly_balances_system' => [
                'monthly_balances_data' => $customer->monthly_balances ?: [],
                'prev_month_balance' => null,
                'current_month_balance' => null
            ],
            'manual_calculation' => [
                'prev_deposits' => 0,
                'prev_purchases' => 0,
                'prev_balance_manual' => 0
            ],
            'discrepancies' => []
        ];
        
        // Get monthly balances (customer detail method)
        $monthlyBalances = $customer->monthly_balances ?: [];
        $prevMonthBalanceFromDB = isset($monthlyBalances[$prevMonthYear]) ?
            floatval($monthlyBalances[$prevMonthYear]) : 0;
        $currentMonthBalanceFromDB = isset($monthlyBalances[$yearMonth]) ?
            floatval($monthlyBalances[$yearMonth]) : null;
            
        $analysis['monthly_balances_system']['prev_month_balance'] = $prevMonthBalanceFromDB;
        $analysis['monthly_balances_system']['current_month_balance'] = $currentMonthBalanceFromDB;
        
        // Manual calculation (old billing method)
        $depositHistory = $this->ensureArray($customer->deposit_history);
        $prevTotalDeposits = 0;
        $prevTotalPurchases = 0;
        
        // Calculate previous deposits manually
        foreach ($depositHistory as $deposit) {
            if (isset($deposit['date'])) {
                $depositDate = \Carbon\Carbon::parse($deposit['date']);
                if ($depositDate < \Carbon\Carbon::createFromDate($year, $month, 1)) {
                    $prevTotalDeposits += floatval($deposit['amount'] ?? 0);
                }
            }
        }
        
        // Calculate previous purchases manually
        $allData = $customer->dataPencatatan()->get();
        foreach ($allData as $item) {
            $dataInput = $this->ensureArray($item->data_input);
            
            if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                continue;
            }
            
            $itemDate = \Carbon\Carbon::parse($dataInput['pembacaan_awal']['waktu']);
            if ($itemDate < \Carbon\Carbon::createFromDate($year, $month, 1)) {
                $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
                $itemYearMonth = $itemDate->format('Y-m');
                $itemPricingInfo = $customer->getPricingForYearMonth($itemYearMonth, $itemDate);
                $volumeSm3 = $volumeFlowMeter * floatval($itemPricingInfo['koreksi_meter'] ?? $customer->koreksi_meter);
                $prevTotalPurchases += $volumeSm3 * floatval($itemPricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
            }
        }
        
        $prevBalanceManual = $prevTotalDeposits - $prevTotalPurchases;
        
        $analysis['manual_calculation'] = [
            'prev_deposits' => $prevTotalDeposits,
            'prev_purchases' => $prevTotalPurchases,
            'prev_balance_manual' => $prevBalanceManual
        ];
        
        // Check for discrepancies
        $balanceDifference = abs($prevMonthBalanceFromDB - $prevBalanceManual);
        if ($balanceDifference > 0.01) { // Allow for small rounding differences
            $analysis['discrepancies'][] = [
                'type' => 'previous_balance_mismatch',
                'monthly_balances_value' => $prevMonthBalanceFromDB,
                'manual_calculation_value' => $prevBalanceManual,
                'difference' => $balanceDifference,
                'severity' => $balanceDifference > 1000 ? 'high' : ($balanceDifference > 100 ? 'medium' : 'low')
            ];
        }
        
        // Additional analysis: check if monthly_balances is missing data
        if (empty($monthlyBalances)) {
            $analysis['discrepancies'][] = [
                'type' => 'missing_monthly_balances',
                'message' => 'monthly_balances field is empty - this may cause calculation issues'
            ];
        }
        
        if (!isset($monthlyBalances[$prevMonthYear])) {
            $analysis['discrepancies'][] = [
                'type' => 'missing_prev_month_balance',
                'message' => "Balance for {$prevMonthYear} not found in monthly_balances"
            ];
        }
        
        Log::info('Balance Calculation Comparison', $analysis);
        
        return response()->json($analysis);
    }
    private function getDuplicateRecommendation($records)
    {
        // Sort by creation time (keep the first created)
        usort($records, function($a, $b) {
            return strtotime($a['created_at']) - strtotime($b['created_at']);
        });
        
        $hasNonZeroVolume = false;
        $nonZeroRecord = null;
        
        foreach ($records as $record) {
            if ($record['volume'] > 0) {
                $hasNonZeroVolume = true;
                $nonZeroRecord = $record;
                break;
            }
        }
        
        if ($hasNonZeroVolume) {
            return [
                'action' => 'keep_non_zero_volume',
                'keep_record_id' => $nonZeroRecord['id'],
                'reason' => 'Keep record with non-zero volume, delete others'
            ];
        } else {
            return [
                'action' => 'keep_first_created',
                'keep_record_id' => $records[0]['id'],
                'reason' => 'All volumes are zero, keep the first created record'
            ];
        }
    }
    public function quickFixDataSync(Request $request)
    {
        $customerId = $request->input('customer_id');
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
        $dryRun = $request->input('dry_run', true); // Default to dry run

        if (!$customerId) {
            return response()->json(['error' => 'Customer ID is required'], 400);
        }

        $customer = User::findOrFail($customerId);
        $yearMonth = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);

        $fixes = [];
        $errors = [];

        try {
            // Fix 1: Update monthly balances
            if (!$dryRun) {
                $customer->updateMonthlyBalances($yearMonth);
                $fixes[] = 'Updated monthly balances starting from ' . $yearMonth;
            } else {
                $fixes[] = '[DRY RUN] Would update monthly balances starting from ' . $yearMonth;
            }

            // Fix 2: Recalculate total purchases
            if (!$dryRun) {
                $oldTotal = $customer->total_purchases;
                $newTotal = app(\App\Http\Controllers\UserController::class)->rekalkulasiTotalPembelian($customer);
                $fixes[] = 'Recalculated total purchases: ' . $oldTotal . ' -> ' . $newTotal;
            } else {
                $fixes[] = '[DRY RUN] Would recalculate total purchases';
            }

        } catch (\Exception $e) {
            $errors[] = 'Critical error during fix process: ' . $e->getMessage();
        }

        $result = [
            'customer_id' => $customerId,
            'period' => $yearMonth,
            'dry_run' => $dryRun,
            'fixes_applied' => $fixes,
            'errors_encountered' => $errors
        ];

        Log::info('Quick Fix Data Sync', $result);

        return response()->json($result);
    }
}
