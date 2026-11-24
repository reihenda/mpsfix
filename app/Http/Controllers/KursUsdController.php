<?php

namespace App\Http\Controllers;

use App\Models\KursUsdBulanan;
use App\Models\User;
use App\Services\CurrencyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Kelola kurs USD -> IDR per bulan (kurs historis) untuk customer MMBTU.
 */
class KursUsdController extends Controller
{
    public function index(Request $request)
    {
        $daftar = KursUsdBulanan::orderByDesc('periode')->get();
        $edit = $request->filled('edit') ? KursUsdBulanan::where('periode', $request->edit)->first() : null;

        return view('kurs-usd.index', [
            'daftar' => $daftar,
            'edit' => $edit,
            'bulanKosong' => $this->bulanMmbtuTanpaKurs($daftar->pluck('periode')->all()),
            'periodeDefault' => $edit->periode ?? now()->format('Y-m'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'periode' => 'required|date_format:Y-m',
            'kurs' => 'required|numeric|min:1',
            'sumber' => 'nullable|in:manual,realtime',
            'catatan' => 'nullable|string|max:255',
        ]);

        KursUsdBulanan::updateOrCreate(
            ['periode' => $data['periode']],
            [
                'kurs' => $data['kurs'],
                'sumber' => $data['sumber'] ?? 'manual',
                'catatan' => $data['catatan'] ?? null,
                'diinput_oleh' => Auth::id(),
            ]
        );
        KursUsdBulanan::lupakanCache();

        $label = Carbon::createFromFormat('Y-m', $data['periode'])->locale('id')->translatedFormat('F Y');
        return redirect()->route('kurs-usd.index')->with('success', "Kurs {$label} disimpan: Rp " . number_format($data['kurs'], 2, ',', '.'));
    }

    public function destroy(KursUsdBulanan $kurs)
    {
        $label = $kurs->periode_label;
        $kurs->delete();
        KursUsdBulanan::lupakanCache();

        return redirect()->route('kurs-usd.index')->with('success', "Kurs {$label} dihapus.");
    }

    /** Kurs real-time (USD -> IDR) untuk tombol bantu di form. */
    public function realtime(CurrencyService $currency)
    {
        try {
            $rate = $currency->refreshRate();
            return response()->json(['success' => true, 'rate' => round((float) $rate, 2)]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengambil kurs real-time.'], 500);
        }
    }

    /** Bulan yang punya transaksi customer MMBTU tetapi kursnya belum diisi. */
    private function bulanMmbtuTanpaKurs(array $periodeAda): array
    {
        $butuh = [];
        foreach (User::where('role', User::ROLE_MMBTU)->get() as $customer) {
            foreach ($customer->bulanTransaksiMmbtu() as $periode) {
                $butuh[$periode] = true;
            }
        }
        $kosong = array_values(array_diff(array_keys($butuh), $periodeAda));
        sort($kosong);
        return $kosong;
    }
}
