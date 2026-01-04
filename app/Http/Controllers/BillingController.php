<?php

namespace App\Http\Controllers;

use App\Models\Billing;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
    /**
     * Generate billing number via AJAX
     */
    public function generateBillingNumber(Request $request, User $customer)
    {
        $periodType = $request->input('period_type', 'monthly');
        $customStartDate = $request->input('custom_start_date');
        
        $billingNumber = Billing::generateBillingNumber($customer, null, $periodType, $customStartDate);
        
        return response()->json([
            'billing_number' => $billingNumber
        ]);
    }

    /**
     * Display a listing of billings.
     */
    public function index()
    {
        $billings = Billing::with('customer')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return view('billings.index', compact('billings'));
    }

    /**
     * Display the list of customers to select for a new billing.
     */
    public function selectCustomer()
    {
        // Ambil customer reguler terlebih dahulu
        $regularCustomers = User::where('role', 'customer')
            ->orderBy('name')
            ->get();
            
        // Ambil customer FOB
        $fobCustomers = User::where('role', 'fob')
            ->orderBy('name')
            ->get();
            
        // Gabungkan dengan urutan customer reguler terlebih dahulu
        $customers = $regularCustomers->concat($fobCustomers);
        
        return view('billings.select-customer', compact('customers'));
    }

    /**
     * Show the form for creating a new billing for a specific customer.
     */
    public function create(User $customer)
    {
        // Generate a default billing number based on the current date (monthly period)
        $billingNumber = Billing::generateBillingNumber($customer, null, 'monthly');
        
        // Default to current month and year
        $month = now()->month;
        $year = now()->year;
        
        return view('billings.create', compact('customer', 'billingNumber', 'month', 'year'));
    }

    /**
     * Store a newly created billing in storage.
     */
    public function store(Request $request, User $customer)
    {
        // Validation rules berdasarkan period type
        $baseRules = [
            'billing_number' => 'required|string|max:50',
            'billing_date' => 'required|date',
            'period_type' => 'required|in:monthly,custom',
        ];
        
        // Add conditional validation
        if ($request->period_type === 'custom') {
            $baseRules['custom_start_date'] = 'required|date';
            $baseRules['custom_end_date'] = 'required|date|after_or_equal:custom_start_date';
        } else {
            $baseRules['month'] = 'required|integer|min:1|max:12';
            $baseRules['year'] = 'required|integer|min:2000|max:2100';
        }
        
        $request->validate($baseRules);
        
        // Additional validation for custom period
        if ($request->period_type === 'custom') {
            $startDate = Carbon::parse($request->custom_start_date);
            $endDate = Carbon::parse($request->custom_end_date);
            $diffDays = $startDate->diffInDays($endDate) + 1;
            
            if ($diffDays > 60) {
                return back()->withErrors(['custom_end_date' => 'Periode maksimal adalah 60 hari.'])->withInput();
            }
        }

        // Determine period for filtering data
        if ($request->period_type === 'custom') {
            $startDate = Carbon::parse($request->custom_start_date);
            $endDate = Carbon::parse($request->custom_end_date);
            $periodType = 'custom';
            
            // For billing number generation, use start date
            $billingDate = $startDate;
        } else {
            // Format filter untuk query monthly
            $yearMonth = $request->year . '-' . str_pad($request->month, 2, '0', STR_PAD_LEFT);
            $periodType = 'monthly';
            $billingDate = Carbon::createFromDate($request->year, $request->month, 1);
        }

        // Base query - ambil semua data
        $dataPencatatan = $customer->dataPencatatan()->get();

        // Filter data berdasarkan periode yang dipilih
        if ($periodType === 'custom') {
            // Filter berdasarkan range tanggal custom
            $dataPencatatan = $dataPencatatan->filter(function ($item) use ($startDate, $endDate) {
                $dataInput = $this->ensureArray($item->data_input);

                // Jika data input kosong atau tidak ada waktu awal, skip
                if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                    return false;
                }

                $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->startOfDay();
                $startFilter = $startDate->copy()->startOfDay();
                $endFilter = $endDate->copy()->endOfDay();

                // Filter by date range
                return $waktuAwal->between($startFilter, $endFilter);
            });
            
            // Get pricing info for start date period
            $yearMonth = $startDate->format('Y-m');
            $pricingInfo = $customer->getPricingForYearMonth($yearMonth);
        } else {
            // Filter data berdasarkan bulan dan tahun dari pembacaan awal
            // PERBAIKAN: Gunakan logika filtering yang sama dengan customer detail
            $dataPencatatan = $dataPencatatan->filter(function ($item) use ($yearMonth) {
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
            
            // Get pricing info for selected month
            $pricingInfo = $customer->getPricingForYearMonth($yearMonth);
        }

        // DEBUG: Log jumlah data yang ditemukan
        \Log::info('Billing store - Data filtering', [
            'customer_id' => $customer->id,
            'period' => $yearMonth,
            'total_records_raw' => $customer->dataPencatatan()->count(),
            'filtered_records' => $dataPencatatan->count(),
            'customer_name' => $customer->name
        ]);
        
        // DEBUG: Deteksi duplikasi pada store juga
        $tanggalDitemukan = [];
        foreach ($dataPencatatan as $item) {
            $dataInput = $this->ensureArray($item->data_input);
            if (!empty($dataInput['pembacaan_awal']['waktu'])) {
                $tanggal = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m-d');
                $volume = floatval($dataInput['volume_flow_meter'] ?? 0);
                $tanggalDitemukan[] = [
                    'id' => $item->id,
                    'tanggal' => $tanggal,
                    'volume' => $volume
                ];
            }
        }
        
        $tanggalCount = array_count_values(array_column($tanggalDitemukan, 'tanggal'));
        $duplicates = array_filter($tanggalCount, function($count) { return $count > 1; });
        
        if (!empty($duplicates)) {
            \Log::warning('Billing store - Duplicate dates detected in source data', [
                'customer_id' => $customer->id,
                'period' => $yearMonth,
                'duplicates' => $duplicates,
                'all_dates_found' => $tanggalDitemukan
            ]);
        }

        // Perhitungan untuk volume dan biaya pemakaian gas
        $totalVolume = 0;
        $totalBiaya = 0;

        foreach ($dataPencatatan as $item) {
            $dataInput = $this->ensureArray($item->data_input);
            $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
            
            // PERBAIKAN: Gunakan pricing yang sesuai periode item (bukan periode billing)
            $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
            $waktuAwalYearMonth = $waktuAwal->format('Y-m');
            $itemPricingInfo = $customer->getPricingForYearMonth($waktuAwalYearMonth, $waktuAwal);
            
            $volumeSm3 = $volumeFlowMeter * floatval($itemPricingInfo['koreksi_meter'] ?? $customer->koreksi_meter);
            $hargaGas = floatval($itemPricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
            $biayaPemakaian = $volumeSm3 * $hargaGas;

            // TETAP HITUNG SEMUA DATA untuk total (termasuk volume 0)
            // karena data volume 0 tetap valid untuk perhitungan saldo
            $totalVolume += $volumeSm3;
            $totalBiaya += $biayaPemakaian;
            
            // DEBUG: Log setiap item yang diproses
            \Log::debug('Billing store - Processing item', [
                'item_id' => $item->id,
                'waktu_awal' => $waktuAwal->format('Y-m-d H:i:s'),
                'volume_flow_meter' => $volumeFlowMeter,
                'volume_sm3' => $volumeSm3,
                'harga_gas' => $hargaGas,
                'biaya_pemakaian' => $biayaPemakaian,
                'included_in_calculation' => true
            ]);
        }

        // Perhitungan untuk penerimaan deposit (hanya untuk periode monthly)
        $totalDeposit = 0;
        $prevMonthBalance = 0;
        $currentMonthBalance = 0;
        $amountToPay = 0;

        if ($periodType === 'monthly') {
            // Perhitungan deposit dan saldo hanya untuk periode bulanan
            $depositHistory = $this->ensureArray($customer->deposit_history);
            foreach ($depositHistory as $deposit) {
                if (isset($deposit['date'])) {
                    $depositDate = Carbon::parse($deposit['date']);
                    // PERBAIKAN: Gunakan format yang konsisten dengan customer detail
                    if ($depositDate->format('Y-m') === $yearMonth) {
                        $totalDeposit += floatval($deposit['amount'] ?? 0);
                    }
                }
            }
            
            // DEBUG: Log total deposit yang ditemukan
            \Log::info('Billing store - Deposit calculation', [
                'customer_id' => $customer->id,
                'period' => $yearMonth,
                'total_deposit' => $totalDeposit,
                'deposit_entries' => count($depositHistory)
            ]);

            // PERBAIKAN: Gunakan sistem monthly_balances yang sama dengan customer detail
            // Menghitung saldo bulan sebelumnya
            $prevDate = Carbon::createFromDate($request->year, $request->month, 1)->subMonth();
            $prevMonthYear = $prevDate->format('Y-m');
            $currentYearMonth = $yearMonth;
            
            // Pastikan monthly balances sudah terupdate
            $customer->updateMonthlyBalances($prevMonthYear);
            
            // Reload customer untuk mendapatkan data terbaru
            $customer = User::findOrFail($customer->id);
            
            // Ambil saldo bulanan dari sistem yang sama dengan customer detail
            $monthlyBalances = $customer->monthly_balances ?: [];
            
            // Ambil saldo bulan sebelumnya dari monthly_balances
            $prevMonthBalance = isset($monthlyBalances[$prevMonthYear]) ?
                floatval($monthlyBalances[$prevMonthYear]) : 0;
            
            // DEBUG: Log saldo calculations dengan metode baru
            \Log::info('Billing store - Balance calculation (monthly_balances method)', [
                'customer_id' => $customer->id,
                'prev_month_year' => $prevMonthYear,
                'current_year_month' => $currentYearMonth,
                'prev_month_balance_from_monthly_balances' => $prevMonthBalance,
                'current_month_deposit' => $totalDeposit,
                'current_month_purchase' => $totalBiaya,
                'monthly_balances_available' => !empty($monthlyBalances),
                'monthly_balances_count' => count($monthlyBalances)
            ]);
            
            // Menghitung saldo bulan ini
            $currentMonthBalance = $prevMonthBalance + $totalDeposit - $totalBiaya;
            
            // Menghitung biaya yang harus dibayar (jika saldo negatif)
            $amountToPay = $currentMonthBalance < 0 ? abs($currentMonthBalance) : 0;
        } else {
            // Untuk periode custom, amount to pay = total biaya pemakaian
            $amountToPay = $totalBiaya;
        }

        // Buat billing dan invoice secara bersamaan dalam database transaction
        DB::beginTransaction();
        
        try {
            // 1. Buat billing terlebih dahulu
            $billing = new Billing();
            $billing->customer_id = $customer->id;
            $billing->billing_number = $request->billing_number;
            $billing->billing_date = $request->billing_date;
            $billing->total_volume = $totalVolume;
            $billing->total_amount = $totalBiaya;
            $billing->total_deposit = $totalDeposit;
            $billing->previous_balance = $prevMonthBalance;
            $billing->current_balance = $currentMonthBalance;
            $billing->amount_to_pay = $amountToPay;
            $billing->period_type = $request->period_type;
            
            if ($request->period_type === 'custom') {
                $billing->custom_start_date = $request->custom_start_date;
                $billing->custom_end_date = $request->custom_end_date;
                $billing->period_month = $startDate->month;
                $billing->period_year = $startDate->year;
            } else {
                $billing->period_month = $request->month;
                $billing->period_year = $request->year;
                $billing->custom_start_date = null;
                $billing->custom_end_date = null;
            }
            
            $billing->save();
            
            // 2. Auto-create invoice dengan nomor yang sama
            $invoice = new Invoice();
            $invoice->customer_id = $customer->id;
            $invoice->billing_id = $billing->id;
            $invoice->invoice_number = Invoice::generateSyncedNumber($customer, $billing->billing_number);
            $invoice->invoice_date = $billing->billing_date;
            $invoice->due_date = Carbon::parse($billing->billing_date)->addDays(10); // Default 10 hari
            $invoice->total_amount = $totalBiaya;
            $invoice->total_volume = $totalVolume;
            $invoice->status = 'unpaid';
            $invoice->description = 'Pemakaian CNG';
            $invoice->no_kontrak = $customer->no_kontrak ?: ('AUTO-' . date('Y')); // Gunakan dari customer atau default
            $invoice->id_pelanggan = sprintf('03C%04d', $customer->id);
            $invoice->period_type = $request->period_type;
            
            if ($request->period_type === 'custom') {
                $invoice->custom_start_date = $request->custom_start_date;
                $invoice->custom_end_date = $request->custom_end_date;
                $invoice->period_month = $startDate->month;
                $invoice->period_year = $startDate->year;
            } else {
                $invoice->period_month = $request->month;
                $invoice->period_year = $request->year;
                $invoice->custom_start_date = null;
                $invoice->custom_end_date = null;
            }
            
            $invoice->save();
            
            // 3. Update billing dengan invoice_id
            $billing->invoice_id = $invoice->id;
            $billing->save();
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error creating billing and invoice sync: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal membuat billing dan invoice: ' . $e->getMessage()])->withInput();
        }
        
        // FINAL DEBUG: Log billing results
        \Log::info('Billing created successfully', [
            'billing_id' => $billing->id,
            'customer_id' => $customer->id,
            'period' => $yearMonth,
            'total_volume' => $totalVolume,
            'total_amount' => $totalBiaya,
            'total_deposit' => $totalDeposit,
            'filtered_records_count' => $dataPencatatan->count()
        ]);

        return redirect()->route('billings.show', $billing)
            ->with('success', 'Billing dan Invoice berhasil dibuat secara bersamaan.');
    }

    /**
     * Display the specified billing.
     */
    public function show(Billing $billing)
    {
        // Authorization: Customer hanya bisa melihat billing milik mereka sendiri
        if ((auth()->user()->isCustomer() || auth()->user()->isFOB()) && $billing->customer_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses ke billing ini.');
        }
        
        $customer = $billing->customer;
        
        // Determine period based on billing type
        if ($billing->period_type === 'custom') {
            $startDate = Carbon::parse($billing->custom_start_date);
            $endDate = Carbon::parse($billing->custom_end_date);
            $yearMonth = $startDate->format('Y-m'); // For pricing info
        } else {
            $yearMonth = $billing->period_year . '-' . str_pad($billing->period_month, 2, '0', STR_PAD_LEFT);
        }
        
        // Get pricing info
        $pricingInfo = $customer->getPricingForYearMonth($yearMonth);
        
        // Retrieve and filter the relevant data - SAMA DENGAN STORE METHOD
        $dataPencatatan = $customer->dataPencatatan()->get();
        
        // Filter data berdasarkan periode billing
        if ($billing->period_type === 'custom') {
            // Filter berdasarkan range tanggal custom
            $dataPencatatan = $dataPencatatan->filter(function ($item) use ($startDate, $endDate) {
                $dataInput = $this->ensureArray($item->data_input);

                // Jika data input kosong atau tidak ada waktu awal, skip
                if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                    return false;
                }

                $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->startOfDay();
                $startFilter = $startDate->copy()->startOfDay();
                $endFilter = $endDate->copy()->endOfDay();

                // Filter by date range
                return $waktuAwal->between($startFilter, $endFilter);
            });
        } else {
            // Filter data berdasarkan bulan dan tahun dari pembacaan awal
            $dataPencatatan = $dataPencatatan->filter(function ($item) use ($yearMonth) {
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
        }
        
        // DEBUG: Log untuk verifikasi data show
        \Log::info('Billing show - Data filtering', [
            'billing_id' => $billing->id,
            'customer_id' => $customer->id,
            'period' => $yearMonth,
            'total_records_raw' => $customer->dataPencatatan()->count(),
            'filtered_records' => $dataPencatatan->count()
        ]);
        
        // DEBUG: Log semua tanggal yang ditemukan untuk deteksi duplikasi
        $tanggalDitemukan = [];
        foreach ($dataPencatatan as $item) {
            $dataInput = $this->ensureArray($item->data_input);
            if (!empty($dataInput['pembacaan_awal']['waktu'])) {
                $tanggal = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m-d');
                $volume = floatval($dataInput['volume_flow_meter'] ?? 0);
                $tanggalDitemukan[] = [
                    'id' => $item->id,
                    'tanggal' => $tanggal,
                    'volume' => $volume,
                    'waktu_lengkap' => $dataInput['pembacaan_awal']['waktu']
                ];
            }
        }
        
        // Detect duplicates
        $tanggalCount = array_count_values(array_column($tanggalDitemukan, 'tanggal'));
        $duplicates = array_filter($tanggalCount, function($count) { return $count > 1; });
        
        if (!empty($duplicates)) {
            \Log::warning('Billing show - Duplicate dates detected', [
                'billing_id' => $billing->id,
                'duplicates' => $duplicates,
                'all_dates_found' => $tanggalDitemukan
            ]);
        }

        // Perhitungan untuk volume dan biaya pemakaian gas
        // PERBAIKAN: Menampilkan semua data tapi hilangkan duplikasi
        $pemakaianGas = [];
        $processedDates = []; // Track tanggal yang sudah diproses untuk menghindari duplikasi
        $i = 1;
        
        foreach ($dataPencatatan as $item) {
            $dataInput = $this->ensureArray($item->data_input);
            $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
            
            $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
            $tanggalKey = $waktuAwal->format('Y-m-d'); // Key untuk deteksi duplikasi
            
            // PERBAIKAN: Skip jika tanggal sudah diproses (hindari duplikasi)
            if (isset($processedDates[$tanggalKey])) {
                \Log::debug('Billing show - Skipping duplicate date', [
                    'billing_id' => $billing->id,
                    'item_id' => $item->id,
                    'duplicate_date' => $tanggalKey,
                    'volume_flow_meter' => $volumeFlowMeter,
                    'already_processed_id' => $processedDates[$tanggalKey]['id'],
                    'already_processed_volume' => $processedDates[$tanggalKey]['volume']
                ]);
                continue;
            }
            
            // Tandai tanggal ini sebagai sudah diproses
            $processedDates[$tanggalKey] = [
                'id' => $item->id,
                'volume' => $volumeFlowMeter
            ];
            
            // PERBAIKAN: Gunakan pricing yang sesuai periode item (bukan periode billing)
            $waktuAwalYearMonth = $waktuAwal->format('Y-m');
            $itemPricingInfo = $customer->getPricingForYearMonth($waktuAwalYearMonth, $waktuAwal);
            
            $volumeSm3 = $volumeFlowMeter * floatval($itemPricingInfo['koreksi_meter'] ?? $customer->koreksi_meter);
            $hargaGas = floatval($itemPricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
            $biayaPemakaian = $volumeSm3 * $hargaGas;

            $periodePemakaian = isset($dataInput['pembacaan_awal']['waktu']) ? 
                            Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('d/m/Y') : '';
            
            $tanggalPemakaian = isset($dataInput['pembacaan_awal']['waktu']) ?
                            Carbon::parse($dataInput['pembacaan_awal']['waktu']) : null;

            $pemakaianGas[] = [
                'no' => $i++,
                'periode_pemakaian' => $periodePemakaian,
                'tanggal_pemakaian' => $tanggalPemakaian,
                'volume_sm3' => $volumeSm3,
                'harga_gas' => $hargaGas,
                'biaya_pemakaian' => $biayaPemakaian
            ];
            
            // DEBUG: Log setiap item yang diproses di show
            \Log::debug('Billing show - Processing unique item', [
                'billing_id' => $billing->id,
                'item_id' => $item->id,
                'tanggal_key' => $tanggalKey,
                'waktu_awal' => $waktuAwal->format('Y-m-d H:i:s'),
                'volume_flow_meter' => $volumeFlowMeter,
                'volume_sm3' => $volumeSm3,
                'harga_gas' => $hargaGas,
                'biaya_pemakaian' => $biayaPemakaian
            ]);
        }
        
        // Urutkan data berdasarkan tanggal periode pemakaian
        usort($pemakaianGas, function($a, $b) {
            if (!$a['tanggal_pemakaian'] || !$b['tanggal_pemakaian']) {
                return 0;
            }
            return $a['tanggal_pemakaian']->timestamp - $b['tanggal_pemakaian']->timestamp;
        });
        
        // Reset nomor urut setelah pengurutan
        $i = 1;
        foreach ($pemakaianGas as &$item) {
            $item['no'] = $i++;
            unset($item['tanggal_pemakaian']); // Hapus field yang hanya untuk pengurutan
        }

        // Perhitungan untuk penerimaan deposit (hanya untuk periode monthly)
        // PERBAIKAN: Gunakan format yang konsisten dengan store method
        $penerimaanDeposit = [];
        $j = 1;
        
        if ($billing->period_type === 'monthly') {
            // Hanya tampilkan deposit untuk periode bulanan
            $depositHistory = $this->ensureArray($customer->deposit_history);
            foreach ($depositHistory as $deposit) {
                if (isset($deposit['date'])) {
                    $depositDate = Carbon::parse($deposit['date']);
                    // PERBAIKAN: Gunakan format yang konsisten dengan customer detail
                    if ($depositDate->format('Y-m') === $yearMonth) {
                        $jumlahDeposit = floatval($deposit['amount'] ?? 0);
                        $penerimaanDeposit[] = [
                            'no' => $j++,
                            'tanggal_deposit' => $depositDate->format('d/m/Y'),
                            'tanggal_untuk_urutan' => $depositDate,
                            'jumlah_penerimaan' => $jumlahDeposit
                        ];
                    }
                }
            }
            
            // Urutkan data penerimaan deposit berdasarkan tanggal
            usort($penerimaanDeposit, function($a, $b) {
                if (!isset($a['tanggal_untuk_urutan']) || !isset($b['tanggal_untuk_urutan'])) {
                    return 0;
                }
                return $a['tanggal_untuk_urutan']->timestamp - $b['tanggal_untuk_urutan']->timestamp;
            });
            
            // Reset nomor urut setelah pengurutan
            $j = 1;
            foreach ($penerimaanDeposit as &$item) {
                $item['no'] = $j++;
                unset($item['tanggal_untuk_urutan']); // Hapus field yang hanya untuk pengurutan
            }
        }

        // Setup data untuk view Billing
        $data = [
            'billing' => $billing,
            'customer' => $customer,
            'periode_bulan' => $billing->period_type === 'custom' ? 
                Carbon::parse($billing->custom_start_date)->format('d/m/Y') . ' - ' . Carbon::parse($billing->custom_end_date)->format('d/m/Y') :
                Carbon::createFromDate($billing->period_year, $billing->period_month, 1)->format('F Y'),
            'pemakaian_gas' => $pemakaianGas,
            'penerimaan_deposit' => $penerimaanDeposit,
        ];
        
        // DEBUG: Log final show data
        \Log::info('Billing show - Final data', [
            'billing_id' => $billing->id,
            'customer_id' => $customer->id,
            'period' => $yearMonth,
            'total_records_filtered' => $dataPencatatan->count(),
            'pemakaian_gas_count_displayed' => count($pemakaianGas), // Setelah deduplication
            'penerimaan_deposit_count' => count($penerimaanDeposit),
            'billing_total_volume' => $billing->total_volume,
            'billing_total_amount' => $billing->total_amount,
            'duplicates_removed' => $dataPencatatan->count() - count($pemakaianGas),
            'unique_dates_count' => count($pemakaianGas)
        ]);

        return view('billings.show', $data);
    }

    /**
     * Show the form for editing the billing.
     */
    public function edit(Billing $billing)
    {
        $customer = $billing->customer;
        return view('billings.edit', compact('billing', 'customer'));
    }

    /**
     * Update the specified billing in storage.
     */
    public function update(Request $request, Billing $billing)
    {
        $request->validate([
            'billing_number' => 'required|string|max:50',
            'billing_date' => 'required|date',
        ]);

        DB::beginTransaction();
        
        try {
            // Update billing
            $billing->billing_number = $request->billing_number;
            $billing->billing_date = $request->billing_date;
            $billing->save();
            
            // Sync update ke invoice jika ada
            if ($billing->invoice) {
                $billing->invoice->invoice_number = Invoice::generateSyncedNumber($billing->customer, $billing->billing_number);
                $billing->invoice->invoice_date = $billing->billing_date;
                $billing->invoice->save();
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error updating billing and invoice sync: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal memperbarui billing dan invoice: ' . $e->getMessage()])->withInput();
        }

        return redirect()->route('billings.show', $billing)
            ->with('success', 'Billing dan Invoice berhasil diperbarui.');
    }

    /**
     * Remove the specified billing from storage.
     */
    public function destroy(Billing $billing)
    {
        DB::beginTransaction();
        
        try {
            // Hapus invoice terkait jika ada
            if ($billing->invoice) {
                $billing->invoice->delete();
            }
            
            // Hapus billing
            $billing->delete();
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error deleting billing and invoice sync: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal menghapus billing dan invoice: ' . $e->getMessage()]);
        }
        
        return redirect()->route('billings.index')
            ->with('success', 'Billing dan Invoice berhasil dihapus.');
    }

    /**
     * Display customer's own billings (Customer view only).
     */
    public function customerBillings(Request $request)
    {
        // Pastikan user adalah customer yang sedang login
        $customer = auth()->user();
        
        if (!$customer->isCustomer() && !$customer->isFOB()) {
            abort(403, 'Akses tidak diizinkan.');
        }
        
        // Query billing milik customer yang sedang login
        $query = Billing::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc');
        
        // Filter berdasarkan search jika ada
        if ($request->has('search') && !empty($request->search)) {
            $query->where('billing_number', 'like', '%' . $request->search . '%');
        }
        
        // Filter berdasarkan periode jika ada
        if ($request->has('period') && !empty($request->period)) {
            $periodParts = explode('-', $request->period);
            if (count($periodParts) == 2) {
                $year = $periodParts[0];
                $month = $periodParts[1];
                $query->where('period_year', $year)
                      ->where('period_month', $month);
            }
        }
        
        $billings = $query->paginate(10);
        
        // Menyimpan parameter filter dalam pagination links
        $billings->appends($request->only(['search', 'period']));
        
        // Deteksi jika request adalah AJAX untuk pencarian real-time
        if ($request->ajax()) {
            return response()->json([
                'html' => view('customer.billings.partials.billing-table', compact('billings'))->render(),
                'pagination' => view('customer.billings.partials.pagination', compact('billings'))->render(),
            ]);
        }
        
        return view('customer.billings.index', compact('billings', 'customer'));
    }

    /**
     * Ensure array format for data.
     */
    private function ensureArray($data)
    {
        if (is_string($data) && !empty($data)) {
            return json_decode($data, true) ?? [];
        }
        
        return is_array($data) ? $data : [];
    }
}
