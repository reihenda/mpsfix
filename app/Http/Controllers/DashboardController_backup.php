<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\DataPencatatan;
use App\Models\User;
use App\Models\RekapPengambilan;
use App\Models\HargaGagas;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // Dashboard untuk SuperAdmin
    public function superadminDashboard()
    {
        $totalUsers = User::count();
        $totalPencatatan = DataPencatatan::count();
        $totalPendapatan = DataPencatatan::sum('harga_final');

        // Fetch all customers (users with role 'customer')
        $customers = User::where('role', 'customer')->orderBy('created_at', 'desc')->get();

        // Fetch all FOBs (users with role 'fob')
        $fobs = User::where('role', 'fob')->orderBy('created_at', 'desc')->get();

        return view('dashboard.superadmin', compact(
            'totalUsers',
            'totalPencatatan',
            'totalPendapatan',
            'customers',
            'fobs'
        ));
    }

    // Dashboard untuk Admin - Kombinasi Rekap Pengambilan dan Rekap Penjualan
    public function adminDashboard(Request $request)
    {
        // Filter Tahun
        $tahunIni = date('Y');
        $selectedTahun = $request->input('tahun', $tahunIni);

        // Filter Bulan
        $bulanIni = date('m');
        $selectedBulan = $request->input('bulan', $bulanIni);

        // Format untuk filter bulanan
        $yearMonth = $selectedTahun . '-' . str_pad($selectedBulan, 2, '0', STR_PAD_LEFT);
        $tanggal = $request->input('tanggal', now()->format('Y-m-d'));

        // 1. Data dari Rekap Penjualan
        // Mendapatkan semua customer (biasa dan FOB)
        $customersDb = User::where('role', 'customer')->get()->toArray();
        $fobDb = User::where('role', 'fob')->get()->toArray();
        $customers = array_merge($customersDb, $fobDb);

        // Data Penjualan untuk periode tahunan
        $penjualanTahunanData = $this->calculateYearlyData($customers, $selectedTahun);

        // Data Penjualan untuk periode bulanan
        $penjualanBulananData = $this->calculateMonthlyData($customers, $yearMonth);

        // Data untuk tabel customer
        $customerPenjualanData = $this->calculateCustomerData($customers, $selectedTahun, $selectedBulan);
        // Data untuk grafik tahunan (data per bulan)
        $yearlyChartData = $this->getYearlyChartData($customers, $selectedTahun);

        // Data untuk grafik bulanan (data per hari)
        $monthlyChartData = $this->getMonthlyChartData($customers, $selectedTahun, $selectedBulan);

        // 2. Data dari Rekap Pengambilan
        // Extract month and year from the selected date
        $selectedDate = Carbon::parse($tanggal);
        $bulan = $selectedDate->month;
        $tahun = $selectedDate->year;

        // Ambil data rekap pengambilan sesuai filter
        $rekapPengambilan = RekapPengambilan::where(function ($query) use ($selectedBulan, $selectedTahun) {
            $query->whereMonth('tanggal', $selectedBulan)
                ->whereYear('tanggal', $selectedTahun);
        })->with('customer')->get();

        // Hitung total volume bulanan pengambilan
        $totalVolumeBulanan = 0;
        foreach ($rekapPengambilan as $rekap) {
            $totalVolumeBulanan += $rekap->volume;
        }

        // Hitung total volume harian berdasarkan tanggal yang dipilih
        $rekapPengambilanHarian = RekapPengambilan::whereDate('tanggal', $tanggal)->get();
        $totalVolumeHarian = 0;
        foreach ($rekapPengambilanHarian as $rekap) {
            $totalVolumeHarian += $rekap->volume;
        }

        // Mendapatkan list tahun untuk dropdown filter
        $availableYears = $this->getAvailableYears();

        // Kelompokkan data pengambilan per customer
        $customerPengambilanData = [];
        foreach ($customers as $customer) {
            $rekapCustomer = RekapPengambilan::where('customer_id', $customer['id'])
                ->whereYear('tanggal', $selectedTahun)
                ->get();

            $volumeTahun = 0;
            foreach ($rekapCustomer as $rekap) {
                $volumeTahun += $rekap->volume;
            }

            $rekapCustomerBulan = RekapPengambilan::where('customer_id', $customer['id'])
                ->whereYear('tanggal', $selectedTahun)
                ->whereMonth('tanggal', $selectedBulan)
                ->get();

            $volumeBulan = 0;
            foreach ($rekapCustomerBulan as $rekap) {
                $volumeBulan += $rekap->volume;
            }

            if ($volumeTahun > 0 || $volumeBulan > 0) {
                $customerPengambilanData[] = [
                    'nama' => $customer['name'],
                    'role' => $customer['role'],
                    'volume_tahun' => round($volumeTahun, 2),
                    'volume_bulan' => round($volumeBulan, 2)
                ];
            }
        }

        // 3. Data Summary - Menghitung Profit
        $summaryData = $this->calculateSummaryData($customers, $selectedTahun, $selectedBulan);

        // 4. Data untuk grafik profit tahunan
        $profitChartData = $this->getProfitChartData($customers, $selectedTahun);

        // 5. Data Rekap Pembelian (dari RekapPembelianController logic)
        $rekapPembelianData = $this->getRekapPembelianData($selectedTahun, $selectedBulan);
        $customerRekapPembelianData = $this->getCustomerRekapPembelianData($selectedTahun, $selectedBulan);

        // 6. Data chart untuk rekap pembelian
        $rekapPembelianYearlyChartData = $this->getRekapPembelianYearlyChartData($selectedTahun);
        $rekapPembelianMonthlyChartData = $this->getRekapPembelianMonthlyChartData($selectedTahun, $selectedBulan);

        return view('dashboard.admin', compact(
            'penjualanTahunanData',
            'penjualanBulananData',
            'customerPenjualanData',
            'rekapPengambilan',
            'totalVolumeBulanan',
            'totalVolumeHarian',
            'customerPengambilanData',
            'selectedTahun',
            'selectedBulan',
            'tanggal',
            'availableYears',
            'yearlyChartData',
            'monthlyChartData',
            'summaryData',
            'profitChartData',
            'rekapPembelianData',
            'customerRekapPembelianData',
            'rekapPembelianYearlyChartData',
            'rekapPembelianMonthlyChartData'
        ));
    }

    // Dashboard untuk Customer
    public function customerDashboard(Request $request)
    {
        $user = Auth::user();

        // Get filter parameters
        $selectedBulan = $request->input('bulan', date('m'));
        $selectedTahun = $request->input('tahun', date('Y'));

        // Format filter untuk query
        $yearMonth = $selectedTahun . '-' . str_pad($selectedBulan, 2, '0', STR_PAD_LEFT);

        // Helper function to ensure data is array (same as DataPencatatanController)
        $ensureArray = function ($data) {
            if (is_string($data)) {
                return json_decode($data, true) ?? [];
            }
            if (is_array($data)) {
                return $data;
            }
            return [];
        };

        // Ambil semua data dulu
        $allData = $user->dataPencatatan;

        // Filter data berdasarkan bulan dan tahun dari pembacaan awal
        $dataPencatatan = $allData->filter(function ($item) use ($yearMonth, $ensureArray) {
            $dataInput = $ensureArray($item->data_input);

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
        $dataPencatatan = $dataPencatatan->sortBy(function ($item) use ($ensureArray) {
            $dataInput = $ensureArray($item->data_input);
            return isset($dataInput['pembacaan_awal']['waktu']) ?
                Carbon::parse($dataInput['pembacaan_awal']['waktu'])->timestamp : 0;
        });

        // Get pricing info for selected month (dynamic pricing)
        $pricingInfo = $user->getPricingForYearMonth($yearMonth);

        // Calculate total volume SM3 for all time
        $totalVolumeSm3 = $user->getTotalVolumeSm3();

        // Calculate total volume SM3 for filtered period
        $filteredVolumeSm3 = 0;
        foreach ($dataPencatatan as $item) {
            $dataInput = $ensureArray($item->data_input);

            // Ambil waktu untuk mendapatkan pricing yang tepat
            $waktuAwalYearMonth = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m');
            $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
            $itemPricingInfo = $user->getPricingForYearMonth($waktuAwalYearMonth, $waktuAwal);

            $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
            $volumeSm3 = $volumeFlowMeter * floatval($itemPricingInfo['koreksi_meter'] ?? $user->koreksi_meter);
            $filteredVolumeSm3 += $volumeSm3;
        }

        // Calculate total purchases for the filtered period
        $filteredTotalPurchases = 0;
        foreach ($dataPencatatan as $item) {
            $dataInput = $ensureArray($item->data_input);

            // Ambil waktu untuk mendapatkan pricing yang tepat
            $waktuAwalYearMonth = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m');
            $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
            $itemPricingInfo = $user->getPricingForYearMonth($waktuAwalYearMonth, $waktuAwal);

            $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
            $volumeSm3 = $volumeFlowMeter * floatval($itemPricingInfo['koreksi_meter'] ?? $user->koreksi_meter);
            $filteredTotalPurchases += $volumeSm3 * floatval($itemPricingInfo['harga_per_meter_kubik'] ?? $user->harga_per_meter_kubik);
        }

        // Calculate total deposits for the filtered period (current month only)
        $filteredTotalDeposits = 0;
        $depositHistory = $ensureArray($user->deposit_history);

        // Format bulan saat ini untuk perbandingan konsisten
        $currentYearMonth = $selectedTahun . '-' . str_pad($selectedBulan, 2, '0', STR_PAD_LEFT);

        foreach ($depositHistory as $deposit) {
            if (isset($deposit['date'])) {
                $depositDate = Carbon::parse($deposit['date']);
                // Pastikan hanya deposit pada bulan dan tahun yang dipilih menggunakan format yang konsisten
                if ($depositDate->format('Y-m') === $currentYearMonth) {
                    $amount = floatval($deposit['amount'] ?? 0);
                    $keterangan = $deposit['keterangan'] ?? 'penambahan';

                    // Handle deposit dan pengurangan dengan benar
                    if ($keterangan === 'pengurangan') {
                        // Jika keterangan pengurangan, kurangi dari total deposit
                        $filteredTotalDeposits -= abs($amount);
                    } else {
                        // Jika keterangan penambahan, tambahkan
                        $filteredTotalDeposits += $amount;
                    }
                }
            }
        }

        // Mendapatkan bulan sebelumnya
        $prevDate = Carbon::createFromDate($selectedTahun, $selectedBulan, 1)->subMonth();
        $prevYearMonth = $prevDate->format('Y-m');

        // Hitung saldo bulan sebelumnya secara real-time
        $realTimePrevMonthBalance = 0;

        // 1. Hitung semua deposit dan pengurangan sampai akhir bulan sebelumnya
        $deposits = $ensureArray($user->deposit_history);
        foreach ($deposits as $deposit) {
            if (isset($deposit['date'])) {
                $depositDate = Carbon::parse($deposit['date']);
                // Ambil deposit sampai akhir bulan sebelumnya
                if ($depositDate->format('Y-m') <= $prevYearMonth) {
                    $amount = floatval($deposit['amount'] ?? 0);
                    $keterangan = $deposit['keterangan'] ?? 'penambahan';

                    // Handle deposit dan pengurangan dengan benar
                    if ($keterangan === 'pengurangan') {
                        // Jika keterangan pengurangan, pastikan amount negatif
                        $realTimePrevMonthBalance -= abs($amount);
                    } else {
                        // Jika keterangan penambahan, tambahkan (bisa positif atau negatif)
                        $realTimePrevMonthBalance += $amount;
                    }
                }
            }
        }

        // 2. Kurangi semua pembelian sampai akhir bulan sebelumnya
        $allDataPencatatan = $user->dataPencatatan()->get();
        foreach ($allDataPencatatan as $purchaseItem) {
            $itemDataInput = $ensureArray($purchaseItem->data_input);
            if (empty($itemDataInput) || empty($itemDataInput['pembacaan_awal']['waktu'])) {
                continue;
            }

            $itemWaktuAwal = Carbon::parse($itemDataInput['pembacaan_awal']['waktu']);

            // Ambil pembelian sampai akhir bulan sebelumnya
            if ($itemWaktuAwal->format('Y-m') <= $prevYearMonth) {
                $volumeFlowMeter = floatval($itemDataInput['volume_flow_meter'] ?? 0);

                // Ambil pricing yang sesuai (bulanan atau periode khusus)
                $itemYearMonth = $itemWaktuAwal->format('Y-m');
                $itemPricingInfo = $user->getPricingForYearMonth($itemYearMonth, $itemWaktuAwal);

                // Hitung volume dan harga
                $itemKoreksiMeter = floatval($itemPricingInfo['koreksi_meter'] ?? $user->koreksi_meter);
                $itemHargaPerM3 = floatval($itemPricingInfo['harga_per_meter_kubik'] ?? $user->harga_per_meter_kubik);
                $itemVolumeSm3 = $volumeFlowMeter * $itemKoreksiMeter;
                $itemHarga = $itemVolumeSm3 * $itemHargaPerM3;

                $realTimePrevMonthBalance -= $itemHarga;
            }
        }

        // Hitung saldo bulan ini menggunakan saldo bulan sebelumnya yang real-time
        $realTimeCurrentMonthBalance = $realTimePrevMonthBalance + $filteredTotalDeposits - $filteredTotalPurchases;

        $totalTagihan = $allData->sum('harga_final');
        $belumLunas = $allData->where('status_pembayaran', 'belum_lunas')->count();

        return view('dashboard.customer', compact(
            'dataPencatatan',
            'totalTagihan',
            'belumLunas',
            'selectedBulan',
            'selectedTahun',
            'totalVolumeSm3',
            'filteredVolumeSm3',
            'filteredTotalPurchases',
            'filteredTotalDeposits',
            'pricingInfo',
            'realTimePrevMonthBalance',
            'realTimeCurrentMonthBalance'
        ));
    }

    // Dashboard untuk FOB (FIXED)
    public function fobDashboard(Request $request)
    {
        $user = Auth::user();

        // Verify user is FOB
        if (!$user->isFOB()) {
            return redirect()->route('login')->with('error', 'Akses ditolak. Anda bukan customer FOB.');
        }

        // Get filter parameters
        $selectedBulan = $request->input('bulan', date('m'));
        $selectedTahun = $request->input('tahun', date('Y'));

        // Format filter untuk query
        $yearMonth = $selectedTahun . '-' . str_pad($selectedBulan, 2, '0', STR_PAD_LEFT);
        $startDate = Carbon::createFromDate($selectedTahun, $selectedBulan, 1)->startOfDay();
        $endDate = Carbon::createFromDate($selectedTahun, $selectedBulan, 1)->endOfMonth()->endOfDay();

        // Helper function to ensure data is array
        $ensureArray = function ($data) {
            if (is_string($data)) {
                return json_decode($data, true) ?? [];
            }
            if (is_array($data)) {
                return $data;
            }
            return [];
        };

        // Ambil semua data FOB
        $allData = $user->dataPencatatan()->get();

        // Filter data berdasarkan bulan dan tahun untuk FOB (menggunakan 'waktu' bukan 'pembacaan_awal')
        $dataPencatatan = $allData->filter(function ($item) use ($startDate, $endDate, $ensureArray) {
            $dataInput = $ensureArray($item->data_input);

            // FOB menggunakan format 'waktu' langsung, bukan 'pembacaan_awal'
            if (empty($dataInput) || empty($dataInput['waktu'])) {
                return false;
            }

            try {
                $dataDate = Carbon::parse($dataInput['waktu']);
                return $dataDate->between($startDate, $endDate);
            } catch (\Exception $e) {
                return false;
            }
        });

        // Get pricing info for selected month
        $pricingInfo = $user->getPricingForYearMonth($yearMonth);

        // Calculate total volume SM3 for all time (FOB format)
        $totalVolumeSm3 = 0;
        foreach ($allData as $item) {
            $dataInput = $ensureArray($item->data_input);
            $totalVolumeSm3 += floatval($dataInput['volume_sm3'] ?? 0);
        }

        // Calculate filtered metrics
        $filteredVolumeSm3 = 0;
        $filteredTotalPurchases = 0;

        foreach ($dataPencatatan as $item) {
            $dataInput = $ensureArray($item->data_input);
            $volumeSm3 = floatval($dataInput['volume_sm3'] ?? 0);
            $filteredVolumeSm3 += $volumeSm3;
            
            // Use harga_final if available, otherwise calculate
            if ($item->harga_final > 0) {
                $filteredTotalPurchases += floatval($item->harga_final);
            } else {
                // Calculate based on current pricing
                $recordDate = Carbon::parse($dataInput['waktu']);
                $recordYearMonth = $recordDate->format('Y-m');
                $itemPricingInfo = $user->getPricingForYearMonth($recordYearMonth, $recordDate);
                $hargaPerM3 = floatval($itemPricingInfo['harga_per_meter_kubik'] ?? $user->harga_per_meter_kubik);
                $filteredTotalPurchases += ($volumeSm3 * $hargaPerM3);
            }
        }

        // Calculate deposits for filtered period
        $filteredTotalDeposits = 0;
        $depositHistory = $ensureArray($user->deposit_history);

        foreach ($depositHistory as $deposit) {
            if (isset($deposit['date'])) {
                $depositDate = Carbon::parse($deposit['date']);
                if ($depositDate->format('Y-m') === $yearMonth) {
                    $amount = floatval($deposit['amount'] ?? 0);
                    $keterangan = $deposit['keterangan'] ?? 'penambahan';

                    if ($keterangan === 'pengurangan') {
                        $filteredTotalDeposits -= abs($amount);
                    } else {
                        $filteredTotalDeposits += $amount;
                    }
                }
            }
        }

        // Calculate real-time balances
        $prevDate = Carbon::createFromDate($selectedTahun, $selectedBulan, 1)->subMonth();
        $prevYearMonth = $prevDate->format('Y-m');
        
        // Previous month balance calculation
        $realTimePrevMonthBalance = 0;
        
        // Sum deposits until previous month
        foreach ($depositHistory as $deposit) {
            if (isset($deposit['date'])) {
                $depositDate = Carbon::parse($deposit['date']);
                if ($depositDate->format('Y-m') <= $prevYearMonth) {
                    $amount = floatval($deposit['amount'] ?? 0);
                    $keterangan = $deposit['keterangan'] ?? 'penambahan';

                    if ($keterangan === 'pengurangan') {
                        $realTimePrevMonthBalance -= abs($amount);
                    } else {
                        $realTimePrevMonthBalance += $amount;
                    }
                }
            }
        }
        
        // Subtract purchases until previous month
        foreach ($allData as $item) {
            $dataInput = $ensureArray($item->data_input);
            if (empty($dataInput) || empty($dataInput['waktu'])) {
                continue;
            }
            
            try {
                $itemDate = Carbon::parse($dataInput['waktu']);
                if ($itemDate->format('Y-m') <= $prevYearMonth) {
                    $realTimePrevMonthBalance -= floatval($item->harga_final ?? 0);
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        // Current month balance
        $realTimeCurrentMonthBalance = $realTimePrevMonthBalance + $filteredTotalDeposits - $filteredTotalPurchases;

        $totalTagihan = $allData->sum('harga_final');
        $belumLunas = $allData->where('status_pembayaran', 'belum_lunas')->count();

        return view('dashboard.fob', compact(
            'dataPencatatan',
            'totalTagihan',
            'belumLunas',
            'selectedBulan',
            'selectedTahun',
            'totalVolumeSm3',
            'filteredVolumeSm3',
            'filteredTotalPurchases',
            'filteredTotalDeposits',
            'pricingInfo',
            'realTimePrevMonthBalance',
            'realTimeCurrentMonthBalance'
        ));
    }

    // Private methods untuk helper functions - simplified
    private function calculateYearlyData($customers, $tahun) { return ['total_pemakaian' => 0, 'total_pembelian' => 0]; }
    private function calculateMonthlyData($customers, $yearMonth) { return ['total_pemakaian' => 0, 'total_pembelian' => 0]; }
    private function calculateCustomerData($customers, $tahun, $bulan) { return []; }
    private function getAvailableYears() { return [date('Y')]; }
    private function getYearlyChartData($customers, $tahun) { return ['pemakaian' => [], 'penjualan' => []]; }
    private function getMonthlyChartData($customers, $tahun, $bulan) { return ['pemakaian' => [], 'penjualan' => []]; }
    private function calculateSummaryData($customers, $tahun, $bulan) { return []; }
    private function getProfitChartData($customers, $tahun) { return ['profit' => []]; }
    private function getRekapPembelianData($tahun, $bulan) { return ['total_pengambilan' => 0, 'total_pembelian' => 0]; }
    private function calculateTotalPembelianFromHargaGagas($tahun, $bulan = null) { return 0; }
    private function getHargaGagasWithFallback($tahun, $bulan) { return null; }
    private function getCustomerRekapPembelianData($tahun, $bulan) { return []; }
    private function getRekapPembelianYearlyChartData($tahun) { return ['pengambilan' => [], 'pembelian' => []]; }
    private function getRekapPembelianMonthlyChartData($tahun, $bulan) { return ['pengambilan' => [], 'pembelian' => []]; }
}