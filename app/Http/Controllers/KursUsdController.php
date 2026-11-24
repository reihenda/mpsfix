<?php

namespace App\Http\Controllers;

use App\Models\KursUsdBulanan;
use App\Models\User;
use App\Services\KursHistorisService;
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
            'sumber' => 'nullable|in:manual,api,realtime',
            'sumber_keterangan' => 'nullable|string|max:255',
            'catatan' => 'nullable|string|max:255',
        ]);
        $sumber = $data['sumber'] ?? 'manual';

        KursUsdBulanan::updateOrCreate(
            ['periode' => $data['periode']],
            [
                'kurs' => $data['kurs'],
                'sumber' => $sumber,
                // Keterangan asal kurs hanya disimpan untuk kurs dari sumber otomatis
                'sumber_keterangan' => $sumber === 'manual' ? null : ($data['sumber_keterangan'] ?? null),
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

    /**
     * Kurs bulan terpilih dari kurs referensi ECB (frankfurter.dev):
     * rata-rata harian dalam bulan itu, atau kurs hari kerja terakhirnya.
     */
    public function realtime(Request $request, KursHistorisService $kursHistoris)
    {
        $data = $request->validate([
            'periode' => 'required|date_format:Y-m',
            'metode' => 'nullable|in:rata,akhir',
        ]);

        try {
            $hasil = $kursHistoris->untukBulan($data['periode'], $data['metode'] ?? 'rata');
            return response()->json(['success' => true] + $hasil);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => $e instanceof \RuntimeException ? $e->getMessage() : 'Gagal mengambil kurs dari sumber ECB. Isi kurs secara manual.',
            ], 422);
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
