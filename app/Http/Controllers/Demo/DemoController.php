<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DataPencatatanController;
use App\Http\Controllers\UserController;
use App\Models\DataPencatatan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DemoController extends Controller
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

    // Demo Admin Dashboard (Similar to customer-detail)
    public function demoAdmin(Request $request)
    {
        // Get the current demo user
        $user = Auth::user();

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

        // Format filter untuk query
        $yearMonth = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT);

        // Base query
        $query = $user->dataPencatatan();

        // Ambil semua data dulu
        $dataPencatatan = $query->get();

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

        // Urutkan data berdasarkan tanggal pembacaan awal
        $dataPencatatan = $dataPencatatan->sortBy(function ($item) {
            $dataInput = $this->ensureArray($item->data_input);
            return isset($dataInput['pembacaan_awal']['waktu']) ?
                Carbon::parse($dataInput['pembacaan_awal']['waktu'])->timestamp : 0;
        });

        // Get pricing info for selected month
        $pricingInfo = $user->getPricingForYearMonth($yearMonth);

        // Calculate total volume SM3 for all time
        $totalVolumeSm3 = $user->getTotalVolumeSm3();

        // Calculate total volume SM3 for filtered period
        $filteredVolumeSm3 = 0;
        foreach ($dataPencatatan as $item) {
            $dataInput = $this->ensureArray($item->data_input);

            $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
            $volumeSm3 = $volumeFlowMeter * floatval($pricingInfo['koreksi_meter'] ?? $user->koreksi_meter);
            $filteredVolumeSm3 += $volumeSm3;
        }

        // Calculate total purchases for the filtered period
        $filteredTotalPurchases = $dataPencatatan->sum(function ($item) use ($pricingInfo, $user) {
            $dataInput = $this->ensureArray($item->data_input);

            $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
            $volumeSm3 = $volumeFlowMeter * floatval($pricingInfo['koreksi_meter'] ?? $user->koreksi_meter);
            return $volumeSm3 * floatval($pricingInfo['harga_per_meter_kubik'] ?? $user->harga_per_meter_kubik);
        });

        // Calculate total deposits for the filtered period
        $filteredTotalDeposits = 0;
        $depositHistory = $this->ensureArray($user->deposit_history);

        foreach ($depositHistory as $deposit) {
            if (isset($deposit['date'])) {
                $depositDate = Carbon::parse($deposit['date']);
                if ($depositDate->month == $bulan && $depositDate->year == $tahun) {
                    $filteredTotalDeposits += floatval($deposit['amount'] ?? 0);
                }
            }
        }

        return view('demo.admin', [
            'customer' => $user,
            'dataPencatatan' => $dataPencatatan,
            'depositHistory' => $user->deposit_history ?? [],
            'totalDeposit' => $user->total_deposit,
            'totalPurchases' => $user->total_purchases,
            'currentBalance' => $user->getCurrentBalance(),
            'selectedBulan' => $bulan,
            'selectedTahun' => $tahun,
            'pricingInfo' => $pricingInfo,
            'totalVolumeSm3' => $totalVolumeSm3,
            'filteredVolumeSm3' => $filteredVolumeSm3,
            'filteredTotalPurchases' => $filteredTotalPurchases,
            'filteredTotalDeposits' => $filteredTotalDeposits
        ]);
    }

    // Demo Customer Dashboard (Similar to customer.blade)
    public function demoCustomer(Request $request)
    {
        $user = Auth::user();

        // Get filter parameters
        $selectedBulan = $request->input('bulan', date('m'));
        $selectedTahun = $request->input('tahun', date('Y'));

        // Format filter untuk query
        $yearMonth = $selectedTahun . '-' . str_pad($selectedBulan, 2, '0', STR_PAD_LEFT);

        // Ambil semua data dulu
        $allData = $user->dataPencatatan;

        // Filter data berdasarkan bulan dan tahun dari pembacaan awal
        $dataPencatatan = $allData->filter(function ($item) use ($yearMonth) {
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

        // Urutkan data berdasarkan tanggal pembacaan awal
        $dataPencatatan = $dataPencatatan->sortBy(function ($item) {
            $dataInput = $this->ensureArray($item->data_input);
            return isset($dataInput['pembacaan_awal']['waktu']) ?
                Carbon::parse($dataInput['pembacaan_awal']['waktu'])->timestamp : 0;
        });

        // Calculate total volume SM3 for all time
        $totalVolumeSm3 = $user->getTotalVolumeSm3();

        // Calculate total volume SM3 for filtered period
        $filteredVolumeSm3 = 0;
        foreach ($dataPencatatan as $item) {
            $dataInput = $this->ensureArray($item->data_input);
            $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
            $volumeSm3 = $volumeFlowMeter * floatval($user->koreksi_meter);
            $filteredVolumeSm3 += $volumeSm3;
        }

        // Calculate total purchases for the filtered period
        $filteredTotalPurchases = $dataPencatatan->sum(function ($item) use ($user) {
            $dataInput = $this->ensureArray($item->data_input);
            $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
            $volumeSm3 = $volumeFlowMeter * floatval($user->koreksi_meter);
            return $volumeSm3 * floatval($user->harga_per_meter_kubik);
        });

        $totalTagihan = $allData->sum('harga_final');
        $belumLunas = $allData->where('status_pembayaran', 'belum_lunas')->count();

        return view('demo.customer', [
            'dataPencatatan' => $dataPencatatan,
            'totalTagihan' => $totalTagihan,
            'belumLunas' => $belumLunas,
            'selectedBulan' => $selectedBulan,
            'selectedTahun' => $selectedTahun,
            'totalVolumeSm3' => $totalVolumeSm3,
            'filteredVolumeSm3' => $filteredVolumeSm3,
            'filteredTotalPurchases' => $filteredTotalPurchases
        ]);
    }

    // Filter by Month and Year
    public function filterByMonthYear(Request $request)
    {
        $validatedData = $request->validate([
            'bulan' => 'required|numeric|between:1,12',
            'tahun' => 'required|numeric|between:2000,2100'
        ]);

        $viewType = $request->input('view_type', 'admin');

        if ($viewType === 'admin') {
            return redirect()->route('demo.admin', [
                'bulan' => $validatedData['bulan'],
                'tahun' => $validatedData['tahun']
            ]);
        } else {
            return redirect()->route('demo.customer', [
                'bulan' => $validatedData['bulan'],
                'tahun' => $validatedData['tahun']
            ]);
        }
    }

    // Create data pencatatan
    public function create()
    {
        $user = Auth::user();

        // Get the latest reading data for this user
        $latestVolume = null;
        $latestDate = null;

        // Find the latest entry
        $latestEntry = $user->dataPencatatan()
            ->get()
            ->filter(function ($item) {
                $dataInput = $this->ensureArray($item->data_input);
                return !empty($dataInput) && !empty($dataInput['pembacaan_akhir']['waktu']) && isset($dataInput['pembacaan_akhir']['volume']);
            })
            ->sortByDesc(function ($item) {
                $dataInput = $this->ensureArray($item->data_input);
                return Carbon::parse($dataInput['pembacaan_akhir']['waktu'])->timestamp;
            })
            ->first();

        if ($latestEntry) {
            $dataInput = $this->ensureArray($latestEntry->data_input);
            $latestVolume = floatval($dataInput['pembacaan_akhir']['volume'] ?? 0);
            $latestDate = Carbon::parse($dataInput['pembacaan_akhir']['waktu'])->format('d-m-Y H:i');
            session()->flash('success', 'Data pembacaan terakhir berhasil diambil');
        }

        return view('demo.create', [
            'user' => $user,
            'latestVolume' => $latestVolume,
            'latestDate' => $latestDate
        ]);
    }

    // Update pricing settings
    public function updatePricing(Request $request)
    {
        $user = Auth::user();

        $validatedData = $request->validate([
            'pricing_date' => 'required|date_format:Y-m',
            'harga_per_meter_kubik' => 'required|numeric|min:0',
            'tekanan_keluar' => 'required|numeric|min:0',
            'suhu' => 'required|numeric',
            'koreksi_meter' => 'required|numeric|min:0'
        ]);

        // Parse pricing date to Carbon instance
        $pricingDate = Carbon::createFromFormat('Y-m', $validatedData['pricing_date'])->startOfMonth();

        // Add pricing history
        $result = $user->addPricingHistory(
            $validatedData['harga_per_meter_kubik'],
            $validatedData['tekanan_keluar'],
            $validatedData['suhu'],
            $validatedData['koreksi_meter'],
            $pricingDate
        );

        if ($result) {
            return redirect()->route('demo.admin')
                ->with('success', 'Pengaturan harga dan koreksi meter berhasil disimpan');
        } else {
            return redirect()->route('demo.admin')
                ->with('error', 'Terjadi kesalahan saat menyimpan pengaturan harga dan koreksi meter');
        }
    }

    // Add deposit
    public function addDeposit(Request $request)
    {
        $user = Auth::user();

        $validatedData = $request->validate([
            'amount' => 'required|numeric|min:0',
            'deposit_date' => 'required|date',
            'description' => 'nullable|string|max:255'
        ]);

        $depositDate = Carbon::parse($validatedData['deposit_date']);

        $result = $user->addDeposit(
            $validatedData['amount'],
            $validatedData['description'],
            $depositDate
        );

        if ($result) {
            return redirect()->route('demo.admin')
                ->with('success', 'Deposit berhasil ditambahkan');
        } else {
            return redirect()->route('demo.admin')
                ->with('error', 'Terjadi kesalahan saat menambahkan deposit');
        }
    }

    // Remove deposit
    public function removeDeposit($index)
    {
        $user = Auth::user();

        $result = $user->removeDeposit($index);

        if ($result) {
            return redirect()->route('demo.admin')
                ->with('success', 'Deposit berhasil dihapus');
        } else {
            return redirect()->route('demo.admin')
                ->with('error', 'Terjadi kesalahan saat menghapus deposit');
        }
    }

    // Store data pencatatan
    public function store(Request $request)
    {
        $user = Auth::user();

        $validatedData = $request->validate([
            'data_input' => 'required|array'
        ]);

        // Flatten and sanitize data input
        $sanitizedDataInput = $this->sanitizeDataInput($validatedData['data_input']);

        // Validate specific input requirements
        $this->validateDataInput($sanitizedDataInput);

        // Process skipped dates
        $this->processSkippedDates($user, $sanitizedDataInput);

        // Konversi data input ke JSON
        $dataInput = json_encode($sanitizedDataInput);

        // Buat data pencatatan baru
        $dataPencatatan = new DataPencatatan();
        $dataPencatatan->customer_id = $user->id;
        $dataPencatatan->data_input = $dataInput;
        $dataPencatatan->nama_customer = $user->name;
        $dataPencatatan->status_pembayaran = 'belum_lunas'; // Default status

        // Hitung harga otomatis
        $dataPencatatan->hitungHarga();

        $dataPencatatan->save();

        // Rekalkulasi total pembelian customer setelah menambah data baru
        app(UserController::class)->rekalkulasiTotalPembelian($user);

        return redirect()->route('demo.admin', ['refresh' => true])
            ->with('success', 'Data berhasil disimpan');
    }

    // Process skipped dates and create entries for them
    private function processSkippedDates(User $user, array $currentData)
    {
        // Mendapatkan data pencatatan terakhir sebelum data saat ini
        $latestEntry = $user->dataPencatatan()
            ->get()
            ->filter(function ($item) use ($currentData) {
                $dataInput = $this->ensureArray($item->data_input);

                // Skip jika data tidak lengkap
                if (empty($dataInput) || empty($dataInput['pembacaan_akhir']['waktu'])) {
                    return false;
                }

                // Cek apakah tanggal akhir dari data sebelumnya berada sebelum tanggal awal data saat ini
                $waktuAkhir = Carbon::parse($dataInput['pembacaan_akhir']['waktu']);
                $currentWaktuAwal = Carbon::parse($currentData['pembacaan_awal']['waktu']);

                return $waktuAkhir->lt($currentWaktuAwal);
            })
            ->sortByDesc(function ($item) {
                $dataInput = $this->ensureArray($item->data_input);
                return isset($dataInput['pembacaan_akhir']['waktu']) ?
                    Carbon::parse($dataInput['pembacaan_akhir']['waktu'])->timestamp : 0;
            })
            ->first();

        // Jika tidak ada data sebelumnya, tidak ada yang perlu diproses
        if (!$latestEntry) {
            return;
        }

        $latestData = $this->ensureArray($latestEntry->data_input);

        // Cek apakah ada gap antara data terakhir dan data saat ini
        $latestEndDate = Carbon::parse($latestData['pembacaan_akhir']['waktu']);
        $currentStartDate = Carbon::parse($currentData['pembacaan_awal']['waktu']);

        // Jika gap kurang dari 1 hari, tidak perlu diproses
        if ($latestEndDate->diffInDays($currentStartDate) < 1) {
            return;
        }
    }

    // Metode sanitizeDataInput untuk memproses dan membersihkan data input
    private function sanitizeDataInput(array $dataInput)
    {
        $sanitizedData = [];

        // Pembacaan Awal
        if (isset($dataInput['pembacaan_awal'])) {
            $sanitizedData['pembacaan_awal'] = [
                'waktu' => $dataInput['pembacaan_awal']['waktu'] ?? now()->format('Y-m-d H:i:s'),
                'volume' => floatval($dataInput['pembacaan_awal']['volume'] ?? 0)
            ];
        }

        // Pembacaan Akhir
        if (isset($dataInput['pembacaan_akhir'])) {
            $sanitizedData['pembacaan_akhir'] = [
                'waktu' => $dataInput['pembacaan_akhir']['waktu'] ?? now()->format('Y-m-d H:i:s'),
                'volume' => floatval($dataInput['pembacaan_akhir']['volume'] ?? 0)
            ];
        }

        // Selisih volume flow meter
        if (isset($dataInput['volume_flow_meter'])) {
            $sanitizedData['volume_flow_meter'] = floatval($dataInput['volume_flow_meter']);
        } else {
            // Calculate volume_flow_meter if not provided
            $volumeAwal = $sanitizedData['pembacaan_awal']['volume'] ?? 0;
            $volumeAkhir = $sanitizedData['pembacaan_akhir']['volume'] ?? 0;
            $sanitizedData['volume_flow_meter'] = max(0, $volumeAkhir - $volumeAwal);
        }

        // Notes
        if (isset($dataInput['notes'])) {
            $sanitizedData['notes'] = trim($dataInput['notes']);
        }

        return $sanitizedData;
    }

    // Metode validateDataInput untuk validasi data input
    private function validateDataInput(array $dataInput)
    {
        $errors = [];

        // Validate required fields
        if (!isset($dataInput['pembacaan_awal']['waktu'])) {
            $errors[] = 'Waktu pembacaan awal harus diisi';
        }

        if (!isset($dataInput['pembacaan_akhir']['waktu'])) {
            $errors[] = 'Waktu pembacaan akhir harus diisi';
        }

        // Validate volume
        if ($dataInput['volume_flow_meter'] <= 0) {
            $errors[] = 'Selisih volume harus lebih besar dari 0';
        }

        // Check if end date is after start date
        $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
        $waktuAkhir = Carbon::parse($dataInput['pembacaan_akhir']['waktu']);

        if ($waktuAkhir->lessThanOrEqualTo($waktuAwal)) {
            $errors[] = 'Waktu pembacaan akhir harus setelah waktu pembacaan awal';
        }

        // If there are errors, throw validation exception
        if (!empty($errors)) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }

        return true;
    }
}
