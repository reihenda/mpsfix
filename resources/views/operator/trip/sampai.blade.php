@extends('layouts.operator')

@section('title', 'Absen Sampai')
@section('page-title', 'Absen Sampai')

@section('css')
<style>
    #foto-preview { max-width: 100%; border-radius: 6px; margin-top: 8px; display: none; }
</style>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <h5 class="mb-1"><i class="fas fa-route mr-1 text-success"></i> Trip Aktif</h5>
        <p class="mb-1"><strong>Menuju:</strong> {{ $activeTrip->tujuan_nama }}</p>
        <p class="mb-2 text-muted">Berangkat dari {{ $activeTrip->asal_nama }} pukul {{ $activeTrip->waktu_berangkat->format('H:i') }}</p>
        <a href="{{ $waLink }}" target="_blank" class="btn btn-outline-success btn-sm btn-block">
            <i class="fab fa-whatsapp mr-1"></i> Share ke WhatsApp
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div id="gps-status" class="alert alert-warning py-2 mb-3">
            <i class="fas fa-map-marker-alt mr-1"></i> Mengambil lokasi GPS...
        </div>

        <form action="{{ route('operator.trip.store-sampai', $activeTrip->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">

            <div class="form-group">
                <label>Tujuan</label>
                <input type="text" class="form-control" value="{{ $activeTrip->tujuan_nama }}" readonly>
            </div>

            <div class="form-group">
                <label for="foto">Foto Sampai <span class="text-danger">*</span></label>
                <input type="file" accept="image/*" capture="environment" name="foto" id="foto" class="form-control-file @error('foto') is-invalid @enderror" required>
                @error('foto')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <img id="foto-preview" alt="Preview foto">
            </div>

            <button type="submit" class="btn btn-success btn-block">
                <i class="fas fa-check mr-1"></i> Konfirmasi Sampai
            </button>
        </form>
    </div>
</div>

<a href="{{ route('operator.dashboard') }}" class="btn btn-secondary btn-block">
    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Dashboard
</a>
@endsection

@section('js')
<script>
    document.getElementById('foto').addEventListener('change', function (e) {
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

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (pos) {
            document.getElementById('latitude').value = pos.coords.latitude;
            document.getElementById('longitude').value = pos.coords.longitude;
            document.getElementById('gps-status').className = 'alert alert-success py-2 mb-3';
            document.getElementById('gps-status').innerHTML = '<i class="fas fa-map-marker-alt mr-1"></i> Lokasi GPS berhasil diambil.';
        }, function () {
            document.getElementById('gps-status').className = 'alert alert-danger py-2 mb-3';
            document.getElementById('gps-status').innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i> Gagal mengambil GPS. Trip tetap bisa disimpan tanpa lokasi.';
        });
    } else {
        document.getElementById('gps-status').style.display = 'none';
    }
</script>
@endsection
