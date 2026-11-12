@extends('layouts.app')

@section('title', 'Trip GPS')

@section('page-title', 'Trip GPS')

@push('styles')
<style>
    .gps-trip-table { min-width: 980px; }
    .gps-trip-table td { vertical-align: middle; }
    .gps-trip-table .kolom-rute { min-width: 240px; }
    /* Rute: deret titik terhubung garis tipis; nama di BAWAH tiap titik, kecil, dipotong "…" (lengkap di tooltip) */
    .rute-titik { display: flex; align-items: flex-start; color: inherit; padding: 2px 0; }
    .rute-titik .titik { position: relative; flex: 0 0 80px; min-width: 0; padding-right: 6px; }
    .rute-titik .titik-lebih { flex: 0 0 28px; padding-right: 0; }
    .rute-titik .titik-dot {
        display: block; width: 7px; height: 7px; border-radius: 50%;
        background: #495057; border: 1.5px solid #495057; position: relative; z-index: 1;
    }
    /* garis dari titik ini ke titik berikutnya (berhenti di tepi titik berikutnya) */
    .rute-titik .titik:not(:last-child)::before {
        content: ''; position: absolute; top: 3px; left: 9px; right: 2px; height: 1px; background: #ced4da;
    }
    .rute-titik .titik-lebih::before { left: 17px; right: 0; }
    .rute-titik .titik-nama {
        display: block; margin-top: 3px; font-size: .7rem; line-height: 1.2; color: #495057;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .rute-titik .titik-singgah .titik-dot { background: #fff; border-color: #868e96; }
    .rute-titik .titik-singgah .titik-nama { color: #868e96; }
    /* "+N" menggantikan titik: sejajar dengan garis */
    .rute-titik .titik-lebih .titik-nama { margin-top: -4px; color: #868e96; font-size: .68rem; overflow: visible; }
    .rute-titik .titik-kosong .titik-dot { background: transparent; border: 1.5px dashed #adb5bd; }
    .rute-titik .titik-kosong .titik-nama { font-style: italic; color: #adb5bd; }
    .gps-trip-table .baris-abaikan .rute-titik .titik-dot { background: #adb5bd; border-color: #adb5bd; }
    .gps-trip-table .baris-abaikan .rute-titik .titik-singgah .titik-dot { background: #fff; }
    .gps-trip-table .baris-abaikan .rute-titik .titik-nama { color: #adb5bd; }
    .header-sekunder { opacity: .8; font-size: .85rem; }
    .gps-trip-table .baris-tanggal td {
        background: #f4f6f9;
        font-weight: 600;
        font-size: .85rem;
        padding-top: .35rem;
        padding-bottom: .35rem;
    }
    .gps-trip-table .baris-abaikan { color: #8a8f98; background: #fcfcfd; }
    .gps-trip-table .baris-abaikan .rute-utama { text-decoration: none; }
    .rute-utama { font-weight: 600; }
    .rute-singgah { font-size: .78rem; color: #6c757d; }
    .nomor-trip {
        display: inline-block; min-width: 1.9rem; text-align: center;
        background: #007bff; color: #fff; border-radius: 1rem; font-size: .78rem; font-weight: 600; padding: .1rem .45rem;
    }
    .driver-select.tersimpan { border-color: #28a745; box-shadow: 0 0 0 .15rem rgba(40,167,69,.2); }
    .driver-select.gagal { border-color: #dc3545; box-shadow: 0 0 0 .15rem rgba(220,53,69,.2); }
    .driver-select.kosong { border-color: #ffc107; }
    .bulk-bar { position: sticky; top: 0; z-index: 10; }
    @media (max-width: 768px) { .gps-trip-table { font-size: .82rem; } }
</style>
@endpush

@section('content')
@php
    use App\Models\GpsTrip;
    use App\Services\GpsTrip\GpsTripImporter;
    use App\Services\GpsTrip\GpsTripParser;
    $tanggalIndo = fn ($d) => $d->locale('id')->translatedFormat('l, d F Y');
@endphp

<div class="row">
    <div class="col-12">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        {{-- Filter --}}
        <div class="card">
            <div class="card-body py-2">
                <form action="{{ route('gps-trip.index') }}" method="GET" class="form-inline">
                    <label class="mr-2 small text-muted">Periode</label>
                    <input type="month" name="bulan" value="{{ $bulan }}" class="form-control form-control-sm mr-2 mb-1" title="Periode lembur diberi nama menurut bulan tanggal 25">
                    <span class="small text-muted mr-3 mb-1">{{ $periodeLabel }}</span>

                    <label class="mr-2 small text-muted">Kendaraan</label>
                    <select name="nopol" class="form-control form-control-sm mr-3 mb-1">
                        <option value="">Semua</option>
                        @foreach ($daftarNopol as $n)
                            <option value="{{ $n }}" @selected($nopolDipilih === $n)>{{ $n }}</option>
                        @endforeach
                    </select>

                    <label class="mr-2 small text-muted">Driver</label>
                    <select name="driver" class="form-control form-control-sm mr-3 mb-1">
                        <option value="">Semua</option>
                        <option value="kosong" @selected($driver === 'kosong')>Belum ditentukan</option>
                        @foreach ($operators as $op)
                            <option value="{{ $op->id }}" @selected((string) $driver === (string) $op->id)>{{ $op->nama }}</option>
                        @endforeach
                    </select>

                    <label class="mr-2 small text-muted">Tampilkan</label>
                    <select name="tampil" class="form-control form-control-sm mr-3 mb-1">
                        <option value="semua" @selected($tampil === 'semua')>Semua</option>
                        <option value="dihitung" @selected($tampil === 'dihitung')>Yang dihitung saja</option>
                        <option value="perlu_dicek" @selected($tampil === 'perlu_dicek')>Perlu dicek</option>
                    </select>

                    <label class="mr-2 small text-muted">Validasi</label>
                    <select name="validasi" class="form-control form-control-sm mr-3 mb-1">
                        <option value="">Semua</option>
                        <option value="belum" @selected($validasi === 'belum')>Belum divalidasi</option>
                        <option value="sudah" @selected($validasi === 'sudah')>Sudah divalidasi</option>
                    </select>

                    <button type="submit" class="btn btn-primary btn-sm mb-1 mr-2"><i class="fas fa-filter mr-1"></i> Terapkan</button>

                    <div class="ml-auto mb-1">
                        <a href="{{ route('gps-trip.lembur', ['bulan' => $bulan]) }}" class="btn btn-primary btn-sm"><i class="fas fa-user-clock mr-1"></i> Terapkan ke Lembur</a>
                        <a href="{{ route('gps-trip.geofence') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-map-pin mr-1"></i> Master Geofence</a>
                        <a href="{{ route('gps-trip.upload') }}" class="btn btn-outline-secondary btn-sm" title="Cadangan bila data Cartrack tidak tersedia"><i class="fas fa-file-upload mr-1"></i> Import Excel</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Sinkron Cartrack: semua trip kendaraan Cartrack masuk ke sini, dengan atau tanpa jadwal perjalanan --}}
        @php
            $waktuSinkron = $sinkronTerakhir ? \Carbon\Carbon::parse($sinkronTerakhir['waktu']) : null;
            $menitSinkron = $waktuSinkron ? (int) $waktuSinkron->diffInMinutes(now()) : null;
            $sinkronHidup = $menitSinkron !== null && $menitSinkron <= 30;
        @endphp
        <div class="card">
            <div class="card-body py-2 d-flex flex-wrap align-items-center">
                <span class="badge {{ $sinkronHidup ? 'badge-success' : 'badge-danger' }} mr-2">{{ $sinkronHidup ? 'Sinkron Cartrack aktif' : 'Sinkron Cartrack tidak jalan' }}</span>
                <span class="small text-muted mr-3">
                    @if ($waktuSinkron)
                        Terakhir {{ $menitSinkron < 1 ? 'baru saja' : $menitSinkron.' menit lalu' }}
                        · {{ $sinkronTerakhir['kendaraan'] }} kendaraan
                        @if ($sinkronTerakhir['baru']) · {{ $sinkronTerakhir['baru'] }} trip baru @endif
                    @else
                        Belum pernah jalan.
                    @endif
                    · otomatis tiap 15 menit untuk semua kendaraan; periode berjalan ditarik penuh sekali sehari
                </span>
                @if (!empty($sinkronTerakhir['galat']))
                    <span class="small text-danger mr-3" title="{{ implode(' | ', array_map(fn ($k, $v) => $k.': '.$v, array_keys($sinkronTerakhir['galat']), $sinkronTerakhir['galat'])) }}">
                        <i class="fas fa-exclamation-triangle"></i> {{ count($sinkronTerakhir['galat']) }} kendaraan gagal ditarik
                    </span>
                @endif
                @if ($belumValidasi)
                    <a href="{{ route('gps-trip.index', ['bulan' => $bulan, 'validasi' => 'belum']) }}" class="small mr-3">
                        <i class="fas fa-clipboard-check"></i> {{ $belumValidasi }} trip periode ini belum divalidasi
                    </a>
                @endif
                @if ($sinkronMenunggu)
                    <span class="small text-info ml-auto mr-2"><i class="fas fa-hourglass-half"></i> Sinkron periode {{ \App\Services\GpsTrip\PeriodeLembur::label($sinkronMenunggu) }} diminta, menunggu penjadwal (±1 menit)</span>
                @endif
                <form action="{{ route('gps-trip.sinkron') }}" method="POST" class="form-inline {{ $sinkronMenunggu ? '' : 'ml-auto' }}">
                    @csrf
                    <input type="hidden" name="bulan" value="{{ $bulan }}">
                    <button type="submit" class="btn btn-success btn-sm" title="Tarik ulang seluruh trip periode {{ $periodeLabel }} dari Cartrack"><i class="fas fa-sync-alt mr-1"></i> Sinkron periode ini</button>
                </form>
            </div>
        </div>

        {{-- Periode yang dipilih belum pernah ditarik penuh dari Cartrack --}}
        @unless ($periodeSinkron)
            <div class="alert alert-warning d-flex flex-wrap align-items-center">
                <div class="mr-3">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    @if ($periodeBerjalan)
                        Periode <strong>{{ $periodeLabel }}</strong> belum ditarik penuh dari Cartrack. Penjadwal akan menariknya otomatis dalam beberapa menit.
                    @else
                        Periode <strong>{{ $periodeLabel }}</strong> belum pernah ditarik dari Cartrack, jadi datanya mungkin kosong atau belum lengkap.
                        Sinkronkan dulu sebelum dipakai.
                    @endif
                </div>
                @unless ($sinkronMenunggu)
                    <form action="{{ route('gps-trip.sinkron') }}" method="POST" class="ml-auto">
                        @csrf
                        <input type="hidden" name="bulan" value="{{ $bulan }}">
                        <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-sync-alt mr-1"></i> Sinkronkan periode ini</button>
                    </form>
                @endunless
            </div>
        @endunless

        {{-- Ringkasan --}}
        <div class="row">
            <div class="col-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-primary"><i class="fas fa-route"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Trip dihitung</span>
                        <span class="info-box-number">{{ $ringkasan['trip_dihitung'] }}</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-info"><i class="fas fa-truck"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Kendaraan</span>
                        <span class="info-box-number">{{ $ringkasan['kendaraan'] }}</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-warning"><i class="fas fa-user-slash"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Tanpa driver</span>
                        <span class="info-box-number">{{ $ringkasan['tanpa_driver'] }}</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-secondary"><i class="fas fa-exclamation-triangle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Perlu dicek / tidak dihitung</span>
                        <span class="info-box-number">{{ $ringkasan['perlu_dicek'] }} / {{ $ringkasan['tidak_dihitung'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel noise: trip < 5 menit dari sistem GPS --}}
        @if ($noise->isNotEmpty())
            <form id="form-noise" action="{{ route('gps-trip.noise') }}" method="POST">
                @csrf
                <div class="card card-warning card-outline" id="panel-noise">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-broom mr-1"></i>
                            <strong class="mr-1">{{ $noise->count() }}</strong>trip noise terdeteksi
                            <small class="header-sekunder ml-1">durasi kurang dari 5 menit</small>
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="px-3 py-2 small border-bottom">
                            Trip sependek ini biasanya hanya pembacaan sistem GPS (mis. PT → PT yang sama selama beberapa detik).
                            Semua sudah <strong>dicentang untuk dihapus</strong>. Hilangkan centang pada trip yang ingin dipertahankan.
                            Trip yang tidak dicentang akan tetap menjadi trip biasa dan tidak ditanyakan lagi.
                        </div>
                        <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
                            <table class="table table-sm table-hover mb-0" style="min-width: 760px;">
                                <thead>
                                    <tr>
                                        <th style="width: 32px;"><input type="checkbox" id="noise-pilih-semua" checked title="Pilih / hilangkan semua"></th>
                                        <th>Kendaraan</th>
                                        <th>Waktu</th>
                                        <th class="text-right">Durasi</th>
                                        <th>Rute</th>
                                        <th class="text-right">Jarak (km)</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($noise as $n)
                                        <tr>
                                            <td>
                                                <input type="hidden" name="semua[]" value="{{ $n->id }}">
                                                <input type="checkbox" name="hapus[]" value="{{ $n->id }}" class="js-noise-hapus" checked>
                                            </td>
                                            <td class="text-nowrap">{{ $n->nopol }}</td>
                                            <td class="text-nowrap">
                                                {{ $n->waktu_mulai->format('d/m H:i:s') }} – {{ $n->waktu_selesai->format('H:i:s') }}
                                            </td>
                                            <td class="text-right text-nowrap" title="menit:detik">{{ GpsTrip::formatMenitDetik($n->durasi_trip_detik) }}</td>
                                            <td>{{ $n->geofence_mulai ?? '(tidak terbaca)' }} <i class="fas fa-long-arrow-alt-right text-muted mx-1"></i> {{ $n->geofence_selesai ?? '(tidak terbaca)' }}</td>
                                            <td class="text-right text-nowrap">{{ number_format($n->jarak_km, 1, ',', '.') }}</td>
                                            <td>
                                                @if ($n->operator)
                                                    <span class="badge badge-info" title="Trip ini sudah diberi driver">driver: {{ $n->operator->nama }}</span>
                                                @endif
                                                @if ($n->operator_gtm_lembur_id)
                                                    <span class="badge badge-warning" title="Sudah dipakai di data lembur; menghapusnya tidak mengubah data lembur itu">sudah di lembur</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center">
                        <button type="submit" class="btn btn-danger" id="btn-hapus-noise"><i class="fas fa-trash mr-1"></i> Hapus yang dicentang (<span id="jumlah-noise-hapus">{{ $noise->count() }}</span>)</button>
                        <small class="text-muted ml-3">Trip noise yang dihapus tidak akan muncul lagi walau file yang sama di-upload ulang.</small>
                    </div>
                </div>
            </form>
        @endif

        @if ($perKendaraan->isEmpty())
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="fas fa-satellite-dish fa-2x mb-3"></i>
                    <p class="mb-3">Belum ada data trip untuk filter ini. Trip dari Cartrack masuk otomatis tiap 15 menit.</p>
                    <a href="{{ route('gps-trip.upload') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-file-upload mr-1"></i> Import Excel GPS</a>
                </div>
            </div>
        @else
            {{-- Rekap per driver --}}
            <div class="card collapsed-card">
                <div class="card-header">
                    <h3 class="card-title">Rekap per Driver (trip yang dihitung)</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Driver</th>
                                <th class="text-right">Hari kerja</th>
                                <th class="text-right">Trip</th>
                                <th class="text-right">Jarak (km)</th>
                                <th class="text-right">Waktu jalan (jam:menit)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rekapDriver as $id => $r)
                                <tr>
                                    <td>{!! $id === 0 ? '<span class="text-warning">Belum ditentukan</span>' : e($r['nama']) !!}</td>
                                    <td class="text-right">{{ $r['hari'] }}</td>
                                    <td class="text-right">{{ $r['trip'] }}</td>
                                    <td class="text-right">{{ number_format($r['jarak'], 1, ',', '.') }}</td>
                                    <td class="text-right">{{ GpsTrip::formatDurasi($r['durasi_jalan']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Aksi massal --}}
            <form id="form-aksi" action="{{ route('gps-trip.aksi') }}" method="POST">
                @csrf
                <div class="card bulk-bar">
                    <div class="card-body py-2 d-flex flex-wrap align-items-center">
                        <span class="mr-3 small text-muted"><strong id="jumlah-terpilih">0</strong> trip dipilih</span>
                        <select name="operator_gtm_id" class="form-control form-control-sm mr-2" style="width: 190px;">
                            <option value="">— kosongkan driver —</option>
                            @foreach ($operators as $op)
                                <option value="{{ $op->id }}">{{ $op->nama }}</option>
                            @endforeach
                        </select>
                        {{-- Nilai aksi dibawa lewat input hidden, BUKAN name/value pada tombol: script global
                             (js/responsive.js) menonaktifkan tombol submit saat submit, sehingga nilainya tidak terkirim. --}}
                        <input type="hidden" name="aksi" id="aksi-nilai" value="">
                        <button type="submit" data-aksi="driver" class="btn btn-primary btn-sm mr-3"><i class="fas fa-user-check mr-1"></i> Set Driver</button>
                        <button type="submit" data-aksi="validasi" class="btn btn-outline-primary btn-sm mr-1" title="Hanya trip Cartrack yang sudah punya driver"><i class="fas fa-check-double mr-1"></i> Validasi</button>
                        <button type="submit" data-aksi="batal_validasi" class="btn btn-outline-secondary btn-sm mr-3">Batal validasi</button>
                        <button type="submit" data-aksi="hitung" class="btn btn-outline-success btn-sm mr-1">Hitung</button>
                        <button type="submit" data-aksi="abaikan" class="btn btn-outline-secondary btn-sm mr-1">Jangan hitung</button>
                        <button type="submit" data-aksi="hapus" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash mr-1"></i> Hapus</button>
                    </div>
                </div>
            </form>

            {{-- Per kendaraan --}}
            @foreach ($perKendaraan as $nopol => $daftar)
                @php
                    $dihitung = $daftar->where('dihitung', true);
                    $terdaftar = isset($kunciTerdaftar[GpsTripImporter::kunciNopol($nopol)]);
                    $perTanggal = $daftar->groupBy(fn ($t) => $t->waktu_mulai->toDateString());
                @endphp
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-truck mr-1"></i> <strong>{{ $nopol }}</strong>
                            <small class="header-sekunder ml-1">{{ $daftar->first()->model_kendaraan }}</small>
                            @unless ($terdaftar)
                                <span class="badge badge-warning ml-2" title="Nopol ini belum ada di Kelola Mobil/NOPOL">belum terdaftar di NOPOL</span>
                            @endunless
                        </h3>
                        <div class="card-tools header-sekunder mr-2">
                            <strong>{{ $dihitung->count() }}</strong> trip ·
                            {{ number_format($dihitung->sum('jarak_km'), 1, ',', '.') }} km ·
                            jalan {{ GpsTrip::formatDurasi($dihitung->sum('durasi_jalan_detik')) }} jam
                            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-sm gps-trip-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 32px;"><input type="checkbox" class="js-pilih-kendaraan" title="Pilih semua trip kendaraan ini"></th>
                                    <th style="width: 50px;">Trip</th>
                                    <th style="width: 120px;">Waktu</th>
                                    <th class="kolom-rute">Rute</th>
                                    <th class="text-right" style="width: 110px;">Durasi</th>
                                    <th class="text-right" style="width: 80px;">Jarak</th>
                                    <th style="width: 190px;">Driver</th>
                                    <th style="width: 120px;">Status</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($perTanggal as $tanggal => $trips)
                                    @php $hitungHari = $trips->where('dihitung', true)->count(); @endphp
                                    <tr class="baris-tanggal">
                                        <td colspan="9">
                                            {{ $tanggalIndo(\Carbon\Carbon::parse($tanggal)) }}
                                            <span class="text-muted font-weight-normal">— {{ $hitungHari }} trip</span>
                                        </td>
                                    </tr>
                                    @foreach ($trips as $t)
                                        @php
                                            $namaCustMulai = $t->geofence_mulai ? ($geofenceCustomer[GpsTripParser::normalisasiNama($t->geofence_mulai)] ?? null) : null;
                                            $namaCustSelesai = $t->geofence_selesai ? ($geofenceCustomer[GpsTripParser::normalisasiNama($t->geofence_selesai)] ?? null) : null;
                                            $lewatTengahMalam = $t->waktu_selesai && !$t->waktu_selesai->isSameDay($t->waktu_mulai);
                                        @endphp
                                        <tr class="{{ $t->dihitung ? '' : 'baris-abaikan' }}">
                                            <td><input type="checkbox" name="ids[]" value="{{ $t->id }}" form="form-aksi" class="js-pilih-trip"></td>
                                            <td>
                                                @if ($t->dihitung)
                                                    <span class="nomor-trip">{{ $nomorTrip[$t->id] ?? '' }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-nowrap">
                                                {{ $t->waktu_mulai->format('H:i') }}
                                                –
                                                @if ($t->waktu_selesai)
                                                    {{ $t->waktu_selesai->format('H:i') }}
                                                    @if ($lewatTengahMalam)<sup class="text-muted">+{{ (int) $t->waktu_mulai->copy()->startOfDay()->diffInDays($t->waktu_selesai->copy()->startOfDay()) }}hr</sup>@endif
                                                @else
                                                    ?
                                                @endif
                                            </td>
                                            <td class="kolom-rute">
                                                {{-- Rute sebagai deret titik terhubung: awal, 1 singgah (sisanya "+N"), akhir.
                                                     Nama di bawah tiap titik, dipotong "…"; nama lengkap & customer ada di tooltip. --}}
                                                @php
                                                    $namaCust = fn ($g) => $g ? ($geofenceCustomer[GpsTripParser::normalisasiNama($g)] ?? null) : null;
                                                    $singgah = $t->perhentian;
                                                    $titik = [['awal', $t->geofence_mulai, $namaCustMulai]];
                                                    foreach (array_slice($singgah, 0, 1) as $s) {
                                                        $titik[] = ['singgah', $s, $namaCust($s)];
                                                    }
                                                    if (count($singgah) > 1) {
                                                        $titik[] = ['lebih', '+' . (count($singgah) - 1), implode(', ', array_slice($singgah, 1))];
                                                    }
                                                    $titik[] = ['akhir', $t->geofence_selesai, $namaCustSelesai];
                                                @endphp
                                                <div class="rute-titik">
                                                    @foreach ($titik as [$jenis, $nama, $cust])
                                                        @if ($jenis === 'lebih')
                                                            <span class="titik titik-lebih" title="Singgah lainnya: {{ $cust }}"><span class="titik-nama">{{ $nama }}</span></span>
                                                        @else
                                                            <span class="titik titik-{{ $jenis }} {{ $nama ? '' : 'titik-kosong' }}"
                                                                  title="{{ ['awal' => 'Mulai', 'singgah' => 'Singgah', 'akhir' => 'Selesai'][$jenis] }}: {{ $nama ?? 'tidak terbaca' }}{{ $cust ? ' · Customer: ' . $cust : '' }}">
                                                                <i class="titik-dot"></i><span class="titik-nama">{{ $nama ?? 'tidak terbaca' }}</span>
                                                            </span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                                @if ($t->catatan)
                                                    <div class="rute-singgah"><i class="far fa-sticky-note"></i> {{ $t->catatan }}</div>
                                                @endif
                                            </td>
                                            <td class="text-right text-nowrap">
                                                {{ GpsTrip::formatDurasi($t->durasi_trip_detik) }}
                                                <div class="rute-singgah" title="Total waktu mesin menyala">jalan {{ GpsTrip::formatDurasi($t->durasi_jalan_detik) }}</div>
                                            </td>
                                            <td class="text-right text-nowrap">{{ number_format($t->jarak_km, 1, ',', '.') }}</td>
                                            <td>
                                                <select class="form-control form-control-sm driver-select {{ $t->operator_gtm_id ? '' : 'kosong' }}"
                                                        data-id="{{ $t->id }}"
                                                        @if ($t->driver_gps) title="Driver di GPS: {{ $t->driver_gps }}" @endif>
                                                    <option value="">— pilih driver —</option>
                                                    @foreach ($operators as $op)
                                                        <option value="{{ $op->id }}" @selected($t->operator_gtm_id === $op->id)>{{ $op->nama }}</option>
                                                    @endforeach
                                                </select>
                                                @if ($t->driver_gps && !$t->operator_gtm_id)
                                                    <div class="rute-singgah">GPS: {{ $t->driver_gps }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($t->status === 'tidak_lengkap')
                                                    <span class="badge badge-warning" title="Akhir trip tidak terbaca di geofence customer atau trip belum selesai di akhir laporan">tidak lengkap</span>
                                                @endif
                                                @if ($t->jeda_terpanjang_detik > GpsTrip::AMBANG_JEDA_DETIK)
                                                    <span class="badge badge-danger" title="Ada jeda {{ GpsTrip::formatDurasi($t->jeda_terpanjang_detik) }} jam antar perjalanan (kendaraan berhenti lama di titik tak terbaca)">jeda {{ GpsTrip::formatDurasi($t->jeda_terpanjang_detik) }}j</span>
                                                @endif
                                                @if (!$t->dihitung)
                                                    <span class="badge badge-secondary">tidak dihitung</span>
                                                @endif
                                                @if ($t->sumber === GpsTrip::SUMBER_CARTRACK)
                                                    @if ($t->divalidasi)
                                                        <span class="badge badge-success" title="Sudah divalidasi, layak masuk halaman Lembur">valid</span>
                                                    @else
                                                        <span class="badge badge-warning" title="Belum divalidasi: belum ikut hitungan lembur">belum valid</span>
                                                    @endif
                                                    @if ($t->jadwal_perjalanan_id)
                                                        <a href="{{ route('jadwal-perjalanan.show', $t->jadwal_perjalanan_id) }}" class="badge badge-info" title="Trip ini bagian dari jadwal perjalanan">jadwal #{{ $t->jadwal_perjalanan_id }}</a>
                                                    @else
                                                        <span class="badge badge-light border" title="Terbaca dari Cartrack tanpa jadwal perjalanan: isi driver lalu validasi di sini">tanpa jadwal</span>
                                                    @endif
                                                @endif
                                                @if ($t->operator_gtm_lembur_id)
                                                    <span class="badge badge-success" title="Sudah dipakai untuk data lembur operator">lembur</span>
                                                @endif
                                                @if ($t->diedit)
                                                    <span class="badge badge-light border" title="Sudah disesuaikan manual, tidak ditimpa saat upload ulang">diedit</span>
                                                @endif
                                            </td>
                                            <td class="text-right">
                                                <button type="button" class="btn btn-outline-primary btn-xs js-edit"
                                                        data-id="{{ $t->id }}"
                                                        data-nopol="{{ $t->nopol }}"
                                                        data-mulai="{{ $t->waktu_mulai->format('Y-m-d\TH:i:s') }}"
                                                        data-selesai="{{ $t->waktu_selesai ? $t->waktu_selesai->format('Y-m-d\TH:i:s') : '' }}"
                                                        data-geo-mulai="{{ $t->geofence_mulai }}"
                                                        data-geo-selesai="{{ $t->geofence_selesai }}"
                                                        data-operator="{{ $t->operator_gtm_id }}"
                                                        data-dihitung="{{ $t->dihitung ? 1 : 0 }}"
                                                        data-catatan="{{ $t->catatan }}"
                                                        data-segmen='@json($t->segmen)'
                                                        title="Edit / lihat detail">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif

        @if ($importTerakhir)
            <p class="text-muted small">
                Upload terakhir: {{ $importTerakhir->nama_file }} · {{ $importTerakhir->created_at->format('d/m/Y H:i') }}
                oleh {{ optional($importTerakhir->user)->name ?? '-' }}
            </p>
        @endif
    </div>
</div>

{{-- Modal edit trip --}}
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form id="form-edit" method="POST" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Edit Trip <span id="edit-nopol" class="text-primary"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Waktu mulai</label>
                        <input type="datetime-local" step="1" name="waktu_mulai" id="edit-mulai" class="form-control" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Waktu selesai</label>
                        <input type="datetime-local" step="1" name="waktu_selesai" id="edit-selesai" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Geofence awal</label>
                        <input type="text" name="geofence_mulai" id="edit-geo-mulai" class="form-control" maxlength="255">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Geofence akhir</label>
                        <input type="text" name="geofence_selesai" id="edit-geo-selesai" class="form-control" maxlength="255">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Driver</label>
                        <select name="operator_gtm_id" id="edit-operator" class="form-control">
                            <option value="">— belum ditentukan —</option>
                            @foreach ($operators as $op)
                                <option value="{{ $op->id }}">{{ $op->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6 d-flex align-items-end">
                        <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input" id="edit-dihitung" name="dihitung" value="1">
                            <label class="custom-control-label" for="edit-dihitung">Hitung sebagai trip</label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Catatan</label>
                    <textarea name="catatan" id="edit-catatan" rows="2" class="form-control" maxlength="1000"></textarea>
                </div>

                <h6 class="mt-3">Detail perjalanan dari GPS</h6>
                <div class="table-responsive" style="max-height: 220px; overflow:auto;">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="thead-light">
                            <tr><th>Mulai</th><th>Selesai</th><th>Dari</th><th>Ke</th><th class="text-right">km</th></tr>
                        </thead>
                        <tbody id="edit-segmen"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var urlAksi = @json(route('gps-trip.aksi'));
    var urlUpdate = @json(route('gps-trip.update', ':id'));
    var csrf = document.querySelector('meta[name="csrf-token"]').content;

    // --- Pilih trip & penghitung
    var jumlahEl = document.getElementById('jumlah-terpilih');
    function hitungTerpilih() {
        jumlahEl.textContent = document.querySelectorAll('.js-pilih-trip:checked').length;
    }
    document.querySelectorAll('.js-pilih-trip').forEach(function (cb) { cb.addEventListener('change', hitungTerpilih); });
    document.querySelectorAll('.js-pilih-kendaraan').forEach(function (cb) {
        cb.addEventListener('change', function () {
            cb.closest('table').querySelectorAll('.js-pilih-trip').forEach(function (x) { x.checked = cb.checked; });
            hitungTerpilih();
        });
    });

    var formAksi = document.getElementById('form-aksi');
    if (formAksi) {
        var aksiNilai = document.getElementById('aksi-nilai');
        // Klik tombol terjadi sebelum submit: catat aksinya di input hidden
        formAksi.querySelectorAll('button[data-aksi]').forEach(function (btn) {
            btn.addEventListener('click', function () { aksiNilai.value = btn.dataset.aksi; });
        });

        formAksi.addEventListener('submit', function (e) {
            var aksi = aksiNilai.value;
            var n = document.querySelectorAll('.js-pilih-trip:checked').length;
            // stopImmediatePropagation: batalkan juga handler global responsive.js yang akan
            // mengubah tombol jadi "Memproses..." walau submit dibatalkan
            if (!aksi) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return;
            }
            if (n === 0) {
                e.preventDefault();
                e.stopImmediatePropagation();
                alert('Pilih minimal satu trip terlebih dahulu.');
                return;
            }
            if (aksi === 'hapus' && !confirm('Hapus ' + n + ' trip terpilih? Trip dari Cartrack tidak akan dibuat lagi; trip dari Excel muncul lagi bila file laporannya di-upload ulang.')) {
                e.preventDefault();
                e.stopImmediatePropagation();
            }
        });
    }

    // --- Panel noise
    var formNoise = document.getElementById('form-noise');
    if (formNoise) {
        var cekNoise = formNoise.querySelectorAll('.js-noise-hapus');
        var jumlahNoiseEl = document.getElementById('jumlah-noise-hapus');
        var semuaNoise = document.getElementById('noise-pilih-semua');
        function hitungNoise() {
            var n = formNoise.querySelectorAll('.js-noise-hapus:checked').length;
            jumlahNoiseEl.textContent = n;
            semuaNoise.checked = n === cekNoise.length;
        }
        cekNoise.forEach(function (cb) { cb.addEventListener('change', hitungNoise); });
        semuaNoise.addEventListener('change', function () {
            cekNoise.forEach(function (cb) { cb.checked = semuaNoise.checked; });
            hitungNoise();
        });
        formNoise.addEventListener('submit', function (e) {
            var hapus = formNoise.querySelectorAll('.js-noise-hapus:checked').length;
            var sisa = cekNoise.length - hapus;
            var pesan = hapus === 0
                ? 'Tidak ada yang dicentang. Semua ' + sisa + ' trip noise akan DIPERTAHANKAN sebagai trip biasa dan tidak ditanyakan lagi. Lanjutkan?'
                : 'Hapus ' + hapus + ' trip noise' + (sisa ? ' dan pertahankan ' + sisa + ' lainnya sebagai trip biasa' : '') + '?';
            if (!confirm(pesan)) { e.preventDefault(); e.stopImmediatePropagation(); }
        });
    }

    // --- Ubah driver langsung dari baris (simpan otomatis)
    document.querySelectorAll('.driver-select').forEach(function (sel) {
        sel.addEventListener('change', function () {
            var body = new FormData();
            body.append('ids[]', sel.dataset.id);
            body.append('aksi', 'driver');
            body.append('operator_gtm_id', sel.value);
            sel.classList.remove('tersimpan', 'gagal');
            fetch(urlAksi, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: body
            }).then(function (r) {
                if (!r.ok) { throw new Error(r.status); }
                sel.classList.add('tersimpan');
                sel.classList.toggle('kosong', sel.value === '');
                setTimeout(function () { sel.classList.remove('tersimpan'); }, 1200);
            }).catch(function () {
                sel.classList.add('gagal');
                alert('Driver gagal disimpan. Muat ulang halaman lalu coba lagi.');
            });
        });
    });

    // --- Modal edit
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }
    document.querySelectorAll('.js-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var d = btn.dataset;
            document.getElementById('form-edit').action = urlUpdate.replace(':id', d.id);
            document.getElementById('edit-nopol').textContent = d.nopol;
            document.getElementById('edit-mulai').value = d.mulai;
            document.getElementById('edit-selesai').value = d.selesai;
            document.getElementById('edit-geo-mulai').value = d.geoMulai;
            document.getElementById('edit-geo-selesai').value = d.geoSelesai;
            document.getElementById('edit-operator').value = d.operator;
            document.getElementById('edit-dihitung').checked = d.dihitung === '1';
            document.getElementById('edit-catatan').value = d.catatan;

            var segmen = [];
            try { segmen = JSON.parse(d.segmen) || []; } catch (e) {}
            document.getElementById('edit-segmen').innerHTML = segmen.map(function (s) {
                return '<tr><td class="text-nowrap">' + esc(s.mulai.substring(5, 16)) + '</td>' +
                    '<td class="text-nowrap">' + esc(s.selesai.substring(5, 16)) + '</td>' +
                    '<td>' + esc(s.geofence_mulai || s.lokasi_mulai) + '</td>' +
                    '<td>' + esc(s.geofence_selesai || s.lokasi_selesai) + '</td>' +
                    '<td class="text-right">' + Number(s.jarak_km).toFixed(1) + '</td></tr>';
            }).join('');

            $('#modalEdit').modal('show');
        });
    });
})();
</script>
@endpush
