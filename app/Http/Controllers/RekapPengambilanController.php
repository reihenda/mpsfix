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
        // Get tanggal_mulai dan tanggal_akhir from request
        // Default: awal bulan sampai akhir bulan ini
        $tanggalMulai = $request->input('tanggal_mulai', now()->startOfMonth()->format('Y-m-d'));
        $tanggalAkhir = $request->input('tanggal_akhir', now()->endOfMonth()->format('Y-m-d'));

        // Validasi rentang tanggal maksimal 90 hari
        $startDate = Carbon::parse($tanggalMulai);
        $endDate = Carbon::parse($tanggalAkhir);
        $daysDiff = $startDate->diffInDays($endDate);

        if ($daysDiff > 90) {
            return redirect()->route('rekap-pengambilan.index')
                ->with('error', 'Rentang tanggal maksimal adalah 90 hari');
        }

        // Validasi tanggal akhir tidak boleh lebih kecil dari tanggal awal
        if ($endDate->lt($startDate)) {
            return redirect()->route('rekap-pengambilan.index')
                ->with('error', 'Tanggal akhir tidak boleh lebih kecil dari tanggal awal');
        }

        // Start with base query berdasarkan rentang tanggal
        // Gunakan whereDate untuk memastikan comparison hanya berdasarkan tanggal, bukan datetime
        $query = RekapPengambilan::whereDate('tanggal', '>=', $tanggalMulai)
            ->whereDate('tanggal', '<=', $tanggalAkhir)
            ->with('customer');

        // Filter berdasarkan pencarian data (customer, nopol, alamat, volume) jika ada
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                // Cari di nama customer
                $q->whereHas('customer', function ($customerQuery) use ($searchTerm) {
                    $customerQuery->where('name', 'like', '%' . $searchTerm . '%');
                })
                // Cari di nopol
                ->orWhere('nopol', 'like', '%' . $searchTerm . '%')
                // Cari di alamat pengambilan
                ->orWhere('alamat_pengambilan', 'like', '%' . $searchTerm . '%')
                // Cari di volume (konversi ke string untuk pencarian partial)
                ->orWhereRaw('CAST(volume AS CHAR) LIKE ?', ['%' . $searchTerm . '%']);
            });
        }

        // Get data dengan filter
        $rekapPengambilan = $query->get()->sortBy('customer.name');

        // Hitung total volume dalam rentang tanggal terpilih (tanpa filter search untuk konsistensi)
        $totalVolumeRentang = RekapPengambilan::whereDate('tanggal', '>=', $tanggalMulai)
            ->whereDate('tanggal', '<=', $tanggalAkhir)
            ->sum('volume');

        // Deteksi jika request adalah AJAX untuk pencarian real-time
        if ($request->ajax() && $request->has('search')) {
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
            'tanggalMulai',
            'tanggalAkhir',
            'totalVolumeRentang'
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
     * Find rekap pengambilan by customer, date, and volume, then redirect to edit.
     */
    public function findByDateAndVolume(User $customer, $date, $volume)
    {
        // Cari rekap pengambilan berdasarkan customer, tanggal, dan volume
        $rekapPengambilan = RekapPengambilan::where('customer_id', $customer->id)
            ->whereDate('tanggal', $date)
            ->where('volume', $volume)
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
                ->with('preset_volume', $volume)
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

            // Buat data pencatatan baru
            $dataPencatatan = new \App\Models\DataPencatatan();
            $dataPencatatan->customer_id = $validatedBase['customer_id'];
            $dataPencatatan->data_input = json_encode($dataInput);
            $dataPencatatan->nama_customer = $customer->name;
            $dataPencatatan->status_pembayaran = 'belum_lunas'; // Default status
            $dataPencatatan->harga_final = $hargaFinal;
            $dataPencatatan->save();

            // Periksa isi object DataPencatatan yang berhasil dibuat
            \Log::info('Data pencatatan FOB berhasil dibuat', [
                'id' => $dataPencatatan->id,
                'customer_id' => $dataPencatatan->customer_id,
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

            // Cari data pencatatan yang terkait berdasarkan tanggal
            // Karena kita tidak memiliki relasi langsung antara RekapPengambilan dan DataPencatatan
            // Format tanggal Y-m-d untuk pencarian JSON substring (lebih fleksibel dari whereJsonContains)
            $tanggalSearch = Carbon::parse($validatedBase['tanggal'])->format('Y-m-d');
            $dataPencatatan = \App\Models\DataPencatatan::where('customer_id', $customer->id)
                ->where('data_input', 'like', '%"waktu":"%' . $tanggalSearch . '%"%')
                ->first();

            if ($dataPencatatan) {
                // Update data pencatatan yang ada
                $dataPencatatan->data_input = json_encode($dataInput);
                $dataPencatatan->harga_final = $hargaFinal;
                $dataPencatatan->save();
                
                \Log::info('Updated existing data_pencatatan', [
                    'data_pencatatan_id' => $dataPencatatan->id,
                    'harga_final' => $hargaFinal
                ]);
            } else {
                // Buat data pencatatan baru jika tidak ditemukan
                $dataPencatatan = new \App\Models\DataPencatatan();
                $dataPencatatan->customer_id = $validatedBase['customer_id'];
                $dataPencatatan->data_input = json_encode($dataInput);
                $dataPencatatan->nama_customer = $customer->name;
                $dataPencatatan->status_pembayaran = 'belum_lunas'; // Default status
                $dataPencatatan->harga_final = $hargaFinal;
                $dataPencatatan->save();
                
                \Log::info('Created new data_pencatatan', [
                    'data_pencatatan_id' => $dataPencatatan->id,
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

        // PERBAIKAN: Cek apakah request datang dari halaman FOB detail
        // Bisa dari session, parameter request, atau HTTP referrer
        $returnToFob = $request->has('return_to_fob') || 
                      session()->has('return_to_fob') ||
                      (str_contains($request->header('referer', ''), '/data-pencatatan/fob/') && 
                       str_contains($request->header('referer', ''), '/detail'));

        if ($returnToFob && $customer) {
            return redirect()->route('data-pencatatan.fob-detail', $customer->id)
                ->with('success', 'Data pengambilan berhasil diperbarui dan disinkronkan dengan FOB.');
        }

        return redirect()->route('rekap-pengambilan.index')
            ->with('success', 'Data pengambilan berhasil diperbarui.');
    }

    public function destroy(Request $request, RekapPengambilan $rekapPengambilan)
    {
        $customer = $rekapPengambilan->customer;
        $tanggal = $rekapPengambilan->tanggal;

        // Jika customer adalah FOB, hapus juga data di data_pencatatan untuk menjaga konsistensi
        if ($customer && $customer->isFOB()) {
            // Cari dan hapus data pencatatan terkait berdasarkan customer_id dan tanggal
            // Format tanggal Y-m-d untuk pencarian JSON substring (lebih fleksibel dari whereJsonContains)
            $tanggalSearch = Carbon::parse($tanggal)->format('Y-m-d');
            $deleteResult = \App\Models\DataPencatatan::where('customer_id', $customer->id)
                ->where('data_input', 'like', '%"waktu":"%' . $tanggalSearch . '%"%')
                ->delete();

            // Log penghapusan data
            \Illuminate\Support\Facades\Log::info('Menghapus data pencatatan FOB dari rekap pengambilan', [
                'customer_id' => $customer->id,
                'tanggal' => $tanggalSearch,
                'deleted_count' => $deleteResult
            ]);

            // Rekalkulasi total pembelian customer
            $userController = new UserController();
            $userController->rekalkulasiTotalPembelianFob($customer);
            
            // Update saldo bulanan dari bulan data yang dihapus
            $startMonth = Carbon::parse($tanggal)->format('Y-m');
            $customer->updateMonthlyBalances($startMonth);
        }

        // Hapus rekap pengambilan
        $rekapPengambilan->delete();

        // PERBAIKAN: Cek apakah request datang dari halaman FOB detail
        // Bisa dari parameter request atau HTTP referrer
        $returnToFob = $request->has('return_to_fob') || 
                      (str_contains($request->header('referer', ''), '/data-pencatatan/fob/') && 
                       str_contains($request->header('referer', ''), '/detail'));

        if ($returnToFob && $customer) {
            return redirect()->route('data-pencatatan.fob-detail', $customer->id)
                ->with('success', 'Data pengambilan berhasil dihapus dari FOB.');
        }

        return redirect()->route('rekap-pengambilan.index')
            ->with('success', 'Data pengambilan berhasil dihapus.');
    }
}
