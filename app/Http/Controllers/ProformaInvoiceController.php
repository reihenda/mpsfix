<?php

namespace App\Http\Controllers;

use App\Models\ProformaInvoice;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProformaInvoiceController extends Controller
{
    /**
     * Get customer balance on specific date via AJAX
     */
    public function getCustomerBalance(Request $request, User $customer)
    {
        $date = $request->input('date', now());
        $balance = $this->getCustomerBalanceOnDate($customer, $date);
        
        return response()->json([
            'balance' => $balance,
            'formatted_balance' => number_format($balance, 0, ',', '.'),
            'date' => Carbon::parse($date)->format('d M Y')
        ]);
    }

    /**
     * Generate proforma number via AJAX
     */
    public function generateProformaNumber(Request $request, User $customer)
    {
        $proformaNumber = ProformaInvoice::generateProformaNumber($customer);
        
        return response()->json([
            'proforma_number' => $proformaNumber
        ]);
    }

    /**
     * Display a listing of proforma invoices.
     */
    public function index(Request $request)
    {
        // Ambil semua customer (termasuk FOB)
        $customers = User::whereIn('role', ['customer', 'fob'])
            ->orderBy('name')
            ->get();
        
        // Query dasar
        $query = ProformaInvoice::with('customer')
            ->orderBy('created_at', 'desc');
        
        // Filter berdasarkan pencarian customer jika ada
        if ($request->has('search') && !empty($request->search)) {
            // Dapatkan ID customer yang namanya cocok dengan pencarian
            $customerIds = User::whereIn('role', ['customer', 'fob'])
                ->where('name', 'like', '%' . $request->search . '%')
                ->pluck('id');
            
            // Filter proforma invoice berdasarkan customer_id yang cocok
            $query->whereIn('customer_id', $customerIds);
        }

        // Filter berdasarkan status jika ada
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }
        
        $proformaInvoices = $query->paginate(15);
        
        // Menyimpan parameter filter dalam pagination links
        $proformaInvoices->appends($request->only(['search', 'status']));
        
        // Deteksi jika request adalah AJAX untuk pencarian real-time
        if ($request->ajax()) {
            return response()->json([
                'html' => view('proforma-invoices.partials.proforma-table', compact('proformaInvoices'))->render(),
                'pagination' => view('proforma-invoices.partials.pagination', compact('proformaInvoices'))->render(),
            ]);
        }
        
        return view('proforma-invoices.index', compact('proformaInvoices', 'customers'));
    }

    /**
     * Display the list of customers to select for a new proforma invoice.
     */
    public function selectCustomer()
    {
        $customers = User::whereIn('role', ['customer', 'fob'])
            ->orderBy('name')
            ->get();
        
        return view('proforma-invoices.select-customer', compact('customers'));
    }

    /**
     * Show the form for creating a new proforma invoice for a specific customer.
     */
    public function create(User $customer)
    {
        // Generate a default proforma number
        $proformaNumber = ProformaInvoice::generateProformaNumber($customer);
        
        // Default period: current month
        $startDate = now()->startOfMonth()->format('Y-m-d');
        $endDate = now()->endOfMonth()->format('Y-m-d');
        
        // Get current balance (saldo per hari ini)
        $currentBalance = $this->getCustomerBalanceOnDate($customer, now());
        
        return view('proforma-invoices.create', compact('customer', 'proformaNumber', 'startDate', 'endDate', 'currentBalance'));
    }

    /**
     * Store a newly created proforma invoice in storage.
     */
    public function store(Request $request, User $customer)
    {
        $request->validate([
            'proforma_number' => 'required|string|max:50|unique:proforma_invoices,proforma_number',
            'proforma_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:proforma_date',
            'period_start_date' => 'required|date',
            'period_end_date' => 'required|date|after_or_equal:period_start_date',
            'validity_date' => 'nullable|date|after_or_equal:proforma_date',
            'volume_per_day' => 'required|numeric|min:0',
            'price_per_sm3' => 'required|numeric|min:0',
            'no_kontrak' => 'required|string|max:50',
            'id_pelanggan' => 'required|string|max:20',
            'description' => 'nullable|string',
        ]);

        // Validasi periode maksimal 60 hari
        $startDate = Carbon::parse($request->period_start_date);
        $endDate = Carbon::parse($request->period_end_date);
        $diffDays = $startDate->diffInDays($endDate); // Tanpa +1, jadi exclusive
        
        if ($diffDays > 60) {
            return back()->withErrors(['period_end_date' => 'Periode maksimal adalah 60 hari.'])->withInput();
        }
        
        // Minimal harus 1 hari
        if ($diffDays < 1) {
            return back()->withErrors(['period_end_date' => 'Periode minimal adalah 1 hari.'])->withInput();
        }

        // Perhitungan berdasarkan input manual
        $volumePerDay = (float) $request->volume_per_day;
        $pricePerSm3 = (float) $request->price_per_sm3;
        $totalDays = $diffDays;
        
        // Kalkulasi total volume dan biaya
        $totalVolume = $volumePerDay * $totalDays;
        $totalBiaya = $totalVolume * $pricePerSm3;
        
        // Bulatkan total biaya untuk konsistensi
        $totalBiaya = round($totalBiaya);
        
        // Buat proforma invoice baru
        $proformaInvoice = new ProformaInvoice();
        $proformaInvoice->customer_id = $customer->id;
        $proformaInvoice->proforma_number = $request->proforma_number;
        $proformaInvoice->proforma_date = $request->proforma_date;
        $proformaInvoice->due_date = $request->due_date;
        $proformaInvoice->total_amount = $totalBiaya;
        $proformaInvoice->total_volume = $totalVolume;
        $proformaInvoice->volume_per_day = $volumePerDay;
        $proformaInvoice->price_per_sm3 = $pricePerSm3;
        $proformaInvoice->total_days = $totalDays;
        $proformaInvoice->status = 'draft';
        $proformaInvoice->description = $request->description;
        $proformaInvoice->no_kontrak = $request->no_kontrak;
        $proformaInvoice->id_pelanggan = $request->id_pelanggan;
        $proformaInvoice->period_start_date = $request->period_start_date;
        $proformaInvoice->period_end_date = $request->period_end_date;
        $proformaInvoice->validity_date = $request->validity_date;
        
        $proformaInvoice->save();

        return redirect()->route('proforma-invoices.show', $proformaInvoice)
            ->with('success', 'Proforma Invoice berhasil dibuat.');
    }

    /**
     * Display the specified proforma invoice.
     */
    public function show(ProformaInvoice $proformaInvoice)
    {
        $customer = $proformaInvoice->customer;
        $startDate = $proformaInvoice->period_start_date;
        $endDate = $proformaInvoice->period_end_date;
        
        // Ambil saldo customer pada tanggal awal periode
        $saldoPerTanggal = $this->getCustomerBalanceOnDate($customer, $proformaInvoice->period_start_date);
        
        // Data untuk tampilan berdasarkan input manual
        $pemakaianGas = [
            [
                'no' => 1,
                'periode_pemakaian' => $proformaInvoice->period_formatted,
                'volume_sm3' => $proformaInvoice->total_volume,
                'harga_gas' => $proformaInvoice->price_per_sm3,
                'biaya_pemakaian' => $proformaInvoice->total_amount,
                'volume_per_day' => $proformaInvoice->volume_per_day,
                'total_days' => $proformaInvoice->total_days,
            ]
        ];
        
        // Generate ID Pelanggan (contoh format)
        $idPelanggan = $proformaInvoice->id_pelanggan;
        
        // Total dari data yang sudah disimpan
        $totalVolume = $proformaInvoice->total_volume;
        $totalBiaya = $proformaInvoice->total_amount;
        
        // Terbilang untuk total tagihan
        $terbilang = $this->terbilang($totalBiaya);

        // Setup data untuk view Proforma Invoice
        $data = [
            'proformaInvoice' => $proformaInvoice,
            'customer' => $customer,
            'periode_bulan' => $proformaInvoice->period_formatted,
            'pemakaian_gas' => $pemakaianGas,
            'total_volume' => $totalVolume,
            'total_biaya' => $totalBiaya,
            'id_pelanggan' => $idPelanggan,
            'terbilang' => $terbilang,
            'saldo_per_tanggal' => $saldoPerTanggal,
            'tanggal_saldo' => $proformaInvoice->period_start_date
        ];

        return view('proforma-invoices.show', $data);
    }

    /**
     * Show the form for editing the proforma invoice.
     */
    public function edit(ProformaInvoice $proformaInvoice)
    {
        $customer = $proformaInvoice->customer;
        return view('proforma-invoices.edit', compact('proformaInvoice', 'customer'));
    }

    /**
     * Update the specified proforma invoice in storage.
     */
    public function update(Request $request, ProformaInvoice $proformaInvoice)
    {
        $request->validate([
            'proforma_number' => 'required|string|max:50|unique:proforma_invoices,proforma_number,' . $proformaInvoice->id,
            'proforma_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:proforma_date',
            'period_start_date' => 'required|date',
            'period_end_date' => 'required|date|after_or_equal:period_start_date',
            'validity_date' => 'nullable|date|after_or_equal:proforma_date',
            'volume_per_day' => 'required|numeric|min:0',
            'price_per_sm3' => 'required|numeric|min:0',
            'status' => 'required|in:draft,sent,expired,converted',
            'no_kontrak' => 'required|string|max:50',
            'id_pelanggan' => 'required|string|max:20',
            'description' => 'nullable|string',
        ]);

        // Validasi periode maksimal 60 hari
        $startDate = Carbon::parse($request->period_start_date);
        $endDate = Carbon::parse($request->period_end_date);
        $diffDays = $startDate->diffInDays($endDate); // Tanpa +1, jadi exclusive
        
        if ($diffDays > 60) {
            return back()->withErrors(['period_end_date' => 'Periode maksimal adalah 60 hari.'])->withInput();
        }
        
        // Minimal harus 1 hari
        if ($diffDays < 1) {
            return back()->withErrors(['period_end_date' => 'Periode minimal adalah 1 hari.'])->withInput();
        }

        // Perhitungan berdasarkan input manual
        $volumePerDay = (float) $request->volume_per_day;
        $pricePerSm3 = (float) $request->price_per_sm3;
        $totalDays = $diffDays;
        
        // Kalkulasi total volume dan biaya
        $totalVolume = $volumePerDay * $totalDays;
        $totalBiaya = $totalVolume * $pricePerSm3;
        
        // Bulatkan total biaya untuk konsistensi
        $totalBiaya = round($totalBiaya);

        $proformaInvoice->proforma_number = $request->proforma_number;
        $proformaInvoice->proforma_date = $request->proforma_date;
        $proformaInvoice->due_date = $request->due_date;
        $proformaInvoice->total_amount = $totalBiaya;
        $proformaInvoice->total_volume = $totalVolume;
        $proformaInvoice->volume_per_day = $volumePerDay;
        $proformaInvoice->price_per_sm3 = $pricePerSm3;
        $proformaInvoice->total_days = $totalDays;
        $proformaInvoice->period_start_date = $request->period_start_date;
        $proformaInvoice->period_end_date = $request->period_end_date;
        $proformaInvoice->validity_date = $request->validity_date;
        $proformaInvoice->status = $request->status;
        $proformaInvoice->description = $request->description;
        $proformaInvoice->no_kontrak = $request->no_kontrak;
        $proformaInvoice->id_pelanggan = $request->id_pelanggan;
        $proformaInvoice->save();

        return redirect()->route('proforma-invoices.show', $proformaInvoice)
            ->with('success', 'Proforma Invoice berhasil diperbarui.');
    }

    /**
     * Remove the specified proforma invoice from storage.
     */
    public function destroy(ProformaInvoice $proformaInvoice)
    {
        $proformaInvoice->delete();
        return redirect()->route('proforma-invoices.index')
            ->with('success', 'Proforma Invoice berhasil dihapus.');
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
     * Get customer balance on specific date
     */
    private function getCustomerBalanceOnDate(User $customer, $date)
    {
        $targetDate = Carbon::parse($date);
        
        // Get all deposits until target date
        $totalDeposits = 0;
        $deposits = is_string($customer->deposit_history) 
            ? json_decode($customer->deposit_history, true) 
            : $customer->deposit_history;
            
        if (is_array($deposits)) {
            foreach ($deposits as $deposit) {
                if (isset($deposit['date'])) {
                    $depositDate = Carbon::parse($deposit['date']);
                    if ($depositDate->lte($targetDate)) {
                        $totalDeposits += floatval($deposit['amount'] ?? 0);
                    }
                }
            }
        }
        
        // Get all purchases until target date
        $totalPurchases = 0;
        $allDataPencatatan = $customer->dataPencatatan()->get();
        
        foreach ($allDataPencatatan as $purchaseItem) {
            $itemDataInput = is_string($purchaseItem->data_input)
                ? json_decode($purchaseItem->data_input, true)
                : $purchaseItem->data_input;
                
            if (empty($itemDataInput) || empty($itemDataInput['pembacaan_awal']['waktu'])) {
                continue;
            }
            
            $itemWaktuAwal = Carbon::parse($itemDataInput['pembacaan_awal']['waktu']);
            
            if ($itemWaktuAwal->lte($targetDate)) {
                $volumeFlowMeter = floatval($itemDataInput['volume_flow_meter'] ?? 0);
                
                // Get pricing info for this specific date
                $itemYearMonth = $itemWaktuAwal->format('Y-m');
                $itemPricingInfo = $customer->getPricingForYearMonth($itemYearMonth, $itemWaktuAwal);
                
                // Calculate volume and price
                $itemKoreksiMeter = floatval($itemPricingInfo['koreksi_meter'] ?? $customer->koreksi_meter);
                $itemHargaPerM3 = floatval($itemPricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
                $itemVolumeSm3 = $volumeFlowMeter * $itemKoreksiMeter;
                $itemHarga = $itemVolumeSm3 * $itemHargaPerM3;
                
                $totalPurchases += $itemHarga;
            }
        }
        
        return $totalDeposits - $totalPurchases;
    }
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
