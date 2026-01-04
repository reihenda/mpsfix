<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\DataPencatatan;
use App\Models\User;
use App\Models\RekapPengambilan;
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
            'monthlyChartData'
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
            $dataPencatatan = $dataPencatatan->filter(function ($item) use ($tahun) {
                $dataInput = json_decode($item->data_input, true) ?? [];

                // Jika data input kosong atau tidak ada waktu awal, skip
                if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                    return false;
                }

                // Ambil tahun dari waktu pembacaan awal
                $dataTahun = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y');

                // Filter berdasarkan tahun
                return $dataTahun == $tahun;
            });

            foreach ($dataPencatatan as $item) {
                $dataInput = json_decode($item->data_input, true) ?? [];

                // Periksa apakah ini FOB atau customer biasa berdasarkan format data_input
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

            foreach ($dataPencatatan as $item) {
                $dataInput = json_decode($item->data_input, true) ?? [];

                // Jika data input kosong atau tidak ada waktu awal, skip
                if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                    continue;
                }

                // Convert the timestamp to year-month format for comparison
                $waktuAwal = \Carbon\Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m');

                // Filter by year-month
                if ($waktuAwal === $yearMonth) {
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
            $dataPencatatanTahun = $allDataPencatatan->filter(function ($item) use ($tahun) {
                $dataInput = json_decode($item->data_input, true) ?? [];

                // Jika data input kosong atau tidak ada waktu awal, skip
                if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                    return false;
                }

                // Ambil tahun dari waktu pembacaan awal
                $dataTahun = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y');

                // Filter berdasarkan tahun
                return $dataTahun == $tahun;
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

            // Data untuk bulan - filter berdasarkan waktu pembacaan awal
            $dataPencatatanBulan = $dataPencatatanTahun->filter(function ($item) use ($yearMonth) {
                $dataInput = json_decode($item->data_input, true) ?? [];

                // Jika data input kosong atau tidak ada waktu awal, skip
                if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                    return false;
                }

                // Convert timestamp to year-month format for comparison
                $waktuAwal = \Carbon\Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m');

                // Filter by year-month
                return $waktuAwal === $yearMonth;
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

        // Calculate total volume SM3 for all time
        $totalVolumeSm3 = $user->getTotalVolumeSm3();

        // Calculate total volume SM3 for filtered period
        $filteredVolumeSm3 = 0;
        foreach ($dataPencatatan as $item) {
            $dataInput = $ensureArray($item->data_input);
            $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
            $volumeSm3 = $volumeFlowMeter * floatval($user->koreksi_meter);
            $filteredVolumeSm3 += $volumeSm3;
        }

        // Calculate total purchases for the filtered period
        $filteredTotalPurchases = $dataPencatatan->sum(function ($item) use ($user, $ensureArray) {
            $dataInput = $ensureArray($item->data_input);
            $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
            $volumeSm3 = $volumeFlowMeter * floatval($user->koreksi_meter);
            return $volumeSm3 * floatval($user->harga_per_meter_kubik);
        });

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
            'filteredTotalPurchases'
        ));
    }
    // Menghitung data untuk grafik tahunan (data per bulan)
    private function getYearlyChartData($customers, $tahun)
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
            $dataPencatatan = $dataPencatatan->filter(function ($item) use ($tahun) {
                $dataInput = json_decode($item->data_input, true) ?? [];

                // Jika data input kosong atau tidak ada waktu awal, skip
                if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                    return false;
                }

                // Ambil tahun dari waktu pembacaan awal
                $dataTahun = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y');

                // Filter berdasarkan tahun
                return $dataTahun == $tahun;
            });

            foreach ($dataPencatatan as $item) {
                $dataInput = json_decode($item->data_input, true) ?? [];

                // Jika data input kosong atau tidak ada waktu awal, skip
                if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                    continue;
                }

                // Ambil bulan dari waktu pembacaan awal (index 0-based)
                $bulanIndex = (int)Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('n') - 1;

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
    private function getMonthlyChartData($customers, $tahun, $bulan)
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
            $dataPencatatan = $dataPencatatan->filter(function ($item) use ($yearMonth) {
                $dataInput = json_decode($item->data_input, true) ?? [];

                // Jika data input kosong atau tidak ada waktu awal, skip
                if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                    return false;
                }

                // Ambil tahun-bulan dari waktu pembacaan awal
                $dataYearMonth = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m');

                // Filter berdasarkan tahun-bulan
                return $dataYearMonth == $yearMonth;
            });

            foreach ($dataPencatatan as $item) {
                $dataInput = json_decode($item->data_input, true) ?? [];

                // Jika data input kosong atau tidak ada waktu awal, skip
                if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                    continue;
                }

                // Ambil hari dari waktu pembacaan awal (index 0-based untuk array)
                $tanggal = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
                $hariIndex = (int)$tanggal->format('j') - 1; // j gives day without leading zeros (1-31)

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
}
