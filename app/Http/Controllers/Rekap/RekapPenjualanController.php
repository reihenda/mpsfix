<?php

namespace App\Http\Controllers\Rekap;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DataPencatatan;
use App\Models\User;
use Carbon\Carbon;

class RekapPenjualanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Debug: Check request parameters
        //\Log::info('RekapPenjualan request params:', $request->all());
        // Filter Tahun
        $tahunIni = date('Y');
        $selectedTahun = $request->input('tahun', $tahunIni);

        // Filter Bulan
        $bulanIni = date('m');
        $selectedBulan = $request->input('bulan', $bulanIni);

        // Format untuk filter bulanan
        $yearMonth = $selectedTahun . '-' . str_pad($selectedBulan, 2, '0', STR_PAD_LEFT);

        // Mendapatkan semua customer (biasa dan FOB)
        $customersDb = User::where('role', 'customer')->get()->toArray();
        $fobDb = User::where('role', 'fob')->get()->toArray();

        // Gabungkan customer biasa dan FOB menggunakan array_merge
        $customers = array_merge($customersDb, $fobDb);

        // Debug untuk melihat jumlah customer yang diambil
        $customerCount = count($customers);

        // Buat array customerNames secara manual
        $customerNames = [];
        foreach ($customers as $customer) {
            $customerNames[] = [
                'name' => $customer['name'],
                'role' => $customer['role']
            ];
        }

        // Data untuk periode tahunan
        $penjualanTahunanData = $this->calculateYearlyData($customers, $selectedTahun);

        // Data untuk periode bulanan
        $penjualanBulananData = $this->calculateMonthlyData($customers, $yearMonth);

        // Data untuk tabel customer
        $customerPenjualanData = $this->calculateCustomerData($customers, $selectedTahun, $selectedBulan);

        // Data untuk grafik tahunan (data per bulan)
        $yearlyChartData = $this->getYearlyChartData($customers, $selectedTahun);

        // Data untuk grafik bulanan (data per hari)
        $monthlyChartData = $this->getMonthlyChartData($customers, $selectedTahun, $selectedBulan);

        // Mendapatkan list tahun untuk dropdown filter
        $availableYears = $this->getAvailableYears();

        return view('rekap.penjualan.index', compact(
            'penjualanTahunanData',
            'penjualanBulananData',
            'customerPenjualanData',
            'selectedTahun',
            'selectedBulan',
            'availableYears',
            'customerCount',
            'customerNames',
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
            $dataPencatatan = $dataPencatatan->filter(function($item) use ($tahun) {
            $dataInput = json_decode($item->data_input, true) ?? [];
            
            // Jika data input kosong, skip
            if (empty($dataInput)) {
            return false;
            }
            
            // Periksa tipe data pencatatan (FOB atau customer biasa)
            if (!empty($dataInput['waktu'])) {
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

                // Jika data input kosong, skip
                if (empty($dataInput)) {
                    continue;
                }

                // Periksa format data (FOB atau customer biasa)
                $matchingMonth = false;
                
                if (!empty($dataInput['waktu'])) {
                    // Format FOB - menggunakan kunci 'waktu'
                    $waktu = \Carbon\Carbon::parse($dataInput['waktu'])->format('Y-m');
                    $matchingMonth = ($waktu === $yearMonth);
                } else if (!empty($dataInput['pembacaan_awal']['waktu'])) {
                    // Format customer biasa - menggunakan kunci 'pembacaan_awal.waktu'
                    $waktuAwal = \Carbon\Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m');
                    $matchingMonth = ($waktuAwal === $yearMonth);
                } else {
                    continue; // Skip jika tidak ada data waktu yang valid
                }

                // Filter by year-month
                if ($matchingMonth) {
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
            $dataPencatatanTahun = $allDataPencatatan->filter(function($item) use ($tahun) {
                $dataInput = json_decode($item->data_input, true) ?? [];
                
                // Jika data input kosong, skip
                if (empty($dataInput)) {
                    return false;
                }
                
                // Periksa tipe data pencatatan (FOB atau customer biasa)
                if (!empty($dataInput['waktu'])) {
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
            $dataPencatatanBulan = $dataPencatatanTahun->filter(function($item) use ($yearMonth) {
                $dataInput = json_decode($item->data_input, true) ?? [];
                
                // Jika data input kosong, skip
                if (empty($dataInput)) {
                    return false;
                }
                
                // Periksa format data (FOB atau customer biasa)
                if (!empty($dataInput['waktu'])) {
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
        foreach($allData as $item) {
            $dataInput = json_decode($item->data_input, true) ?? [];
            
            // Jika data input kosong atau tidak ada waktu awal, skip
            if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                continue;
            }
            
            // Ambil tahun dari waktu pencatatan
            $tahun = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y');
            $years[] = $tahun;
        }
        
        // Hapus duplikat, urutkan secara descending
        $years = array_unique($years);
        rsort($years);
        
        // Jika tidak ada data, gunakan tahun saat ini
        if (empty($years)) {
            $years[] = date('Y'); // Default tahun saat ini jika tidak ada data
        }
        
        return $years;
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

                // Jika data input kosong, skip
                if (empty($dataInput)) {
                    return false;
                }

                // Periksa tipe data pencatatan (FOB atau customer biasa)
                if (!empty($dataInput['waktu'])) {
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
                
                if (!empty($dataInput['waktu'])) {
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

                // Jika data input kosong, skip
                if (empty($dataInput)) {
                    return false;
                }

                // Periksa format data (FOB atau customer biasa)
                if (!empty($dataInput['waktu'])) {
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
                
                if (!empty($dataInput['waktu'])) {
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
    
    /**
     * Cetak rekap penjualan dalam bentuk PDF
     */
    public function cetakRekapPenjualan(Request $request)
    {
        // Get filter parameters
        $tahun = $request->input('tahun', date('Y'));
        
        // Mendapatkan semua customer (biasa dan FOB)
        $customersDb = User::where('role', 'customer')->get()->toArray();
        $fobDb = User::where('role', 'fob')->get()->toArray();

        // Gabungkan customer biasa dan FOB menggunakan array_merge
        $customers = array_merge($customersDb, $fobDb);
        
        // Data bulanan untuk rekap tahunan
        $monthlyData = [];
        
        // Data untuk setiap customer
        $customersData = [];
        
        // Array untuk menyimpan total pemakaian dan penjualan per bulan
        $yearlyData = [
            'bulanan' => [],
            'total' => [
                'total_pemakaian' => 0,
                'total_pembelian' => 0
            ]
        ];

        // Loop through all months
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $yearMonth = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT);
            
            // Hitung data per bulan
            $monthlyData[$bulan] = $this->calculateMonthlyData($customers, $yearMonth);
            
            // Akumulasi ke total tahunan
            $yearlyData['total']['total_pemakaian'] += $monthlyData[$bulan]['total_pemakaian'];
            $yearlyData['total']['total_pembelian'] += $monthlyData[$bulan]['total_pembelian'];
            
            // Data untuk grafik bulanan
            $yearlyData['bulanan'][] = [
                'bulan' => date('F', mktime(0, 0, 0, $bulan, 1)),
                'total_pemakaian' => $monthlyData[$bulan]['total_pemakaian'],
                'total_pembelian' => $monthlyData[$bulan]['total_pembelian']
            ];
        }
        
        // Calculate data for each customer for the entire year
        foreach ($customers as $customer) {
            $pemakaianTahun = 0;
            $pembelianTahun = 0;
            $pemakaianBulanData = [];
            $pembelianBulanData = [];
            
            // Ambil data per bulan untuk customer ini
            for ($bulan = 1; $bulan <= 12; $bulan++) {
                $yearMonth = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT);
                
                // Data untuk tahun - ambil semua data pencatatan untuk customer ini
                $allDataPencatatan = DataPencatatan::where('customer_id', $customer['id'])->get();
                
                // Filter berdasarkan bulan dan tahun dari data_input
                $dataPencatatanBulan = $allDataPencatatan->filter(function($item) use ($yearMonth) {
                    $dataInput = json_decode($item->data_input, true) ?? [];
                    
                    // Jika data input kosong atau tidak ada waktu awal, skip
                    if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                        return false;
                    }
                    
                    // Convert the timestamp to year-month format for comparison
                    $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m');
                    
                    // Filter by year-month
                    return $waktuAwal === $yearMonth;
                });

                $pemakaianBulan = 0;
                $pembelianBulan = 0;

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

                $pemakaianBulanData[$bulan] = round($pemakaianBulan, 2);
                $pembelianBulanData[$bulan] = round($pembelianBulan, 0);
                
                // Akumulasi ke total tahunan per customer
                $pemakaianTahun += $pemakaianBulan;
                $pembelianTahun += $pembelianBulan;
            }
            
            // Tambahkan ke array customer data
            $customersData[] = [
                'nama' => $customer['name'],
                'role' => $customer['role'],
                'pemakaian_tahun' => round($pemakaianTahun, 2),
                'pembelian_tahun' => round($pembelianTahun, 0),
                'pemakaian_bulan' => $pemakaianBulanData,
                'pembelian_bulan' => $pembelianBulanData
            ];
        }
        
        // Data untuk view PDF
        $data = [
            'tahun' => $tahun,
            'yearlyData' => $yearlyData,
            'customersData' => $customersData,
            'currentDate' => Carbon::now()->format('d F Y')
        ];

        // Return view PDF yang dapat dicetak
        return view('pdf.rekap-penjualan', $data);
    }
}
