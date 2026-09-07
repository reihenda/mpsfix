@extends('layouts.operator')

@section('title', 'Ajukan Perbaikan Trip')
@section('page-title', 'Ajukan Perbaikan Trip')

@section('css')
<style>
    #foto-preview { max-width: 100%; border-radius: 6px; margin-top: 8px; display: none; }
</style>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <p class="text-muted small mb-0">
            Gunakan form ini kalau Anda tidak sempat Absen Trip karena sistem/sinyal offline saat kejadian.
            Upload foto bukti dari aplikasi kamera lain yang sudah ada timestamp-nya, lalu isi data trip secara manual.
        </p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('operator.perbaikan.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label for="tanggal">Tanggal <span class="text-danger">*</span></label>
                <input type="date" name="tanggal" id="tanggal" class="form-control @error('tanggal') is-invalid @enderror" value="{{ old('tanggal', now()->toDateString()) }}" required>
                @error('tanggal')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="asal_nama">Asal <span class="text-danger">*</span></label>
                <input type="text" name="asal_nama" id="asal_nama" class="form-control @error('asal_nama') is-invalid @enderror" value="{{ old('asal_nama') }}" required>
                @error('asal_nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="tujuan_nama">Tujuan <span class="text-danger">*</span></label>
                <input type="text" name="tujuan_nama" id="tujuan_nama" class="form-control @error('tujuan_nama') is-invalid @enderror" value="{{ old('tujuan_nama') }}" required>
                @error('tujuan_nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="nopol">GTM Nopol</label>
                <input type="text" name="nopol" id="nopol" class="form-control @error('nopol') is-invalid @enderror" value="{{ old('nopol') }}" placeholder="Contoh: B 9228 SDC">
                @error('nopol')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="tekanan">Tekanan (bar) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" name="tekanan" id="tekanan" class="form-control @error('tekanan') is-invalid @enderror" value="{{ old('tekanan') }}" required>
                @error('tekanan')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="waktu_klaim">Waktu Kejadian <span class="text-danger">*</span></label>
                <input type="datetime-local" name="waktu_klaim" id="waktu_klaim" class="form-control @error('waktu_klaim') is-invalid @enderror" value="{{ old('waktu_klaim') }}" required>
                <small class="form-text text-muted">Lihat timestamp di foto bukti, atau isi manual sesuai kejadian sebenarnya.</small>
                @error('waktu_klaim')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="keterangan">Keterangan <span class="text-danger">*</span></label>
                <textarea name="keterangan" id="keterangan" rows="3" class="form-control @error('keterangan') is-invalid @enderror" required>{{ old('keterangan') }}</textarea>
                <small class="form-text text-muted">Jelaskan singkat kenapa mengajukan perbaikan (mis. sistem offline saat itu).</small>
                @error('keterangan')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="foto_bukti">Foto Bukti <span class="text-danger">*</span></label>
                <input type="file" accept="image/*" name="foto_bukti" id="foto_bukti" class="form-control-file @error('foto_bukti') is-invalid @enderror" required>
                @error('foto_bukti')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <img id="foto-preview" alt="Preview foto">
            </div>

            <button type="submit" class="btn btn-warning btn-block">
                <i class="fas fa-paper-plane mr-1"></i> Kirim Pengajuan
            </button>
        </form>
    </div>
</div>

@if ($riwayat->isNotEmpty())
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Riwayat Pengajuan Saya</h5>
    </div>
    <div class="card-body p-0">
        <ul class="list-group list-group-flush">
            @foreach ($riwayat as $item)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <div>{{ $item->asal_nama }} → {{ $item->tujuan_nama }}</div>
                        <small class="text-muted">{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</small>
                    </div>
                    @if ($item->status === 'pending')
                        <span class="badge badge-warning">Menunggu</span>
                    @elseif ($item->status === 'approved')
                        <span class="badge badge-success">Disetujui</span>
                    @else
                        <span class="badge badge-danger">Ditolak</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endif

<a href="{{ route('operator.dashboard') }}" class="btn btn-secondary btn-block">
    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Dashboard
</a>
@endsection

@section('js')
<script>
    document.getElementById('foto_bukti').addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (ev) {
            const img = document.getElementById('foto-preview');
            img.src = ev.target.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(file);
    });
</script>
@endsection
