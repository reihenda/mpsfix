@extends('layouts.app')

@section('title', 'Review Perbaikan Trip')

@section('page-title', 'Review Pengajuan Perbaikan Trip')

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
                <h3 class="card-title">Menunggu Review ({{ $pending->count() }})</h3>
            </div>
            <div class="card-body">
                @forelse ($pending as $item)
                    <div class="border rounded p-3 mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                @if ($item->foto_bukti_path)
                                    <img src="{{ asset('storage/' . $item->foto_bukti_path) }}" class="img-fluid rounded" alt="Bukti">
                                @else
                                    <div class="text-muted">Tidak ada foto</div>
                                @endif
                            </div>
                            <div class="col-md-5">
                                <p class="mb-1"><strong>Operator:</strong> {{ optional($item->operator)->nama }}</p>
                                <p class="mb-1"><strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</p>
                                <p class="mb-1"><strong>Nopol:</strong> {{ $item->nopol ?? '-' }}</p>
                                <p class="mb-1"><strong>Asal → Tujuan:</strong> {{ $item->asal_nama }} → {{ $item->tujuan_nama }}</p>
                                <p class="mb-1"><strong>Tekanan:</strong> {{ $item->tekanan ?? '-' }}</p>
                                <p class="mb-1"><strong>Waktu Klaim:</strong> {{ optional($item->waktu_klaim)->format('d/m/Y H:i') }}</p>
                                <p class="mb-0"><strong>Keterangan:</strong> {{ $item->keterangan }}</p>
                            </div>
                            <div class="col-md-4">
                                <form action="{{ route('operator-trip-perbaikan.approve', $item->id) }}" method="POST" class="mb-2">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm btn-block">
                                        <i class="fas fa-check mr-1"></i> Setujui
                                    </button>
                                </form>
                                <form action="{{ route('operator-trip-perbaikan.reject', $item->id) }}" method="POST">
                                    @csrf
                                    <div class="input-group input-group-sm">
                                        <input type="text" name="catatan_admin" class="form-control" placeholder="Alasan tolak" required>
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-danger">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-center mb-0">Tidak ada pengajuan yang menunggu review.</p>
                @endforelse
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
                            <th>Operator</th>
                            <th>Tanggal</th>
                            <th>Asal → Tujuan</th>
                            <th>Status</th>
                            <th>Catatan Admin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($riwayat as $item)
                            <tr>
                                <td>{{ optional($item->operator)->nama }}</td>
                                <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</td>
                                <td>{{ $item->asal_nama }} → {{ $item->tujuan_nama }}</td>
                                <td>
                                    @if ($item->status === 'approved')
                                        <span class="badge badge-success">Approved</span>
                                    @else
                                        <span class="badge badge-danger">Ditolak</span>
                                    @endif
                                </td>
                                <td>{{ $item->catatan_admin ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Belum ada riwayat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
