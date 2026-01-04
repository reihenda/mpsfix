<?php

namespace App\Http\Controllers\Rekap;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DataPencatatan;
use App\Models\RekapPengambilan;
use App\Models\HargaGagas;
use App\Services\CurrencyService;
use Carbon\Carbon;
use DB;
use Log;

class RekapPembelianController extends Controller
{
    protected $currencyService;
    
    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }
    
    public function index(Request $request)
    {
        $selectedTahun = $request->get('tahun', date('Y'));
        $selectedBulan = $request->get('bulan', date('n'));
        
        // Dapatkan tahun yang tersedia
        $availableYears = RekapPengambilan::selectRaw('YEAR(tanggal) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();
        
        if (empty($availableYears)) {
            $availableYears = [date('Y')];
        }
        
        // Cek status harga gagas dan info fallback untuk periode yang dipilih
        $hargaGagasInfo = $this->getHargaGagasWithFallback($selectedTahun, $selectedBulan);
        $fallbackWarnings = $this->getFallbackWarnings($selectedTahun, $selectedBulan);
        
        // Ambil data rekap tahunan
        $pembelianTahunanData = $this->getRekapTahunan($selectedTahun);
        
        // Ambil data rekap bulanan
        $pembelianBulananData = $this->getRekapBulanan($selectedTahun, $selectedBulan);
        
        // Data untuk grafik tahunan
        $yearlyChartData = $this->getYearlyChartData($selectedTahun);
        
        // Data untuk grafik bulanan (harian)
        $monthlyChartData = $this->getMonthlyChartData($selectedTahun, $selectedBulan);
        
        // Data pembelian per customer
        $customerPembelianData = $this->getCustomerPembelianData($selectedTahun, $selectedBulan);
        
        return view('rekap.pembelian.index', compact(
            'selectedTahun',
            'selectedBulan', 
            'availableYears',
            'pembelianTahunanData',
            'pembelianBulananData',
            'yearlyChartData',
            'monthlyChartData',
            'customerPembelianData',
            'hargaGagasInfo',
            'fallbackWarnings'
        ));
    }
    
    /**
     * Mendapatkan harga gagas dengan fallback ke periode sebelumnya
     */
    private function getHargaGagasWithFallback($tahun, $bulan)
    {
        // Coba ambil harga gagas untuk periode yang diminta
        $hargaGagas = HargaGagas::where('periode_tahun', $tahun)
            ->where('periode_bulan', $bulan)
            ->latest()
            ->first();
        
        if ($hargaGagas) {
            return [
                'harga_gagas' => $hargaGagas,
                'is_fallback' => false,
                'original_periode' => null,
                'fallback_periode' => null
            ];
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
                // Log fallback usage
                Log::info("Harga Gagas Fallback: Periode {$tahun}-{$bulan} menggunakan data dari {$fallbackDate->year}-{$fallbackDate->month}");
                
                return [
                    'harga_gagas' => $fallbackHarga,
                    'is_fallback' => true,
                    'original_periode' => "{$tahun}-{$bulan}",
                    'fallback_periode' => $fallbackDate->format('Y-m')
                ];
            }
        }
        
        // Jika tidak ada data sama sekali
        return [
            'harga_gagas' => null,
            'is_fallback' => false,
            'original_periode' => "{$tahun}-{$bulan}",
            'fallback_periode' => null
        ];
    }
    
    /**
     * Mendapatkan informasi warning untuk periode yang menggunakan fallback
     */
    private function getFallbackWarnings($tahun, $bulan = null)
    {
        $warnings = [];
        
        if ($bulan) {
            // Check single month
            $info = $this->getHargaGagasWithFallback($tahun, $bulan);
            if ($info['is_fallback']) {
                $fallbackDate = Carbon::createFromFormat('Y-m', $info['fallback_periode']);
                $warnings[] = [
                    'type' => 'monthly',
                    'periode' => $info['original_periode'],
                    'fallback_periode' => $fallbackDate->format('F Y'),
                    'message' => "Data harga gagas untuk " . Carbon::create($tahun, $bulan)->format('F Y') . 
                               " belum diatur. Menggunakan data dari " . $fallbackDate->format('F Y')
                ];
            } elseif (!$info['harga_gagas']) {
                $warnings[] = [
                    'type' => 'no_data',
                    'periode' => $info['original_periode'],
                    'message' => "Data harga gagas untuk " . Carbon::create($tahun, $bulan)->format('F Y') . 
                               " belum diatur dan tidak ada data fallback yang tersedia"
                ];
            }
        } else {
            // Check all months in year
            for ($m = 1; $m <= 12; $m++) {
                $info = $this->getHargaGagasWithFallback($tahun, $m);
                if ($info['is_fallback']) {
                    $fallbackDate = Carbon::createFromFormat('Y-m', $info['fallback_periode']);
                    $warnings[] = [
                        'type' => 'yearly_fallback',
                        'periode' => Carbon::create($tahun, $m)->format('F Y'),
                        'fallback_periode' => $fallbackDate->format('F Y'),
                        'message' => Carbon::create($tahun, $m)->format('F') . " menggunakan data " . $fallbackDate->format('F Y')
                    ];
                } elseif (!$info['harga_gagas']) {
                    $warnings[] = [
                        'type' => 'yearly_no_data',
                        'periode' => Carbon::create($tahun, $m)->format('F Y'),
                        'message' => Carbon::create($tahun, $m)->format('F') . " tidak ada data"
                    ];
                }
            }
        }
        
        return $warnings;
    }
    
    private function getRekapTahunan($tahun)
    {
        // Total pengambilan dalam tahun
        $totalPengambilan = RekapPengambilan::whereYear('tanggal', $tahun)
            ->sum('volume');
        
        // Total pembelian berdasarkan harga gagas
        $totalPembelian = $this->calculateTotalPembelian($tahun);
        
        return [
            'total_pengambilan' => $totalPengambilan,
            'total_pembelian' => $totalPembelian
        ];
    }
    
    private function getRekapBulanan($tahun, $bulan)
    {
        // Total pengambilan dalam bulan
        $totalPengambilan = RekapPengambilan::whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->sum('volume');
        
        // Total pembelian berdasarkan harga gagas untuk bulan tersebut
        $totalPembelian = $this->calculateTotalPembelian($tahun, $bulan);
        
        return [
            'total_pengambilan' => $totalPengambilan,
            'total_pembelian' => $totalPembelian
        ];
    }
    
    private function calculateTotalPembelian($tahun, $bulan = null)
    {
        if ($bulan) {
            // Untuk bulan tertentu, gunakan fallback logic
            $hargaGagasInfo = $this->getHargaGagasWithFallback($tahun, $bulan);
            $hargaGagas = $hargaGagasInfo['harga_gagas'];
        } else {
            // Untuk tahunan, hitung semua bulan
            $totalPembelianTahun = 0;
            for ($m = 1; $m <= 12; $m++) {
                $totalPembelianTahun += $this->calculateTotalPembelian($tahun, $m);
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
    
    private function getYearlyChartData($tahun)
    {
        $pengambilan = [];
        $pembelian = [];
        
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            // Pengambilan bulanan
            $pengambilanBulan = RekapPengambilan::whereYear('tanggal', $tahun)
                ->whereMonth('tanggal', $bulan)
                ->sum('volume');
            $pengambilan[] = $pengambilanBulan;
            
            // Pembelian bulanan dengan fallback logic
            $pembelianBulan = $this->calculateTotalPembelian($tahun, $bulan);
            $pembelian[] = $pembelianBulan / 1000000; // Convert ke juta rupiah
        }
        
        return [
            'pengambilan' => $pengambilan,
            'pembelian' => $pembelian
        ];
    }
    
    private function getMonthlyChartData($tahun, $bulan)
    {
        $daysInMonth = Carbon::create($tahun, $bulan)->daysInMonth;
        $pengambilan = [];
        $pembelian = [];
        
        // Ambil harga gagas dengan fallback untuk bulan ini
        $hargaGagasInfo = $this->getHargaGagasWithFallback($tahun, $bulan);
        $hargaGagas = $hargaGagasInfo['harga_gagas'];
        
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
    
    private function getCustomerPembelianData($tahun, $bulan)
    {
        // Ambil semua customer yang memiliki data pengambilan
        $customers = User::whereIn('role', ['customer', 'fob'])
            ->whereHas('rekapPengambilan', function($query) use ($tahun) {
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
            $hargaGagasInfo = $this->getHargaGagasWithFallback($tahun, $bulan);
            $hargaGagas = $hargaGagasInfo['harga_gagas'];
            
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
                    if ($hargaInfoBulan['harga_gagas'] && $hargaInfoBulan['harga_gagas']->kalori > 0) {
                        $hargaIDRBulan = $hargaInfoBulan['harga_gagas']->harga_usd * $hargaInfoBulan['harga_gagas']->rate_konversi_idr;
                        $pembelianTahun += ($pengambilanCustomerBulan / $hargaInfoBulan['harga_gagas']->kalori) * $hargaIDRBulan;
                    }
                }
                
                $pembelianBulan = ($pengambilanBulan / $hargaGagas->kalori) * $hargaIDR;
            } else {
                $pembelianTahun = 0;
                $pembelianBulan = 0;
            }
            
            $data[] = [
                'nama' => $customer->name,
                'role' => $customer->role,
                'pengambilan_tahun' => $pengambilanTahun,
                'pengambilan_bulan' => $pengambilanBulan,
                'pembelian_tahun' => $pembelianTahun,
                'pembelian_bulan' => $pembelianBulan,
            ];
        }
        
        return $data;
    }
    
    public function kelolaHargaGagas(Request $request)
    {
        $selectedTahun = $request->get('tahun', date('Y'));
        $selectedBulan = $request->get('bulan', date('n'));
        
        // Ambil data harga gagas untuk periode yang dipilih
        $hargaGagas = HargaGagas::where('periode_tahun', $selectedTahun)
            ->where('periode_bulan', $selectedBulan)
            ->latest()
            ->first();
        
        // Cari harga gagas periode sebelumnya untuk fitur copy
        $previousPeriodData = $this->findPreviousPeriodData($selectedTahun, $selectedBulan);
        
        // Ambil history harga gagas (10 data terbaru) - distinct per periode
        $historyHargaGagas = HargaGagas::select('*')
            ->orderBy('periode_tahun', 'desc')
            ->orderBy('periode_bulan', 'desc')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->unique(function ($item) {
                return $item->periode_tahun . '-' . $item->periode_bulan;
            })
            ->values();
        
        // Ambil rate USD ke IDR realtime
        $currentUsdToIdr = $this->currencyService->getUsdToIdrRate();
        $rateInfo = $this->currencyService->getLastUpdateInfo();
        
        // Ambil total volume pengambilan untuk bulan yang dipilih
        $totalVolume = RekapPengambilan::whereYear('tanggal', $selectedTahun)
            ->whereMonth('tanggal', $selectedBulan)
            ->sum('volume');
        
        // Hitung data berdasarkan harga gagas yang ada
        $totalMMBTU = 0;
        $totalPembelian = 0;
        
        if ($hargaGagas && $hargaGagas->kalori > 0) {
            $totalMMBTU = $totalVolume / $hargaGagas->kalori;
            $hargaIDR = $hargaGagas->harga_usd * $hargaGagas->rate_konversi_idr;
            $totalPembelian = $totalMMBTU * $hargaIDR;
        }
        
        return view('rekap.pembelian.kelola-harga-gagas', compact(
            'selectedTahun',
            'selectedBulan',
            'hargaGagas',
            'previousPeriodData',
            'historyHargaGagas',
            'totalVolume',
            'totalMMBTU',
            'totalPembelian',
            'currentUsdToIdr',
            'rateInfo'
        ));
    }
    
    /**
     * Mencari data periode sebelumnya untuk fitur copy
     */
    private function findPreviousPeriodData($tahun, $bulan)
    {
        $currentDate = Carbon::create($tahun, $bulan, 1);
        
        for ($i = 1; $i <= 12; $i++) {
            $previousDate = $currentDate->copy()->subMonths($i);
            $previousData = HargaGagas::where('periode_tahun', $previousDate->year)
                ->where('periode_bulan', $previousDate->month)
                ->latest()
                ->first();
            
            if ($previousData) {
                return [
                    'data' => $previousData,
                    'periode' => $previousDate->format('F Y')
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Copy data dari periode sebelumnya
     */
    public function copyFromPreviousPeriod(Request $request)
    {
        $request->validate([
            'tahun' => 'required|integer|min:2020|max:2050',
            'bulan' => 'required|integer|min:1|max:12',
        ]);
        
        $previousData = $this->findPreviousPeriodData($request->tahun, $request->bulan);
        
        if (!$previousData) {
            return redirect()->back()->withErrors([
                'general' => 'Tidak ada data periode sebelumnya yang dapat disalin'
            ]);
        }
        
        try {
            // Gunakan rate USD terbaru
            $currentRate = $this->currencyService->getUsdToIdrRate();
            
            $hargaGagas = HargaGagas::upsertHargaGagas([
                'harga_usd' => $previousData['data']->harga_usd,
                'rate_konversi_idr' => $currentRate,
                'kalori' => $previousData['data']->kalori,
                'periode_tahun' => $request->tahun,
                'periode_bulan' => $request->bulan,
            ]);
            
            $currentPeriode = Carbon::create($request->tahun, $request->bulan)->format('F Y');
            
            return redirect()->back()->with('success', 
                "Data harga gagas berhasil disalin dari {$previousData['periode']} ke {$currentPeriode}"
            );
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'general' => 'Gagal menyalin data: ' . $e->getMessage()
            ]);
        }
    }
    
    public function updateHargaGagas(Request $request)
    {
        $request->validate([
            'harga_usd' => 'required|numeric|min:0',
            'kalori' => [
                'required',
                'numeric',
                'min:0.0000000000000001', // Minimal value untuk 16 digit presisi
                'regex:/^[0-9]+([.][0-9]{1,16})?$/' // Pattern untuk maksimal 16 digit di belakang koma
            ],
            'tahun' => 'required|integer|min:2020|max:2050',
            'bulan' => 'required|integer|min:1|max:12',
        ], [
            'kalori.regex' => 'Nilai kalori maksimal 16 angka di belakang koma',
            'kalori.min' => 'Nilai kalori harus lebih dari 0'
        ]);
        
        // Gunakan rate realtime atau input manual
        $rateKonversi = $request->filled('manual_rate') ? 
            $request->manual_rate : 
            $this->currencyService->getUsdToIdrRate();
        
        // Validasi rate konversi
        if ($rateKonversi <= 0) {
            return redirect()->back()->withErrors([
                'manual_rate' => 'Rate konversi harus lebih dari 0'
            ]);
        }
        
        try {
            $monthName = date('F', mktime(0, 0, 0, $request->bulan, 1));
            
            // Gunakan upsert method untuk update atau create
            $hargaGagas = HargaGagas::upsertHargaGagas([
                'harga_usd' => $request->harga_usd,
                'rate_konversi_idr' => $rateKonversi,
                'kalori' => $request->kalori,
                'periode_tahun' => $request->tahun,
                'periode_bulan' => $request->bulan,
            ]);
            
            // Tentukan pesan berdasarkan apakah data baru dibuat atau diupdate
            $wasRecentlyCreated = $hargaGagas->wasRecentlyCreated;
            
            if ($wasRecentlyCreated) {
                return redirect()->back()->with('success', 
                    "Harga gagas untuk {$monthName} {$request->tahun} berhasil disimpan"
                );
            } else {
                return redirect()->back()->with('success', 
                    "Harga gagas untuk {$monthName} {$request->tahun} berhasil diperbarui (data lama di-replace)"
                );
            }
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'general' => 'Gagal menyimpan data: ' . $e->getMessage()
            ]);
        }
    }
    
    public function getCurrentRate()
    {
        try {
            $rate = $this->currencyService->refreshRate();
            $info = $this->currencyService->getLastUpdateInfo();
            
            return response()->json([
                'success' => true,
                'rate' => $rate,
                'formatted_rate' => $this->currencyService->formatRate($rate),
                'last_update' => $info['last_update']->format('d/m/Y H:i:s'),
                'source' => $info['source']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil rate terbaru: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function deleteHargaGagas($id)
    {
        try {
            $hargaGagas = HargaGagas::findOrFail($id);
            $periode = $hargaGagas->periode_format;
            
            $hargaGagas->delete();
            
            return redirect()->back()->with('success', 
                "Data harga gagas untuk periode {$periode} berhasil dihapus"
            );
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'general' => 'Gagal menghapus data: ' . $e->getMessage()
            ]);
        }
    }
}
