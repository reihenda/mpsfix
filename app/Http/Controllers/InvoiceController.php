<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Billing;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    /**
     * Generate invoice number via AJAX
     */
    public function generateInvoiceNumber(Request $request, User $customer)
    {
        $periodType = $request->input('period_type', 'monthly');
        $customStartDate = $request->input('custom_start_date');
        
        $invoiceNumber = Invoice::generateInvoiceNumber($customer, null, $periodType, $customStartDate);
        
        return response()->json([
            'invoice_number' => $invoiceNumber
        ]);
    }
    /**
     * Display a listing of customers (admin invoice menu).
     */
    public function index(Request $request)
    {
        $query = User::whereIn('role', ['customer', 'fob', 'mmbtu'])
            ->withCount('invoices')
            ->orderBy('name');

        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $customers = $query->paginate(15);
        $customers->appends($request->only('search'));

        if ($request->ajax()) {
            return response()->json([
                'html' => view('invoices.partials.customer-table', compact('customers'))->render(),
                'pagination' => view('invoices.partials.pagination-customers', compact('customers'))->render(),
            ]);
        }

        return view('invoices.index', compact('customers'));
    }

    /**
     * Display invoice list for a specific customer (admin view).
     */
    public function customerInvoiceList(Request $request, User $customer)
    {
        $query = Invoice::where('customer_id', $customer->id)
            ->orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->orderByRaw("CASE WHEN period_type = 'monthly' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc');

        if ($request->has('search') && !empty($request->search)) {
            $query->where('invoice_number', 'like', '%' . $request->search . '%');
        }

        $invoices = $query->paginate(15);
        $invoices->appends($request->only('search'));

        // Samakan sumber total dengan halaman detail invoice (show()): hitung ulang
        // live dari data pencatatan + pricing saat ini, bukan pakai total_amount beku dari DB.
        $dataPencatatanAll = $customer->dataPencatatan()->get();
        foreach ($invoices as $invoice) {
            $invoice->total_amount = $this->calculateInvoiceTotalBiaya($invoice, $customer, $dataPencatatanAll);
        }

        if ($request->ajax()) {
            return response()->json([
                'html' => view('invoices.partials.invoice-table', compact('invoices', 'customer'))->render(),
                'pagination' => view('invoices.partials.pagination', compact('invoices', 'customer'))->render(),
            ]);
        }

        return view('invoices.customer-invoices', compact('invoices', 'customer'));
    }

    /**
     * Hitung ulang total biaya invoice secara live dari data pencatatan + pricing saat ini.
     * Logika ini mengikuti perhitungan di show(), supaya daftar invoice (customerInvoiceList)
     * menampilkan total yang sama dengan halaman detail invoice, bukan total_amount beku dari DB.
     */
    private function calculateInvoiceTotalBiaya(Invoice $invoice, User $customer, $dataPencatatanAll = null): float
    {
        $dataPencatatanAll = $dataPencatatanAll ?? $customer->dataPencatatan()->get();
        $isMmbtu = $customer->isMmbtu();

        if ($invoice->period_type === 'custom') {
            $startDate = Carbon::parse($invoice->custom_start_date);
            $endDate = Carbon::parse($invoice->custom_end_date);

            $dataPencatatan = $dataPencatatanAll->filter(function ($item) use ($startDate, $endDate) {
                $dataInput = $this->ensureArray($item->data_input);
                if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                    return false;
                }
                $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->startOfDay();
                return $waktuAwal->between($startDate->copy()->startOfDay(), $endDate->copy()->endOfDay());
            });
        } else {
            $yearMonth = $invoice->period_year . '-' . str_pad($invoice->period_month, 2, '0', STR_PAD_LEFT);

            $dataPencatatan = $dataPencatatanAll->filter(function ($item) use ($yearMonth) {
                $dataInput = $this->ensureArray($item->data_input);
                if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                    return false;
                }
                return Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m') === $yearMonth;
            });
        }

        $totalBiaya = 0;
        $totalVolume = 0;
        $totalVolumeMmbtu = 0;
        $processedDates = [];
        $hargaGas = 0;

        if ($invoice->period_type === 'custom') {
            // Group by pricing period, sama seperti show()
            $pricingPeriods = [];

            foreach ($dataPencatatan as $item) {
                $dataInput = $this->ensureArray($item->data_input);
                $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);

                $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
                $tanggalKey = $waktuAwal->format('Y-m-d H:i:s');

                if (isset($processedDates[$tanggalKey])) {
                    continue;
                }
                $processedDates[$tanggalKey] = true;

                $waktuAwalYearMonth = $waktuAwal->format('Y-m');
                $itemPricingInfo = $customer->getPricingForYearMonth($waktuAwalYearMonth, $waktuAwal);

                if ($isMmbtu) {
                    $koreksiMeter = floatval($itemPricingInfo['koreksi_meter'] ?? $customer->koreksi_meter ?? 1);
                    $pembajangSm3Mmbtu = floatval($itemPricingInfo['pembagi_sm3_ke_mmbtu'] ?? $customer->pembagi_sm3_ke_mmbtu ?? 1);
                    $hargaPeriod = floatval($itemPricingInfo['harga_per_mmbtu_usd'] ?? $customer->harga_per_mmbtu_usd ?? 0);
                    $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
                    $volumePeriod = $pembajangSm3Mmbtu > 0 ? $volumeSm3 / $pembajangSm3Mmbtu : 0;
                } else {
                    $volumePeriod = $volumeFlowMeter * floatval($itemPricingInfo['koreksi_meter'] ?? $customer->koreksi_meter);
                    $hargaPeriod = floatval($itemPricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
                }

                if (!isset($pricingPeriods[$hargaPeriod])) {
                    $pricingPeriods[$hargaPeriod] = ['harga' => $hargaPeriod, 'volume' => 0];
                }
                $pricingPeriods[$hargaPeriod]['volume'] += $volumePeriod;
            }

            foreach ($pricingPeriods as $period) {
                $totalBiaya += $period['volume'] * $period['harga'];
            }
        } else {
            foreach ($dataPencatatan as $item) {
                $dataInput = $this->ensureArray($item->data_input);
                $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);

                $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
                $tanggalKey = $waktuAwal->format('Y-m-d H:i:s');

                if (isset($processedDates[$tanggalKey])) {
                    continue;
                }
                $processedDates[$tanggalKey] = true;

                $waktuAwalYearMonth = $waktuAwal->format('Y-m');
                $itemPricingInfo = $customer->getPricingForYearMonth($waktuAwalYearMonth, $waktuAwal);

                if ($isMmbtu) {
                    $koreksiMeter = floatval($itemPricingInfo['koreksi_meter'] ?? $customer->koreksi_meter ?? 1);
                    $pembajangSm3Mmbtu = floatval($itemPricingInfo['pembagi_sm3_ke_mmbtu'] ?? $customer->pembagi_sm3_ke_mmbtu ?? 1);
                    $hargaGas = floatval($itemPricingInfo['harga_per_mmbtu_usd'] ?? $customer->harga_per_mmbtu_usd ?? 0);
                    $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
                    $totalVolumeMmbtu += $pembajangSm3Mmbtu > 0 ? $volumeSm3 / $pembajangSm3Mmbtu : 0;
                } else {
                    $volumeSm3 = $volumeFlowMeter * floatval($itemPricingInfo['koreksi_meter'] ?? $customer->koreksi_meter);
                    $hargaGas = floatval($itemPricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
                }

                $totalVolume += $volumeSm3;
            }

            $totalBiaya = $isMmbtu ? $totalVolumeMmbtu * $hargaGas : $totalVolume * $hargaGas;
        }

        return $isMmbtu ? round($totalBiaya, 2) : round($totalBiaya);
    }

    /**
     * Upload invoice bermaterai (PDF).
     */
    public function uploadMaterai(Request $request, Invoice $invoice)
    {
        $request->validate([
            'materai_file' => 'required|file|mimes:pdf|max:10240',
        ], [
            'materai_file.required' => 'File invoice bermaterai wajib diupload.',
            'materai_file.mimes' => 'File harus berformat PDF.',
            'materai_file.max' => 'Ukuran file maksimal 10MB.',
        ]);

        if ($invoice->materai_file_path) {
            Storage::disk('public')->delete($invoice->materai_file_path);
        }

        $path = $request->file('materai_file')->store('invoice-materai', 'public');

        $invoice->update(['materai_file_path' => $path]);

        return back()->with('success', 'Invoice bermaterai berhasil diupload.');
    }

    /**
     * Download invoice bermaterai.
     */
    public function downloadMaterai(Invoice $invoice)
    {
        $user = auth()->user();
        if (($user->isCustomer() || $user->isFOB() || $user->isMmbtu()) && $invoice->customer_id !== $user->id) {
            abort(403);
        }

        if (!$invoice->materai_file_path || !Storage::disk('public')->exists($invoice->materai_file_path)) {
            return back()->with('error', 'Invoice bermaterai belum tersedia.');
        }

        $filename = 'Invoice-Bermaterai-' . str_replace(['/', '\\', ' '], '-', $invoice->invoice_number) . '.pdf';

        return Storage::disk('public')->download($invoice->materai_file_path, $filename);
    }

    /**
     * Display the list of customers to select for a new invoice.
     */
    public function selectCustomer()
    {
        $customers = User::whereIn('role', ['customer', 'fob', 'mmbtu'])
            ->orderBy('name')
            ->get();

        return view('invoices.select-customer', compact('customers'));
    }

    /**
     * Show the form for creating a new invoice for a specific customer.
     */
    public function create(User $customer)
    {
        // Generate a default invoice number based on the current date (monthly period)
        $invoiceNumber = Invoice::generateInvoiceNumber($customer, null, 'monthly');
        
        // Default to current month and year
        $month = now()->month;
        $year = now()->year;
        
        return view('invoices.create', compact('customer', 'invoiceNumber', 'month', 'year'));
    }

    /**
     * Store a newly created invoice in storage.
     */
    public function store(Request $request, User $customer)
    {
        // Validation rules berdasarkan period type
        $baseRules = [
            'invoice_number' => 'required|string|max:50',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'period_type' => 'required|in:monthly,custom',
            'no_kontrak' => 'required|string|max:50',
            'id_pelanggan' => 'required|string|max:20',
            'description' => 'nullable|string',
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
            $yearMonth = $startDate->format('Y-m');
        } else {
            $yearMonth = $request->year . '-' . str_pad($request->month, 2, '0', STR_PAD_LEFT);
            $periodType = 'monthly';
        }

        // Get pricing info for selected period
        $pricingInfo = $customer->getPricingForYearMonth($yearMonth);

        // Base query
        $query = $customer->dataPencatatan();

        // Ambil semua data
        $dataPencatatan = $query->get();

        // Filter data berdasarkan periode yang dipilih
        if ($periodType === 'custom') {
            // Filter berdasarkan range tanggal custom
            $dataPencatatan = $dataPencatatan->filter(function ($item) use ($startDate, $endDate) {
                $dataInput = $this->ensureArray($item->data_input);

                if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                    return false;
                }

                $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->startOfDay();
                $startFilter = $startDate->copy()->startOfDay();
                $endFilter = $endDate->copy()->endOfDay();

                return $waktuAwal->between($startFilter, $endFilter);
            });
        } else {
            // Filter data berdasarkan bulan dan tahun
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

        // DEBUG: Log jumlah data yang ditemukan
        \Log::info('Invoice store - Data filtering', [
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
                $tanggal = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m-d H:i:s');
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
            \Log::warning('Invoice store - Duplicate dates detected in source data', [
                'customer_id' => $customer->id,
                'period' => $yearMonth,
                'duplicates' => $duplicates,
                'all_dates_found' => $tanggalDitemukan
            ]);
        }

        // Perhitungan volume dan biaya total
        $totalVolume = 0;
        $totalBiaya = 0;
        $processedDates = [];
        $isMmbtu = $customer->isMmbtu();

        // Group data by price periods for custom period
        if ($periodType === 'custom') {
            // Collect all unique pricing periods within the custom date range
            $pricingPeriods = [];

            foreach ($dataPencatatan as $item) {
                $dataInput = $this->ensureArray($item->data_input);
                $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);

                $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
                // Key mencakup jam agar shift berbeda di hari yang sama (mis. 07:00-15:00 dan 15:00-00:00) tidak dianggap duplikat
                $tanggalKey = $waktuAwal->format('Y-m-d H:i:s');

                // Skip hanya jika timestamp pembacaan_awal persis sama (data benar-benar terduplikasi)
                if (isset($processedDates[$tanggalKey])) {
                    continue;
                }

                $processedDates[$tanggalKey] = true;

                // Get pricing info for this specific date
                $waktuAwalYearMonth = $waktuAwal->format('Y-m');
                $itemPricingInfo = $customer->getPricingForYearMonth($waktuAwalYearMonth, $waktuAwal);

                if ($isMmbtu) {
                    $koreksiMeter = floatval($itemPricingInfo['koreksi_meter'] ?? $customer->koreksi_meter ?? 1);
                    $pembajangSm3Mmbtu = floatval($itemPricingInfo['pembagi_sm3_ke_mmbtu'] ?? $customer->pembagi_sm3_ke_mmbtu ?? 1);
                    $hargaGas = floatval($itemPricingInfo['harga_per_mmbtu_usd'] ?? $customer->harga_per_mmbtu_usd ?? 0);
                    $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
                    $volumeMmbtu = $pembajangSm3Mmbtu > 0 ? $volumeSm3 / $pembajangSm3Mmbtu : 0;
                    $priceKey = $hargaGas;
                    if (!isset($pricingPeriods[$priceKey])) {
                        $pricingPeriods[$priceKey] = ['harga' => $hargaGas, 'volume' => 0, 'volume_sm3' => 0];
                    }
                    $pricingPeriods[$priceKey]['volume'] += $volumeMmbtu;
                    $pricingPeriods[$priceKey]['volume_sm3'] += $volumeSm3;
                    $totalVolume += $volumeSm3;
                } else {
                    $volumeSm3 = $volumeFlowMeter * floatval($itemPricingInfo['koreksi_meter'] ?? $customer->koreksi_meter);
                    $hargaGas = floatval($itemPricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
                    $priceKey = $hargaGas;
                    if (!isset($pricingPeriods[$priceKey])) {
                        $pricingPeriods[$priceKey] = ['harga' => $hargaGas, 'volume' => 0];
                    }
                    $pricingPeriods[$priceKey]['volume'] += $volumeSm3;
                    $totalVolume += $volumeSm3;
                }
            }

            // Calculate total cost from all pricing periods
            foreach ($pricingPeriods as $period) {
                $totalBiaya += $period['volume'] * $period['harga'];
            }
        } else {
            // PERBAIKAN: For monthly period, tambahkan deteksi duplikasi sama seperti Billing
            $hargaGas = 0;
            $totalVolumeMmbtu = 0;

            foreach ($dataPencatatan as $item) {
                $dataInput = $this->ensureArray($item->data_input);
                $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);

                $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
                // Key mencakup jam agar shift berbeda di hari yang sama (mis. 07:00-15:00 dan 15:00-00:00) tidak dianggap duplikat
                $tanggalKey = $waktuAwal->format('Y-m-d H:i:s');

                // Skip hanya jika timestamp pembacaan_awal persis sama (data benar-benar terduplikasi)
                if (isset($processedDates[$tanggalKey])) {
                    \Log::debug('Invoice store - Skipping duplicate date', [
                        'customer_id' => $customer->id,
                        'item_id' => $item->id,
                        'duplicate_date' => $tanggalKey,
                        'volume_flow_meter' => $volumeFlowMeter
                    ]);
                    continue;
                }

                // Tandai tanggal ini sebagai sudah diproses
                $processedDates[$tanggalKey] = true;

                // PERBAIKAN: Gunakan pricing yang sesuai periode item (bukan periode invoice)
                $waktuAwalYearMonth = $waktuAwal->format('Y-m');
                $itemPricingInfo = $customer->getPricingForYearMonth($waktuAwalYearMonth, $waktuAwal);

                if ($isMmbtu) {
                    $koreksiMeter = floatval($itemPricingInfo['koreksi_meter'] ?? $customer->koreksi_meter ?? 1);
                    $pembajangSm3Mmbtu = floatval($itemPricingInfo['pembagi_sm3_ke_mmbtu'] ?? $customer->pembagi_sm3_ke_mmbtu ?? 1);
                    $hargaGas = floatval($itemPricingInfo['harga_per_mmbtu_usd'] ?? $customer->harga_per_mmbtu_usd ?? 0);
                    $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
                    $totalVolumeMmbtu += $pembajangSm3Mmbtu > 0 ? $volumeSm3 / $pembajangSm3Mmbtu : 0;
                } else {
                    $volumeSm3 = $volumeFlowMeter * floatval($itemPricingInfo['koreksi_meter'] ?? $customer->koreksi_meter);
                    $hargaGas = floatval($itemPricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
                }

                $totalVolume += $volumeSm3;

                // DEBUG: Log setiap item yang diproses
                \Log::debug('Invoice store - Processing item', [
                    'item_id' => $item->id,
                    'waktu_awal' => $waktuAwal->format('Y-m-d H:i:s'),
                    'volume_flow_meter' => $volumeFlowMeter,
                    'volume_sm3' => $volumeSm3,
                    'included_in_calculation' => true
                ]);
            }

            // Calculate total cost with total volume
            if ($isMmbtu) {
                $totalBiaya = $totalVolumeMmbtu * $hargaGas;
            } else {
                $totalBiaya = $totalVolume * $hargaGas;
            }
        }
        
        // FINAL DEBUG: Log invoice results
        \Log::info('Invoice store - Calculation results', [
            'customer_id' => $customer->id,
            'period' => $yearMonth,
            'total_volume' => $totalVolume,
            'total_amount' => $totalBiaya,
            'filtered_records_count' => $dataPencatatan->count(),
            'unique_dates_processed' => count($processedDates)
        ]);

        // Bulatkan total biaya untuk konsistensi
        $totalBiaya = round($totalBiaya);
        
        // Buat invoice dan billing secara bersamaan dalam database transaction
        DB::beginTransaction();
        
        try {
            // 1. Buat invoice terlebih dahulu
            $invoice = new Invoice();
            $invoice->customer_id = $customer->id;
            $invoice->invoice_number = $request->invoice_number;
            $invoice->invoice_date = $request->invoice_date;
            $invoice->due_date = $request->due_date;
            $invoice->total_amount = $totalBiaya;
            $invoice->total_volume = $totalVolume;
            $invoice->status = 'unpaid';
            $invoice->description = $request->description;
            $invoice->no_kontrak = $customer->no_kontrak ?: ('AUTO-' . date('Y')); // Gunakan dari customer atau default
            $invoice->id_pelanggan = $request->id_pelanggan;
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
            
            // 2. Perhitungan untuk billing (deposit dan balance hanya untuk monthly)
            $totalDeposit = 0;
            $prevMonthBalance = 0;
            $currentMonthBalance = 0;
            $amountToPay = 0;
            
            if ($periodType === 'monthly') {
                // Perhitungan deposit history
                $depositHistory = $this->ensureArray($customer->deposit_history);
                foreach ($depositHistory as $deposit) {
                    if (isset($deposit['date'])) {
                        $depositDate = Carbon::parse($deposit['date']);
                        if ($depositDate->format('Y-m') === $yearMonth) {
                            // Untuk MMBTU, hanya ambil deposit yang memiliki flag is_mmbtu
                            if ($isMmbtu && empty($deposit['is_mmbtu'])) continue;
                            $totalDeposit += floatval($deposit['amount'] ?? 0);
                        }
                    }
                }
                
                // Perhitungan balance
                $prevDate = Carbon::createFromDate($request->year, $request->month, 1)->subMonth();
                $prevMonthYear = $prevDate->format('Y-m');
                
                $customer->updateMonthlyBalances($prevMonthYear);
                $customer = User::findOrFail($customer->id);
                
                $monthlyBalances = $customer->monthly_balances ?: [];
                $prevMonthBalance = isset($monthlyBalances[$prevMonthYear]) ?
                    floatval($monthlyBalances[$prevMonthYear]) : 0;
                
                $currentMonthBalance = $prevMonthBalance + $totalDeposit - $totalBiaya;
                $amountToPay = $currentMonthBalance < 0 ? abs($currentMonthBalance) : 0;
            } else {
                // Untuk periode custom, amount to pay = total biaya pemakaian
                $amountToPay = $totalBiaya;
            }
            
            // 3. Auto-create billing dengan nomor yang sama
            $billing = new Billing();
            $billing->customer_id = $customer->id;
            $billing->invoice_id = $invoice->id;
            $billing->billing_number = Billing::generateSyncedNumber($customer, $invoice->invoice_number);
            $billing->billing_date = $invoice->invoice_date;
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
            
            // 4. Update invoice dengan billing_id
            $invoice->billing_id = $billing->id;
            $invoice->save();
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error creating invoice and billing sync: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal membuat invoice dan billing: ' . $e->getMessage()])->withInput();
        }

        // FINAL LOG: Log created invoice and billing
        \Log::info('Invoice and Billing created successfully', [
            'invoice_id' => $invoice->id,
            'billing_id' => $billing->id,
            'customer_id' => $customer->id,
            'period' => $yearMonth,
            'total_volume' => $totalVolume,
            'total_amount' => $totalBiaya
        ]);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice dan Billing berhasil dibuat secara bersamaan.');
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice)
    {
        // Authorization: Customer hanya bisa melihat invoice milik mereka sendiri
        if ((auth()->user()->isCustomer() || auth()->user()->isFOB() || auth()->user()->isMmbtu()) && $invoice->customer_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses ke invoice ini.');
        }
        
        $customer = $invoice->customer;
        
        // Determine period based on invoice type
        if ($invoice->period_type === 'custom') {
            $startDate = Carbon::parse($invoice->custom_start_date);
            $endDate = Carbon::parse($invoice->custom_end_date);
            $yearMonth = $startDate->format('Y-m');
        } else {
            $yearMonth = $invoice->period_year . '-' . str_pad($invoice->period_month, 2, '0', STR_PAD_LEFT);
        }
        
        // Get pricing info
        $pricingInfo = $customer->getPricingForYearMonth($yearMonth);
        
        // Retrieve and filter the relevant data
        $dataPencatatan = $customer->dataPencatatan()->get();
        
        // Filter data berdasarkan periode invoice
        if ($invoice->period_type === 'custom') {
            // Filter berdasarkan range tanggal custom
            $dataPencatatan = $dataPencatatan->filter(function ($item) use ($startDate, $endDate) {
                $dataInput = $this->ensureArray($item->data_input);

                if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                    return false;
                }

                $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->startOfDay();
                $startFilter = $startDate->copy()->startOfDay();
                $endFilter = $endDate->copy()->endOfDay();

                return $waktuAwal->between($startFilter, $endFilter);
            });
        } else {
            // Filter data berdasarkan bulan dan tahun
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
        \Log::info('Invoice show - Data filtering', [
            'invoice_id' => $invoice->id,
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
                $tanggal = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m-d H:i:s');
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
            \Log::warning('Invoice show - Duplicate dates detected', [
                'invoice_id' => $invoice->id,
                'duplicates' => $duplicates,
                'all_dates_found' => $tanggalDitemukan
            ]);
        }

        // Perhitungan untuk volume dan biaya pemakaian gas
        $pemakaianGas = [];
        $totalVolume = 0;
        $totalBiaya = 0;
        $processedDates = [];
        $isMmbtu = $customer->isMmbtu();

        // Group data by price periods for custom period
        if ($invoice->period_type === 'custom') {
            // Collect all unique pricing periods within the custom date range
            $pricingPeriods = [];

            foreach ($dataPencatatan as $item) {
                $dataInput = $this->ensureArray($item->data_input);
                $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);

                $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
                // Key mencakup jam agar shift berbeda di hari yang sama (mis. 07:00-15:00 dan 15:00-00:00) tidak dianggap duplikat
                $tanggalKey = $waktuAwal->format('Y-m-d H:i:s');

                // Skip hanya jika timestamp pembacaan_awal persis sama (data benar-benar terduplikasi)
                if (isset($processedDates[$tanggalKey])) {
                    \Log::debug('Invoice show - Skipping duplicate date', [
                        'invoice_id' => $invoice->id,
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

                // Get pricing info for this specific date
                $waktuAwalYearMonth = $waktuAwal->format('Y-m');
                $itemPricingInfo = $customer->getPricingForYearMonth($waktuAwalYearMonth, $waktuAwal);

                if ($isMmbtu) {
                    $koreksiMeter = floatval($itemPricingInfo['koreksi_meter'] ?? $customer->koreksi_meter ?? 1);
                    $pembajangSm3Mmbtu = floatval($itemPricingInfo['pembagi_sm3_ke_mmbtu'] ?? $customer->pembagi_sm3_ke_mmbtu ?? 1);
                    $hargaGas = floatval($itemPricingInfo['harga_per_mmbtu_usd'] ?? $customer->harga_per_mmbtu_usd ?? 0);
                    $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
                    $volumeMmbtu = $pembajangSm3Mmbtu > 0 ? $volumeSm3 / $pembajangSm3Mmbtu : 0;
                    $priceKey = $hargaGas;
                    if (!isset($pricingPeriods[$priceKey])) {
                        $pricingPeriods[$priceKey] = [
                            'harga' => $hargaGas, 'volume' => 0, 'volume_sm3' => 0,
                            'dates' => [], 'start_date' => $waktuAwal, 'end_date' => $waktuAwal
                        ];
                    }
                    $pricingPeriods[$priceKey]['volume'] += $volumeMmbtu;
                    $pricingPeriods[$priceKey]['volume_sm3'] += $volumeSm3;
                } else {
                    $volumeSm3 = $volumeFlowMeter * floatval($itemPricingInfo['koreksi_meter'] ?? $customer->koreksi_meter);
                    $hargaGas = floatval($itemPricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
                    $priceKey = $hargaGas;
                    if (!isset($pricingPeriods[$priceKey])) {
                        $pricingPeriods[$priceKey] = [
                            'harga' => $hargaGas, 'volume' => 0,
                            'dates' => [], 'start_date' => $waktuAwal, 'end_date' => $waktuAwal
                        ];
                    }
                    $pricingPeriods[$priceKey]['volume'] += $volumeSm3;
                }

                $pricingPeriods[$priceKey]['dates'][] = $waktuAwal;

                if ($waktuAwal->lt($pricingPeriods[$priceKey]['start_date'])) {
                    $pricingPeriods[$priceKey]['start_date'] = $waktuAwal;
                }
                if ($waktuAwal->gt($pricingPeriods[$priceKey]['end_date'])) {
                    $pricingPeriods[$priceKey]['end_date'] = $waktuAwal;
                }

                $totalVolume += $volumeSm3;

                // DEBUG: Log setiap item yang diproses di show
                \Log::debug('Invoice show - Processing unique item', [
                    'invoice_id' => $invoice->id,
                    'item_id' => $item->id,
                    'tanggal_key' => $tanggalKey,
                    'waktu_awal' => $waktuAwal->format('Y-m-d H:i:s'),
                    'volume_flow_meter' => $volumeFlowMeter,
                    'volume_sm3' => $volumeSm3,
                    'harga_gas' => $hargaGas
                ]);
            }

            // Sort pricing periods by start date
            uasort($pricingPeriods, function($a, $b) {
                return $a['start_date']->timestamp - $b['start_date']->timestamp;
            });

            // Create rows for each pricing period
            $rowNumber = 1;
            foreach ($pricingPeriods as $period) {
                $biayaPemakaian = $period['volume'] * $period['harga'];
                $totalBiaya += $biayaPemakaian;

                // Format periode based on date range
                if ($period['start_date']->format('Y-m-d') === $period['end_date']->format('Y-m-d')) {
                    $periodePemakaian = $period['start_date']->format('d F Y');
                } else {
                    $periodePemakaian = $period['start_date']->format('d F Y') . ' - ' . $period['end_date']->format('d F Y');
                }

                $pemakaianGas[] = [
                    'no' => $rowNumber++,
                    'periode_pemakaian' => $periodePemakaian,
                    'volume_sm3' => $isMmbtu ? ($period['volume_sm3'] ?? 0) : $period['volume'],
                    'volume_mmbtu' => $isMmbtu ? $period['volume'] : null,
                    'harga_gas' => $period['harga'],
                    'biaya_pemakaian' => $biayaPemakaian
                ];
            }
        } else {
            // PERBAIKAN: For monthly period, tambahkan deteksi duplikasi sama seperti Billing
            $hargaGas = 0;
            $totalVolumeMmbtu = 0;

            foreach ($dataPencatatan as $item) {
                $dataInput = $this->ensureArray($item->data_input);
                $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);

                $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
                // Key mencakup jam agar shift berbeda di hari yang sama (mis. 07:00-15:00 dan 15:00-00:00) tidak dianggap duplikat
                $tanggalKey = $waktuAwal->format('Y-m-d H:i:s');

                // Skip hanya jika timestamp pembacaan_awal persis sama (data benar-benar terduplikasi)
                if (isset($processedDates[$tanggalKey])) {
                    \Log::debug('Invoice show - Skipping duplicate date (monthly)', [
                        'invoice_id' => $invoice->id,
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

                // PERBAIKAN: Gunakan pricing yang sesuai periode item (bukan periode invoice)
                $waktuAwalYearMonth = $waktuAwal->format('Y-m');
                $itemPricingInfo = $customer->getPricingForYearMonth($waktuAwalYearMonth, $waktuAwal);

                if ($isMmbtu) {
                    $koreksiMeter = floatval($itemPricingInfo['koreksi_meter'] ?? $customer->koreksi_meter ?? 1);
                    $pembajangSm3Mmbtu = floatval($itemPricingInfo['pembagi_sm3_ke_mmbtu'] ?? $customer->pembagi_sm3_ke_mmbtu ?? 1);
                    $hargaGas = floatval($itemPricingInfo['harga_per_mmbtu_usd'] ?? $customer->harga_per_mmbtu_usd ?? 0);
                    $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
                    $totalVolumeMmbtu += $pembajangSm3Mmbtu > 0 ? $volumeSm3 / $pembajangSm3Mmbtu : 0;
                } else {
                    $volumeSm3 = $volumeFlowMeter * floatval($itemPricingInfo['koreksi_meter'] ?? $customer->koreksi_meter);
                    $hargaGas = floatval($itemPricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
                }

                $totalVolume += $volumeSm3;

                // DEBUG: Log setiap item yang diproses di show (monthly)
                \Log::debug('Invoice show - Processing unique item (monthly)', [
                    'invoice_id' => $invoice->id,
                    'item_id' => $item->id,
                    'tanggal_key' => $tanggalKey,
                    'waktu_awal' => $waktuAwal->format('Y-m-d H:i:s'),
                    'volume_flow_meter' => $volumeFlowMeter,
                    'volume_sm3' => $volumeSm3,
                    'harga_gas' => $hargaGas
                ]);
            }

            // Calculate total cost with total volume
            if ($isMmbtu) {
                $totalBiaya = $totalVolumeMmbtu * $hargaGas;
            } else {
                $totalBiaya = $totalVolume * $hargaGas;
            }

            // Create a single row with period's total
            $periodePemakaian = Carbon::createFromDate($invoice->period_year, $invoice->period_month, 1)->format('1 F Y') . " - " .
                             Carbon::createFromDate($invoice->period_year, $invoice->period_month, 1)->endOfMonth()->format('d F Y');

            $pemakaianGas[] = [
                'no' => 1,
                'periode_pemakaian' => $periodePemakaian,
                'volume_sm3' => $totalVolume,
                'volume_mmbtu' => $isMmbtu ? $totalVolumeMmbtu : null,
                'harga_gas' => $hargaGas,
                'biaya_pemakaian' => $totalBiaya
            ];
        }
        
        // DEBUG: Log final show data
        \Log::info('Invoice show - Final data', [
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'period' => $yearMonth,
            'total_records_filtered' => $dataPencatatan->count(),
            'pemakaian_gas_count_displayed' => count($pemakaianGas),
            'invoice_total_volume' => $invoice->total_volume,
            'calculated_total_volume' => $totalVolume,
            'invoice_total_amount' => $invoice->total_amount,
            'calculated_total_amount' => $totalBiaya,
            'duplicates_removed' => $dataPencatatan->count() - count(array_filter($processedDates)),
            'unique_dates_count' => count($processedDates)
        ]);
        
        // Generate ID Pelanggan (contoh format)
        $idPelanggan = sprintf('03C%04d', $customer->id);
        
        // Bulatkan total biaya untuk konsistensi dengan tampilan
        $totalBiaya = $isMmbtu ? round($totalBiaya, 2) : round($totalBiaya);

        // Terbilang untuk total tagihan
        $terbilang = $this->terbilang((int) round($totalBiaya));

        // Setup data untuk view Invoice
        $data = [
            'invoice' => $invoice,
            'customer' => $customer,
            'is_mmbtu' => $isMmbtu,
            'periode_bulan' => $invoice->period_type === 'custom' ?
                Carbon::parse($invoice->custom_start_date)->format('d/m/Y') . ' - ' . Carbon::parse($invoice->custom_end_date)->format('d/m/Y') :
                Carbon::createFromDate($invoice->period_year, $invoice->period_month, 1)->format('F Y'),
            'pemakaian_gas' => $pemakaianGas,
            'total_volume' => $totalVolume,
            'total_biaya' => $totalBiaya,
            'id_pelanggan' => $idPelanggan,
            'terbilang' => $terbilang
        ];

        return view('invoices.show', $data);
    }

    /**
     * Show the form for editing the invoice.
     */
    public function edit(Invoice $invoice)
    {
        $customer = $invoice->customer;
        return view('invoices.edit', compact('invoice', 'customer'));
    }

    /**
     * Update the specified invoice in storage.
     */
    public function update(Request $request, Invoice $invoice)
    {
        $request->validate([
            'invoice_number' => 'required|string|max:50',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'status' => 'required|in:paid,unpaid,partial,cancelled',
            'no_kontrak' => 'required|string|max:50',
            'id_pelanggan' => 'required|string|max:20',
            'description' => 'nullable|string',
        ]);

        DB::beginTransaction();
        
        try {
            // Update invoice
            $invoice->invoice_number = $request->invoice_number;
            $invoice->invoice_date = $request->invoice_date;
            $invoice->due_date = $request->due_date;
            $invoice->status = $request->status;
            $invoice->description = $request->description;
            $invoice->no_kontrak = $request->no_kontrak;
            $invoice->id_pelanggan = $request->id_pelanggan;
            $invoice->save();
            
            // Sync update ke billing jika ada
            if ($invoice->billing) {
                $invoice->billing->billing_number = Billing::generateSyncedNumber($invoice->customer, $invoice->invoice_number);
                $invoice->billing->billing_date = $invoice->invoice_date;
                $invoice->billing->save();
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error updating invoice and billing sync: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal memperbarui invoice dan billing: ' . $e->getMessage()])->withInput();
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice dan Billing berhasil diperbarui.');
    }

    /**
     * Remove the specified invoice from storage.
     */
    public function destroy(Invoice $invoice)
    {
        DB::beginTransaction();
        
        try {
            // Hapus billing terkait jika ada
            if ($invoice->billing) {
                $invoice->billing->delete();
            }
            
            // Hapus invoice
            $invoice->delete();
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error deleting invoice and billing sync: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal menghapus invoice dan billing: ' . $e->getMessage()]);
        }
        
        return redirect()->route('invoices.index')
            ->with('success', 'Invoice dan Billing berhasil dihapus.');
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
    
    /**
     * Display customer's own invoices (Customer view only).
     */
    public function customerInvoices(Request $request)
    {
        // Pastikan user adalah customer yang sedang login
        $customer = auth()->user();
        
        if (!$customer->isCustomer() && !$customer->isFOB() && !$customer->isMmbtu()) {
            abort(403, 'Akses tidak diizinkan.');
        }

        // Query invoice milik customer yang sedang login dengan sorting berdasarkan periode
        $query = Invoice::where('customer_id', $customer->id)
            ->orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->orderByRaw("CASE WHEN period_type = 'monthly' THEN 0 ELSE 1 END") // monthly first, then custom
            ->orderBy('created_at', 'desc');
        
        // Filter berdasarkan search jika ada
        if ($request->has('search') && !empty($request->search)) {
            $query->where('invoice_number', 'like', '%' . $request->search . '%');
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
        
        $invoices = $query->paginate(10);
        
        // Menyimpan parameter filter dalam pagination links
        $invoices->appends($request->only(['search', 'period']));
        
        // Deteksi jika request adalah AJAX untuk pencarian real-time
        if ($request->ajax()) {
            return response()->json([
                'html' => view('customer.invoices.partials.invoice-table', compact('invoices'))->render(),
                'pagination' => view('customer.invoices.partials.pagination', compact('invoices'))->render(),
            ]);
        }
        
        return view('customer.invoices.index', compact('invoices', 'customer'));
    }

    /**
     * Convert number to Indonesian words.
     */
    private function terbilang($angka) {
        $angka = abs($angka);
        $baca = array('', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas');
        $terbilang = '';
        
        if ($angka < 12) {
            $terbilang = ' ' . $baca[$angka];
        } elseif ($angka < 20) {
            $terbilang = $this->terbilang($angka - 10) . ' belas';
        } elseif ($angka < 100) {
            $terbilang = $this->terbilang((int)($angka / 10)) . ' puluh' . $this->terbilang($angka % 10);
        } elseif ($angka < 200) {
            $terbilang = ' seratus' . $this->terbilang($angka - 100);
        } elseif ($angka < 1000) {
            $terbilang = $this->terbilang((int)($angka / 100)) . ' ratus' . $this->terbilang($angka % 100);
        } elseif ($angka < 2000) {
            $terbilang = ' seribu' . $this->terbilang($angka - 1000);
        } elseif ($angka < 1000000) {
            $terbilang = $this->terbilang((int)($angka / 1000)) . ' ribu' . $this->terbilang($angka % 1000);
        } elseif ($angka < 1000000000) {
            $terbilang = $this->terbilang((int)($angka / 1000000)) . ' juta' . $this->terbilang($angka % 1000000);
        } elseif ($angka < 1000000000000) {
            $terbilang = $this->terbilang((int)($angka / 1000000000)) . ' milyar' . $this->terbilang($angka % 1000000000);
        } elseif ($angka < 1000000000000000) {
            $terbilang = $this->terbilang((int)($angka / 1000000000000)) . ' trilyun' . $this->terbilang($angka % 1000000000000);
        }
        
        return $terbilang;
    }
}
