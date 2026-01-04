<?php

namespace App\Http\Controllers;

use App\Models\NomorPolisi;
use App\Models\RekapPengambilan;
use App\Models\Ukuran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NomorPolisiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $nomorPolisiList = NomorPolisi::with('ukuran')
            ->orderByRaw('CASE WHEN no_gtm IS NULL THEN 1 ELSE 0 END')
            ->orderBy('no_gtm', 'asc')
            ->get();
        $ukuranList = Ukuran::orderBy('nama_ukuran')->get();
        return view('nomor-polisi.index', compact('nomorPolisiList', 'ukuranList'));
    }

    /**
     * Generate No GTM berdasarkan status
     */
    private function generateNoGTM($status)
    {
        if ($status == 'milik' || $status == 'disewakan') {
            // Cari nomor GTM terakhir
            $lastNomorGTM = NomorPolisi::whereNotNull('no_gtm')
                ->where('no_gtm', 'like', 'MPS%')
                ->orderBy('no_gtm', 'desc')
                ->first();

            if ($lastNomorGTM) {
                // Ambil nomor urut terakhir
                $lastNumber = (int) substr($lastNomorGTM->no_gtm, 3);
                // Increment dan format dengan leading zeros
                $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
                return "MPS{$newNumber}";
            } else {
                // Jika belum ada, mulai dari 001
                return "MPS001";
            }
        }
        return null; // Jika status bukan milik atau disewakan
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            \Log::info('Request data:', $request->all());
            
            // Jika ukuran_id adalah 'tambah_baru', atur nilainya menjadi null agar tidak divalidasi
            if ($request->ukuran_id === 'tambah_baru') {
                $request->merge(['ukuran_id' => null]);
            }
            
            $validated = $request->validate([
                'nopol' => 'required|string|max:20|unique:nomor_polisi,nopol',
                'keterangan' => 'nullable|string|max:255',
                'jenis' => 'nullable|string|max:100',
                'ukuran_id' => 'nullable|exists:ukuran,id',
                'area_operasi' => 'nullable|string|max:100',
                'status' => 'nullable|in:milik,sewa,disewakan,FOB',
                'iso' => 'nullable|in:ISO - 11439,ISO - 11119',
                'coi' => 'nullable|in:sudah,belum',
            ]);

            // Jika ukuran_new diisi, buat ukuran baru
            if ($request->has('ukuran_new') && !empty($request->ukuran_new)) {
                try {
                    // Cek apakah ukuran sudah ada
                    $ukuran = Ukuran::firstOrCreate(
                        ['nama_ukuran' => $request->ukuran_new]
                    );
                    $validated['ukuran_id'] = $ukuran->id;
                    \Log::info('Created or found ukuran: ' . $ukuran->id . ' - ' . $ukuran->nama_ukuran);
                } catch (\Exception $e) {
                    \Log::error('Error creating ukuran: ' . $e->getMessage());
                    throw $e;
                }
            }

            // Generate No GTM jika status milik atau disewakan
            if (in_array($validated['status'] ?? '', ['milik', 'disewakan'])) {
                $validated['no_gtm'] = $this->generateNoGTM($validated['status']);
            }

            NomorPolisi::create($validated);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Nomor polisi berhasil ditambahkan.',
                    'nopol' => $validated['nopol'],
                    'id' => NomorPolisi::where('nopol', $validated['nopol'])->first()->id
                ]);
            }

            return redirect()->back()->with('success', 'Nomor polisi berhasil ditambahkan.');
        } catch (\Exception $e) {
            \Log::error('Error adding nomor polisi: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, NomorPolisi $nomorPolisi)
    {
        try {
            \Log::info('Update request data:', $request->all());
            
            // Jika ukuran_id adalah 'tambah_baru', atur nilainya menjadi null agar tidak divalidasi
            if ($request->ukuran_id === 'tambah_baru') {
                $request->merge(['ukuran_id' => null]);
            }
            
            $validated = $request->validate([
                'nopol' => 'required|string|max:20|unique:nomor_polisi,nopol,'.$nomorPolisi->id,
                'keterangan' => 'nullable|string|max:255',
                'jenis' => 'nullable|string|max:100',
                'ukuran_id' => 'nullable|exists:ukuran,id',
                'area_operasi' => 'nullable|string|max:100',
                'status' => 'nullable|in:milik,sewa,disewakan,FOB',
                'iso' => 'nullable|in:ISO - 11439,ISO - 11119',
                'coi' => 'nullable|in:sudah,belum',
            ]);
            
            // Jika ukuran_new diisi, buat ukuran baru
            if ($request->has('ukuran_new') && !empty($request->ukuran_new)) {
                try {
                    // Cek apakah ukuran sudah ada
                    $ukuran = Ukuran::firstOrCreate(
                        ['nama_ukuran' => $request->ukuran_new]
                    );
                    $validated['ukuran_id'] = $ukuran->id;
                    \Log::info('Created or found ukuran for update: ' . $ukuran->id . ' - ' . $ukuran->nama_ukuran);
                } catch (\Exception $e) {
                    \Log::error('Error creating ukuran for update: ' . $e->getMessage());
                    throw $e;
                }
            }
            
            // Generate No GTM jika status berubah menjadi milik atau disewakan
            if (in_array($validated['status'] ?? '', ['milik', 'disewakan']) && 
                ($nomorPolisi->status != $validated['status'] || empty($nomorPolisi->no_gtm))) {
                $validated['no_gtm'] = $this->generateNoGTM($validated['status']);
            } elseif ($validated['status'] == 'sewa') {
                $validated['no_gtm'] = null;
            }
            
            // Trim nopol value to remove whitespaces
            $validated['nopol'] = trim($validated['nopol']);
            
            // Check if we're actually changing the nopol value
            $nopolChanged = ($nomorPolisi->nopol != $validated['nopol']);
            
            if ($nopolChanged) {
                // Cek apakah nomor polisi baru sudah ada di tabel nomor_polisi
                $existingNopol = NomorPolisi::where('nopol', $validated['nopol'])->first();
                if ($existingNopol && $existingNopol->id != $nomorPolisi->id) {
                    return redirect()->back()->with('error', 'Nomor polisi ' . $validated['nopol'] . ' sudah digunakan.');
                }
                
                // Jika kita mengubah nilai nopol, jalankan dalam transaksi
                DB::beginTransaction();
                try {
                    // Log untuk debug
                    \Log::info('Updating nopol from ' . $nomorPolisi->nopol . ' to ' . $validated['nopol']);
                    
                    // Cari data rekap_pengambilan yang menggunakan nomor polisi lama
                    $rekapCount = RekapPengambilan::where('nopol', $nomorPolisi->getOriginal('nopol'))->count();
                    
                    // Pendekatan baru untuk mengatasi batasan foreign key
                    if ($rekapCount > 0) {
                        // 1. Simpan nomor polisi yang akan diupdate
                        $oldNopol = $nomorPolisi->nopol;
                        $newNopol = $validated['nopol'];
                        
                        // 2. Nonaktifkan foreign key checks
                        DB::statement('SET FOREIGN_KEY_CHECKS=0');
                        
                        try {
                            // 3. Update nomor polisi di tabel utama
                            $nomorPolisi->update($validated);
                            
                            // 4. Update semua rekap pengambilan terkait dengan query native
                            // Gunakan string asli agar tidak masalah dengan tanda kutip
                            $updateRekap = "UPDATE rekap_pengambilan SET nopol = '{$newNopol}' WHERE nopol = '{$oldNopol}'";
                            \Log::info('Running query: ' . $updateRekap);
                            $affectedRows = DB::statement($updateRekap);
                            \Log::info('Updated rekap records: ' . ($affectedRows ? 'success' : 'failure'));
                            
                            // 5. Aktifkan kembali foreign key checks
                            DB::statement('SET FOREIGN_KEY_CHECKS=1');
                        } catch (\Exception $innerEx) {
                            // Jika ada error, aktifkan kembali foreign key checks dan lempar exception
                            DB::statement('SET FOREIGN_KEY_CHECKS=1');
                            throw $innerEx;
                        }
                    } else {
                        // Jika tidak ada relasi, cukup update nomor polisi saja
                        $nomorPolisi->update($validated);
                    }
                    
                    DB::commit();
                    \Log::info('Transaction committed successfully');
                } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::error('Failed to update nopol: ' . $e->getMessage());
                    \Log::error($e->getTraceAsString());
                    return redirect()->back()->with('error', 'Gagal mengubah nomor polisi: ' . $e->getMessage());
                }
            } else {
                // Jika tidak mengubah nopol, cukup update data
                $nomorPolisi->update($validated);
            }
            
            return redirect()->back()->with('success', 'Nomor polisi berhasil diperbarui.');
        } catch (\Exception $e) {
            \Log::error('General error in update method: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(NomorPolisi $nomorPolisi)
    {
        // Check if the nopol is used in rekap_pengambilan
        $isUsed = RekapPengambilan::where('nopol', $nomorPolisi->nopol)->exists();

        if ($isUsed) {
            return redirect()->back()->with('error', 'Nomor polisi tidak dapat dihapus karena sedang digunakan dalam data pengambilan.');
        }

        $nomorPolisi->delete();

        return redirect()->back()->with('success', 'Nomor polisi berhasil dihapus.');
    }
    
    /**
     * Get all nomor polisi (for AJAX)
     */
    public function getAll()
    {
        $nopolList = NomorPolisi::select('id', 'nopol', 'keterangan', 'created_at')->orderBy('nopol')->get();
        return response()->json($nopolList);
    }
}