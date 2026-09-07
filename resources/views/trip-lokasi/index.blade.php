@extends('layouts.app')

@section('title', 'Approval Lokasi Trip')

@section('page-title', 'Approval Lokasi Trip')

@section('content')
<div class="row">
    <div class="col-12">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Lokasi Menunggu Approval ({{ $pending->count() }})</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nama Lokasi</th>
                            <th>Diajukan Oleh</th>
                            <th>Tanggal Diajukan</th>
                            <th style="width: 320px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pending as $lokasi)
                            <tr>
                                <td>{{ $lokasi->nama_lokasi }}</td>
                                <td>{{ optional($lokasi->diajukanOleh)->name ?? '-' }}</td>
                                <td>{{ $lokasi->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <form action="{{ route('trip-lokasi.approve', $lokasi->id) }}" method="POST" class="form-inline">
                                        @csrf
                                        <input type="text" name="nama_lokasi" value="{{ $lokasi->nama_lokasi }}" class="form-control form-control-sm mr-1" style="width: 160px;" required>
                                        <button type="submit" class="btn btn-success btn-sm mr-1">
                                            <i class="fas fa-check mr-1"></i> Setujui
                                        </button>
                                    </form>
                                    <form action="{{ route('trip-lokasi.reject', $lokasi->id) }}" method="POST" class="form-inline d-inline" onsubmit="return confirm('Tolak lokasi ini?');">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-times mr-1"></i> Tolak
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Tidak ada lokasi yang menunggu approval.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Riwayat</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Nama Lokasi</th>
                            <th>Status</th>
                            <th>Diproses Oleh</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($riwayat as $lokasi)
                            <tr>
                                <td>{{ $lokasi->nama_lokasi }}</td>
                                <td>
                                    @if ($lokasi->status === 'approved')
                                        <span class="badge badge-success">Approved</span>
                                    @else
                                        <span class="badge badge-danger">Ditolak</span>
                                    @endif
                                </td>
                                <td>{{ optional($lokasi->approvedBy)->name ?? '-' }}</td>
                                <td>{{ $lokasi->updated_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Belum ada riwayat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
