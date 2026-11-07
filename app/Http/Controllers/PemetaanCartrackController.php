<?php

namespace App\Http\Controllers;

use App\Models\NomorPolisi;
use App\Models\OperatorGtm;
use App\Services\Cartrack\CartrackDirectory;
use App\Services\GpsTrip\GpsTripImporter;
use Illuminate\Http\Request;
use Throwable;

/**
 * Pemetaan operator (driver kita) -> driver Cartrack, dan pengecekan nopol kita <-> kendaraan Cartrack.
 */
class PemetaanCartrackController extends Controller
{
    public function __construct(private CartrackDirectory $direktori)
    {
    }

    public function index()
    {
        $galat = null;
        $driverCartrack = [];
        $kendaraanCartrack = [];

        try {
            $driverCartrack = $this->direktori->driver();
            $kendaraanCartrack = $this->direktori->kendaraan();
        } catch (Throwable $e) {
            $galat = $e->getMessage();
        }

        $operators = OperatorGtm::orderBy('nama')->get(['id', 'nama', 'cartrack_driver_id']);
        $dipakai = $operators->pluck('cartrack_driver_id')->filter()->all();

        $saran = [];
        foreach ($operators as $op) {
            if (!$op->cartrack_driver_id && $driverCartrack) {
                $saran[$op->id] = $this->direktori->sarankanDriver($op, $dipakai);
            }
        }

        // Kendaraan: cocokkan lewat kunci nopol (spasi dan huruf O/angka 0 diabaikan)
        $kunciCartrack = [];
        foreach ($kendaraanCartrack as $v) {
            $kunciCartrack[GpsTripImporter::kunciNopol($v['registration'])] = $v['registration'];
        }
        $nopolKita = NomorPolisi::orderBy('nopol')->pluck('nopol');
        $kunciKita = $nopolKita->mapWithKeys(fn ($n) => [GpsTripImporter::kunciNopol($n) => $n]);

        return view('pemetaan-cartrack.index', [
            'galat' => $galat,
            'operators' => $operators,
            'driverCartrack' => $driverCartrack,
            'saran' => $saran,
            // Yang ada di dua sisi jadi daftar utama; selisihnya ditampilkan terlipat di view
            'kendaraanCocok' => $nopolKita
                ->map(fn ($n) => ['nopol' => $n, 'registrasi' => $kunciCartrack[GpsTripImporter::kunciNopol($n)] ?? null])
                ->filter(fn ($k) => $k['registrasi'] !== null)
                ->values(),
            'kendaraanHanyaDiCartrack' => collect($kunciCartrack)->except($kunciKita->keys()->all())->values(),
            'kendaraanHanyaDiKita' => $kendaraanCartrack
                ? $nopolKita->filter(fn ($n) => !isset($kunciCartrack[GpsTripImporter::kunciNopol($n)]))->values()
                : collect(),
        ]);
    }

    public function simpan(Request $request)
    {
        $data = $request->validate([
            'peta' => 'required|array',
            'peta.*' => 'nullable|string|max:36',
        ]);

        $driverValid = array_keys($this->direktori->driver());
        $terpakai = [];
        $galat = [];

        foreach ($data['peta'] as $operatorId => $driverId) {
            $driverId = trim((string) $driverId) ?: null;
            if ($driverId === null) {
                continue;
            }
            if (!in_array($driverId, $driverValid, true)) {
                $galat[] = "Driver Cartrack untuk operator #{$operatorId} tidak ditemukan.";
            } elseif (isset($terpakai[$driverId])) {
                $galat[] = 'Satu driver Cartrack tidak boleh dipetakan ke dua operator: '.$this->direktori->namaDriver($driverId).'.';
            }
            $terpakai[$driverId] = true;
        }

        if ($galat) {
            return back()->withInput()->withErrors($galat);
        }

        $disimpan = 0;
        foreach (OperatorGtm::whereIn('id', array_keys($data['peta']))->get() as $op) {
            $baru = trim((string) ($data['peta'][$op->id] ?? '')) ?: null;
            if ($op->cartrack_driver_id !== $baru) {
                $op->update(['cartrack_driver_id' => $baru]);
                $disimpan++;
            }
        }

        return back()->with('success', "Pemetaan disimpan ({$disimpan} perubahan).");
    }

    public function segarkan()
    {
        $this->direktori->lupakan();

        return back()->with('success', 'Data kendaraan & driver Cartrack disegarkan.');
    }
}
