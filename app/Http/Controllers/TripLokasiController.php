<?php

namespace App\Http\Controllers;

use App\Models\TripLokasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TripLokasiController extends Controller
{
    public function index()
    {
        $pending = TripLokasi::where('status', 'pending')->orderBy('created_at')->get();
        $riwayat = TripLokasi::whereIn('status', ['approved', 'ditolak'])->orderByDesc('updated_at')->limit(50)->get();

        return view('trip-lokasi.index', compact('pending', 'riwayat'));
    }

    public function approve(Request $request, TripLokasi $tripLokasi)
    {
        $validated = $request->validate([
            'nama_lokasi' => 'required|string|max:255',
        ]);

        $tripLokasi->update([
            'nama_lokasi' => $validated['nama_lokasi'],
            'status' => 'approved',
            'approved_by_user_id' => Auth::id(),
        ]);

        return redirect()->route('trip-lokasi.index')
            ->with('success', 'Lokasi "' . $tripLokasi->nama_lokasi . '" berhasil disetujui.');
    }

    public function reject(TripLokasi $tripLokasi)
    {
        $tripLokasi->update([
            'status' => 'ditolak',
            'approved_by_user_id' => Auth::id(),
        ]);

        return redirect()->route('trip-lokasi.index')
            ->with('success', 'Lokasi "' . $tripLokasi->nama_lokasi . '" ditolak.');
    }
}
