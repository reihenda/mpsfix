@extends('layouts.app')

@section('title', 'Jadwal Perjalanan')

@section('page-title', 'Jadwal Perjalanan')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/jadwal-perjalanan.css') }}?v={{ filemtime(public_path('css/jadwal-perjalanan.css')) }}">
@endpush

@section('content')
@php
    use App\Models\JadwalPerjalanan;
    $kelasStatus = [
        'terjadwal' => 'is-info',
        'berjalan' => 'is-accent',
        'selesai' => 'is-ok',
        'batal' => 'is-mute',
    ];
@endphp

<div class="jp">
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Tutup"><span>&times;</span></button></div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Tutup"><span>&times;</span></button></div>
    @endif

    <div class="jp-head">
        <p>Atur kendaraan, driver, dan rute. Driver dikirim ke Cartrack saat jadwal dimulai. Jadwal selesai sendiri saat semua tujuan pada rute tercapai, lalu trip ditarik untuk divalidasi.</p>
        <div class="jp-head-actions">
            <a href="{{ route('pemetaan-cartrack.index') }}" class="btn btn-ghost"><i class="fas fa-link"></i>Pemetaan Cartrack</a>
            <a href="{{ route('jadwal-perjalanan.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i>Buat jadwal</a>
        </div>
    </div>

    @php
        $menitDetak = $detakPenjadwal ? (int) $detakPenjadwal->diffInMinutes(now()) : null;
        $penjadwalHidup = $menitDetak !== null && $menitDetak <= 3;
    @endphp
    <div class="mb-3">
        @if ($penjadwalHidup)
            <span class="jp-badge is-ok">Penjadwal aktif</span>
            <span class="jp-sub d-inline ml-1">terakhir jalan {{ $menitDetak < 1 ? 'baru saja' : $menitDetak.' menit lalu' }}</span>
        @else
            <span class="jp-badge is-bad">Penjadwal tidak jalan</span>
            <span class="jp-sub d-inline ml-1">
                @if ($detakPenjadwal)
                    Terakhir jalan {{ $detakPenjadwal->copy()->setTimezone(config('services.cartrack.zona_lokal', 'Asia/Jakarta'))->format('d/m H:i') }}.
                @else
                    Belum pernah jalan.
                @endif
                Jadwal tidak akan dikirim ke Cartrack otomatis. Periksa cron job di hPanel.
            </span>
        @endif
    </div>

    <div class="jp-stats">
        <div class="jp-stat is-accent">
            <span class="jp-stat-icon"><i class="fas fa-truck-moving"></i></span>
            <div>
                <div class="jp-stat-value">{{ $ringkasan['berjalan'] }}</div>
                <div class="jp-stat-label">Sedang berjalan</div>
            </div>
        </div>
        <a href="{{ route('jadwal-perjalanan.index', ['perlu_validasi' => 1]) }}" class="jp-stat {{ $ringkasan['perlu_validasi'] ? 'is-warn' : '' }}">
            <span class="jp-stat-icon"><i class="fas fa-clipboard-check"></i></span>
            <div>
                <div class="jp-stat-value">{{ $ringkasan['perlu_validasi'] }}</div>
                <div class="jp-stat-label">Trip menunggu validasi</div>
            </div>
        </a>
        <div class="jp-stat {{ $ringkasan['link_gagal'] ? 'is-bad' : '' }}">
            <span class="jp-stat-icon"><i class="fas fa-exclamation-triangle"></i></span>
            <div>
                <div class="jp-stat-value">{{ $ringkasan['link_gagal'] }}</div>
                <div class="jp-stat-label">Gagal dikirim ke Cartrack</div>
            </div>
        </div>
    </div>

    <div class="card">
        <form method="GET" class="jp-toolbar">
            @if ($perluValidasi)
                <input type="hidden" name="perlu_validasi" value="1">
                <span class="jp-filter-chip">Hanya jadwal dengan trip belum divalidasi
                    <a href="{{ route('jadwal-perjalanan.index') }}" aria-label="Hapus filter">&times;</a></span>
            @else
                <div class="jp-field">
                    <label for="f-dari">Dari</label>
                    <input type="date" id="f-dari" name="dari" value="{{ $dari->toDateString() }}" class="form-control">
                </div>
                <div class="jp-field">
                    <label for="f-sampai">Sampai</label>
                    <input type="date" id="f-sampai" name="sampai" value="{{ $sampai->toDateString() }}" class="form-control">
                </div>
            @endif
            <div class="jp-field">
                <label for="f-status">Status</label>
                <select id="f-status" name="status" class="form-control">
                    <option value="">Semua status</option>
                    @foreach (JadwalPerjalanan::STATUS_LABEL as $k => $l)
                        <option value="{{ $k }}" @selected($status === $k)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="jp-field jp-grow">
                <label for="f-q">Cari</label>
                <input type="text" id="f-q" name="q" value="{{ $cari }}" placeholder="Nopol atau nama driver" class="form-control">
            </div>
            <button class="btn btn-primary"><i class="fas fa-filter"></i>Terapkan</button>
        </form>

        <div class="table-responsive">
            <table class="table jp-table" style="min-width: 1020px;">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Kendaraan</th>
                        <th>Driver</th>
                        <th>Rute</th>
                        <th>Status</th>
                        <th>Cartrack</th>
                        <th>Trip</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($jadwals as $j)
                        <tr>
                            <td class="text-nowrap jp-when">
                                <strong>{{ $j->waktu_mulai->format('d/m/Y') }}</strong>
                                @if ($j->waktu_selesai)
                                    <span class="jp-sub">{{ $j->waktu_mulai->format('H:i') }} – {{ $j->waktu_selesai->format('H:i') }}@if (!$j->waktu_mulai->isSameDay($j->waktu_selesai)) ({{ $j->waktu_selesai->format('d/m') }})@endif</span>
                                @else
                                    <span class="jp-sub">{{ $j->waktu_mulai->format('H:i') }} – {{ $j->status === 'batal' ? 'batal' : 'rute selesai' }}</span>
                                @endif
                            </td>
                            <td><a href="{{ route('jadwal-perjalanan.show', $j) }}" class="jp-nopol text-dark">{{ $j->nopol }}</a></td>
                            <td class="text-nowrap">{{ $j->operator?->nama ?? '–' }}</td>
                            <td>
                                <div class="jp-route">
                                    @foreach ($j->rute as $r)
                                        <span class="jp-stop">{{ $r->tujuan }}</span>
                                        @if (!$loop->last)<i class="fas fa-chevron-right jp-sep"></i>@endif
                                    @endforeach
                                </div>
                                @if ($j->aktif && $j->rute->count())
                                    <span class="jp-sub">{{ $j->rute_tercapai_count }} dari {{ $j->rute->count() }} tujuan tercapai</span>
                                @endif
                            </td>
                            <td><span class="jp-badge {{ $kelasStatus[$j->status] ?? '' }}">{{ $j->status_label }}</span></td>
                            <td>
                                @switch($j->link_status)
                                    @case('terkirim') <span class="jp-badge is-ok">Driver ter-link</span> @break
                                    @case('gagal') <span class="jp-badge is-bad" title="{{ $j->link_error }}">Gagal</span> @break
                                    @case('dilewati') <span class="jp-badge is-mute">Tidak dikirim</span> @break
                                    @default <span class="jp-badge is-mute">Menunggu</span>
                                @endswitch
                            </td>
                            <td class="text-nowrap">
                                @if ($j->trips_count)
                                    <span>{{ $j->trips_count }} trip</span>
                                    @if ($j->trips_belum_validasi_count)
                                        <span class="jp-badge is-warn ml-1">{{ $j->trips_belum_validasi_count }} belum valid</span>
                                    @else
                                        <span class="jp-badge is-ok ml-1">Valid</span>
                                    @endif
                                @elseif ($j->status === 'selesai')
                                    <span class="jp-sub">{{ $j->trip_ditarik_pada ? 'Tidak ada trip' : 'Menunggu tarikan' }}</span>
                                @else
                                    <span class="text-muted">–</span>
                                @endif
                            </td>
                            <td class="text-right text-nowrap">
                                @if ($j->status === 'batal')
                                    <form method="POST" action="{{ route('jadwal-perjalanan.destroy', $j) }}" class="d-inline"
                                          onsubmit="return confirm('Hapus jadwal {{ $j->nopol }} ini permanen?{{ $j->trips_belum_validasi_count ? ' '.$j->trips_belum_validasi_count.' trip yang belum divalidasi ikut terhapus.' : '' }} Tindakan ini tidak bisa dibatalkan.')">@csrf @method('DELETE')
                                        <button class="btn btn-sm btn-link-quiet text-danger" title="Hapus jadwal" aria-label="Hapus jadwal"><i class="fas fa-trash mr-0"></i></button></form>
                                @endif
                                <a href="{{ route('jadwal-perjalanan.show', $j) }}" class="btn btn-sm btn-link-quiet">Buka<i class="fas fa-arrow-right ml-1 mr-0"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8">
                            <div class="jp-empty">
                                <i class="far fa-calendar-alt"></i>
                                <strong>Belum ada jadwal pada filter ini</strong>
                                Ubah rentang tanggal atau buat jadwal baru.
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($jadwals->hasPages())
            <div class="card-footer">{{ $jadwals->links() }}</div>
        @endif
    </div>
</div>
@endsection
