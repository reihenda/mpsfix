<?php

namespace App\Http\Controllers;

use App\Models\RekapPengambilan;
use App\Models\User;
use App\Models\NomorPolisi;
use App\Models\AlamatPengambilan;
use App\Models\DataPencatatan;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RekapPengambilanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get tanggal from request or use current date
        $tanggal = $request->input('tanggal', now()->format('Y-m-d'));

        // Extract month and year from the selected date
        $selectedDate = Carbon::parse($tanggal);
        $bulan = $selectedDate->month;
        $tahun = $selectedDate->year;

        // Start with base query
        $query = RekapPengambilan::filterByMonthYear($bulan, $tahun)
            ->with('customer');

        // Filter berdasarkan pencarian customer jika ada
        if ($request->has('search_customer') && !empty($request->search_customer)) {
            $searchTerm = $request->search_customer;
            $query->whereHas('customer', function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%');
            });
        }

        // Get data dengan filter
        $rekapPengambilan = $query->get()->sortBy('customer.name');

        // Hitung total volume bulanan (tanpa filter search untuk konsistensi)
        $totalVolumeBulanan = RekapPengambilan::getTotalVolumeMonthly($bulan, $tahun);

        // Hitung total volume harian berdasarkan tanggal yang dipilih (tanpa filter search)
        $totalVolumeHarian = RekapPengambilan::getTotalVolumeDaily($tanggal);

        // Deteksi jika request adalah AJAX untuk pencarian real-time
        if ($request->ajax() && $request->has('search_customer')) {
            $html = view('rekap-pengambilan.partials.table', ['rekapPengambilan' => $rekapPengambilan])->render();
            return response()->json([
                'html' => $html
            ]);
        }

        // Get list customer untuk dropdown
        $customers = User::where('role', 'customer')->orWhere('role', 'fob')->get();

        return view('rekap-pengambilan.index', compact(
            'rekapPengambilan',
            'customers',
            'bulan',
            'tahun',
            'tanggal',
            'totalVolumeBulanan',
            'totalVolumeHarian'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = User::where('role', 'customer')->orWhere('role', 'fob')->get();
        $nomorPolisList = NomorPolisi::orderBy('nopol')->get();
        $alamatList = AlamatPengambilan::orderBy('nama_alamat')->get();

        return view('rekap-pengambilan.create', compact('customers', 'nomorPolisList', 'alamatList'));
    }

    /**
     * Show the form for creating a new resource with pre-selected customer.
     */
    public function createWithCustomer(User $customer)
    {
        $customers = User::where('role', 'customer')->orWhere('role', 'fob')->get();
        $nomorPolisList = NomorPolisi::orderBy('nopol')->get();
        $alamatList = AlamatPengambilan::orderBy('nama_alamat')->get();
        $selectedCustomer = $customer;

        return view('rekap-pengambilan.create', compact('customers', 'nomorPolisList', 'alamatList', 'selectedCustomer'));
    }

    /**
     * Find rekap pengambilan by customer and date, then redirect to edit.
     */
    public function findByDate(User $customer, $date)
    {
        // Cari rekap pengambilan berdasarkan customer dan tanggal
        $rekapPengambilan = RekapPengambilan::where('customer_id', $customer->id)
            ->whereDate('tanggal', $date)
            ->first();

        if ($rekapPengambilan) {
            // Jika ditemukan, redirect ke halaman edit
            return redirect()->route('rekap-pengambilan.edit', $rekapPengambilan->id)
                ->with('info', 'Data rekap pengambilan ditemukan dan siap untuk diedit.')
                ->with('return_to_fob', true);
        } else {
            // Jika tidak ditemukan, redirect ke create dengan customer terpilih dan tanggal
            return redirect()->route('rekap-pengambilan.create-with-customer', $customer->id)
                ->with('info', 'Data rekap pengambilan tidak ditemukan. Silakan buat data baru.')
                ->with('preset_date', $date)
                ->with('return_to_fob', true);
        }
    }

    public function store(Request $request)
    {
        // Jika alamat_pengambilan_id adalah 'tambah_baru', atur nilainya menjadi null agar tidak divalidasi
        if ($request->alamat_pengambilan_id === 'tambah_baru') {
            $request->merge(['alamat_pengambilan_id' => null]);
        }

        $validatedBase = $request->validate([
            'customer_id' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'nopol' => 'required|string|max:20',
            'volume' => 'required|numeric|min:0',
            'alamat_pengambilan_id' => 'nullable|exists:alamat_pengambilan,id',
            'alamat_pengambilan' => 'nullable|string|max:500',
            'keterangan' => 'nullable|string',
        ]);

        // Jika alamat_new diisi, buat alamat baru
        if ($request->has('alamat_new') && !empty($request->alamat_new)) {
            try {
                // Cek apakah alamat sudah ada
                $alamat = AlamatPengambilan::firstOrCreate(
                    ['nama_alamat' => $request->alamat_new]
                );
                $validatedBase['alamat_pengambilan_id'] = $alamat->id;
                $validatedBase['alamat_pengambilan'] = $alamat->nama_alamat;
                \Log::info('Created or found alamat: ' . $alamat->id . ' - ' . $alamat->nama_alamat);
            } catch (\Exception $e) {
                \Log::error('Error creating alamat: ' . $e->getMessage());
                throw $e;
            }
        } elseif (!empty($validatedBase['alamat_pengambilan_id'])) {
            // Jika memilih dari dropdown, ambil nama alamatnya
            $alamat = AlamatPengambilan::find($validatedBase['alamat_pengambilan_id']);
            if ($alamat) {
                $validatedBase['alamat_pengambilan'] = $alamat->nama_alamat;
            }
        }

        $customer = User::findOrFail($validatedBase['customer_id']);

        // Buat rekap pengambilan
        $rekap = RekapPengambilan::create($validatedBase);

        // Jika customer adalah FOB, tambahkan juga ke data_pencatatan untuk sinkronisasi data
        if ($customer->isFOB()) {
            // Ambil waktu untuk mendapatkan pricing yang tepat
            $waktuDateTime = Carbon::parse($validatedBase['tanggal']);
            $waktuYearMonth = $waktuDateTime->format('Y-m');
            
            // Ambil pricing info berdasarkan tanggal spesifik
            $pricingInfo = $customer->getPricingForYearMonth($waktuYearMonth, $waktuDateTime);
            
            // Hitung harga dengan pricing yang tepat untuk periode ini
            $volumeSm3 = floatval($validatedBase['volume']);
            $hargaPerM3 = floatval($pricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
            $hargaFinal = $volumeSm3 * $hargaPerM3;

            // Format data untuk FOB
            $dataInput = [
                'waktu' => Carbon::parse($validatedBase['tanggal'])->format('Y-m-d H:i:s'),
                'volume_sm3' => $validatedBase['volume'],
                'alamat_pengambilan' => $validatedBase['alamat_pengambilan'] ?? null,
                'keterangan' => $validatedBase['keterangan'] ?? null
            ];

            // Tambahkan log untuk melihat data yang disimpan dan perhitungan harga
            \Log::info('Menyimpan data FOB dari RekapPengambilan', [
                'customer_id' => $validatedBase['customer_id'],
                'tanggal' => $validatedBase['tanggal'],
                'volume' => $validatedBase['volume'],
                'keterangan' => $validatedBase['keterangan'] ?? null,
                'dataInput' => $dataInput,
                'pricing_info' => $pricingInfo,
                'harga_per_m3' => $hargaPerM3,
                'harga_final' => $hargaFinal
            ]);

            // PERBAIKAN: Buat data pencatatan baru dengan relasi ke rekap_pengambilan
            $dataPencatatan = new \App\Models\DataPencatatan();
            $dataPencatatan->customer_id = $validatedBase['customer_id'];
            $dataPencatatan->rekap_pengambilan_id = $rekap->id; // Set relasi
            $dataPencatatan->data_input = json_encode($dataInput);
            $dataPencatatan->nama_customer = $customer->name;
            $dataPencatatan->status_pembayaran = 'belum_lunas'; // Default status
            $dataPencatatan->harga_final = $hargaFinal;
            $dataPencatatan->save();

            // Periksa isi object DataPencatatan yang berhasil dibuat
            \Log::info('Data pencatatan FOB berhasil dibuat', [
                'id' => $dataPencatatan->id,
                'customer_id' => $dataPencatatan->customer_id,
                'rekap_pengambilan_id' => $dataPencatatan->rekap_pengambilan_id,
                'nama_customer' => $dataPencatatan->nama_customer,
                'data_input' => json_decode($dataPencatatan->data_input, true),
                'harga_final' => $dataPencatatan->harga_final,
                'created_at' => $dataPencatatan->created_at
            ]);

            // Update total pembelian customer
            $customer->recordPurchase($hargaFinal);
            $userController = new UserController();
            $userController->rekalkulasiTotalPembelianFob($customer);
        }

        // Check if there's a return URL from FOB detail page
        if ($request->has('return_to_fob') && $request->return_to_fob) {
            return redirect()->route('data-pencatatan.fob-detail', $customer->id)
                ->with('success', 'Data pengambilan berhasil ditambahkan dan disinkronkan dengan FOB.');
        }

        return redirect()->route('rekap-pengambilan.index')
            ->with('success', 'Data pengambilan berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(RekapPengambilan $rekapPengambilan)
    {
        return view('rekap-pengambilan.show', compact('rekapPengambilan'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RekapPengambilan $rekapPengambilan)
    {
        $customers = User::where('role', 'customer')->orWhere('role', 'fob')->get();
        $nomorPolisList = NomorPolisi::orderBy('nopol')->get();
        $alamatList = AlamatPengambilan::orderBy('nama_alamat')->get();

        return view('rekap-pengambilan.edit', compact('rekapPengambilan', 'customers', 'nomorPolisList', 'alamatList'));
    }

    public function update(Request $request, RekapPengambilan $rekapPengambilan)
    {
        // Jika alamat_pengambilan_id adalah 'tambah_baru', atur nilainya menjadi null agar tidak divalidasi
        if ($request->alamat_pengambilan_id === 'tambah_baru') {
            $request->merge(['alamat_pengambilan_id' => null]);
        }

        $validatedBase = $request->validate([
            'customer_id' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'nopol' => 'required|string|max:20',
            'volume' => 'required|numeric|min:0',
            'alamat_pengambilan_id' => 'nullable|exists:alamat_pengambilan,id',
            'alamat_pengambilan' => 'nullable|string|max:500',
            'keterangan' => 'nullable|string',
        ]);

        // Jika alamat_new diisi, buat alamat baru
        if ($request->has('alamat_new') && !empty($request->alamat_new)) {
            try {
                // Cek apakah alamat sudah ada
                $alamat = AlamatPengambilan::firstOrCreate(
                    ['nama_alamat' => $request->alamat_new]
                );
                $validatedBase['alamat_pengambilan_id'] = $alamat->id;
                $validatedBase['alamat_pengambilan'] = $alamat->nama_alamat;
                \Log::info('Created or found alamat for update: ' . $alamat->id . ' - ' . $alamat->nama_alamat);
            } catch (\Exception $e) {
                \Log::error('Error creating alamat for update: ' . $e->getMessage());
                throw $e;
            }
        } elseif (!empty($validatedBase['alamat_pengambilan_id'])) {
            // Jika memilih dari dropdown, ambil nama alamatnya
            $alamat = AlamatPengambilan::find($validatedBase['alamat_pengambilan_id']);
            if ($alamat) {
                $validatedBase['alamat_pengambilan'] = $alamat->nama_alamat;
            }
        }

        $customer = User::findOrFail($validatedBase['customer_id']);

        // Update rekap pengambilan
        $rekapPengambilan->update($validatedBase);

        // Jika customer adalah FOB, update atau buat data_pencatatan untuk sinkronisasi
        if ($customer->isFOB()) {
            // Ambil waktu untuk mendapatkan pricing yang tepat
            $waktuDateTime = Carbon::parse($validatedBase['tanggal']);
            $waktuYearMonth = $waktuDateTime->format('Y-m');
            
            // Ambil pricing info berdasarkan tanggal spesifik
            $pricingInfo = $customer->getPricingForYearMonth($waktuYearMonth, $waktuDateTime);
            
            // Hitung harga dengan pricing yang tepat
            $volumeSm3 = floatval($validatedBase['volume']);
            $hargaPerM3 = floatval($pricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
            $hargaFinal = $volumeSm3 * $hargaPerM3;

            // Format data untuk FOB
            $dataInput = [
                'waktu' => Carbon::parse($validatedBase['tanggal'])->format('Y-m-d H:i:s'),
                'volume_sm3' => $validatedBase['volume'],
                'alamat_pengambilan' => $validatedBase['alamat_pengambilan'] ?? null,
                'keterangan' => $validatedBase['keterangan'] ?? null
            ];
            
            // Log info untuk debugging
            \Log::info('Updating FOB data from rekap_pengambilan', [
                'rekap_id' => $rekapPengambilan->id,
                'customer_id' => $customer->id,
                'tanggal' => $validatedBase['tanggal'],
                'pricing_info' => $pricingInfo,
                'harga_per_m3' => $hargaPerM3,
                'volume' => $volumeSm3,
                'harga_final' => $hargaFinal
            ]);

            // PERBAIKAN: Gunakan relasi langsung atau pencarian yang lebih presisi
            $dataPencatatan = null;
            
            // 1. Coba gunakan relasi langsung jika ada
            if ($rekapPengambilan->dataPencatatan) {
                $dataPencatatan = $rekapPengambilan->dataPencatatan;
                \Log::info('Mengupdate data pencatatan FOB menggunakan relasi langsung', [
                    'rekap_id' => $rekapPengambilan->id,
                    'pencatatan_id' => $dataPencatatan->id
                ]);
            } else {
                // 2. Jika relasi tidak ada, cari dengan kriteria presisi tinggi
                $tanggalSearch = Carbon::parse($validatedBase['tanggal'])->format('Y-m-d');
                $volumeSearch = floatval($validatedBase['volume']);
                
                // Cari data pencatatan yang matching berdasarkan tanggal DAN volume
                $potentialMatches = \App\Models\DataPencatatan::where('customer_id', $customer->id)
                    ->where('data_input', 'like', '%"waktu":"%' . $tanggalSearch . '%"%')
                    ->get();
                
                // Filter lebih presisi berdasarkan volume
                foreach ($potentialMatches as $match) {
                    $matchDataInput = json_decode($match->data_input, true) ?? [];
                    $matchVolume = floatval($matchDataInput['volume_sm3'] ?? 0);
                    $matchDate = isset($matchDataInput['waktu']) ? Carbon::parse($matchDataInput['waktu'])->format('Y-m-d') : null;
                    
                    // Match harus tepat berdasarkan tanggal DAN volume (dengan toleransi 0.01)
                    if ($matchDate === $tanggalSearch && abs($matchVolume - $volumeSearch) < 0.01) {
                        $dataPencatatan = $match;
                        // Set relasi jika belum ada
                        if (!$dataPencatatan->rekap_pengambilan_id) {
                            $dataPencatatan->rekap_pengambilan_id = $rekapPengambilan->id;
                        }
                        break;
                    }
                }
            }
            
            if ($dataPencatatan) {
                // Update data pencatatan yang ada
                $dataPencatatan->data_input = json_encode($dataInput);
                $dataPencatatan->harga_final = $hargaFinal;
                $dataPencatatan->rekap_pengambilan_id = $rekapPengambilan->id; // Pastikan relasi ter-set
                $dataPencatatan->save();
                
                \Log::info('Updated existing data_pencatatan', [
                    'data_pencatatan_id' => $dataPencatatan->id,
                    'rekap_pengambilan_id' => $rekapPengambilan->id,
                    'harga_final' => $hargaFinal
                ]);
            } else {
                // Buat data pencatatan baru jika tidak ditemukan
                $dataPencatatan = new \App\Models\DataPencatatan();
                $dataPencatatan->customer_id = $validatedBase['customer_id'];
                $dataPencatatan->rekap_pengambilan_id = $rekapPengambilan->id; // PERBAIKAN: Set relasi
                $dataPencatatan->data_input = json_encode($dataInput);
                $dataPencatatan->nama_customer = $customer->name;
                $dataPencatatan->status_pembayaran = 'belum_lunas'; // Default status
                $dataPencatatan->harga_final = $hargaFinal;
                $dataPencatatan->save();
                
                \Log::info('Created new data_pencatatan', [
                    'data_pencatatan_id' => $dataPencatatan->id,
                    'rekap_pengambilan_id' => $rekapPengambilan->id,
                    'harga_final' => $hargaFinal
                ]);
            }

            // Rekalkulasi total pembelian customer
            $userController = new UserController();
            $userController->rekalkulasiTotalPembelianFob($customer);
            
            // Update saldo bulanan mulai dari bulan data
            $startMonth = $waktuYearMonth;
            $customer->updateMonthlyBalances($startMonth);
        }

        // Check if there's a return URL from FOB detail page
        if ($request->has('return_to_fob') && $request->return_to_fob) {
            return redirect()->route('data-pencatatan.fob-detail', $customer->id)
                ->with('success', 'Data pengambilan berhasil diperbarui dan disinkronkan dengan FOB.');
        }

        return redirect()->route('rekap-pengambilan.index')
            ->with('success', 'Data pengambilan berhasil diperbarui.');
    }

    public function destroy(RekapPengambilan $rekapPengambilan)
    {
        $customer = $rekapPengambilan->customer;
        $tanggal = $rekapPengambilan->tanggal;

        try {
            DB::beginTransaction();

            // Jika customer adalah FOB, hapus data pencatatan yang terkait dengan presisi tinggi
            if ($customer && $customer->isFOB()) {
                // PERBAIKAN: Gunakan relasi langsung atau pencarian yang lebih presisi
                $dataPencatatanToDelete = null;
                
                // 1. Coba gunakan relasi langsung jika ada
                if ($rekapPengambilan->dataPencatatan) {
                    $dataPencatatanToDelete = $rekapPengambilan->dataPencatatan;
                    \Illuminate\Support\Facades\Log::info('Menghapus data pencatatan FOB menggunakan relasi langsung', [
                        'rekap_id' => $rekapPengambilan->id,
                        'pencatatan_id' => $dataPencatatanToDelete->id,
                        'customer_id' => $customer->id,
                        'tanggal' => $tanggal->format('Y-m-d H:i:s')
                    ]);
                } else {
                    // 2. Jika relasi tidak ada, cari dengan kriteria presisi tinggi
                    $tanggalSearch = Carbon::parse($tanggal)->format('Y-m-d');
                    $volumeSearch = $rekapPengambilan->volume;
                    
                    // Cari data pencatatan yang matching berdasarkan tanggal DAN volume
                    $potentialMatches = \App\Models\DataPencatatan::where('customer_id', $customer->id)
                        ->where('data_input', 'like', '%"waktu":"%' . $tanggalSearch . '%"%')
                        ->get();
                    
                    // Filter lebih presisi berdasarkan volume
                    foreach ($potentialMatches as $match) {
                        $dataInput = json_decode($match->data_input, true) ?? [];
                        $matchVolume = floatval($dataInput['volume_sm3'] ?? 0);
                        $matchDate = isset($dataInput['waktu']) ? Carbon::parse($dataInput['waktu'])->format('Y-m-d') : null;
                        
                        // Match harus tepat berdasarkan tanggal DAN volume (dengan toleransi 0.01)
                        if ($matchDate === $tanggalSearch && abs($matchVolume - $volumeSearch) < 0.01) {
                            $dataPencatatanToDelete = $match;
                            break;
                        }
                    }
                    
                    if ($dataPencatatanToDelete) {
                        \Illuminate\Support\Facades\Log::info('Menghapus data pencatatan FOB dengan pencarian presisi', [
                            'rekap_id' => $rekapPengambilan->id,
                            'pencatatan_id' => $dataPencatatanToDelete->id,
                            'customer_id' => $customer->id,
                            'tanggal' => $tanggalSearch,
                            'volume_rekap' => $volumeSearch,
                            'volume_pencatatan' => floatval(json_decode($dataPencatatanToDelete->data_input, true)['volume_sm3'] ?? 0)
                        ]);
                    } else {
                        \Illuminate\Support\Facades\Log::warning('Data pencatatan tidak ditemukan untuk dihapus', [
                            'rekap_id' => $rekapPengambilan->id,
                            'customer_id' => $customer->id,
                            'tanggal' => $tanggalSearch,
                            'volume' => $volumeSearch,
                            'potential_matches_count' => $potentialMatches->count()
                        ]);
                    }
                }
                
                // Hapus data pencatatan jika ditemukan
                if ($dataPencatatanToDelete) {
                    $deletedHargaFinal = $dataPencatatanToDelete->harga_final;
                    $dataPencatatanToDelete->delete();
                    
                    \Illuminate\Support\Facades\Log::info('Data pencatatan FOB berhasil dihapus', [
                        'deleted_pencatatan_id' => $dataPencatatanToDelete->id,
                        'deleted_harga_final' => $deletedHargaFinal
                    ]);
                }

                // Rekalkulasi total pembelian customer
                $userController = new UserController();
                $userController->rekalkulasiTotalPembelianFob($customer);
                
                // Update saldo bulanan dari bulan data yang dihapus
                $startMonth = Carbon::parse($tanggal)->format('Y-m');
                $customer->updateMonthlyBalances($startMonth);
            }

            // Hapus rekap pengambilan
            $rekapPengambilan->delete();
            
            DB::commit();

            return redirect()->route('rekap-pengambilan.index')
                ->with('success', 'Data pengambilan berhasil dihapus.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Error saat menghapus rekap pengambilan: ' . $e->getMessage(), [
                'rekap_id' => $rekapPengambilan->id,
                'customer_id' => $customer->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('rekap-pengambilan.index')
                ->with('error', 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage());
        }
    }
}
