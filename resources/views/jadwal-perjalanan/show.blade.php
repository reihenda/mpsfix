@extends('layouts.app')

@section('title', 'Jadwal Perjalanan #'.$jadwal->id)

@section('page-title', 'Jadwal Perjalanan #'.$jadwal->id)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/jadwal-perjalanan.css') }}?v={{ filemtime(public_path('css/jadwal-perjalanan.css')) }}">
@endpush

@section('content')
@php
    use App\Models\GpsTrip;
    use App\Services\GpsTrip\GpsTripParser;
    $kelasStatus = ['terjadwal' => 'is-info', 'berjalan' => 'is-accent', 'selesai' => 'is-ok', 'batal' => 'is-mute'];
    $namaOperator = GpsTripParser::normalisasiNama($namaDriverCartrack);
    $belumValid = $jadwal->trips->where('divalidasi', false);
@endphp

<div class="jp">
    <div class="jp-head is-start">
        <a href="{{ route('jadwal-perjalanan.index') }}" class="btn btn-ghost btn-sm"><i class="fas fa-arrow-left"></i>Semua jadwal</a>
        <span class="jp-badge {{ $kelasStatus[$jadwal->status] ?? '' }}">{{ $jadwal->status_label }}</span>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Tutup"><span>&times;</span></button></div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Tutup"><span>&times;</span></button></div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0 pl-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Ringkasan</h3></div>
                <div class="card-body">
                    <dl class="jp-dl">
                        <dt>Kendaraan</dt><dd class="jp-nopol">{{ $jadwal->nopol }}</dd>
                        <dt>Driver</dt>
                        <dd>{{ $jadwal->operator?->nama ?? '–' }}
                            @if ($namaDriverCartrack) <small class="jp-sub">Di Cartrack: {{ $namaDriverCartrack }}</small>
                            @else <small class="jp-sub text-danger">Belum dipetakan ke driver Cartrack</small> @endif
                        </dd>
                        <dt>Mulai</dt><dd>{{ $jadwal->waktu_mulai->format('d/m/Y H:i') }}</dd>
                        <dt>Selesai</dt><dd>{{ $jadwal->waktu_selesai->format('d/m/Y H:i') }}</dd>
                        @if ($jadwal->catatan)
                            <dt>Catatan</dt><dd>{{ $jadwal->catatan }}</dd>
                        @endif
                        <dt>Dibuat oleh</dt><dd>{{ $jadwal->pembuat?->name ?? '–' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Rute</h3></div>
                <ol class="jp-steps py-2">
                    @foreach ($jadwal->rute as $r)
                        <li class="jp-step">
                            <span class="jp-step-no">{{ $r->urutan }}</span>
                            <div class="jp-step-body">
                                <strong>{{ $r->tujuan }}</strong>
                                @if ($r->catatan) <span class="jp-sub">{{ $r->catatan }}</span> @endif
                            </div>
                            @if ($jadwal->trips->isNotEmpty())
                                <span class="jp-step-end">
                                    @if ($ruteTercapai[$r->id] ?? false)
                                        <span class="jp-badge is-ok" title="Tujuan ini tampil di geofence trip">Tercapai</span>
                                    @else
                                        <span class="jp-badge is-warn" title="Tidak ada trip yang melewati geofence ini">Tidak terdeteksi</span>
                                    @endif
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Cartrack</h3></div>
                <div class="card-body pb-2">
                    <div class="jp-kv">
                        <span>Link driver</span>
                        <span>
                            @switch($jadwal->link_status)
                                @case('terkirim') <span class="jp-badge is-ok">Terkirim</span>
                                    @if ($jadwal->link_dikirim_pada) <span class="jp-sub d-inline ml-1">{{ $jadwal->link_dikirim_pada->format('d/m H:i') }}</span> @endif @break
                                @case('gagal') <span class="jp-badge is-bad">Gagal</span> @break
                                @case('dilewati') <span class="jp-badge is-mute">Tidak dikirim</span> @break
                                @default <span class="jp-badge is-mute">Menunggu waktu mulai</span>
                            @endswitch
                        </span>
                    </div>
                    @if ($jadwal->unlink_pada)
                        <div class="jp-kv"><span>Driver dilepas</span><span>{{ $jadwal->unlink_pada->format('d/m H:i') }}</span></div>
                    @endif
                    <div class="jp-kv">
                        <span>Trip</span>
                        <span>{{ $jadwal->trip_ditarik_pada ? 'Ditarik '.$jadwal->trip_ditarik_pada->format('d/m H:i') : 'Belum ditarik' }}</span>
                    </div>
                    @if ($jadwal->link_error) <div class="jp-note-bad">{{ $jadwal->link_error }}</div> @endif
                    @if ($jadwal->trip_tarik_error) <div class="jp-note-bad">{{ $jadwal->trip_tarik_error }}</div> @endif
                </div>
                <div class="card-footer jp-actions">
                    @if ($jadwal->aktif)
                        <a href="{{ route('jadwal-perjalanan.edit', $jadwal) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-pen"></i>Ubah</a>
                    @endif
                    @if ($jadwal->aktif && $jadwal->link_status === 'gagal')
                        <form method="POST" action="{{ route('jadwal-perjalanan.ulangi-link', $jadwal) }}">@csrf
                            <button class="btn btn-sm btn-warning"><i class="fas fa-redo"></i>Kirim ulang driver</button></form>
                    @endif
                    @if ($jadwal->status === 'berjalan')
                        <form method="POST" action="{{ route('jadwal-perjalanan.selesaikan', $jadwal) }}"
                              onsubmit="return confirm('Selesaikan jadwal sekarang? Driver dilepas dari kendaraan di Cartrack dan trip ditarik.')">@csrf
                            <button class="btn btn-sm btn-success"><i class="fas fa-flag-checkered"></i>Selesaikan sekarang</button></form>
                    @endif
                    @if ($jadwal->status !== 'batal' && $jadwal->waktu_mulai->lte(\App\Services\Cartrack\JadwalPerjalananService::sekarangLokal()))
                        <form method="POST" action="{{ route('jadwal-perjalanan.tarik-trip', $jadwal) }}">@csrf
                            <button class="btn btn-sm btn-ghost"><i class="fas fa-download"></i>{{ $jadwal->trip_ditarik_pada ? 'Tarik ulang trip' : 'Tarik trip' }}</button></form>
                    @endif
                    @if ($jadwal->aktif)
                        <form method="POST" action="{{ route('jadwal-perjalanan.batalkan', $jadwal) }}"
                              onsubmit="return confirm('Batalkan jadwal ini?{{ $jadwal->status === 'berjalan' ? ' Driver akan dilepas dari kendaraan di Cartrack.' : '' }}')">@csrf
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-ban"></i>Batalkan</button></form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <form method="POST" action="{{ route('jadwal-perjalanan.validasi') }}" id="form-validasi">
                @csrf
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Trip dari Cartrack
                            <span class="jp-badge is-plain is-mute">{{ $jadwal->trips->count() }}</span>
                            @if ($belumValid->count()) <span class="jp-badge is-warn">{{ $belumValid->count() }} belum divalidasi</span> @endif
                        </h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table jp-table" style="min-width: 580px;">
                            <thead>
                                <tr>
                                    <th style="width:36px"><input type="checkbox" id="pilih-semua" aria-label="Pilih semua trip"></th>
                                    <th>Waktu</th>
                                    <th>Rute GPS</th>
                                    <th class="text-right">Jarak</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($jadwal->trips as $t)
                                    @php
                                        $terkunci = $t->operator_gtm_lembur_id !== null;
                                        $driverBeda = $t->driver_gps
                                            && $namaOperator !== ''
                                            && GpsTripParser::normalisasiNama($t->driver_gps) !== $namaOperator;
                                    @endphp
                                    <tr class="{{ !$t->dihitung ? 'text-muted' : '' }}">
                                        <td><input type="checkbox" name="trip_ids[]" value="{{ $t->id }}" class="js-pilih" aria-label="Pilih trip" @disabled($terkunci)></td>
                                        <td class="text-nowrap">
                                            <strong>{{ $t->waktu_mulai->format('d/m H:i') }} – {{ $t->waktu_selesai?->format('H:i') ?? '?' }}</strong>
                                            <span class="jp-sub">{{ GpsTrip::formatDurasi($t->durasi_trip_detik) }} jam</span>
                                        </td>
                                        <td>
                                            <div class="jp-route is-compact">
                                                <span class="jp-stop"><strong>{{ $t->geofence_mulai ?? '(luar geofence)' }}</strong></span>
                                                <i class="fas fa-long-arrow-alt-right jp-sep"></i>
                                                <span class="jp-stop"><strong>{{ $t->geofence_selesai ?? '(luar geofence)' }}</strong></span>
                                            </div>
                                            @if ($t->perhentian) <span class="jp-sub">via {{ implode(', ', $t->perhentian) }}</span> @endif
                                            @if ($driverBeda)
                                                <span class="jp-sub text-danger"><i class="fas fa-exclamation-triangle"></i>
                                                    Cartrack mencatat driver "{{ $t->driver_gps }}", jadwal: {{ $jadwal->operator?->nama }}</span>
                                            @elseif (!$t->driver_gps)
                                                <span class="jp-sub">Cartrack tidak mencatat driver</span>
                                            @endif
                                            @if ($t->perlu_dicek) <span class="jp-badge is-warn mt-1">Perlu dicek</span> @endif
                                        </td>
                                        <td class="text-right text-nowrap">{{ number_format($t->jarak_km, 1, ',', '.') }} km</td>
                                        <td class="text-nowrap">
                                            @if ($terkunci) <span class="jp-badge is-mute">Sudah di lembur</span>
                                            @elseif ($t->divalidasi) <span class="jp-badge is-ok">Valid</span>
                                            @else <span class="jp-badge is-warn">Belum valid</span> @endif
                                            @if (!$t->dihitung) <span class="jp-sub">Tidak dihitung</span> @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5">
                                        <div class="jp-empty">
                                            <i class="fas fa-route"></i>
                                            <strong>{{ $jadwal->trip_ditarik_pada ? 'Tidak ada trip terdeteksi' : 'Trip belum ditarik' }}</strong>
                                            {{ $jadwal->trip_ditarik_pada ? 'Cartrack tidak mencatat perjalanan pada jendela waktu jadwal ini.' : 'Trip ditarik otomatis setelah jadwal selesai.' }}
                                        </div>
                                    </td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($jadwal->trips->isNotEmpty())
                        <div class="card-footer">
                            <div class="jp-actions">
                                <button name="aksi" value="validasi" class="btn btn-sm btn-success js-aksi"><i class="fas fa-check"></i>Validasi terpilih</button>
                                <button name="aksi" value="tidak_dihitung" class="btn btn-sm btn-ghost js-aksi">Tandai tidak dihitung</button>
                                <button name="aksi" value="hitung" class="btn btn-sm btn-ghost js-aksi">Tandai dihitung</button>
                                <button name="aksi" value="batal_validasi" class="btn btn-sm btn-outline-danger js-aksi">Batalkan validasi</button>
                            </div>
                            <span class="form-hint mt-2">Trip yang sudah divalidasi otomatis masuk perhitungan lembur driver jadwal ini (halaman Lembur oleh admin).</span>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // public/js/responsive.js menonaktifkan tombol submit saat submit, jadi aksi dikirim lewat input hidden
    const form = document.getElementById('form-validasi');
    if (!form) return;
    form.querySelectorAll('.js-aksi').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (!form.querySelector('.js-pilih:checked')) { alert('Pilih minimal satu trip.'); return; }
            const h = document.createElement('input');
            h.type = 'hidden'; h.name = 'aksi'; h.value = btn.value;
            form.appendChild(h);
            form.submit();
        });
    });
    const semua = document.getElementById('pilih-semua');
    if (semua) semua.addEventListener('change', function () {
        form.querySelectorAll('.js-pilih:not(:disabled)').forEach(function (c) { c.checked = semua.checked; });
    });
})();
</script>
@endpush
