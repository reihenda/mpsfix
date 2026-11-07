@extends('layouts.app')

@section('title', 'Jadwal Perjalanan #'.$jadwal->id)

@section('page-title', 'Jadwal Perjalanan #'.$jadwal->id)

@section('content')
@php
    use App\Models\GpsTrip;
    use App\Services\GpsTrip\GpsTripParser;
    $badgeStatus = ['terjadwal' => 'badge-info', 'berjalan' => 'badge-primary', 'selesai' => 'badge-success', 'batal' => 'badge-secondary'];
    $namaOperator = GpsTripParser::normalisasiNama($namaDriverCartrack);
    $belumValid = $jadwal->trips->where('divalidasi', false);
@endphp

<div class="row">
    <div class="col-12">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Jadwal <span class="badge {{ $badgeStatus[$jadwal->status] ?? 'badge-light' }} ml-1">{{ $jadwal->status_label }}</span></h3>
                <div class="card-tools">
                    <a href="{{ route('jadwal-perjalanan.index') }}" class="btn btn-sm btn-default">Kembali</a>
                </div>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Kendaraan</dt><dd class="col-sm-8">{{ $jadwal->nopol }}</dd>
                    <dt class="col-sm-4">Driver</dt>
                    <dd class="col-sm-8">{{ $jadwal->operator?->nama }}
                        @if ($namaDriverCartrack) <small class="text-muted d-block">di Cartrack: {{ $namaDriverCartrack }}</small>
                        @else <small class="text-danger d-block">belum dipetakan ke driver Cartrack</small> @endif
                    </dd>
                    <dt class="col-sm-4">Mulai</dt><dd class="col-sm-8">{{ $jadwal->waktu_mulai->format('d/m/Y H:i') }}</dd>
                    <dt class="col-sm-4">Selesai</dt><dd class="col-sm-8">{{ $jadwal->waktu_selesai->format('d/m/Y H:i') }}</dd>
                    @if ($jadwal->catatan)
                        <dt class="col-sm-4">Catatan</dt><dd class="col-sm-8">{{ $jadwal->catatan }}</dd>
                    @endif
                    <dt class="col-sm-4">Dibuat oleh</dt><dd class="col-sm-8">{{ $jadwal->pembuat?->name ?? '-' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Rute</h3></div>
            <ul class="list-group list-group-flush">
                @foreach ($jadwal->rute as $r)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><strong>{{ $r->urutan }}.</strong> {{ $r->tujuan }}
                            @if ($r->catatan) <small class="text-muted d-block">{{ $r->catatan }}</small> @endif
                        </span>
                        @if ($jadwal->trips->isNotEmpty())
                            @if ($ruteTercapai[$r->id] ?? false)
                                <span class="badge badge-success" title="Tujuan ini tampil di geofence trip">Tercapai</span>
                            @else
                                <span class="badge badge-warning" title="Tidak ada trip yang melewati geofence ini">Tidak terdeteksi</span>
                            @endif
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Cartrack</h3></div>
            <div class="card-body">
                <p class="mb-1">Link driver:
                    @switch($jadwal->link_status)
                        @case('terkirim') <span class="badge badge-success">Terkirim</span> {{ $jadwal->link_dikirim_pada?->format('d/m H:i') }} @break
                        @case('gagal') <span class="badge badge-danger">Gagal</span> @break
                        @case('dilewati') <span class="badge badge-light">Tidak dikirim</span> @break
                        @default <span class="badge badge-light">Menunggu waktu mulai</span>
                    @endswitch
                </p>
                @if ($jadwal->link_error) <p class="small text-danger mb-1">{{ $jadwal->link_error }}</p> @endif
                @if ($jadwal->unlink_pada) <p class="small text-muted mb-1">Driver dilepas {{ $jadwal->unlink_pada->format('d/m H:i') }}</p> @endif
                <p class="mb-1">Trip:
                    @if ($jadwal->trip_ditarik_pada) ditarik {{ $jadwal->trip_ditarik_pada->format('d/m H:i') }}
                    @else belum ditarik @endif
                </p>
                @if ($jadwal->trip_tarik_error) <p class="small text-danger mb-1">{{ $jadwal->trip_tarik_error }}</p> @endif
            </div>
            <div class="card-footer d-flex flex-wrap" style="gap:.4rem">
                @if ($jadwal->aktif)
                    <a href="{{ route('jadwal-perjalanan.edit', $jadwal) }}" class="btn btn-sm btn-outline-primary">Ubah</a>
                @endif
                @if ($jadwal->aktif && $jadwal->link_status === 'gagal')
                    <form method="POST" action="{{ route('jadwal-perjalanan.ulangi-link', $jadwal) }}">@csrf
                        <button class="btn btn-sm btn-warning">Kirim ulang driver</button></form>
                @endif
                @if ($jadwal->status === 'berjalan')
                    <form method="POST" action="{{ route('jadwal-perjalanan.selesaikan', $jadwal) }}"
                          onsubmit="return confirm('Selesaikan jadwal sekarang? Driver dilepas dari kendaraan di Cartrack dan trip ditarik.')">@csrf
                        <button class="btn btn-sm btn-success">Selesaikan sekarang</button></form>
                @endif
                @if ($jadwal->status !== 'batal' && $jadwal->waktu_mulai->lte(\App\Services\Cartrack\JadwalPerjalananService::sekarangLokal()))
                    <form method="POST" action="{{ route('jadwal-perjalanan.tarik-trip', $jadwal) }}">@csrf
                        <button class="btn btn-sm btn-outline-secondary">{{ $jadwal->trip_ditarik_pada ? 'Tarik ulang trip' : 'Tarik trip' }}</button></form>
                @endif
                @if ($jadwal->aktif)
                    <form method="POST" action="{{ route('jadwal-perjalanan.batalkan', $jadwal) }}"
                          onsubmit="return confirm('Batalkan jadwal ini?{{ $jadwal->status === 'berjalan' ? ' Driver akan dilepas dari kendaraan di Cartrack.' : '' }}')">@csrf
                        <button class="btn btn-sm btn-outline-danger">Batalkan</button></form>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <form method="POST" action="{{ route('jadwal-perjalanan.validasi') }}" id="form-validasi">
            @csrf
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Trip dari Cartrack ({{ $jadwal->trips->count() }})
                        @if ($belumValid->count()) <span class="badge badge-warning">{{ $belumValid->count() }} belum divalidasi</span> @endif
                    </h3>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-sm mb-0" style="min-width: 560px;">
                        <thead>
                            <tr>
                                <th style="width:30px"><input type="checkbox" id="pilih-semua"></th>
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
                                    <td><input type="checkbox" name="trip_ids[]" value="{{ $t->id }}" class="js-pilih" @disabled($terkunci)></td>
                                    <td class="text-nowrap">
                                        {{ $t->waktu_mulai->format('d/m H:i') }}
                                        – {{ $t->waktu_selesai?->format('H:i') ?? '?' }}
                                        <small class="text-muted d-block">{{ GpsTrip::formatDurasi($t->durasi_trip_detik) }} jam</small>
                                    </td>
                                    <td class="small">
                                        <strong>{{ $t->geofence_mulai ?? '(luar geofence)' }}</strong> → <strong>{{ $t->geofence_selesai ?? '(luar geofence)' }}</strong>
                                        @if ($t->perhentian) <span class="text-muted d-block">via {{ implode(', ', $t->perhentian) }}</span> @endif
                                        @if ($driverBeda)
                                            <span class="text-danger d-block"><i class="fas fa-exclamation-triangle"></i>
                                                Cartrack mencatat driver "{{ $t->driver_gps }}", jadwal: {{ $jadwal->operator?->nama }}</span>
                                        @elseif (!$t->driver_gps)
                                            <span class="text-muted d-block">Cartrack tidak mencatat driver</span>
                                        @endif
                                        @if ($t->perlu_dicek) <span class="badge badge-warning">perlu dicek</span> @endif
                                    </td>
                                    <td class="text-right text-nowrap">{{ number_format($t->jarak_km, 1, ',', '.') }} km</td>
                                    <td class="text-nowrap">
                                        @if ($terkunci) <span class="badge badge-secondary">Sudah di lembur</span>
                                        @elseif ($t->divalidasi) <span class="badge badge-success">Valid</span>
                                        @else <span class="badge badge-warning">Belum valid</span> @endif
                                        @if (!$t->dihitung) <span class="badge badge-light">tidak dihitung</span> @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">
                                    {{ $jadwal->trip_ditarik_pada ? 'Tidak ada trip terdeteksi di Cartrack pada jendela waktu jadwal ini.' : 'Trip belum ditarik dari Cartrack.' }}
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($jadwal->trips->isNotEmpty())
                    <div class="card-footer d-flex flex-wrap" style="gap:.4rem">
                        <button name="aksi" value="validasi" class="btn btn-sm btn-success js-aksi">Validasi terpilih</button>
                        <button name="aksi" value="tidak_dihitung" class="btn btn-sm btn-outline-secondary js-aksi">Tandai tidak dihitung</button>
                        <button name="aksi" value="hitung" class="btn btn-sm btn-outline-secondary js-aksi">Tandai dihitung</button>
                        <button name="aksi" value="batal_validasi" class="btn btn-sm btn-outline-danger js-aksi">Batalkan validasi</button>
                        <small class="text-muted w-100">Trip yang sudah divalidasi otomatis masuk perhitungan lembur driver jadwal ini (halaman Lembur oleh admin).</small>
                    </div>
                @endif
            </div>
        </form>
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
