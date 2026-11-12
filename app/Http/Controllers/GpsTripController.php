<?php

namespace App\Http\Controllers;

use App\Models\GpsGeofence;
use App\Models\GpsSinkronPeriode;
use App\Models\GpsTrip;
use App\Models\GpsTripDibuang;
use App\Models\GpsTripImport;
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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class GpsTripController extends Controller
{
    private const DIR_UPLOAD = 'gps-trip-uploads';

    public function __construct(private GpsTripParser $parser, private GpsTripImporter $importer)
    {
    }

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

        $perKendaraan = $trips->groupBy('nopol');

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

        // Riwayat upload Excel saja; tarikan Cartrack (satu baris per hari) punya status sendiri
        $importTerakhir = GpsTripImport::with('user:id,name')->where('nama_file', 'not like', 'Cartrack%')->latest()->first();

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

        return view('gps-trip.index', compact(
            'bulan', 'perKendaraan', 'nomorTrip', 'rekapDriver', 'ringkasan', 'daftarNopol',
            'operators', 'kunciTerdaftar', 'geofenceCustomer', 'importTerakhir', 'tampil', 'driver', 'noise',
            'validasi', 'belumValidasi', 'sinkronTerakhir', 'sinkronMenunggu', 'periodeSinkron', 'periodeLabel', 'periodeBerjalan'
        ) + ['nopolDipilih' => $request->query('nopol')]);
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
    // Upload & klasifikasi geofence
    // ------------------------------------------------------------------

    public function upload()
    {
        $riwayat = GpsTripImport::with('user:id,name')->where('nama_file', 'not like', 'Cartrack%')->latest()->limit(10)->get();

        return view('gps-trip.upload', compact('riwayat'));
    }

    public function prosesUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx,csv,txt|max:20480',
        ], [
            'file.required' => 'Pilih file Excel terlebih dahulu.',
            'file.mimes' => 'File harus berformat .xls atau .xlsx.',
            'file.max' => 'Ukuran file maksimal 20 MB.',
        ]);

        $file = $request->file('file');

        try {
            $parsed = $this->parser->parse($file->getRealPath());
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membaca file: ' . $e->getMessage());
        }

        $namaFile = $file->getClientOriginalName();
        $geofenceFile = GpsTripParser::hitungGeofence($parsed);

        $dikenal = GpsGeofence::pluck('nama')->map(fn ($n) => GpsTripParser::normalisasiNama($n))->flip();
        $baru = array_filter($geofenceFile, fn ($jumlah, $nama) => !isset($dikenal[GpsTripParser::normalisasiNama($nama)]), ARRAY_FILTER_USE_BOTH);

        // Semua geofence sudah dikenal -> langsung import
        if (empty($baru)) {
            $stat = $this->importer->import($parsed, $namaFile, $request->user()->id);

            return $this->redirectSetelahImport($parsed, $stat);
        }

        // Ada geofence baru -> simpan sementara & minta admin mengklasifikasi dulu
        $this->bersihkanUploadLama();
        $token = (string) \Illuminate\Support\Str::uuid();
        Storage::disk('local')->put(self::DIR_UPLOAD . "/{$token}.json", json_encode([
            'nama_file' => $namaFile,
            'user_id' => $request->user()->id,
            'parsed' => $parsed,
        ]));

        return redirect()->route('gps-trip.review', $token);
    }

    public function review(string $token)
    {
        $data = $this->bacaUpload($token);
        if (!$data) {
            return redirect()->route('gps-trip.upload')->with('error', 'Sesi upload sudah kedaluwarsa, silakan upload ulang file.');
        }

        $geofenceFile = GpsTripParser::hitungGeofence($data['parsed']);
        $dikenal = GpsGeofence::pluck('nama')->map(fn ($n) => GpsTripParser::normalisasiNama($n))->flip();
        $customers = $this->daftarCustomer();

        $baru = [];
        foreach ($geofenceFile as $nama => $jumlah) {
            if (isset($dikenal[GpsTripParser::normalisasiNama($nama)])) {
                continue;
            }
            $baru[] = [
                'nama' => $nama,
                'jumlah' => $jumlah,
                'tipe' => GpsTripImporter::sarankanTipe($nama),
                'customer_id' => GpsTripImporter::sarankanCustomer($nama, $customers),
            ];
        }

        $jumlahSegmen = array_sum(array_map(fn ($k) => count($k['segmen']), $data['parsed']['kendaraan']));

        return view('gps-trip.review', [
            'token' => $token,
            'namaFile' => $data['nama_file'],
            'periode' => $data['parsed']['periode'],
            'jumlahKendaraan' => count($data['parsed']['kendaraan']),
            'jumlahSegmen' => $jumlahSegmen,
            'geofenceBaru' => $baru,
            'customers' => $customers,
        ]);
    }

    public function konfirmasiImport(Request $request, string $token)
    {
        $data = $this->bacaUpload($token);
        if (!$data) {
            return redirect()->route('gps-trip.upload')->with('error', 'Sesi upload sudah kedaluwarsa, silakan upload ulang file.');
        }

        $validated = $request->validate([
            'geofence' => 'required|array',
            'geofence.*.nama' => 'required|string|max:255',
            'geofence.*.tipe' => ['required', Rule::in(array_keys(GpsGeofence::TIPE_LABEL))],
            'geofence.*.customer_id' => 'nullable|integer|exists:users,id',
        ]);

        foreach ($validated['geofence'] as $g) {
            GpsGeofence::firstOrCreate(
                ['nama' => $g['nama']],
                [
                    'tipe' => $g['tipe'],
                    'customer_id' => $g['tipe'] === GpsGeofence::TIPE_CUSTOMER ? ($g['customer_id'] ?? null) : null,
                ]
            );
        }

        $stat = $this->importer->import($data['parsed'], $data['nama_file'], $request->user()->id);
        Storage::disk('local')->delete(self::DIR_UPLOAD . "/{$token}.json");

        return $this->redirectSetelahImport($data['parsed'], $stat);
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
            ->with('success', "Geofence \"{$geofence->nama}\" diperbarui. Upload ulang file laporan untuk menghitung ulang trip.");
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

        return back()->with('success', 'Trip berhasil diperbarui.');
    }

    /**
     * Aksi massal / per baris: set driver, hitung, abaikan, hapus.
     */
    public function aksiMassal(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:gps_trip,id',
            'aksi' => ['required', Rule::in(['driver', 'hitung', 'abaikan', 'hapus', 'validasi', 'batal_validasi'])],
            'operator_gtm_id' => 'nullable|integer|exists:operator_gtm,id',
        ]);

        $query = GpsTrip::whereIn('id', $validated['ids']);
        $jumlah = (clone $query)->count();

        switch ($validated['aksi']) {
            case 'validasi':
            case 'batal_validasi':
                $pesan = $this->validasiMassal($validated['ids'], $validated['aksi'] === 'validasi', $request->user()->id);
                break;
            case 'driver':
                $query->update(['operator_gtm_id' => $validated['operator_gtm_id'] ?? null]);
                $pesan = "Driver diperbarui untuk {$jumlah} trip.";
                break;
            case 'hitung':
                $query->update(['dihitung' => true, 'diedit' => true]);
                $pesan = "{$jumlah} trip dihitung.";
                break;
            case 'abaikan':
                $query->update(['dihitung' => false, 'diedit' => true]);
                $pesan = "{$jumlah} trip tidak dihitung.";
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

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'pesan' => $pesan]);
        }

        return back()->with('success', $pesan);
    }

    /**
     * Validasi / batal validasi trip hasil Cartrack. Aturannya sama dengan validasi di halaman jadwal:
     * hanya trip Cartrack, yang belum masuk lembur, dan (untuk validasi) sudah punya driver.
     */
    private function validasiMassal(array $ids, bool $validasi, int $userId): string
    {
        $trips = GpsTrip::whereIn('id', $ids)
            ->where('sumber', GpsTrip::SUMBER_CARTRACK)
            ->whereNull('operator_gtm_lembur_id')
            ->get();

        $lewat = count($ids) - $trips->count();
        $berubah = 0;

        foreach ($trips as $trip) {
            if ($trip->divalidasi === $validasi) {
                continue;
            }
            if ($validasi && $trip->operator_gtm_id === null) {
                $lewat++;
                continue;
            }

            $trip->update($validasi
                ? ['divalidasi' => true, 'divalidasi_oleh' => $userId, 'divalidasi_pada' => now()]
                : ['divalidasi' => false, 'divalidasi_oleh' => null, 'divalidasi_pada' => null]);
            $berubah++;
        }

        $pesan = $berubah.' trip '.($validasi ? 'divalidasi' : 'dibatalkan validasinya').'.';
        if ($lewat > 0) {
            $pesan .= " {$lewat} trip dilewati (tanpa driver, bukan dari Cartrack, atau sudah masuk lembur).";
        }

        return $pesan;
    }

    /**
     * Hapus trip noise yang dicentang. Noise yang TIDAK dicentang dipertahankan (ditandai "bukan noise",
     * ikut dihitung bila trip selesai) dan tidak ditanyakan lagi. Noise yang dihapus dicatat agar tidak
     * dibuat lagi saat file yang sama di-upload ulang.
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
                        // waktu mulai asli GPS, agar cocok dengan hasil deteksi saat upload ulang
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

    private function bulanDari(array $parsed): string
    {
        return $parsed['periode'][0]
            ? PeriodeLembur::bulanUntuk(Carbon::parse($parsed['periode'][0]))
            : PeriodeLembur::bulanUntuk(now());
    }

    /**
     * Setelah import: arahkan ke daftar trip bulan file, loncat ke panel noise bila ada trip < 5 menit.
     */
    private function redirectSetelahImport(array $parsed, array $stat)
    {
        $bulan = $this->bulanDari($parsed);
        [$awal, $akhir] = PeriodeLembur::rentang($bulan);
        $jumlahNoise = GpsTrip::noise()->whereBetween('waktu_mulai', [$awal, $akhir])->count();

        $pesan = $this->pesanHasil($stat);
        if ($jumlahNoise > 0) {
            $pesan .= " Terdeteksi {$jumlahNoise} trip noise (kurang dari 5 menit), silakan tinjau di panel noise.";
        }

        return redirect(route('gps-trip.index', ['bulan' => $bulan]) . ($jumlahNoise > 0 ? '#panel-noise' : ''))
            ->with('success', $pesan);
    }

    private function pesanHasil(array $stat): string
    {
        $pesan = "Import selesai: {$stat['total']} trip dari {$stat['kendaraan']} kendaraan ({$stat['baru']} baru, {$stat['diperbarui']} diperbarui";
        if ($stat['dilewati']) {
            $pesan .= ", {$stat['dilewati']} dilewati karena sudah disesuaikan manual";
        }
        if ($stat['dihapus']) {
            $pesan .= ", {$stat['dihapus']} dihapus karena tidak terdeteksi lagi";
        }
        if ($stat['dipertahankan']) {
            $pesan .= ", {$stat['dipertahankan']} trip lengkap dipertahankan karena file ini berakhir sebelum trip selesai";
        }
        if ($stat['dibuang']) {
            $pesan .= ", {$stat['dibuang']} noise yang sudah pernah dihapus tidak dibuat lagi";
        }

        return $pesan . ').';
    }

    private function bacaUpload(string $token): ?array
    {
        if (!preg_match('/^[0-9a-f-]{36}$/', $token)) {
            return null;
        }

        $path = self::DIR_UPLOAD . "/{$token}.json";
        if (!Storage::disk('local')->exists($path)) {
            return null;
        }

        $data = json_decode(Storage::disk('local')->get($path), true);

        // Hanya pengunggah yang boleh melanjutkan
        return ($data && ($data['user_id'] ?? null) === auth()->id()) ? $data : null;
    }

    private function bersihkanUploadLama(): void
    {
        $dir = Storage::disk('local')->path(self::DIR_UPLOAD);
        if (!File::isDirectory($dir)) {
            return;
        }

        foreach (File::files($dir) as $f) {
            if ($f->getMTime() < now()->subDay()->getTimestamp()) {
                File::delete($f->getPathname());
            }
        }
    }
}
