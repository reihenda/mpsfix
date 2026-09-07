<?php

namespace App\Http\Controllers;

use App\Models\OperatorGtm;
use App\Models\OperatorGtmLembur;
use App\Models\OperatorTrip;
use App\Models\OperatorTripPerbaikan;
use Illuminate\Http\Request;

class OperatorTripSesiController extends Controller
{
    public function index(OperatorGtm $operatorGtm, Request $request)
    {
        $tanggal = $request->query('tanggal') ?: $this->tanggalUngroupedTerbaru($operatorGtm) ?: now()->toDateString();

        $kandidatSesi = $this->buildKandidatSesi($operatorGtm, $tanggal);
        $lembur = OperatorGtmLembur::where('operator_gtm_id', $operatorGtm->id)
            ->where('tanggal', $tanggal)
            ->first();

        return view('operator-gtm.trip-sesi', compact('operatorGtm', 'tanggal', 'kandidatSesi', 'lembur'));
    }

    public function store(OperatorGtm $operatorGtm, Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'items' => 'array',
            'items.*' => 'nullable|string',
            'sesi' => 'array',
            'sesi.*' => 'nullable|integer|min:1|max:5',
        ]);

        // Kumpulkan semua item-ref per nomor sesi yang dipilih admin (bisa gabungan beberapa kandidat)
        $assignments = [];
        foreach ($validated['sesi'] ?? [] as $index => $sesiKe) {
            if (!$sesiKe) {
                continue;
            }
            $refs = array_filter(explode(',', $validated['items'][$index] ?? ''));
            foreach ($refs as $ref) {
                $assignments[$sesiKe][] = $ref;
            }
        }

        if (empty($assignments)) {
            return redirect()->route('operator-gtm.trip-sesi', ['operatorGtm' => $operatorGtm->id, 'tanggal' => $validated['tanggal']])
                ->with('error', 'Tidak ada kandidat sesi yang dipilih.');
        }

        $lembur = OperatorGtmLembur::firstOrNew([
            'operator_gtm_id' => $operatorGtm->id,
            'tanggal' => $validated['tanggal'],
        ]);

        $tripIdsPerSesi = [];
        $perbaikanIdsPerSesi = [];

        foreach ($assignments as $sesiKe => $refs) {
            $waktuList = [];
            $tripIds = [];
            $perbaikanIds = [];

            foreach ($refs as $ref) {
                [$type, $id] = explode(':', $ref, 2);

                if ($type === 'trip') {
                    $trip = OperatorTrip::find($id);
                    if (!$trip) {
                        continue;
                    }
                    $tripIds[] = $trip->id;
                    if ($trip->waktu_berangkat) {
                        $waktuList[] = $trip->waktu_berangkat;
                    }
                    if ($trip->waktu_sampai) {
                        $waktuList[] = $trip->waktu_sampai;
                    }
                } elseif ($type === 'perbaikan') {
                    $perbaikan = OperatorTripPerbaikan::find($id);
                    if (!$perbaikan) {
                        continue;
                    }
                    $perbaikanIds[] = $perbaikan->id;
                    if ($perbaikan->waktu_klaim) {
                        $waktuList[] = $perbaikan->waktu_klaim;
                    }
                }
            }

            if (empty($waktuList)) {
                continue;
            }

            sort($waktuList);
            $jamMasuk = reset($waktuList);
            $jamKeluar = end($waktuList);

            $lembur->{"jam_masuk_sesi_{$sesiKe}"} = $jamMasuk->format('H:i:s');
            $lembur->{"jam_keluar_sesi_{$sesiKe}"} = $jamKeluar->format('H:i:s');

            $tripIdsPerSesi[$sesiKe] = $tripIds;
            $perbaikanIdsPerSesi[$sesiKe] = $perbaikanIds;
        }

        // Hitung ulang total jam kerja & lembur dari SELURUH sesi 1-5 yang ada di record ini
        // (termasuk sesi yang sudah diisi sebelumnya lewat input manual admin atau pengelompokan sebelumnya)
        // supaya tetap satu sumber kebenaran sesuai OperatorGtmLembur::calculateUpahLembur.
        $lembur->operator_gtm_id = $operatorGtm->id;
        $lembur->tanggal = $validated['tanggal'];
        $lembur->save();

        $sesiData = $lembur->only([
            'jam_masuk_sesi_1', 'jam_keluar_sesi_1',
            'jam_masuk_sesi_2', 'jam_keluar_sesi_2',
            'jam_masuk_sesi_3', 'jam_keluar_sesi_3',
            'jam_masuk_sesi_4', 'jam_keluar_sesi_4',
            'jam_masuk_sesi_5', 'jam_keluar_sesi_5',
        ]);

        $totalJamKerja = OperatorGtmLembur::calculateTotalWorkingHours($sesiData);
        $lemburCalc = OperatorGtmLembur::calculateUpahLembur($totalJamKerja, $operatorGtm->jam_kerja);

        $lembur->total_jam_kerja = $totalJamKerja;
        $lembur->total_jam_lembur = $lemburCalc['total_jam_lembur'];
        $lembur->upah_lembur = $lemburCalc['upah_lembur'];
        $lembur->save();

        foreach ($tripIdsPerSesi as $sesiKe => $tripIds) {
            OperatorTrip::whereIn('id', $tripIds)->update([
                'sesi_ke' => $sesiKe,
                'operator_gtm_lembur_id' => $lembur->id,
            ]);
        }

        foreach ($perbaikanIdsPerSesi as $sesiKe => $perbaikanIds) {
            OperatorTripPerbaikan::whereIn('id', $perbaikanIds)->update([
                'sesi_ke' => $sesiKe,
            ]);
        }

        return redirect()->route('operator-gtm.trip-sesi', ['operatorGtm' => $operatorGtm->id, 'tanggal' => $validated['tanggal']])
            ->with('success', 'Pengelompokan sesi berhasil disimpan.');
    }

    /**
     * Auto-suggest kandidat sesi: kelompokkan trip/perbaikan yang belum ter-assign
     * secara berurutan berdasarkan waktu, kandidat baru dimulai setiap kali tujuan berubah.
     * Tidak ada aturan berbasis waktu/jeda sama sekali.
     */
    private function buildKandidatSesi(OperatorGtm $operatorGtm, string $tanggal): array
    {
        $trips = OperatorTrip::where('operator_gtm_id', $operatorGtm->id)
            ->where('tanggal', $tanggal)
            ->where('status', 'selesai')
            ->whereNull('sesi_ke')
            ->get()
            ->map(function ($trip) {
                return [
                    'ref' => 'trip:' . $trip->id,
                    'waktu_urut' => $trip->waktu_berangkat,
                    'asal_nama' => $trip->asal_nama,
                    'tujuan_nama' => $trip->tujuan_nama,
                    'label' => $trip->asal_nama . ' → ' . $trip->tujuan_nama
                        . ' (' . $trip->waktu_berangkat->format('H:i') . '-' . optional($trip->waktu_sampai)->format('H:i') . ')',
                ];
            });

        $perbaikans = OperatorTripPerbaikan::where('operator_gtm_id', $operatorGtm->id)
            ->where('tanggal', $tanggal)
            ->where('status', 'approved')
            ->whereNull('sesi_ke')
            ->get()
            ->map(function ($item) {
                return [
                    'ref' => 'perbaikan:' . $item->id,
                    'waktu_urut' => $item->waktu_klaim,
                    'asal_nama' => $item->asal_nama,
                    'tujuan_nama' => $item->tujuan_nama,
                    'label' => $item->asal_nama . ' → ' . $item->tujuan_nama
                        . ' (perbaikan, ' . optional($item->waktu_klaim)->format('H:i') . ')',
                ];
            });

        $combined = $trips->concat($perbaikans)
            ->filter(fn ($item) => $item['waktu_urut'] !== null)
            ->sortBy('waktu_urut')
            ->values();

        $kandidat = [];
        $current = null;

        foreach ($combined as $item) {
            if (!$current || $current['tujuan_nama'] !== $item['tujuan_nama']) {
                if ($current) {
                    $kandidat[] = $current;
                }
                $current = [
                    'asal_nama' => $item['asal_nama'],
                    'tujuan_nama' => $item['tujuan_nama'],
                    'items' => [],
                    'refs' => [],
                ];
            }
            $current['items'][] = $item;
            $current['refs'][] = $item['ref'];
        }
        if ($current) {
            $kandidat[] = $current;
        }

        return $kandidat;
    }

    private function tanggalUngroupedTerbaru(OperatorGtm $operatorGtm): ?string
    {
        $trip = OperatorTrip::where('operator_gtm_id', $operatorGtm->id)
            ->where('status', 'selesai')
            ->whereNull('sesi_ke')
            ->orderByDesc('tanggal')
            ->first();

        return $trip?->tanggal?->toDateString();
    }
}
