@extends('layouts.app')

@section('title', 'Jadwal Perjalanan')

@section('page-title', 'Jadwal Perjalanan')

@section('content')
@php
    use App\Models\JadwalPerjalanan;
    $badgeStatus = [
        'terjadwal' => 'badge-info',
        'berjalan' => 'badge-primary',
        'selesai' => 'badge-success',
        'batal' => 'badge-secondary',
    ];
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

        <div class="row">
            <div class="col-md-4 col-6">
                <div class="small-box bg-primary mb-3">
                    <div class="inner"><h3>{{ $ringkasan['berjalan'] }}</h3><p>Sedang berjalan</p></div>
                    <div class="icon"><i class="fas fa-truck-moving"></i></div>
                </div>
            </div>
            <div class="col-md-4 col-6">
                <a href="{{ route('jadwal-perjalanan.index', ['perlu_validasi' => 1]) }}" class="text-white">
                    <div class="small-box {{ $ringkasan['perlu_validasi'] ? 'bg-warning' : 'bg-secondary' }} mb-3">
                        <div class="inner"><h3>{{ $ringkasan['perlu_validasi'] }}</h3><p>Trip menunggu validasi</p></div>
                        <div class="icon"><i class="fas fa-clipboard-check"></i></div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 col-12">
                <div class="small-box {{ $ringkasan['link_gagal'] ? 'bg-danger' : 'bg-secondary' }} mb-3">
                    <div class="inner"><h3>{{ $ringkasan['link_gagal'] }}</h3><p>Gagal dikirim ke Cartrack</p></div>
                    <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <form method="GET" class="form-inline">
                    @if ($perluValidasi)
                        <input type="hidden" name="perlu_validasi" value="1">
                        <span class="badge badge-warning mr-3 p-2">Hanya jadwal yang punya trip belum divalidasi
                            <a href="{{ route('jadwal-perjalanan.index') }}" class="text-dark ml-1">&times;</a></span>
                    @else
                        <input type="date" name="dari" value="{{ $dari->toDateString() }}" class="form-control form-control-sm mr-1">
                        <span class="mr-1">s/d</span>
                        <input type="date" name="sampai" value="{{ $sampai->toDateString() }}" class="form-control form-control-sm mr-2">
                    @endif
                    <select name="status" class="form-control form-control-sm mr-2">
                        <option value="">Semua status</option>
                        @foreach (JadwalPerjalanan::STATUS_LABEL as $k => $l)
                            <option value="{{ $k }}" @selected($status === $k)>{{ $l }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="q" value="{{ $cari }}" placeholder="Nopol / driver" class="form-control form-control-sm mr-2">
                    <button class="btn btn-sm btn-secondary">Filter</button>
                </form>
                <div class="card-tools">
                    <a href="{{ route('pemetaan-cartrack.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-link"></i> Pemetaan Cartrack</a>
                    <a href="{{ route('jadwal-perjalanan.create') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Buat Jadwal</a>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-sm mb-0" style="min-width: 900px;">
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
                                <td class="text-nowrap">
                                    {{ $j->waktu_mulai->format('d/m/Y H:i') }}<br>
                                    <small class="text-muted">s/d {{ $j->waktu_selesai->format('d/m H:i') }}</small>
                                </td>
                                <td>{{ $j->nopol }}</td>
                                <td>{{ $j->operator?->nama }}</td>
                                <td class="small">{{ $j->rute->pluck('tujuan')->implode(' → ') }}</td>
                                <td><span class="badge {{ $badgeStatus[$j->status] ?? 'badge-light' }}">{{ $j->status_label }}</span></td>
                                <td>
                                    @switch($j->link_status)
                                        @case('terkirim') <span class="badge badge-success">Driver ter-link</span> @break
                                        @case('gagal') <span class="badge badge-danger" title="{{ $j->link_error }}">Gagal</span> @break
                                        @case('dilewati') <span class="badge badge-light">Tidak dikirim</span> @break
                                        @default <span class="badge badge-light">Menunggu</span>
                                    @endswitch
                                </td>
                                <td class="text-nowrap">
                                    @if ($j->trips_count)
                                        {{ $j->trips_count }} trip
                                        @if ($j->trips_belum_validasi_count)
                                            <span class="badge badge-warning">{{ $j->trips_belum_validasi_count }} belum valid</span>
                                        @else
                                            <span class="badge badge-success">valid</span>
                                        @endif
                                    @elseif ($j->status === 'selesai')
                                        <span class="text-muted small">{{ $j->trip_ditarik_pada ? 'Tidak ada trip' : 'Menunggu tarikan' }}</span>
                                    @else
                                        <span class="text-muted">–</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('jadwal-perjalanan.show', $j) }}" class="btn btn-xs btn-outline-primary">Buka</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">Belum ada jadwal pada filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($jadwals->hasPages())
                <div class="card-footer">{{ $jadwals->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
