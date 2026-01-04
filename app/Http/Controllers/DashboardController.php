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

    // Menghitung data untuk periode tahunan
    private function calculateYearlyData($customers, $tahun)
    {
        $totalPemakaian = 0;
        $totalPembelian = 0;

        foreach ($customers as $customer) {
            // Untuk semua customer (biasa atau FOB) ambil data pencatatan dari database
            $dataPencatatan = DataPencatatan::where('customer_id', $customer['id'])->get();

            // Filter berdasarkan tahun dari data_input, bukan created_at
            $dataPencatatan = $dataPencatatan->filter(function ($item) use ($tahun, $customer) {
                $dataInput = json_decode($item->data_input, true) ?? [];

                // Jika data input kosong, skip
                if (empty($dataInput)) {
                    return false;
                }

                // Periksa format data berdasarkan role customer
                if (isset($customer['role']) && $customer['role'] === 'fob' && isset($dataInput['waktu'])) {
                    // Format FOB - menggunakan kunci 'waktu'
                    $dataTahun = Carbon::parse($dataInput['waktu'])->format('Y');
                    return $dataTahun == $tahun;
                } else if (!empty($dataInput['pembacaan_awal']['waktu'])) {
                    // Format customer biasa - menggunakan kunci 'pembacaan_awal.waktu'
                    $dataTahun = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y');
                    return $dataTahun == $tahun;
                }

                return false; // Skip jika tidak ada data waktu yang valid
            });

            foreach ($dataPencatatan as $item) {
                $dataInput = json_decode($item->data_input, true) ?? [];

                // Periksa apakah ini FOB atau customer biasa berdasarkan format data_input dan role
                if (isset($customer['role']) && $customer['role'] === 'fob' && isset($dataInput['volume_sm3'])) {
                    // FOB format data (simpler)
                    $volumeSm3 = floatval($dataInput['volume_sm3']);
                    // Gunakan harga per meter kubik yang sesuai untuk FOB
                    $hargaPerMeterKubik = floatval($customer['harga_per_meter_kubik'] ?? 0);
                    $pembelian = $volumeSm3 * $hargaPerMeterKubik;
                } else {
                    // Customer biasa format data
                    $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
                    $koreksiMeter = floatval($customer['koreksi_meter'] ?? 1.0);
                    $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
                    // Gunakan harga per meter kubik yang sesuai untuk customer biasa
                    $hargaPerMeterKubik = floatval($customer['harga_per_meter_kubik'] ?? 0);
                    $pembelian = $volumeSm3 * $hargaPerMeterKubik;
                }

                $totalPemakaian += $volumeSm3;
                $totalPembelian += $pembelian; // Gunakan hasil perhitungan bukan harga_final dari database
            }
        }

        // Log untuk debugging
        \Log::info("Rekap Penjualan - Tahunan ($tahun): Pemakaian: $totalPemakaian Sm³, Pembelian: Rp " . number_format($totalPembelian, 0));

        return [
            'total_pemakaian' => round($totalPemakaian, 2),
            'total_pembelian' => round($totalPembelian, 0) // Bulatkan ke angka bulat untuk Rupiah
        ];
    }

    // Menghitung data untuk periode bulanan
    private function calculateMonthlyData($customers, $yearMonth)
    {
        $totalPemakaian = 0;
        $totalPembelian = 0;

        foreach ($customers as $customer) {
            // Ambil semua data pencatatan untuk customer ini
            $dataPencatatan = DataPencatatan::where('customer_id', $customer['id'])->get();

            // Filter data berdasarkan bulan dan tahun
            $filteredPencatatan = $dataPencatatan->filter(function ($item) use ($yearMonth, $customer) {
                $dataInput = json_decode($item->data_input, true) ?? [];

                // Jika data input kosong, skip
                if (empty($dataInput)) {
                    return false;
                }

                // Periksa format data (FOB atau customer biasa)
                if (isset($customer['role']) && $customer['role'] === 'fob' && isset($dataInput['waktu'])) {
                    // Format FOB - menggunakan kunci 'waktu'
                    $waktu = \Carbon\Carbon::parse($dataInput['waktu'])->format('Y-m');
                    return $waktu === $yearMonth;
                } else if (!empty($dataInput['pembacaan_awal']['waktu'])) {
                    // Format customer biasa - menggunakan kunci 'pembacaan_awal.waktu'
                    $waktuAwal = \Carbon\Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m');
                    return $waktuAwal === $yearMonth;
                }

                return false; // Skip jika tidak ada data waktu yang valid
            });

            foreach ($filteredPencatatan as $item) {
                $dataInput = json_decode($item->data_input, true) ?? [];

                // Jika data input kosong, skip
                if (empty($dataInput)) {
                    continue;
                }
                if (isset($customer['role']) && $customer['role'] === 'fob' && isset($dataInput['volume_sm3'])) {
                    // FOB format data (simpler)
                    $volumeSm3 = floatval($dataInput['volume_sm3']);
                    // Gunakan harga per meter kubik yang sesuai untuk FOB
                    $hargaPerMeterKubik = floatval($customer['harga_per_meter_kubik'] ?? 0);
                    $pembelian = $volumeSm3 * $hargaPerMeterKubik;
                } else {
                    // Customer biasa format data
                    $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
                    $koreksiMeter = floatval($customer['koreksi_meter'] ?? 1.0);
                    $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
                    // Gunakan harga per meter kubik yang sesuai untuk customer biasa
                    $hargaPerMeterKubik = floatval($customer['harga_per_meter_kubik'] ?? 0);
                    $pembelian = $volumeSm3 * $hargaPerMeterKubik;
                }

                $totalPemakaian += $volumeSm3;
                $totalPembelian += $pembelian; // Gunakan hasil perhitungan bukan harga_final dari database
            }
        }

        // Log untuk debugging
        \Log::info("Rekap Penjualan - Bulanan ($yearMonth): Pemakaian: $totalPemakaian Sm³, Pembelian: Rp " . number_format($totalPembelian, 0));

        return [
            'total_pemakaian' => round($totalPemakaian, 2),
            'total_pembelian' => round($totalPembelian, 0) // Bulatkan ke angka bulat untuk Rupiah
        ];
    }

    // Menghitung data untuk tabel customer
    private function calculateCustomerData($customers, $tahun, $bulan)
    {
        $customerData = [];
        $yearMonth = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT);

        foreach ($customers as $customer) {
            $pemakaianTahun = 0;
            $pembelianTahun = 0;
            $pemakaianBulan = 0;
            $pembelianBulan = 0;

            // Data untuk tahun - ambil semua data pencatatan untuk customer ini
            $allDataPencatatan = DataPencatatan::where('customer_id', $customer['id'])->get();

            // Filter berdasarkan tahun dari data_input, bukan created_at
            $dataPencatatanTahun = $allDataPencatatan->filter(function ($item) use ($tahun, $customer) {
                $dataInput = json_decode($item->data_input, true) ?? [];

                // Jika data input kosong, skip
                if (empty($dataInput)) {
                    return false;
                }

                // Periksa tipe data pencatatan (FOB atau customer biasa)
                if (isset($customer['role']) && $customer['role'] === 'fob' && isset($dataInput['waktu'])) {
                    // Format FOB - menggunakan kunci 'waktu'
                    $dataTahun = Carbon::parse($dataInput['waktu'])->format('Y');
                    return $dataTahun == $tahun;
                } else if (!empty($dataInput['pembacaan_awal']['waktu'])) {
                    // Format customer biasa - menggunakan kunci 'pembacaan_awal.waktu'
                    $dataTahun = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y');
                    return $dataTahun == $tahun;
                }

                return false; // Skip jika tidak ada data waktu yang valid
            });

            foreach ($dataPencatatanTahun as $item) {
                $dataInput = json_decode($item->data_input, true) ?? [];

                // Periksa apakah ini FOB atau customer biasa berdasarkan format data_input
                if (isset($customer['role']) && $customer['role'] === 'fob' && isset($dataInput['volume_sm3'])) {
                    // FOB format data
                    $volumeSm3 = floatval($dataInput['volume_sm3']);
                    // Gunakan harga per meter kubik yang sesuai untuk FOB
                    $hargaPerMeterKubik = floatval($customer['harga_per_meter_kubik'] ?? 0);
                    $pembelian = $volumeSm3 * $hargaPerMeterKubik;
                } else {
                    // Customer biasa format data
                    $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
                    $koreksiMeter = floatval($customer['koreksi_meter'] ?? 1.0);
                    $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
                    // Gunakan harga per meter kubik yang sesuai untuk customer biasa
                    $hargaPerMeterKubik = floatval($customer['harga_per_meter_kubik'] ?? 0);
                    $pembelian = $volumeSm3 * $hargaPerMeterKubik;
                }

                $pemakaianTahun += $volumeSm3;
                $pembelianTahun += $pembelian;
            }

            // Data untuk bulan - filter berdasarkan waktu pencatatan
            $dataPencatatanBulan = $dataPencatatanTahun->filter(function ($item) use ($yearMonth, $customer) {
                $dataInput = json_decode($item->data_input, true) ?? [];

                // Jika data input kosong, skip
                if (empty($dataInput)) {
                    return false;
                }

                // Periksa format data (FOB atau customer biasa)
                if (isset($customer['role']) && $customer['role'] === 'fob' && isset($dataInput['waktu'])) {
                    // Format FOB - menggunakan kunci 'waktu'
                    $waktu = \Carbon\Carbon::parse($dataInput['waktu'])->format('Y-m');
                    return $waktu === $yearMonth;
                } else if (!empty($dataInput['pembacaan_awal']['waktu'])) {
                    // Format customer biasa - menggunakan kunci 'pembacaan_awal.waktu'
                    $waktuAwal = \Carbon\Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m');
                    return $waktuAwal === $yearMonth;
                }

                return false; // Skip jika tidak ada data waktu yang valid
            });

            foreach ($dataPencatatanBulan as $item) {
                $dataInput = json_decode($item->data_input, true) ?? [];

                // Periksa apakah ini FOB atau customer biasa berdasarkan format data_input
                if (isset($customer['role']) && $customer['role'] === 'fob' && isset($dataInput['volume_sm3'])) {
                    // FOB format data
                    $volumeSm3 = floatval($dataInput['volume_sm3']);
                    // Gunakan harga per meter kubik yang sesuai untuk FOB
                    $hargaPerMeterKubik = floatval($customer['harga_per_meter_kubik'] ?? 0);
                    $pembelian = $volumeSm3 * $hargaPerMeterKubik;
                } else {
                    // Customer biasa format data
                    $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
                    $koreksiMeter = floatval($customer['koreksi_meter'] ?? 1.0);
                    $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
                    // Gunakan harga per meter kubik yang sesuai untuk customer biasa
                    $hargaPerMeterKubik = floatval($customer['harga_per_meter_kubik'] ?? 0);
                    $pembelian = $volumeSm3 * $hargaPerMeterKubik;
                }

                $pemakaianBulan += $volumeSm3;
                $pembelianBulan += $pembelian;
            }

            // Log untuk debugging per customer
            \Log::info("Customer {$customer['name']} - Tahun $tahun: Pemakaian: $pemakaianTahun Sm³, Pembelian: Rp " . number_format($pembelianTahun, 0));
            \Log::info("Customer {$customer['name']} - Bulan $yearMonth: Pemakaian: $pemakaianBulan Sm³, Pembelian: Rp " . number_format($pembelianBulan, 0));

            // Tambahkan semua customer, meskipun belum ada data
            $customerData[] = [
                'nama' => $customer['name'],
                'role' => $customer['role'],
                'pemakaian_tahun' => round($pemakaianTahun, 2),
                'pembelian_tahun' => round($pembelianTahun, 0), // Bulatkan ke angka bulat untuk Rupiah
                'pemakaian_bulan' => round($pemakaianBulan, 2),
                'pembelian_bulan' => round($pembelianBulan, 0)  // Bulatkan ke angka bulat untuk Rupiah
            ];
        }

        return $customerData;
    }

    // Mendapatkan list tahun yang tersedia untuk filter berdasarkan data aktual
    private function getAvailableYears()
    {
        // Ambil semua data pencatatan
        $allData = DataPencatatan::all();
        $years = [];

        // Loop melalui semua data dan ekstrak tahun dari waktu pembacaan awal
        foreach ($allData as $item) {
            $dataInput = json_decode($item->data_input, true) ?? [];

            // Jika data input kosong atau tidak ada waktu awal, skip
            if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                continue;
            }

            // Ambil tahun dari waktu pencatatan
            $tahun = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y');
            $years[] = $tahun;
        }

        // Ambil juga tahun dari rekap pengambilan
        $pengambilanYears = RekapPengambilan::selectRaw('YEAR(tanggal) as year')
            ->distinct()
            ->pluck('year')
            ->toArray();

        $years = array_merge($years, $pengambilanYears);

        // Hapus duplikat, urutkan secara descending
        $years = array_unique($years);
        rsort($years);

        // Jika tidak ada data, gunakan tahun saat ini
        if (empty($years)) {
            $years[] = date('Y'); // Default tahun saat ini jika tidak ada data
        }

        return $years;
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
        
        // DEBUG: Log jumlah data mentah sebelum filter
        \Log::info('Customer Dashboard - Data mentah', [
            'customer_id' => $user->id,
            'total_records' => $allData->count(),
            'year_month' => $yearMonth
        ]);

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
        
        // DEBUG: Log jumlah data setelah filter duplikasi
        \Log::info('Customer Dashboard - Data setelah filter', [
            'customer_id' => $user->id,
            'filtered_records' => $dataPencatatan->count(),
            'duplicates_removed' => $allData->count() - $dataPencatatan->count()
        ]);

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

        // Hitung total tagihan dari semua data pencatatan (untuk backward compatibility)
        $allDataPencatatan = $user->dataPencatatan;
        $totalTagihan = $allDataPencatatan->sum('harga_final');
        $belumLunas = $allDataPencatatan->where('status_pembayaran', 'belum_lunas')->count();

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
    // Dashboard untuk FOB
    public function fobDashboard(Request $request)
    {
        $user = Auth::user();

        // Get filter parameters
        $selectedBulan = $request->input('bulan', date('m'));
        $selectedTahun = $request->input('tahun', date('Y'));

        // Format filter untuk query
        $yearMonth = $selectedTahun . '-' . str_pad($selectedBulan, 2, '0', STR_PAD_LEFT);
        $startDate = Carbon::createFromDate($selectedTahun, $selectedBulan, 1)->startOfDay();
        $endDate = Carbon::createFromDate($selectedTahun, $selectedBulan, 1)->endOfMonth()->endOfDay();

        // Helper function to ensure data is array (same as FOB detail)
        $ensureArray = function ($data) {
            if (is_string($data)) {
                return json_decode($data, true) ?? [];
            }
            if (is_array($data)) {
                return $data;
            }
            return [];
        };

        // PERBAIKAN: Ambil data dari RekapPengambilan (sama seperti fob-detail.blade.php)
        $allRekapPengambilan = RekapPengambilan::where('customer_id', $user->id)->get();
        
        // Filter data berdasarkan periode (sama logic dengan fob-detail)
        $dataPencatatan = $allRekapPengambilan->filter(function ($item) use ($startDate, $endDate) {
            $tanggalItem = Carbon::parse($item->tanggal);
            return $tanggalItem->between($startDate, $endDate);
        });

        // Urutkan data berdasarkan tanggal (descending)
        $dataPencatatan = $dataPencatatan->sortByDesc('tanggal');

        // Get pricing info for selected month (dynamic pricing)
        $pricingInfo = $user->getPricingForYearMonth($yearMonth);

        // Calculate total volume SM3 for all time dari RekapPengambilan
        $totalVolumeSm3 = $allRekapPengambilan->sum('volume');

        // Calculate total volume SM3 for filtered period dari RekapPengambilan
        $filteredVolumeSm3 = $dataPencatatan->sum('volume');

        // Calculate total purchases for the filtered period dengan pricing yang tepat
        $filteredTotalPurchases = 0;
        foreach ($dataPencatatan as $item) {
            $volumeSm3 = floatval($item->volume);

            // Ambil pricing berdasarkan tanggal item
            $itemDate = Carbon::parse($item->tanggal);
            $itemYearMonth = $itemDate->format('Y-m');
            $itemPricingInfo = $user->getPricingForYearMonth($itemYearMonth, $itemDate);
            
            $hargaPerM3 = floatval($itemPricingInfo['harga_per_meter_kubik'] ?? $user->harga_per_meter_kubik);
            $filteredTotalPurchases += $volumeSm3 * $hargaPerM3;
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

        // Hitung saldo bulan sebelumnya secara real-time (FOB format)
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
                        $realTimePrevMonthBalance -= abs($amount);
                    } else {
                        $realTimePrevMonthBalance += $amount;
                    }
                }
            }
        }

        // 2. Kurangi semua pembelian sampai akhir bulan sebelumnya dari RekapPengambilan
        $allRekapUntilPrevMonth = $allRekapPengambilan->filter(function ($item) use ($startDate) {
            $itemDate = Carbon::parse($item->tanggal);
            return $itemDate < $startDate;
        });
        
        foreach ($allRekapUntilPrevMonth as $item) {
            $volumeSm3 = floatval($item->volume);
            $itemDate = Carbon::parse($item->tanggal);
            $itemYearMonth = $itemDate->format('Y-m');
            $itemPricingInfo = $user->getPricingForYearMonth($itemYearMonth, $itemDate);
            $hargaPerM3 = floatval($itemPricingInfo['harga_per_meter_kubik'] ?? $user->harga_per_meter_kubik);
            $realTimePrevMonthBalance -= ($volumeSm3 * $hargaPerM3);
        }

        // Hitung saldo bulan ini menggunakan saldo bulan sebelumnya yang real-time
        $realTimeCurrentMonthBalance = $realTimePrevMonthBalance + $filteredTotalDeposits - $filteredTotalPurchases;

        // Hitung total tagihan dari semua data pencatatan (untuk backward compatibility)
        $allDataPencatatan = $user->dataPencatatan;
        $totalTagihan = $allDataPencatatan->sum('harga_final');
        $belumLunas = $allDataPencatatan->where('status_pembayaran', 'belum_lunas')->count();

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
    // Menghitung data untuk grafik tahunan (data per bulan)
    public function getYearlyChartData($customers, $tahun)
    {
        // Array untuk menyimpan total pemakaian dan penjualan per bulan
        $monthlyData = [
            'pemakaian' => array_fill(0, 12, 0),
            'penjualan' => array_fill(0, 12, 0)
        ];

        foreach ($customers as $customer) {
            // Ambil semua data pencatatan untuk customer ini di tahun yang dipilih
            $dataPencatatan = DataPencatatan::where('customer_id', $customer['id'])->get();

            // Filter berdasarkan tahun dari data_input
            $dataPencatatan = $dataPencatatan->filter(function ($item) use ($tahun, $customer) {
                $dataInput = json_decode($item->data_input, true) ?? [];

                // Jika data input kosong, skip
                if (empty($dataInput)) {
                    return false;
                }

                // Periksa tipe data pencatatan (FOB atau customer biasa)
                if (isset($customer['role']) && $customer['role'] === 'fob' && isset($dataInput['waktu'])) {
                    // Format FOB - menggunakan kunci 'waktu'
                    $dataTahun = Carbon::parse($dataInput['waktu'])->format('Y');
                    return $dataTahun == $tahun;
                } else if (!empty($dataInput['pembacaan_awal']['waktu'])) {
                    // Format customer biasa - menggunakan kunci 'pembacaan_awal.waktu'
                    $dataTahun = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y');
                    return $dataTahun == $tahun;
                }

                return false; // Skip jika tidak ada data waktu yang valid
            });

            foreach ($dataPencatatan as $item) {
                $dataInput = json_decode($item->data_input, true) ?? [];

                // Jika data input kosong, skip
                if (empty($dataInput)) {
                    continue;
                }

                // Ambil bulan berdasarkan format data
                $bulanIndex = -1;

                if (isset($customer['role']) && $customer['role'] === 'fob' && isset($dataInput['waktu'])) {
                    // Format FOB - menggunakan kunci 'waktu'
                    $bulanIndex = (int)Carbon::parse($dataInput['waktu'])->format('n') - 1;
                } else if (!empty($dataInput['pembacaan_awal']['waktu'])) {
                    // Format customer biasa - menggunakan kunci 'pembacaan_awal.waktu'
                    $bulanIndex = (int)Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('n') - 1;
                } else {
                    continue; // Skip jika tidak ada data waktu yang valid
                }

                // Hitung volume dan harga berdasarkan tipe customer
                if (isset($customer['role']) && $customer['role'] === 'fob' && isset($dataInput['volume_sm3'])) {
                    // FOB format data
                    $volumeSm3 = floatval($dataInput['volume_sm3']);
                    $hargaPerMeterKubik = floatval($customer['harga_per_meter_kubik'] ?? 0);
                    $pembelian = $volumeSm3 * $hargaPerMeterKubik;
                } else {
                    // Customer biasa format data
                    $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
                    $koreksiMeter = floatval($customer['koreksi_meter'] ?? 1.0);
                    $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
                    $hargaPerMeterKubik = floatval($customer['harga_per_meter_kubik'] ?? 0);
                    $pembelian = $volumeSm3 * $hargaPerMeterKubik;
                }

                // Tambahkan ke data bulanan
                $monthlyData['pemakaian'][$bulanIndex] += $volumeSm3;
                $monthlyData['penjualan'][$bulanIndex] += ($pembelian / 1000000); // Convert to millions for better display
            }
        }

        // Format data untuk JavaScript
        return [
            'pemakaian' => array_map(function ($val) {
                return round($val, 2);
            }, $monthlyData['pemakaian']),
            'penjualan' => array_map(function ($val) {
                return round($val, 2);
            }, $monthlyData['penjualan'])
        ];
    }

    // Menghitung data untuk grafik bulanan (data per hari)
    public function getMonthlyChartData($customers, $tahun, $bulan)
    {
        // Format untuk filter bulanan
        $yearMonth = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT);

        // Jumlah hari dalam bulan
        $daysInMonth = Carbon::createFromDate($tahun, $bulan, 1)->daysInMonth;

        // Array untuk menyimpan total pemakaian dan penjualan per hari
        $dailyData = [
            'pemakaian' => array_fill(0, $daysInMonth, 0),
            'penjualan' => array_fill(0, $daysInMonth, 0)
        ];

        foreach ($customers as $customer) {
            // Ambil semua data pencatatan untuk customer ini
            $dataPencatatan = DataPencatatan::where('customer_id', $customer['id'])->get();

            // Filter data berdasarkan bulan dan tahun
            $dataPencatatan = $dataPencatatan->filter(function ($item) use ($yearMonth, $customer) {
                $dataInput = json_decode($item->data_input, true) ?? [];

                // Jika data input kosong, skip
                if (empty($dataInput)) {
                    return false;
                }

                // Periksa format data (FOB atau customer biasa)
                if (isset($customer['role']) && $customer['role'] === 'fob' && isset($dataInput['waktu'])) {
                    // Format FOB - menggunakan kunci 'waktu'
                    $dataYearMonth = Carbon::parse($dataInput['waktu'])->format('Y-m');
                    return $dataYearMonth == $yearMonth;
                } else if (!empty($dataInput['pembacaan_awal']['waktu'])) {
                    // Format customer biasa - menggunakan kunci 'pembacaan_awal.waktu'
                    $dataYearMonth = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m');
                    return $dataYearMonth == $yearMonth;
                }

                return false; // Skip jika tidak ada data waktu yang valid
            });

            foreach ($dataPencatatan as $item) {
                $dataInput = json_decode($item->data_input, true) ?? [];

                // Jika data input kosong, skip
                if (empty($dataInput)) {
                    continue;
                }

                // Ambil hari berdasarkan format data
                $hariIndex = -1;

                if (isset($customer['role']) && $customer['role'] === 'fob' && isset($dataInput['waktu'])) {
                    // Format FOB - menggunakan kunci 'waktu'
                    $tanggal = Carbon::parse($dataInput['waktu']);
                    $hariIndex = (int)$tanggal->format('j') - 1; // j gives day without leading zeros (1-31)
                } else if (!empty($dataInput['pembacaan_awal']['waktu'])) {
                    // Format customer biasa - menggunakan kunci 'pembacaan_awal.waktu'
                    $tanggal = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
                    $hariIndex = (int)$tanggal->format('j') - 1; // j gives day without leading zeros (1-31)
                } else {
                    continue; // Skip jika tidak ada data waktu yang valid
                }

                // Hitung volume dan harga berdasarkan tipe customer
                if (isset($customer['role']) && $customer['role'] === 'fob' && isset($dataInput['volume_sm3'])) {
                    // FOB format data
                    $volumeSm3 = floatval($dataInput['volume_sm3']);
                    $hargaPerMeterKubik = floatval($customer['harga_per_meter_kubik'] ?? 0);
                    $pembelian = $volumeSm3 * $hargaPerMeterKubik;
                } else {
                    // Customer biasa format data
                    $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
                    $koreksiMeter = floatval($customer['koreksi_meter'] ?? 1.0);
                    $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
                    $hargaPerMeterKubik = floatval($customer['harga_per_meter_kubik'] ?? 0);
                    $pembelian = $volumeSm3 * $hargaPerMeterKubik;
                }

                // Tambahkan ke data harian
                $dailyData['pemakaian'][$hariIndex] += $volumeSm3;
                $dailyData['penjualan'][$hariIndex] += ($pembelian / 1000000); // Convert to millions for better display
            }
        }

        // Format data untuk JavaScript
        return [
            'pemakaian' => array_map(function ($val) {
                return round($val, 2);
            }, $dailyData['pemakaian']),
            'penjualan' => array_map(function ($val) {
                return round($val, 2);
            }, $dailyData['penjualan'])
        ];
    }

    // Menghitung data summary untuk profit
    private function calculateSummaryData($customers, $tahun, $bulan)
    {
        $yearMonth = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT);

        // 1. Hitung total penjualan bulanan (revenue dari customer)
        $penjualanBulanan = $this->calculateMonthlyData($customers, $yearMonth);
        $totalPenjualan = $penjualanBulanan['total_pembelian'];  // Ini adalah revenue dari customer
        $totalVolumePenjualan = $penjualanBulanan['total_pemakaian'];

        // 2. Hitung total pembelian gas dari supplier (cost berdasarkan HargaGagas)
        $pembelianData = $this->getRekapPembelianData($tahun, $bulan);
        $totalVolumePengambilan = $pembelianData['total_pengambilan'];
        $totalPembelianGas = $pembelianData['total_pembelian'];

        // 3. Hitung profit (Revenue - Cost)
        $totalProfit = $totalPenjualan - $totalPembelianGas;

        // 4. Hitung selisih volume
        $selisihVolume = $totalVolumePenjualan - $totalVolumePengambilan;

        // 5. Hitung profit margin
        $profitMargin = $totalPenjualan > 0 ? (($totalProfit / $totalPenjualan) * 100) : 0;

        // 6. Hitung efisiensi volume
        $efisiensiVolume = $totalVolumePengambilan > 0 ? (($totalVolumePenjualan / $totalVolumePengambilan) * 100) : 0;

        // 7. Hitung harga rata-rata
        $hargaRataPenjualan = $totalVolumePenjualan > 0 ? ($totalPenjualan / $totalVolumePenjualan) : 0;
        $hargaRataPembelian = $totalVolumePengambilan > 0 ? ($totalPembelianGas / $totalVolumePengambilan) : 0;

        // 8. Validasi data dan fallback
        if ($totalVolumePenjualan == 0 && $totalVolumePengambilan == 0) {
            \Log::warning('No sales or procurement data found', ['year_month' => $yearMonth]);
        }

        return [
            'total_penjualan' => round($totalPenjualan, 0),
            'total_volume_penjualan' => round($totalVolumePenjualan, 2),
            'total_pembelian_pengambilan' => round($totalPembelianGas, 0),
            'total_volume_pengambilan' => round($totalVolumePengambilan, 2),
            'total_profit' => round($totalProfit, 0),
            'selisih_volume' => round($selisihVolume, 2),
            'harga_rata_penjualan' => round($hargaRataPenjualan, 0),
            'harga_rata_pengambilan' => round($hargaRataPembelian, 0),
            'profit_margin' => round($profitMargin, 2),
            'efisiensi_volume' => round($efisiensiVolume, 2),
            'method' => 'Berdasarkan HargaGagas' // Identifier untuk UI
        ];
    }

    // Menghitung data untuk grafik profit tahunan (per bulan)
    private function getProfitChartData($customers, $tahun)
    {
        $profitData = array_fill(0, 12, 0);

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $yearMonth = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT);

            // Hitung penjualan bulanan (revenue)
            $penjualanBulanan = $this->calculateMonthlyData($customers, $yearMonth);
            $totalPenjualan = $penjualanBulanan['total_pembelian'];

            // Hitung pembelian gas berdasarkan HargaGagas (cost)
            $totalPembelianGas = $this->calculateTotalPembelianFromHargaGagas($tahun, $bulan);

            // Hitung profit dan convert ke jutaan untuk display
            $profit = $totalPenjualan - $totalPembelianGas;
            $profitData[$bulan - 1] = round($profit / 1000000, 2); // Convert to millions
        }

        return [
            'profit' => $profitData
        ];
    }

    // Method untuk mendapatkan data rekap pembelian (dari RekapPembelianController logic)
    private function getRekapPembelianData($tahun, $bulan)
    {
        // Total pengambilan bulanan
        $totalPengambilan = RekapPengambilan::whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->sum('volume');

        // Total pembelian berdasarkan harga gagas untuk bulan tersebut
        $totalPembelian = $this->calculateTotalPembelianFromHargaGagas($tahun, $bulan);

        return [
            'total_pengambilan' => $totalPengambilan,
            'total_pembelian' => $totalPembelian
        ];
    }

    // Menghitung total pembelian berdasarkan harga gagas
    private function calculateTotalPembelianFromHargaGagas($tahun, $bulan = null)
    {
        if ($bulan) {
            // Untuk bulan tertentu, gunakan fallback logic
            $hargaGagas = $this->getHargaGagasWithFallback($tahun, $bulan);
        } else {
            // Untuk tahunan, hitung semua bulan
            $totalPembelianTahun = 0;
            for ($m = 1; $m <= 12; $m++) {
                $totalPembelianTahun += $this->calculateTotalPembelianFromHargaGagas($tahun, $m);
            }
            return $totalPembelianTahun;
        }

        if (!$hargaGagas) {
            return 0;
        }

        // Ambil total volume pengambilan
        $totalVolume = RekapPengambilan::whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->sum('volume');

        // Hitung MMBTU (Total Volume SM3 / Kalori)
        $totalMMBTU = $hargaGagas->kalori > 0 ? $totalVolume / $hargaGagas->kalori : 0;

        // Hitung total pembelian (MMBTU * Harga USD dalam IDR)
        $hargaIDR = $hargaGagas->harga_usd * $hargaGagas->rate_konversi_idr;
        $totalPembelian = $totalMMBTU * $hargaIDR;

        return $totalPembelian;
    }

    // Mendapatkan harga gagas dengan fallback ke periode sebelumnya
    private function getHargaGagasWithFallback($tahun, $bulan)
    {
        // Coba ambil harga gagas untuk periode yang diminta
        $hargaGagas = HargaGagas::where('periode_tahun', $tahun)
            ->where('periode_bulan', $bulan)
            ->latest()
            ->first();

        if ($hargaGagas) {
            return $hargaGagas;
        }

        // Jika tidak ada, cari periode sebelumnya (maksimal 12 bulan ke belakang)
        $currentDate = Carbon::create($tahun, $bulan, 1);

        for ($i = 1; $i <= 12; $i++) {
            $fallbackDate = $currentDate->copy()->subMonths($i);
            $fallbackHarga = HargaGagas::where('periode_tahun', $fallbackDate->year)
                ->where('periode_bulan', $fallbackDate->month)
                ->latest()
                ->first();

            if ($fallbackHarga) {
                \Log::info("Harga Gagas Fallback: Periode {$tahun}-{$bulan} menggunakan data dari {$fallbackDate->year}-{$fallbackDate->month}");
                return $fallbackHarga;
            }
        }

        // Jika tidak ada data sama sekali
        return null;
    }

    // Mendapatkan data customer rekap pembelian
    private function getCustomerRekapPembelianData($tahun, $bulan)
    {
        // Ambil semua customer yang memiliki data pengambilan
        $customers = User::whereIn('role', ['customer', 'fob'])
            ->whereHas('rekapPengambilan', function ($query) use ($tahun) {
                $query->whereYear('tanggal', $tahun);
            })
            ->get();

        $data = [];

        foreach ($customers as $customer) {
            // Pengambilan tahunan
            $pengambilanTahun = RekapPengambilan::where('customer_id', $customer->id)
                ->whereYear('tanggal', $tahun)
                ->sum('volume');

            // Pengambilan bulanan
            $pengambilanBulan = RekapPengambilan::where('customer_id', $customer->id)
                ->whereYear('tanggal', $tahun)
                ->whereMonth('tanggal', $bulan)
                ->sum('volume');

            // Pembelian berdasarkan harga gagas dengan fallback
            $hargaGagas = $this->getHargaGagasWithFallback($tahun, $bulan);

            if ($hargaGagas && $hargaGagas->kalori > 0) {
                $hargaIDR = $hargaGagas->harga_usd * $hargaGagas->rate_konversi_idr;

                // Untuk pembelian tahunan, hitung per bulan
                $pembelianTahun = 0;
                for ($m = 1; $m <= 12; $m++) {
                    $pengambilanCustomerBulan = RekapPengambilan::where('customer_id', $customer->id)
                        ->whereYear('tanggal', $tahun)
                        ->whereMonth('tanggal', $m)
                        ->sum('volume');

                    $hargaInfoBulan = $this->getHargaGagasWithFallback($tahun, $m);
                    if ($hargaInfoBulan && $hargaInfoBulan->kalori > 0) {
                        $hargaIDRBulan = $hargaInfoBulan->harga_usd * $hargaInfoBulan->rate_konversi_idr;
                        $pembelianTahun += ($pengambilanCustomerBulan / $hargaInfoBulan->kalori) * $hargaIDRBulan;
                    }
                }

                $pembelianBulan = ($pengambilanBulan / $hargaGagas->kalori) * $hargaIDR;
            } else {
                $pembelianTahun = 0;
                $pembelianBulan = 0;
            }

            if ($pengambilanTahun > 0 || $pengambilanBulan > 0) {
                $data[] = [
                    'nama' => $customer->name,
                    'role' => $customer->role,
                    'pengambilan_tahun' => round($pengambilanTahun, 2),
                    'pengambilan_bulan' => round($pengambilanBulan, 2),
                    'pembelian_tahun' => round($pembelianTahun, 0),
                    'pembelian_bulan' => round($pembelianBulan, 0),
                ];
            }
        }

        return $data;
    }

    // Mendapatkan data chart tahunan untuk rekap pembelian
    private function getRekapPembelianYearlyChartData($tahun)
    {
        $pengambilan = [];
        $pembelian = [];
        $totalPengambilanTahun = 0;
        $totalPembelianTahun = 0;

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            // Pengambilan bulanan
            $pengambilanBulan = RekapPengambilan::whereYear('tanggal', $tahun)
                ->whereMonth('tanggal', $bulan)
                ->sum('volume');
            $pengambilan[] = $pengambilanBulan;
            $totalPengambilanTahun += $pengambilanBulan;

            // Pembelian bulanan dengan fallback logic
            $pembelianBulan = $this->calculateTotalPembelianFromHargaGagas($tahun, $bulan);
            $pembelian[] = $pembelianBulan / 1000000; // Convert ke juta rupiah
            $totalPembelianTahun += $pembelianBulan;
        }

        return [
            'pengambilan' => $pengambilan,
            'pembelian' => $pembelian,
            'total_pengambilan_tahun' => $totalPengambilanTahun,
            'total_pembelian_tahun' => $totalPembelianTahun
        ];
    }

    // Mendapatkan data chart bulanan untuk rekap pembelian
    private function getRekapPembelianMonthlyChartData($tahun, $bulan)
    {
        $daysInMonth = Carbon::create($tahun, $bulan)->daysInMonth;
        $pengambilan = [];
        $pembelian = [];

        // Ambil harga gagas dengan fallback untuk bulan ini
        $hargaGagas = $this->getHargaGagasWithFallback($tahun, $bulan);

        for ($hari = 1; $hari <= $daysInMonth; $hari++) {
            $tanggal = Carbon::create($tahun, $bulan, $hari)->format('Y-m-d');

            // Pengambilan harian
            $pengambilanHari = RekapPengambilan::whereDate('tanggal', $tanggal)
                ->sum('volume');
            $pengambilan[] = $pengambilanHari;

            // Pembelian harian (proporsional)
            if ($hargaGagas && $hargaGagas->kalori > 0) {
                $mmbtuHari = $pengambilanHari / $hargaGagas->kalori;
                $hargaIDR = $hargaGagas->harga_usd * $hargaGagas->rate_konversi_idr;
                $pembelianHari = $mmbtuHari * $hargaIDR;
                $pembelian[] = $pembelianHari / 1000000; // Convert ke juta rupiah
            } else {
                $pembelian[] = 0;
            }
        }

        return [
            'pengambilan' => $pengambilan,
            'pembelian' => $pembelian
        ];
    }
}
