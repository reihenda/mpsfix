<?php

namespace App\Http\Controllers;

use App\Models\OperatorTripPerbaikan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OperatorTripPerbaikanReviewController extends Controller
{
    public function index()
    {
        $pending = OperatorTripPerbaikan::with('operator')
            ->where('status', 'pending')
            ->orderBy('tanggal')
            ->get();

        $riwayat = OperatorTripPerbaikan::with('operator')
            ->whereIn('status', ['approved', 'ditolak'])
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        return view('operator-trip-perbaikan.index', compact('pending', 'riwayat'));
    }

    public function approve(OperatorTripPerbaikan $operatorTripPerbaikan)
    {
        $operatorTripPerbaikan->update([
            'status' => 'approved',
            'reviewed_by_user_id' => Auth::id(),
        ]);

        return redirect()->route('operator-trip-perbaikan.index')
            ->with('success', 'Pengajuan perbaikan trip disetujui. Akan ikut masuk ke pengelompokan sesi.');
    }

    public function reject(Request $request, OperatorTripPerbaikan $operatorTripPerbaikan)
    {
        $validated = $request->validate([
            'catatan_admin' => 'required|string|max:500',
        ]);

        $operatorTripPerbaikan->update([
            'status' => 'ditolak',
            'reviewed_by_user_id' => Auth::id(),
            'catatan_admin' => $validated['catatan_admin'],
        ]);

        return redirect()->route('operator-trip-perbaikan.index')
            ->with('success', 'Pengajuan perbaikan trip ditolak.');
    }
}
