<?php

namespace App\Http\Controllers;

use App\Models\GpsGeofence;
use App\Models\GpsHentiBukti;
use App\Models\GpsSinkronPeriode;
use App\Models\GpsTrip;
use App\Models\GpsTripDibuang;
use App\Models\NomorPolisi;
use App\Models\OperatorGtm;
use App\Models\User;
use App\Services\Cartrack\CartrackSinkronService;
use App\Services\GpsTrip\GpsTripImporter;
use App\Services\GpsTrip\GpsTripLemburService;
use App\Services\GpsTrip\GpsTripParser;
use App\Services\GpsTrip\PeriodeLembur;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class GpsTripController extends Controller
{
    // ------------------------------------------------------------------
    // Daftar trip
    // ------------------------------------------------------------------

    public function index(Request $request)
    {
        // Periode lembur: 26 bulan lalu s/d 25 bulan berlabel. Default = periode yang sedang berjalan.
        [$bulan, $awal, $akhir] = $this->periodeBulan($request->query('bulan'));

        $query = GpsTrip::with('operator:id,nama')
            ->whereBetween('waktu_mulai', [$awal, $akhir])
            ->orderBy('nopol')
            ->orderBy('waktu_mulai');

        if ($nopol = $request->query('nopol')) {
            $query->where('nopol', $nopol);
        }

        $driver = $request->query('driver');
        if ($driver === 'kosong') {
            $query->whereNull('operator_gtm_id');
        } elseif ($driver) {
            $query->where('operator_gtm_id', (int) $driver);
        }

        $validasi = $request->query('validasi');
        if ($validasi === 'belum') {
            $query->where('divalidasi', false);
        } elseif ($validasi === 'sudah') {
            $query->where('divalidasi', true);
        }

        $tampil = $request->query('tampil', 'semua');
        if ($tampil === 'dihitung') {
            $query->where('dihitung', true);
        } elseif ($tampil === 'perlu_dicek') {
            $query->where('status', 'tidak_lengkap');
        }

        $trips = $query->get();

        // Nomor trip per kendaraan per hari, hanya untuk trip yang dihitung
        $nomorTrip = [];
        $urutan = [];
        foreach ($trips as $trip) {
            if (!$trip->dihitung) {
                continue;
            }
            $kunci = $trip->nopol . '|' . $trip->waktu_mulai->toDateString();
            $urutan[$kunci] = ($urutan[$kunci] ?? 0) + 1;
            $nomorTrip[$trip->id] = $urutan[$kunci];
        }

        // Kendaraan yang semua trip dihitungnya sudah divalidasi ditaruh paling bawah (urutan nopol tetap)
        $perKendaraan = $trips->groupBy('nopol')
            ->sortBy(fn ($daftar) => GpsTrip::semuaTervalidasi($daftar) ? 1 : 0);

        // Rekap per driver (hanya trip yang dihitung dari hasil filter)
        $rekapDriver = $trips->where('dihitung', true)
            ->groupBy(fn ($t) => $t->operator_gtm_id ?? 0)
            ->map(fn ($grup) => [
                'nama' => $grup->first()->operator->nama ?? 'Belum ditentukan',
                'trip' => $grup->count(),
                'jarak' => $grup->sum('jarak_km'),
                'durasi_jalan' => $grup->sum('durasi_jalan_detik'),
                'hari' => $grup->map(fn ($t) => $t->waktu_mulai->toDateString())->unique()->count(),
            ])
            ->sortBy(fn ($r, $id) => $id === 0 ? 1 : 0);

        $ringkasan = [
            'trip_dihitung' => $trips->where('dihitung', true)->count(),
            'kendaraan' => $perKendaraan->count(),
            'tanpa_driver' => $trips->where('dihitung', true)->whereNull('operator_gtm_id')->count(),
            'perlu_dicek' => $trips->filter(fn ($t) => $t->perlu_dicek)->count(),
            'tidak_dihitung' => $trips->where('dihitung', false)->count(),
        ];

        $daftarNopol = GpsTrip::whereBetween('waktu_mulai', [$awal, $akhir])->distinct()->orderBy('nopol')->pluck('nopol');
        $operators = OperatorGtm::orderBy('nama')->get(['id', 'nama']);

        $kunciTerdaftar = NomorPolisi::pluck('nopol')->map(fn ($n) => GpsTripImporter::kunciNopol($n))->flip();

        $geofenceCustomer = GpsGeofence::with('customer:id,name')
            ->whereNotNull('customer_id')
            ->get()
            ->mapWithKeys(fn ($g) => [GpsTripParser::normalisasiNama($g->nama) => $g->customer->name ?? null]);


        // Periode ini sudah pernah ditarik penuh dari Cartrack? Bila belum, admin diminta menyinkronkan dulu
        $periodeSinkron = GpsSinkronPeriode::where('periode', $bulan)->first();
        $periodeLabel = PeriodeLembur::label($bulan);
        $periodeBerjalan = $bulan === PeriodeLembur::bulanUntuk(Carbon::now(config('services.cartrack.zona_lokal', 'Asia/Jakarta')));

        // Trip hasil sinkron Cartrack yang belum divalidasi di periode ini (belum ikut hitungan lembur)
        $belumValidasi = GpsTrip::where('divalidasi', false)
            ->whereBetween('waktu_mulai', [$awal, $akhir])
            ->count();
        $sinkronTerakhir = CartrackSinkronService::hasilTerakhir();
        $sinkronMenunggu = CartrackSinkronService::permintaanMenunggu();

        // Trip noise (< 5 menit) di bulan ini, terlepas dari filter di atas
        $noise = GpsTrip::noise()
            ->with('operator:id,nama')
            ->whereBetween('waktu_mulai', [$awal, $akhir])
            ->orderBy('nopol')
            ->orderBy('waktu_mulai')
            ->get();

        // Berhenti lama di luar geofence (> 1 jam, jam kerjanya dipotong) di periode ini, terlepas dari filter di atas
        $hentiLama = [];
        $buktiSet = GpsHentiBukti::himpunan($awal->copy()->subDay(), $akhir->copy()->addDay());
        $buktiFoto = GpsHentiBukti::with('pengunggah:id,name')
            ->whereBetween('waktu_mulai', [$awal->copy()->subDay(), $akhir->copy()->addDay()])
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($b) => GpsHentiBukti::kunci($b->nopol, $b->waktu_mulai->format('Y-m-d H:i:s')))
            ->map(fn ($grup) => $grup->map(fn ($b) => [
                'id' => $b->id,
                'url' => route('gps-trip.henti-bukti.foto', $b->id),
                'hapus' => route('gps-trip.henti-bukti.hapus', $b->id),
                'catatan' => $b->catatan,
                'oleh' => $b->pengunggah->name ?? null,
                'waktu' => $b->created_at?->format('d/m/Y H:i'),
            ])->values()->all());
        $tripHenti = GpsTrip::with('operator:id,nama')
            ->where('dihitung', true)
            ->where('diedit', false)
            ->where('jumlah_segmen', '>', 1)
            ->whereBetween('waktu_mulai', [$awal, $akhir])
            ->get();
        foreach ($tripHenti as $trip) {
            foreach ($trip->hentiDenganBukti($buktiSet) as $h) {
                $hentiLama[] = $h + [
                    'foto' => $buktiFoto[$h['kunci']] ?? [],
                    'trip_id' => $trip->id,
                    'nopol' => $trip->nopol,
                    'driver' => $trip->operator->nama ?? null,
                    'divalidasi' => (bool) $trip->divalidasi,
                    'di_lembur' => (bool) $trip->operator_gtm_lembur_id,
                ];
            }
        }
        usort($hentiLama, fn ($a, $b) => [$a['nopol'], $a['mulai']] <=> [$b['nopol'], $b['mulai']]);

        return view('gps-trip.index', compact(
            'hentiLama', 'buktiSet',
            'bulan', 'perKendaraan', 'nomorTrip', 'rekapDriver', 'ringkasan', 'daftarNopol',
            'operators', 'kunciTerdaftar', 'geofenceCustomer', 'tampil', 'driver', 'noise',
            'validasi', 'belumValidasi', 'sinkronTerakhir', 'sinkronMenunggu', 'periodeSinkron', 'periodeLabel', 'periodeBerjalan'
        ) + ['nopolDipilih' => $request->query('nopol')]);
    }

    // ------------------------------------------------------------------
    // Foto bukti berhenti lama
    // ------------------------------------------------------------------

    /**
     * Simpan foto bukti untuk satu kejadian berhenti lama. Kejadian harus benar-benar terdeteksi dari data trip
     * (nopol + waktu mulai berhenti), jadi bukti tidak bisa ditempel ke kejadian yang tidak ada.
     */
    public function simpanBukti(Request $request)
    {
        $data = $request->validate([
            'nopol' => ['required', 'string', 'max:30'],
            'mulai' => ['required', 'date_format:Y-m-d H:i:s'],
            'foto' => ['required', 'array', 'min:1', 'max:5'],
            'foto.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ], [
            'foto.required' => 'Pilih minimal satu foto.',
            'foto.*.mimes' => 'Foto harus berformat JPG, PNG, atau WEBP.',
            'foto.*.max' => 'Ukuran tiap foto maksimal 8 MB.',
            'foto.*.uploaded' => 'Foto gagal diunggah (kemungkinan terlalu besar untuk server). Coba kecilkan ukurannya.',
        ]);

        $kejadian = null;
        $trips = GpsTrip::where('nopol', $data['nopol'])
            ->where('waktu_mulai', '<=', $data['mulai'])
            ->where('waktu_selesai', '>=', $data['mulai'])
            ->get();
        foreach ($trips as $trip) {
            foreach ($trip->hentiDiJalan() as $h) {
                if ($h['mulai_teks'] === $data['mulai']) {
                    $kejadian = $h;
                    break 2;
                }
            }
        }
        if (!$kejadian) {
            return back()->withErrors(['foto' => 'Kejadian berhenti lama itu tidak ditemukan (mungkin data tripnya berubah setelah sinkron). Muat ulang halaman.']);
        }

        foreach ($request->file('foto') as $file) {
            $path = $file->store('henti-bukti/'.substr($data['mulai'], 0, 7), GpsHentiBukti::DISK);
            GpsHentiBukti::create([
                'nopol' => $data['nopol'],
                'waktu_mulai' => $kejadian['mulai_teks'],
                'waktu_selesai' => $kejadian['selesai_teks'],
                'path' => $path,
                'nama_asli' => mb_substr($file->getClientOriginalName(), 0, 255),
                'catatan' => $data['catatan'] ?? null,
                'diunggah_oleh' => $request->user()?->id,
            ]);
        }

        return back()->with('success', 'Bukti foto tersimpan. Jam kerja untuk berhenti lama ini kini dipotong.'
            .$this->terapkanUlangHenti($data['nopol'], $kejadian['mulai_teks']));
    }

    public function fotoBukti(GpsHentiBukti $bukti)
    {
        abort_unless(Storage::disk(GpsHentiBukti::DISK)->exists($bukti->path), 404);

        return Storage::disk(GpsHentiBukti::DISK)->response($bukti->path);
    }

    public function hapusBukti(GpsHentiBukti $bukti)
    {
        Storage::disk(GpsHentiBukti::DISK)->delete($bukti->path);
        $nopol = $bukti->nopol;
        $mulai = $bukti->waktu_mulai->format('Y-m-d H:i:s');
        $bukti->delete();

        return back()->with('success', 'Foto bukti dihapus. Bila itu foto terakhir, jam kerja untuk berhenti lama ini tidak lagi dipotong.'
            .$this->terapkanUlangHenti($nopol, $mulai));
    }

    /** Foto bukti berubah: hitung ulang data lembur hari trip valid yang memuat kejadian berhenti itu. */
    private function terapkanUlangHenti(string $nopol, string $mulai): string
    {
        $trips = GpsTrip::where('nopol', $nopol)
            ->where('divalidasi', true)
            ->where('waktu_mulai', '<=', $mulai)
            ->where('waktu_selesai', '>=', $mulai)
            ->get();
        if ($trips->isEmpty()) {
            return '';
        }

        return GpsTripLemburService::ringkasan(app(GpsTripLemburService::class)->terapkanHari(
            $trips->flatMap(fn ($t) => GpsTripLemburService::hariTrip($t))->all(),
            $trips->pluck('operator_gtm_lembur_id')->all()
        ));
    }

    // ------------------------------------------------------------------
    // Sinkron dari Cartrack
    // ------------------------------------------------------------------

    /**
     * Minta tarik penuh satu periode lembur dari Cartrack. Menarik semua kendaraan bisa memakan waktu semenit
     * lebih, jadi hanya dicatat dan dikerjakan penjadwal pada menit berikutnya.
     */
    public function sinkron(Request $request)
    {
        $data = $request->validate([
            'bulan' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        CartrackSinkronService::mintaPeriode($data['bulan']);

        return redirect()->route('gps-trip.index', ['bulan' => $data['bulan']])
            ->with('success', 'Sinkron periode '.PeriodeLembur::label($data['bulan']).' diminta. Penjadwal mengerjakannya dalam sekitar satu menit; muat ulang halaman ini setelah itu.');
    }

    // ------------------------------------------------------------------
    // Master geofence
    // ------------------------------------------------------------------

    public function geofence()
    {
        $geofence = GpsGeofence::with('customer:id,name')->orderBy('tipe')->orderBy('nama')->get();
        $customers = $this->daftarCustomer();

        return view('gps-trip.geofence', compact('geofence', 'customers'));
    }

    public function updateGeofence(Request $request, GpsGeofence $geofence)
    {
        $validated = $request->validate([
            'tipe' => ['required', Rule::in(array_keys(GpsGeofence::TIPE_LABEL))],
            'customer_id' => 'nullable|integer|exists:users,id',
        ]);

        $geofence->update([
            'tipe' => $validated['tipe'],
            'customer_id' => $validated['tipe'] === GpsGeofence::TIPE_CUSTOMER ? ($validated['customer_id'] ?? null) : null,
        ]);

        return redirect()->route('gps-trip.geofence')
            ->with('success', "Geofence \"{$geofence->nama}\" diperbarui. Klik \"Sinkron periode ini\" di Trip GPS untuk menghitung ulang trip.");
    }

    // ------------------------------------------------------------------
    // Penyesuaian trip
    // ------------------------------------------------------------------

    public function update(Request $request, GpsTrip $gpsTrip)
    {
        $validated = $request->validate([
            'waktu_mulai' => 'required|date',
            'waktu_selesai' => 'nullable|date|after_or_equal:waktu_mulai',
            'geofence_mulai' => 'nullable|string|max:255',
            'geofence_selesai' => 'nullable|string|max:255',
            'operator_gtm_id' => 'nullable|integer|exists:operator_gtm,id',
            'dihitung' => 'nullable|boolean',
            'catatan' => 'nullable|string|max:1000',
        ]);

        $mulai = Carbon::parse($validated['waktu_mulai']);
        $duplikat = GpsTrip::where('nopol', $gpsTrip->nopol)
            ->where('waktu_mulai', $mulai)
            ->where('id', '!=', $gpsTrip->id)
            ->exists();
        if ($duplikat) {
            return back()->withInput()->with('error', 'Sudah ada trip lain untuk kendaraan ini dengan waktu mulai yang sama.');
        }

        $hariLama = $gpsTrip->divalidasi ? GpsTripLemburService::hariTrip($gpsTrip) : [];
        $lemburLama = $gpsTrip->operator_gtm_lembur_id;

        $gpsTrip->update([
            'waktu_mulai' => $mulai,
            'waktu_selesai' => $validated['waktu_selesai'] ?? null,
            'geofence_mulai' => $validated['geofence_mulai'] ?? null,
            'geofence_selesai' => $validated['geofence_selesai'] ?? null,
            'operator_gtm_id' => $validated['operator_gtm_id'] ?? null,
            'dihitung' => $request->boolean('dihitung'),
            'catatan' => $validated['catatan'] ?? null,
            'diedit' => true,
        ]);

        $pesan = 'Trip berhasil diperbarui.';
        if ($gpsTrip->divalidasi || $hariLama) {
            $hari = array_merge($hariLama, $gpsTrip->divalidasi ? GpsTripLemburService::hariTrip($gpsTrip->fresh()) : []);
            $pesan .= GpsTripLemburService::ringkasan(app(GpsTripLemburService::class)->terapkanHari($hari, [$lemburLama]));
        }

        return back()->with('success', $pesan);
    }

    /**
     * Aksi massal: set driver, validasi / batal validasi, hapus. Menghitung atau tidak menghitung satu trip dilakukan lewat edit trip.
     */
    public function aksiMassal(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:gps_trip,id',
            'aksi' => ['required', Rule::in(['driver', 'hapus', 'validasi', 'batal_validasi'])],
            'operator_gtm_id' => 'nullable|integer|exists:operator_gtm,id',
        ]);

        $query = GpsTrip::whereIn('id', $validated['ids']);
        $jumlah = (clone $query)->count();

        // Trip valid yang berubah driver / dihapus menyentuh data lembur: catat harinya sebelum diubah
        $tripValid = (clone $query)->where('divalidasi', true)->get();
        $hari = $tripValid->flatMap(fn ($t) => GpsTripLemburService::hariTrip($t))->all();
        $lemburLama = $tripValid->pluck('operator_gtm_lembur_id')->all();

        switch ($validated['aksi']) {
            case 'validasi':
            case 'batal_validasi':
                $pesan = $this->validasiMassal($validated['ids'], $validated['aksi'] === 'validasi', $request->user()->id);
                break;
            case 'driver':
                $query->update(['operator_gtm_id' => $validated['operator_gtm_id'] ?? null]);
                $pesan = "Driver diperbarui untuk {$jumlah} trip.";
                foreach ($tripValid as $t) {
                    array_push($hari, ...GpsTripLemburService::hariTrip($t, $validated['operator_gtm_id'] ?? null));
                }
                break;
            default:
                // Trip hasil sinkron Cartrack yang dihapus dicatat agar tidak dibuat lagi oleh sinkron berikutnya
                $query->get()
                    ->where('sumber', GpsTrip::SUMBER_CARTRACK)
                    ->each(fn (GpsTrip $t) => GpsTripDibuang::firstOrCreate([
                        'nopol' => $t->nopol,
                        'waktu_mulai' => ($t->kunci_waktu_mulai ?? $t->waktu_mulai)->format('Y-m-d H:i:s'),
                    ]));
                $query->delete();
                $pesan = "{$jumlah} trip dihapus.";
        }

        if (in_array($validated['aksi'], ['driver', 'hapus'], true) && $hari) {
            $pesan .= GpsTripLemburService::ringkasan(app(GpsTripLemburService::class)->terapkanHari($hari, $lemburLama));
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'pesan' => $pesan]);
        }

        return back()->with('success', $pesan);
    }

    /**
     * Validasi / batal validasi trip (validasi butuh driver).
     * Validasi = menyetujui trip masuk lembur: hari-hari yang tersentuh langsung diterapkan ke data lembur.
     * Batal validasi mengeluarkan trip dari data lembur (hari itu dihitung ulang).
     */
    private function validasiMassal(array $ids, bool $validasi, int $userId): string
    {
        $trips = GpsTrip::whereIn('id', $ids)->get();

        $lewat = count($ids) - $trips->count();
        $berubah = 0;
        $hari = [];
        $lemburLama = [];

        foreach ($trips as $trip) {
            if ($trip->divalidasi === $validasi) {
                continue;
            }
            if ($validasi && $trip->operator_gtm_id === null) {
                $lewat++;
                continue;
            }

            $lemburLama[] = $trip->operator_gtm_lembur_id;
            $trip->update($validasi
                ? ['divalidasi' => true, 'divalidasi_oleh' => $userId, 'divalidasi_pada' => now()]
                : ['divalidasi' => false, 'divalidasi_oleh' => null, 'divalidasi_pada' => null]);
            array_push($hari, ...GpsTripLemburService::hariTrip($trip));
            $berubah++;
        }

        $pesan = $berubah.' trip '.($validasi ? 'divalidasi' : 'dibatalkan validasinya').'.';
        if ($lewat > 0) {
            $pesan .= " {$lewat} trip dilewati karena belum punya driver.";
        }

        return $pesan.GpsTripLemburService::ringkasan(app(GpsTripLemburService::class)->terapkanHari($hari, $lemburLama));
    }

    /**
     * Hapus trip noise yang dicentang. Noise yang TIDAK dicentang dipertahankan (ditandai "bukan noise",
     * ikut dihitung bila trip selesai) dan tidak ditanyakan lagi. Noise yang dihapus dicatat agar tidak
     * dibuat lagi saat sinkron Cartrack berikutnya.
     */
    public function hapusNoise(Request $request)
    {
        $validated = $request->validate([
            'semua' => 'required|array|min:1',
            'semua.*' => 'integer',
            'hapus' => 'nullable|array',
            'hapus.*' => 'integer',
        ]);

        $hapus = array_flip($validated['hapus'] ?? []);
        $dihapus = 0;
        $dipertahankan = 0;

        DB::transaction(function () use ($validated, $hapus, &$dihapus, &$dipertahankan) {
            // Cek ulang di server: hanya trip yang memang masih tergolong noise yang disentuh
            foreach (GpsTrip::noise()->whereIn('id', $validated['semua'])->get() as $trip) {
                if (isset($hapus[$trip->id])) {
                    GpsTripDibuang::firstOrCreate([
                        'nopol' => $trip->nopol,
                        // waktu mulai asli GPS, agar cocok dengan hasil deteksi saat sinkron ulang
                        'waktu_mulai' => ($trip->kunci_waktu_mulai ?? $trip->waktu_mulai)->format('Y-m-d H:i:s'),
                    ]);
                    $trip->delete();
                    $dihapus++;
                } else {
                    $trip->update([
                        'bukan_noise' => true,
                        'dihitung' => $trip->status === 'selesai',
                        'diedit' => true,
                    ]);
                    $dipertahankan++;
                }
            }
        });

        $pesan = "{$dihapus} trip noise dihapus";
        if ($dipertahankan) {
            $pesan .= ", {$dipertahankan} dipertahankan sebagai trip";
        }

        return back()->with('success', $pesan . '.');
    }

    // ------------------------------------------------------------------
    // Terapkan ke data lembur operator
    // ------------------------------------------------------------------

    public function lembur(Request $request, GpsTripLemburService $service)
    {
        [$bulan, $awal, $akhir] = $this->periodeBulan($request->query('bulan'));
        $hasil = $service->susun($awal, $akhir);

        $ringkasan = [
            'baru' => count(array_filter($hasil['baris'], fn ($b) => $b['status'] === 'baru')),
            'berbeda' => count(array_filter($hasil['baris'], fn ($b) => $b['status'] === 'berbeda')),
            'sama' => count(array_filter($hasil['baris'], fn ($b) => $b['status'] === 'sama')),
        ];

        return view('gps-trip.lembur', [
            'bulan' => $bulan,
            'periodeLabel' => PeriodeLembur::label($bulan),
            'baris' => $hasil['baris'],
            'tanpaDriver' => $hasil['tanpa_driver'],
            'belumValidasi' => $hasil['belum_validasi'],
            'ringkasan' => $ringkasan,
        ]);
    }

    public function terapkanLembur(Request $request, GpsTripLemburService $service)
    {
        $validated = $request->validate([
            'bulan' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'pilih' => 'required|array|min:1',
            'pilih.*' => ['string', 'regex:/^\d+\|\d{4}-\d{2}-\d{2}$/'],
        ], [
            'pilih.required' => 'Pilih minimal satu baris untuk diterapkan.',
        ]);

        [$bulan, $awal, $akhir] = $this->periodeBulan($validated['bulan']);
        $hasil = $service->terapkan($awal, $akhir, $validated['pilih']);

        $pesan = "Data lembur diperbarui: {$hasil['dibuat']} dibuat, {$hasil['diperbarui']} diperbarui";
        if ($hasil['dilewati']) {
            $pesan .= ", {$hasil['dilewati']} dilewati karena sudah sama";
        }

        return redirect()->route('gps-trip.lembur', ['bulan' => $bulan])->with('success', $pesan . '.');
    }

    // ------------------------------------------------------------------
    // Helper
    // ------------------------------------------------------------------

    /**
     * Periode lembur (26 bulan lalu s/d 25 bulan berlabel) dari label YYYY-MM; kosong/tidak valid = periode berjalan.
     *
     * @return array{0: string, 1: Carbon, 2: Carbon}
     */
    private function periodeBulan(?string $bulan): array
    {
        if (!PeriodeLembur::valid($bulan)) {
            $bulan = PeriodeLembur::bulanUntuk(Carbon::now(config('services.cartrack.zona_lokal', 'Asia/Jakarta')));
        }

        [$awal, $akhir] = PeriodeLembur::rentang($bulan);

        return [$bulan, $awal, $akhir];
    }

    private function daftarCustomer()
    {
        return User::whereIn('role', [User::ROLE_CUSTOMER, User::ROLE_MMBTU])->orderBy('name')->get(['id', 'name']);
    }
}
