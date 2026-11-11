<?php

namespace App\Http\Controllers;

use App\Models\GpsGeofence;
use App\Models\GpsTrip;
use App\Models\JadwalPerjalanan;
use App\Models\NomorPolisi;
use App\Models\OperatorGtm;
use App\Services\Cartrack\CartrackDirectory;
use App\Services\Cartrack\CartrackException;
use App\Services\Cartrack\JadwalPerjalananService;
use App\Services\GpsTrip\GpsTripImporter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Penjadwalan perjalanan (kendaraan + driver + rute) oleh staff operasional/admin, push driver ke
 * Cartrack, dan validasi trip hasil tarikan sebelum masuk hitungan lembur.
 */
class JadwalPerjalananController extends Controller
{
    public function __construct(
        private JadwalPerjalananService $service,
        private CartrackDirectory $direktori,
    ) {
    }

    public function index(Request $request)
    {
        $dari = $this->tanggal($request->query('dari')) ?? Carbon::today()->subDays(7);
        $sampai = $this->tanggal($request->query('sampai')) ?? Carbon::today()->addDays(14);
        $status = $request->query('status');
        $perluValidasi = $request->boolean('perlu_validasi');
        $cari = trim((string) $request->query('q'));

        $query = JadwalPerjalanan::with(['operator:id,nama', 'rute'])
            ->withCount([
                'trips',
                'trips as trips_belum_validasi_count' => fn ($q) => $q->where('divalidasi', false),
                'rute as rute_tercapai_count' => fn ($q) => $q->whereNotNull('tercapai_pada'),
            ])
            ->when(!$perluValidasi, fn ($q) => $q->where('waktu_mulai', '<=', $sampai->copy()->endOfDay())
                ->where(fn ($w) => $w->whereNull('waktu_selesai')->orWhere('waktu_selesai', '>=', $dari->copy()->startOfDay())))
            ->when($status && isset(JadwalPerjalanan::STATUS_LABEL[$status]), fn ($q) => $q->where('status', $status))
            ->when($perluValidasi, fn ($q) => $q->whereHas('trips', fn ($t) => $t->where('divalidasi', false)))
            ->when($cari !== '', fn ($q) => $q->where(function ($w) use ($cari) {
                $w->where('nopol', 'like', "%{$cari}%")
                    ->orWhereHas('operator', fn ($o) => $o->where('nama', 'like', "%{$cari}%"));
            }))
            ->orderByDesc('waktu_mulai');

        $ringkasan = [
            'berjalan' => JadwalPerjalanan::where('status', JadwalPerjalanan::STATUS_BERJALAN)->count(),
            'link_gagal' => JadwalPerjalanan::where('link_status', 'gagal')
                ->whereIn('status', [JadwalPerjalanan::STATUS_TERJADWAL, JadwalPerjalanan::STATUS_BERJALAN])->count(),
            'perlu_validasi' => GpsTrip::where('sumber', GpsTrip::SUMBER_CARTRACK)->where('divalidasi', false)
                ->whereNotNull('jadwal_perjalanan_id')->count(),
        ];

        return view('jadwal-perjalanan.index', [
            'jadwals' => $query->paginate(20)->withQueryString(),
            'dari' => $dari,
            'sampai' => $sampai,
            'status' => $status,
            'perluValidasi' => $perluValidasi,
            'cari' => $cari,
            'ringkasan' => $ringkasan,
            'detakPenjadwal' => JadwalPerjalananService::detakTerakhir(),
        ]);
    }

    public function create(Request $request)
    {
        $jadwal = new JadwalPerjalanan(['waktu_mulai' => Carbon::today()->setTime(7, 0)]);

        return view('jadwal-perjalanan.form', $this->dataForm($jadwal) + ['rute' => [['tujuan' => '', 'catatan' => '']]]);
    }

    public function store(Request $request)
    {
        $data = $this->validasiInput($request, null);

        $jadwal = DB::transaction(function () use ($data, $request) {
            $jadwal = JadwalPerjalanan::create([
                'nopol' => $data['nopol'],
                'operator_gtm_id' => $data['operator_gtm_id'],
                'waktu_mulai' => $data['waktu_mulai'],
                'catatan' => $data['catatan'] ?? null,
                'dibuat_oleh' => $request->user()->id,
            ]);
            $this->simpanRute($jadwal, $data['rute']);

            return $jadwal;
        });

        return redirect()->route('jadwal-perjalanan.show', $jadwal)
            ->with('success', 'Jadwal dibuat. '.$this->prosesSekarang($jadwal));
    }

    public function show(JadwalPerjalanan $jadwalPerjalanan)
    {
        $jadwal = $jadwalPerjalanan->load(['operator', 'rute', 'trips.operator', 'pembuat:id,name']);

        $namaDriverCartrack = null;
        try {
            $namaDriverCartrack = $this->direktori->namaDriver($jadwal->operator?->cartrack_driver_id);
        } catch (Throwable) {
            // tampilan tetap jalan tanpa nama driver Cartrack
        }

        return view('jadwal-perjalanan.show', [
            'jadwal' => $jadwal,
            'namaDriverCartrack' => $namaDriverCartrack,
        ]);
    }

    public function edit(JadwalPerjalanan $jadwalPerjalanan)
    {
        if (!$jadwalPerjalanan->aktif) {
            return redirect()->route('jadwal-perjalanan.show', $jadwalPerjalanan)
                ->with('error', 'Jadwal yang sudah selesai atau dibatalkan tidak bisa diubah.');
        }

        $rute = $jadwalPerjalanan->rute->map(fn ($r) => ['tujuan' => $r->tujuan, 'catatan' => $r->catatan])->all();

        return view('jadwal-perjalanan.form', $this->dataForm($jadwalPerjalanan) + [
            'rute' => $rute ?: [['tujuan' => '', 'catatan' => '']],
        ]);
    }

    public function update(Request $request, JadwalPerjalanan $jadwalPerjalanan)
    {
        if (!$jadwalPerjalanan->aktif) {
            return redirect()->route('jadwal-perjalanan.show', $jadwalPerjalanan)
                ->with('error', 'Jadwal yang sudah selesai atau dibatalkan tidak bisa diubah.');
        }

        $berjalan = $jadwalPerjalanan->status === JadwalPerjalanan::STATUS_BERJALAN;
        $data = $this->validasiInput($request, $jadwalPerjalanan);

        if ($berjalan && ($data['nopol'] !== $jadwalPerjalanan->nopol
                || (int) $data['operator_gtm_id'] !== (int) $jadwalPerjalanan->operator_gtm_id
                || Carbon::parse($data['waktu_mulai'])->ne($jadwalPerjalanan->waktu_mulai))) {
            throw ValidationException::withMessages([
                'nopol' => 'Jadwal sudah berjalan: kendaraan, driver, dan waktu mulai tidak bisa diubah. '
                    .'Batalkan lalu buat jadwal baru bila perlu mengganti driver.',
            ]);
        }

        DB::transaction(function () use ($data, $jadwalPerjalanan, $berjalan) {
            $jadwalPerjalanan->update([
                'nopol' => $data['nopol'],
                'operator_gtm_id' => $data['operator_gtm_id'],
                'waktu_mulai' => $data['waktu_mulai'],
                'catatan' => $data['catatan'] ?? null,
            ] + ($berjalan
                // rute berubah: hitung ulang tujuan mana yang sudah tercapai, jadi waktu selesai lama tidak berlaku
                ? ['waktu_selesai' => null, 'rute_dicek_pada' => null]
                : ['link_status' => 'menunggu', 'link_percobaan' => 0, 'link_error' => null]));

            $jadwalPerjalanan->rute()->delete();
            $this->simpanRute($jadwalPerjalanan, $data['rute']);
        });

        return redirect()->route('jadwal-perjalanan.show', $jadwalPerjalanan)
            ->with('success', 'Jadwal diperbarui. '.$this->prosesSekarang($jadwalPerjalanan));
    }

    public function batalkan(JadwalPerjalanan $jadwalPerjalanan)
    {
        if (!$jadwalPerjalanan->aktif) {
            return back()->with('error', 'Hanya jadwal terjadwal/berjalan yang bisa dibatalkan.');
        }

        $this->service->batalkan($jadwalPerjalanan);

        return redirect()->route('jadwal-perjalanan.show', $jadwalPerjalanan)->with('success', 'Jadwal dibatalkan.');
    }

    /**
     * Hapus jadwal yang sudah dibatalkan. Trip hasil tarikannya yang belum divalidasi ikut dihapus
     * (kalau dibiarkan, trip itu jadi yatim: tidak punya jadwal sehingga tak muncul lagi di daftar validasi).
     * Bila ada trip yang sudah divalidasi atau sudah masuk lembur, jadwal ditahan supaya hitungan lembur tak terganggu.
     */
    public function destroy(JadwalPerjalanan $jadwalPerjalanan)
    {
        if ($jadwalPerjalanan->status !== JadwalPerjalanan::STATUS_BATAL) {
            return back()->with('error', 'Hanya jadwal yang sudah dibatalkan yang bisa dihapus. Batalkan dulu jadwalnya.');
        }

        $terkunci = $jadwalPerjalanan->trips()
            ->where(fn ($q) => $q->where('divalidasi', true)->orWhereNotNull('operator_gtm_lembur_id'))
            ->count();
        if ($terkunci > 0) {
            return back()->with('error', "Jadwal tidak bisa dihapus: {$terkunci} trip-nya sudah divalidasi atau masuk lembur. "
                .'Batalkan validasinya dulu di halaman jadwal ini (trip yang sudah masuk lembur tidak bisa dihapus).');
        }

        $jumlahTrip = DB::transaction(function () use ($jadwalPerjalanan) {
            $jumlah = $jadwalPerjalanan->trips()->delete();
            $jadwalPerjalanan->delete();

            return $jumlah;
        });

        return redirect()->route('jadwal-perjalanan.index')->with(
            'success',
            "Jadwal #{$jadwalPerjalanan->id} dihapus".($jumlahTrip ? " beserta {$jumlahTrip} trip yang belum divalidasi." : '.')
        );
    }

    public function selesaikan(JadwalPerjalanan $jadwalPerjalanan)
    {
        if ($jadwalPerjalanan->status !== JadwalPerjalanan::STATUS_BERJALAN) {
            return back()->with('error', 'Hanya jadwal yang sedang berjalan yang bisa diselesaikan lebih awal.');
        }

        $this->service->selesaikanSekarang($jadwalPerjalanan);
        $jadwalPerjalanan->refresh();

        return redirect()->route('jadwal-perjalanan.show', $jadwalPerjalanan)->with(
            $jadwalPerjalanan->trip_tarik_error ? 'error' : 'success',
            $jadwalPerjalanan->trip_tarik_error
                ? 'Jadwal diselesaikan, tapi trip belum bisa ditarik: '.$jadwalPerjalanan->trip_tarik_error
                : 'Jadwal diselesaikan dan trip ditarik dari Cartrack.'
        );
    }

    public function ulangiLink(JadwalPerjalanan $jadwalPerjalanan)
    {
        if (!$jadwalPerjalanan->aktif) {
            return back()->with('error', 'Jadwal ini sudah tidak aktif.');
        }

        $this->service->ulangiLink($jadwalPerjalanan);
        $jadwalPerjalanan->refresh();

        return back()->with(
            $jadwalPerjalanan->link_status === 'gagal' ? 'error' : 'success',
            $jadwalPerjalanan->link_status === 'gagal'
                ? 'Link ke Cartrack masih gagal: '.$jadwalPerjalanan->link_error
                : 'Pengiriman driver ke Cartrack diproses.'
        );
    }

    public function tarikTrip(Request $request, JadwalPerjalanan $jadwalPerjalanan)
    {
        if ($jadwalPerjalanan->status === JadwalPerjalanan::STATUS_BATAL) {
            return back()->with('error', 'Jadwal yang dibatalkan tidak punya trip.');
        }
        if ($jadwalPerjalanan->waktu_mulai->gt(JadwalPerjalananService::sekarangLokal())) {
            return back()->with('error', 'Jadwal belum dimulai, belum ada trip yang bisa ditarik.');
        }

        try {
            $hasil = $this->service->tarikTrip($jadwalPerjalanan, $request->user()->id);
        } catch (CartrackException $e) {
            return back()->with('error', 'Gagal menarik trip: '.$e->getMessage());
        }

        $pesan = "Trip ditarik dari Cartrack: {$hasil['jumlah_api']} segmen terbaca, {$hasil['tertaut']} trip tertaut ke jadwal.";
        if ($hasil['geofence_baru'] > 0) {
            $pesan .= " {$hasil['geofence_baru']} geofence baru didaftarkan dengan tebakan tipe (cek di menu Geofence).";
        }

        return back()->with('success', $pesan);
    }

    /**
     * Validasi massal trip hasil tarikan Cartrack. Hanya trip yang belum masuk lembur yang bisa diubah.
     */
    public function validasi(Request $request)
    {
        $data = $request->validate([
            'aksi' => ['required', Rule::in(['validasi', 'batal_validasi', 'tidak_dihitung', 'hitung'])],
            'trip_ids' => 'required|array|min:1',
            'trip_ids.*' => 'integer',
        ], ['trip_ids.required' => 'Pilih minimal satu trip.']);

        $trips = GpsTrip::whereIn('id', $data['trip_ids'])
            ->where('sumber', GpsTrip::SUMBER_CARTRACK)
            ->whereNull('operator_gtm_lembur_id')
            ->get();

        $lewat = count($data['trip_ids']) - $trips->count();
        $berubah = 0;

        foreach ($trips as $trip) {
            switch ($data['aksi']) {
                case 'validasi':
                    if ($trip->divalidasi) {
                        break;
                    }
                    if ($trip->operator_gtm_id === null) {
                        $lewat++;
                        break;
                    }
                    $trip->update([
                        'divalidasi' => true,
                        'divalidasi_oleh' => $request->user()->id,
                        'divalidasi_pada' => now(),
                    ]);
                    $berubah++;
                    break;
                case 'batal_validasi':
                    if ($trip->divalidasi) {
                        $trip->update(['divalidasi' => false, 'divalidasi_oleh' => null, 'divalidasi_pada' => null]);
                        $berubah++;
                    }
                    break;
                case 'tidak_dihitung':
                case 'hitung':
                    $nilai = $data['aksi'] === 'hitung';
                    if ($trip->dihitung !== $nilai) {
                        $trip->update(['dihitung' => $nilai, 'diedit' => true]);
                        $berubah++;
                    }
                    break;
            }
        }

        $label = ['validasi' => 'divalidasi', 'batal_validasi' => 'dibatalkan validasinya', 'tidak_dihitung' => 'ditandai tidak dihitung', 'hitung' => 'ditandai dihitung'][$data['aksi']];
        $pesan = "{$berubah} trip {$label}.";
        if ($lewat > 0) {
            $pesan .= " {$lewat} trip dilewati (tanpa driver, bukan dari Cartrack, atau sudah masuk lembur).";
        }

        return back()->with('success', $pesan);
    }

    // ------------------------------------------------------------------------------------------

    private function validasiInput(Request $request, ?JadwalPerjalanan $jadwal): array
    {
        $data = $request->validate([
            'nopol' => ['required', 'string', 'max:50', Rule::exists('nomor_polisi', 'nopol')],
            'operator_gtm_id' => ['required', 'integer', 'exists:operator_gtm,id'],
            'waktu_mulai' => ['required', 'date'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'rute' => ['required', 'array', 'min:1'],
            'rute.*.tujuan' => ['nullable', 'string', 'max:255'],
            'rute.*.catatan' => ['nullable', 'string', 'max:255'],
        ], [
            'rute.required' => 'Tentukan minimal satu tujuan pada rute.',
        ]);

        $data['rute'] = array_values(array_filter($data['rute'], fn ($r) => trim((string) ($r['tujuan'] ?? '')) !== ''));
        if (empty($data['rute'])) {
            throw ValidationException::withMessages(['rute' => 'Tentukan minimal satu tujuan pada rute.']);
        }

        $mulai = Carbon::parse($data['waktu_mulai']);

        // Satu mobil / satu driver tidak boleh dobel. Jadwal lain yang belum selesai dianggap masih
        // memakainya (jadwal yang belum mulai: perkiraan JadwalPerjalanan::PERKIRAAN_JAM jam sejak mulai).
        $bentrok = JadwalPerjalanan::bentrok($data['nopol'], (int) $data['operator_gtm_id'], $mulai, $jadwal?->id);
        if ($bentrok->isNotEmpty()) {
            $bentrok->load('operator:id,nama');
            $daftar = $bentrok->map(fn ($b) => "#{$b->id} {$b->nopol} / {$b->operator?->nama} (mulai "
                .$b->waktu_mulai->format('d/m H:i').', '.($b->waktu_selesai
                    ? 'selesai '.$b->waktu_selesai->format('d/m H:i')
                    : ($b->status === JadwalPerjalanan::STATUS_BERJALAN ? 'masih berjalan' : 'belum selesai')).')')->implode('; ');
            throw ValidationException::withMessages([
                'waktu_mulai' => "Bentrok dengan jadwal lain pada kendaraan atau driver yang sama: {$daftar}",
            ]);
        }

        return $data;
    }

    private function simpanRute(JadwalPerjalanan $jadwal, array $rute): void
    {
        foreach (array_values($rute) as $i => $r) {
            $jadwal->rute()->create([
                'urutan' => $i + 1,
                'tujuan' => trim($r['tujuan']),
                'catatan' => ($r['catatan'] ?? null) ?: null,
            ]);
        }
    }

    /** Jalankan mesin status sekali agar jadwal yang waktunya sudah tiba langsung diproses. */
    private function prosesSekarang(JadwalPerjalanan $jadwal): string
    {
        try {
            $this->service->proses($jadwal);
        } catch (Throwable $e) {
            return 'Namun pemrosesan awal ke Cartrack gagal: '.$e->getMessage();
        }

        $jadwal->refresh();

        return match (true) {
            $jadwal->link_status === 'gagal' => 'Driver belum bisa dikirim ke Cartrack: '.$jadwal->link_error,
            $jadwal->link_status === 'terkirim' => 'Driver sudah di-link ke kendaraan di Cartrack.',
            $jadwal->link_status === 'dilewati' && $jadwal->status !== JadwalPerjalanan::STATUS_SELESAI => 'Mode uji: tidak ada yang dikirim ke Cartrack.',
            $jadwal->status === JadwalPerjalanan::STATUS_SELESAI => 'Rute ini sudah tuntas di Cartrack; trip ditarik untuk divalidasi.',
            default => 'Driver akan dikirim ke Cartrack otomatis saat waktu mulai tiba.',
        };
    }

    /**
     * Pilihan kendaraan dan driver di form hanya yang sudah terhubung dengan Cartrack. Yang belum
     * terhubung dipisah supaya form bisa menjelaskan kenapa tidak muncul. Bila Cartrack tak terjangkau,
     * semua pilihan ditampilkan seperti biasa (tidak ada dasar untuk menyaring).
     */
    private function dataForm(JadwalPerjalanan $jadwal): array
    {
        $registrasiCartrack = null;
        $driverIdCartrack = null;
        try {
            $registrasiCartrack = collect($this->direktori->kendaraan())->pluck('registration')->all();
            $driverIdCartrack = array_keys($this->direktori->driver());
        } catch (Throwable) {
            // Cartrack tak terjangkau: form tetap bisa dipakai, tanpa penyaringan
        }
        $cartrackMuat = $registrasiCartrack !== null;

        $semuaNopol = NomorPolisi::orderBy('nopol')->pluck('nopol');
        $kunciCartrack = $cartrackMuat
            ? collect($registrasiCartrack)->mapWithKeys(fn ($r) => [GpsTripImporter::kunciNopol($r) => $r])
            : collect();
        $pilihanNopol = old('nopol', $jadwal->nopol);

        $nopolTerhubung = $semuaNopol->filter(fn ($n) => !$cartrackMuat
            || $kunciCartrack->has(GpsTripImporter::kunciNopol($n))
            || $n === $pilihanNopol)->values();
        $nopolBelum = $semuaNopol->diff($nopolTerhubung)->values();
        $kunciKita = $semuaNopol->map(fn ($n) => GpsTripImporter::kunciNopol($n))->all();
        $nopolHanyaCartrack = $kunciCartrack->except($kunciKita)->values();

        $semuaOperator = OperatorGtm::orderBy('nama')->get(['id', 'nama', 'cartrack_driver_id']);
        $pilihanOperator = (int) old('operator_gtm_id', $jadwal->operator_gtm_id);
        $terhubung = fn ($o) => $o->id === $pilihanOperator
            || ($o->cartrack_driver_id && ($driverIdCartrack === null || in_array($o->cartrack_driver_id, $driverIdCartrack, true)));
        $operatorTerhubung = $semuaOperator->filter($terhubung)->values();
        $operatorBelum = $semuaOperator->reject($terhubung)->values();

        return [
            'jadwal' => $jadwal,
            'cartrackMuat' => $cartrackMuat,
            'daftarNopol' => $nopolTerhubung,
            'nopolBelumTerhubung' => $nopolBelum,
            'nopolHanyaCartrack' => $nopolHanyaCartrack,
            'operators' => $operatorTerhubung,
            'operatorBelumTerhubung' => $operatorBelum,
            'daftarTujuan' => GpsGeofence::orderBy('nama')->pluck('nama'),
            'terkunci' => $jadwal->status === JadwalPerjalanan::STATUS_BERJALAN,
        ];
    }

    private function tanggal(?string $nilai): ?Carbon
    {
        try {
            return $nilai ? Carbon::parse($nilai) : null;
        } catch (Throwable) {
            return null;
        }
    }
}
