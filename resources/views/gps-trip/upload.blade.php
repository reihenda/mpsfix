@extends('layouts.app')

@section('title', 'Upload Laporan Trip GPS')

@section('page-title', 'Upload Laporan Trip GPS')

@section('content')
<div class="row">
    <div class="col-lg-7">
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

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Upload Excel "Standard Summary Trip Report"</h3>
            </div>
            <form action="{{ route('gps-trip.upload.proses') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="file">File laporan GPS (.xls / .xlsx)</label>
                        <input type="file" name="file" id="file" class="form-control-file" accept=".xls,.xlsx,.csv" required>
                        <small class="form-text text-muted">Bisa upload per bulan. Upload ulang file yang sama aman: trip tidak digandakan, dan trip yang sudah Anda sesuaikan tidak ditimpa.</small>
                    </div>

                    <div class="callout callout-info mb-0">
                        <h6>Cara trip dibentuk</h6>
                        <ul class="pl-3 mb-0 small">
                            <li>Trip <strong>dimulai</strong> saat <em>Start Geofence</em> adalah customer/PT.</li>
                            <li>Trip <strong>berakhir</strong> saat <em>End Geofence</em> adalah customer/PT (tidak harus PT yang sama).</li>
                            <li>Jika singgah di SPBG atau lokasi lain, trip <strong>tetap berlanjut</strong>.</li>
                            <li>Geofence baru yang belum dikenal akan ditanyakan dulu: customer/PT, SPBG, atau lainnya.</li>
                        </ul>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload mr-1"></i> Upload &amp; Proses</button>
                    <a href="{{ route('gps-trip.index') }}" class="btn btn-secondary">Kembali</a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Riwayat Upload</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm">
                    <thead>
                        <tr><th>File</th><th>Periode</th><th class="text-right">Trip</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($riwayat as $r)
                            <tr>
                                <td>
                                    <div class="text-truncate" style="max-width: 190px;" title="{{ $r->nama_file }}">{{ $r->nama_file }}</div>
                                    <small class="text-muted">{{ $r->created_at->format('d/m/Y H:i') }} · {{ optional($r->user)->name ?? '-' }}</small>
                                </td>
                                <td class="small">
                                    {{ optional($r->periode_mulai)->format('d/m/y') }} – {{ optional($r->periode_selesai)->format('d/m/y') }}
                                </td>
                                <td class="text-right">{{ $r->jumlah_trip }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">Belum ada upload.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
