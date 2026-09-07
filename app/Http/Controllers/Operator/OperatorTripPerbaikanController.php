<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Concerns\CompressesTripPhotos;
use App\Http\Controllers\Controller;
use App\Models\OperatorTripPerbaikan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OperatorTripPerbaikanController extends Controller
{
    use CompressesTripPhotos;

    public function index()
    {
        $riwayat = OperatorTripPerbaikan::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('operator.perbaikan.index', compact('riwayat'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'tanggal' => 'required|date',
            'asal_nama' => 'required|string|max:255',
            'tujuan_nama' => 'required|string|max:255',
            'nopol' => 'nullable|string|max:20',
            'tekanan' => 'required|numeric|min:0',
            'waktu_klaim' => 'required|date',
            'keterangan' => 'required|string|max:500',
            'foto_bukti' => 'required|image|max:10240',
        ]);

        $fotoPath = $this->compressAndStorePhoto($request->file('foto_bukti'), 'trip-photos/perbaikan');

        OperatorTripPerbaikan::create([
            'operator_gtm_id' => $user->operator_gtm_id,
            'user_id' => $user->id,
            'tanggal' => $validated['tanggal'],
            'nopol' => $validated['nopol'] ?? null,
            'asal_nama' => $validated['asal_nama'],
            'tujuan_nama' => $validated['tujuan_nama'],
            'tekanan' => $validated['tekanan'],
            'foto_bukti_path' => $fotoPath,
            'waktu_klaim' => $validated['waktu_klaim'],
            'keterangan' => $validated['keterangan'],
            'status' => 'pending',
        ]);

        return redirect()->route('operator.perbaikan.index')
            ->with('success', 'Pengajuan perbaikan trip berhasil dikirim, menunggu review admin.');
    }
}
