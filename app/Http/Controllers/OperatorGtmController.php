<?php

namespace App\Http\Controllers;

use App\Models\OperatorGtm;
use App\Models\OperatorGtmLembur;
use App\Models\KonfigurasiLembur;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OperatorGtmController extends Controller
{
    /**
     * Menampilkan daftar operator GTM
     */
    public function index()
    {
        // Ambil data operator dengan informasi update terakhir berdasarkan tanggal lembur
        $operators = OperatorGtm::with(['lemburRecords' => function($query) {
            $query->orderBy('tanggal', 'desc')->orderBy('created_at', 'desc')->limit(1);
        }])->paginate(10);
        
        return view('operator-gtm.index', compact('operators'));
    }

    /**
     * Menampilkan form untuk menambah operator baru
     */
    public function create()
    {
        return view('operator-gtm.create');
    }

    /**
     * Menyimpan operator baru ke database
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
            'lokasi_kerja' => 'required|string|max:255',
            'gaji_pokok' => 'required|numeric|min:0',
            'jam_kerja' => 'required|integer|in:8,10',
            'tanggal_bergabung' => 'required|date',
        ]);

        OperatorGtm::create($validatedData);

        return redirect()->route('operator-gtm.index')
            ->with('success', 'Operator GTM berhasil ditambahkan');
    }

    /**
     * Menampilkan detail operator tertentu
     */
    public function show(Request $request, OperatorGtm $operatorGtm)
    {
        // Ambil semua data lembur untuk operator ini (tidak difilter di database)
        $lemburRecords = $operatorGtm->lemburRecords()->orderBy('tanggal', 'asc')->get();
        
        // Set default ke bulan dan tahun saat ini jika tidak ada parameter
        $selectedMonth = $request->input('month', date('m'));
        $selectedYear = $request->input('year', date('Y'));
        
        // Hitung periode tanggal
        $displayMonth = $selectedMonth;
        $displayYear = $selectedYear;
        
        // Tanggal 26 bulan sebelumnya
        $prevMonth = $displayMonth == '01' ? '12' : str_pad((int)$displayMonth - 1, 2, '0', STR_PAD_LEFT);
        $prevYear = $displayMonth == '01' ? $displayYear - 1 : $displayYear;
        $startDate = Carbon::createFromFormat('Y-m-d', $prevYear . '-' . $prevMonth . '-26');
        
        // Tanggal 25 bulan yang dipilih (untuk tampilan periode data lembur)
        $endDate = Carbon::createFromFormat('Y-m-d', $displayYear . '-' . $displayMonth . '-25');
        
        // Tanggal akhir bulan yang dipilih (untuk perhitungan hari kerja gaji)
        $endOfMonth = Carbon::createFromFormat('Y-m-d', $displayYear . '-' . $displayMonth . '-01')->endOfMonth();
        
        // Log untuk debugging
        \Log::info('Periode data lembur: ' . $startDate->format('Y-m-d') . ' s/d ' . $endDate->format('Y-m-d'));
        \Log::info('Periode gaji: ' . $startDate->format('Y-m-d') . ' s/d ' . $endOfMonth->format('Y-m-d'));
        
        // Buat array tanggal untuk periode penuh dan tandai tanggal yang memiliki data
        $allDatesInPeriod = [];
        $currentDate = clone $startDate;
        while ($currentDate->lte($endDate)) {
            $dateKey = $currentDate->format('Y-m-d');
            $allDatesInPeriod[$dateKey] = null;
            $currentDate->addDay();
        }
        
        // Masukkan data lembur yang ada ke array berdasarkan tanggal
        foreach ($lemburRecords as $record) {
            $recordDateStr = date('Y-m-d', strtotime($record->tanggal));
            
            if (array_key_exists($recordDateStr, $allDatesInPeriod)) {
                $allDatesInPeriod[$recordDateStr] = $record;
            } else {
                // Cek jika tanggalnya close match (mungkin ada masalah timezone atau format)
                foreach (array_keys($allDatesInPeriod) as $periodDate) {
                    $diff = abs(strtotime($recordDateStr) - strtotime($periodDate));
                    if ($diff < 86400) { // selisih kurang dari 1 hari (dalam detik)
                        $allDatesInPeriod[$periodDate] = $record;
                        break;
                    }
                }
            }
        }
        
        // Cek apakah ada data untuk sesi 4 atau 5
        $hasSesi4 = false;
        $hasSesi5 = false;
        foreach ($allDatesInPeriod as $date => $record) {
            if ($record) {
                if ($record->jam_masuk_sesi_4 || $record->jam_keluar_sesi_4) {
                    $hasSesi4 = true;
                }
                if ($record->jam_masuk_sesi_5 || $record->jam_keluar_sesi_5) {
                    $hasSesi5 = true;
                }
            }
        }
        
        // Log untuk debugging
        \Log::info('Total data lembur operator: ' . count($lemburRecords));
        foreach ($lemburRecords as $record) {
            \Log::info('Record: ID=' . $record->id . ' | Tanggal=' . $record->tanggal . ' | Format=' . date('Y-m-d', strtotime($record->tanggal)) . 
            ' | Raw=' . var_export($record->getOriginal('tanggal'), true));
        }
        
        // Log untuk debugging periode yang dipilih
        \Log::info('Filter periode - bulan: ' . $selectedMonth . ', tahun: ' . $selectedYear);
            
        return view('operator-gtm.show', compact(
            'operatorGtm', 
            'lemburRecords', 
            'selectedMonth', 
            'selectedYear',
            'allDatesInPeriod',
            'hasSesi4',
            'hasSesi5',
            'startDate',
            'endDate',
            'endOfMonth'
        ));
    }

    /**
     * Menampilkan form untuk mengedit operator
     */
    public function edit(OperatorGtm $operatorGtm)
    {
        return view('operator-gtm.edit', compact('operatorGtm'));
    }

    /**
     * Menyimpan perubahan pada operator ke database
     */
    public function update(Request $request, OperatorGtm $operatorGtm)
    {
        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
            'lokasi_kerja' => 'required|string|max:255',
            'gaji_pokok' => 'required|numeric|min:0',
            'jam_kerja' => 'required|integer|in:8,10',
            'tanggal_bergabung' => 'required|date',
        ]);

        $operatorGtm->update($validatedData);

        return redirect()->route('operator-gtm.show', $operatorGtm->id)
            ->with('success', 'Data operator GTM berhasil diperbarui');
    }

    /**
     * Menghapus operator dari database
     */
    public function destroy(OperatorGtm $operatorGtm)
    {
        $operatorGtm->delete();

        return redirect()->route('operator-gtm.index')
            ->with('success', 'Operator GTM berhasil dihapus');
    }

    /**
     * Menampilkan form untuk tambah data lembur
     */
    public function createLembur(Request $request, OperatorGtm $operatorGtm)
    {
        // Periksa apakah ada parameter tanggal di URL
        $tanggal = $request->query('tanggal') ?: date('Y-m-d');
        
        return view('operator-gtm.create-lembur', compact('operatorGtm', 'tanggal'));
    }

    /**
     * Menyimpan data lembur baru
     */
    public function storeLembur(Request $request, OperatorGtm $operatorGtm)
    {
        $validatedData = $request->validate([
            'tanggal' => 'required|date',
            'jam_masuk_sesi_1' => 'nullable|date_format:H:i',
            'jam_keluar_sesi_1' => 'nullable|date_format:H:i',
            'jam_masuk_sesi_2' => 'nullable|date_format:H:i',
            'jam_keluar_sesi_2' => 'nullable|date_format:H:i',
            'jam_masuk_sesi_3' => 'nullable|date_format:H:i',
            'jam_keluar_sesi_3' => 'nullable|date_format:H:i',
            'jam_masuk_sesi_4' => 'nullable|date_format:H:i',
            'jam_keluar_sesi_4' => 'nullable|date_format:H:i',
            'jam_masuk_sesi_5' => 'nullable|date_format:H:i',
            'jam_keluar_sesi_5' => 'nullable|date_format:H:i',
        ]);

        // Filter data untuk menghapus sesi yang kosong
        $filteredData = $this->filterEmptySessions($validatedData);
        
        // Hitung total jam kerja untuk semua sesi
        $totalJamKerja = $this->calculateTotalWorkingHours($request);
        
        \Log::info('Total jam kerja: ' . $totalJamKerja . ' menit');
        
        // Hitung jam lembur berdasarkan jam kerja operator (8 jam = 480 menit, 10 jam = 600 menit)
        $jamKerjaMenit = ($operatorGtm->jam_kerja ?? 8) * 60;
        $jamLembur = max(0, $totalJamKerja - $jamKerjaMenit);
        \Log::info('Jam kerja operator: ' . ($operatorGtm->jam_kerja ?? 8) . ' jam (' . $jamKerjaMenit . ' menit)');
        \Log::info('Jam lembur: ' . $jamLembur . ' menit');
        
        // Ambil tarif lembur dari konfigurasi
        $upahPerJam = KonfigurasiLembur::getTarifLembur();
        $upahLembur = ($jamLembur / 60) * $upahPerJam;
        
        // Tambahkan data perhitungan ke validated data
        $filteredData['total_jam_kerja'] = $totalJamKerja;
        $filteredData['total_jam_lembur'] = $jamLembur;
        $filteredData['upah_lembur'] = $upahLembur;
        $filteredData['operator_gtm_id'] = $operatorGtm->id;

        OperatorGtmLembur::create($filteredData);

        return redirect()->route('operator-gtm.show', $operatorGtm->id)
            ->with('success', 'Data lembur berhasil ditambahkan');
    }

    /**
     * Menampilkan form untuk edit data lembur
     */
    public function editLembur(OperatorGtmLembur $lembur)
    {
        $operatorGtm = $lembur->operator;
        return view('operator-gtm.edit-lembur', compact('lembur', 'operatorGtm'));
    }

    /**
     * Menyimpan perubahan data lembur
     */
    public function updateLembur(Request $request, OperatorGtmLembur $lembur)
    {
        $validatedData = $request->validate([
            'tanggal' => 'required|date',
            'jam_masuk_sesi_1' => 'nullable|date_format:H:i',
            'jam_keluar_sesi_1' => 'nullable|date_format:H:i',
            'jam_masuk_sesi_2' => 'nullable|date_format:H:i',
            'jam_keluar_sesi_2' => 'nullable|date_format:H:i',
            'jam_masuk_sesi_3' => 'nullable|date_format:H:i',
            'jam_keluar_sesi_3' => 'nullable|date_format:H:i',
            'jam_masuk_sesi_4' => 'nullable|date_format:H:i',
            'jam_keluar_sesi_4' => 'nullable|date_format:H:i',
            'jam_masuk_sesi_5' => 'nullable|date_format:H:i',
            'jam_keluar_sesi_5' => 'nullable|date_format:H:i',
        ]);

        // Filter data untuk menghapus sesi yang kosong
        $filteredData = $this->filterEmptySessions($validatedData);
        
        // Hitung total jam kerja untuk semua sesi
        $totalJamKerja = $this->calculateTotalWorkingHours($request);
        
        \Log::info('Total jam kerja: ' . $totalJamKerja . ' menit');
        
        // Hitung jam lembur berdasarkan jam kerja operator (8 jam = 480 menit, 10 jam = 600 menit)
        $jamKerjaMenit = ($lembur->operator->jam_kerja ?? 8) * 60;
        $jamLembur = max(0, $totalJamKerja - $jamKerjaMenit);
        \Log::info('Jam kerja operator: ' . ($lembur->operator->jam_kerja ?? 8) . ' jam (' . $jamKerjaMenit . ' menit)');
        \Log::info('Jam lembur: ' . $jamLembur . ' menit');
        
        // Ambil tarif lembur dari konfigurasi
        $upahPerJam = KonfigurasiLembur::getTarifLembur();
        $upahLembur = ($jamLembur / 60) * $upahPerJam;
        
        // Tambahkan data perhitungan ke validated data
        $filteredData['total_jam_kerja'] = $totalJamKerja;
        $filteredData['total_jam_lembur'] = $jamLembur;
        $filteredData['upah_lembur'] = $upahLembur;

        $lembur->update($filteredData);

        return redirect()->route('operator-gtm.show', $lembur->operator_gtm_id)
            ->with('success', 'Data lembur berhasil diperbarui');
    }

    /**
     * Menghapus data lembur
     */
    public function destroyLembur(OperatorGtmLembur $lembur)
    {
        $operatorId = $lembur->operator_gtm_id;
        $lembur->delete();

        return redirect()->route('operator-gtm.show', $operatorId)
            ->with('success', 'Data lembur berhasil dihapus');
    }

    /**
     * Helper method untuk menghitung total jam kerja dari semua sesi
     */
    private function calculateTotalWorkingHours(Request $request)
    {
        $totalJamKerja = 0;
        
        // Loop untuk semua sesi (1-5)
        for ($sesi = 1; $sesi <= 5; $sesi++) {
            $jamMasuk = $request->input("jam_masuk_sesi_{$sesi}");
            $jamKeluar = $request->input("jam_keluar_sesi_{$sesi}");
            
            if ($jamMasuk && $jamKeluar) {
                $durasi = $this->calculateSessionDuration($jamMasuk, $jamKeluar);
                $totalJamKerja += $durasi;
                \Log::info("Durasi sesi {$sesi}: {$durasi} menit");
            }
        }
        
        return $totalJamKerja;
    }

    /**
     * Helper method untuk menghitung durasi satu sesi
     */
    private function calculateSessionDuration($jamMasuk, $jamKeluar)
    {
        // Menggunakan tanggal hari ini sebagai basis
        $today = Carbon::today();
        
        // Parse input time dengan tanggal hari ini
        $masuk = Carbon::parse($today->format('Y-m-d') . ' ' . $jamMasuk);
        $keluar = Carbon::parse($today->format('Y-m-d') . ' ' . $jamKeluar);
        
        // Jika keluar lebih kecil dari masuk, artinya melewati tengah malam
        if ($keluar->lt($masuk)) {
            $keluar->addDay();
        }
        
        // Perhitungan durasi: jam keluar - jam masuk
        return $masuk->diffInMinutes($keluar);
    }

    /**
     * Helper method untuk memfilter sesi yang kosong (tidak disimpan ke database)
     */
    private function filterEmptySessions($data)
    {
        $filteredData = [];
        
        // Copy data non-sesi
        foreach ($data as $key => $value) {
            if (!str_contains($key, 'jam_masuk_sesi_') && !str_contains($key, 'jam_keluar_sesi_')) {
                $filteredData[$key] = $value;
            }
        }
        
        // Filter sesi yang memiliki kedua jam masuk dan keluar
        for ($sesi = 1; $sesi <= 5; $sesi++) {
            $jamMasuk = $data["jam_masuk_sesi_{$sesi}"] ?? null;
            $jamKeluar = $data["jam_keluar_sesi_{$sesi}"] ?? null;
            
            // Hanya simpan jika kedua jam terisi
            if ($jamMasuk && $jamKeluar) {
                $filteredData["jam_masuk_sesi_{$sesi}"] = $jamMasuk;
                $filteredData["jam_keluar_sesi_{$sesi}"] = $jamKeluar;
            }
        }
        
        return $filteredData;
    }
}