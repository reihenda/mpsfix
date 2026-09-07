@extends('layouts.operator')

@section('title', 'Absen Berangkat')
@section('page-title', 'Absen Berangkat')

@section('css')
<style>
    .select2-container { width: 100% !important; }
    #foto-preview { max-width: 100%; border-radius: 6px; margin-top: 8px; display: none; }
</style>
<link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
@endsection

@section('content')
@if ($tripSelesai)
    <div class="card border-success">
        <div class="card-body">
            <h5 class="mb-1 text-success"><i class="fas fa-check-circle mr-1"></i> Trip Selesai</h5>
            <p class="mb-2 text-muted">{{ $tripSelesai->asal_nama }} → {{ $tripSelesai->tujuan_nama }}</p>
            <a href="{{ $waLinkSelesai }}" target="_blank" class="btn btn-outline-success btn-sm btn-block">
                <i class="fab fa-whatsapp mr-1"></i> Share ke WhatsApp
            </a>
        </div>
    </div>
@endif

<div class="card">
    <div class="card-body">
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div id="gps-status" class="alert alert-warning py-2 mb-3">
            <i class="fas fa-map-marker-alt mr-1"></i> Mengambil lokasi GPS...
        </div>

        <form action="{{ route('operator.trip.store-berangkat') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">

            <div class="form-group">
                <label>Asal <span class="text-danger">*</span></label>
                <select name="asal_value" id="asal_value" class="form-control select2" required>
                    <option value=""></option>
                    @foreach ($lokasiOptions->groupBy('group') as $group => $items)
                        <optgroup label="{{ $group }}">
                            @foreach ($items as $item)
                                <option value="{{ $item['value'] }}" {{ old('asal_value') == $item['value'] ? 'selected' : '' }}>{{ $item['label'] }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                    <option value="new" {{ old('asal_value') == 'new' ? 'selected' : '' }}>+ Tambah lokasi baru</option>
                </select>
                @error('asal_value')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                <div id="asal-baru-container" class="mt-2" style="display:none;">
                    <input type="text" name="asal_baru" id="asal_baru" class="form-control @error('asal_baru') is-invalid @enderror" placeholder="Nama lokasi asal baru" value="{{ old('asal_baru') }}">
                    @error('asal_baru')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-group">
                <label>Tujuan <span class="text-danger">*</span></label>
                <select name="tujuan_value" id="tujuan_value" class="form-control select2" required>
                    <option value=""></option>
                    @foreach ($lokasiOptions->groupBy('group') as $group => $items)
                        <optgroup label="{{ $group }}">
                            @foreach ($items as $item)
                                <option value="{{ $item['value'] }}" {{ old('tujuan_value') == $item['value'] ? 'selected' : '' }}>{{ $item['label'] }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                    <option value="new" {{ old('tujuan_value') == 'new' ? 'selected' : '' }}>+ Tambah lokasi baru</option>
                </select>
                @error('tujuan_value')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                <div id="tujuan-baru-container" class="mt-2" style="display:none;">
                    <input type="text" name="tujuan_baru" id="tujuan_baru" class="form-control @error('tujuan_baru') is-invalid @enderror" placeholder="Nama lokasi tujuan baru" value="{{ old('tujuan_baru') }}">
                    @error('tujuan_baru')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-group">
                <label>GTM Nopol <span class="text-danger">*</span></label>
                <select name="nopol_value" id="nopol_value" class="form-control select2" required>
                    <option value=""></option>
                    @foreach ($nopolOptions as $nopol)
                        <option value="{{ $nopol }}" {{ old('nopol_value') == $nopol ? 'selected' : '' }}>{{ $nopol }}</option>
                    @endforeach
                    <option value="manual" {{ old('nopol_value') == 'manual' ? 'selected' : '' }}>+ Nopol lain (ketik manual)</option>
                </select>
                @error('nopol_value')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                <div id="nopol-manual-container" class="mt-2" style="display:none;">
                    <input type="text" name="nopol_manual" id="nopol_manual" class="form-control @error('nopol_manual') is-invalid @enderror" placeholder="Contoh: B 9228 SDC" value="{{ old('nopol_manual') }}">
                    @error('nopol_manual')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-group">
                <label for="tekanan">Tekanan (bar) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" name="tekanan" id="tekanan" class="form-control @error('tekanan') is-invalid @enderror" value="{{ old('tekanan') }}" required>
                @error('tekanan')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="foto">Foto Berangkat <span class="text-danger">*</span></label>
                <input type="file" accept="image/*" capture="environment" name="foto" id="foto" class="form-control-file @error('foto') is-invalid @enderror" required>
                @error('foto')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <img id="foto-preview" alt="Preview foto">
            </div>

            <button type="submit" class="btn btn-success btn-block" id="submit-btn">
                <i class="fas fa-check mr-1"></i> Konfirmasi Berangkat
            </button>
        </form>
    </div>
</div>

<a href="{{ route('operator.dashboard') }}" class="btn btn-secondary btn-block">
    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Dashboard
</a>
@endsection

@section('js')
<script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
<script>
    $(function () {
        $('.select2').select2({ theme: 'default', width: '100%' });

        function toggleBaru(selectId, containerId, inputId, triggerValue) {
            const isTrigger = $(selectId).val() === triggerValue;
            $(containerId).toggle(isTrigger);
            if (!isTrigger) { $(inputId).val(''); }
        }

        $('#asal_value').on('change', function () { toggleBaru('#asal_value', '#asal-baru-container', '#asal_baru', 'new'); });
        $('#tujuan_value').on('change', function () { toggleBaru('#tujuan_value', '#tujuan-baru-container', '#tujuan_baru', 'new'); });
        $('#nopol_value').on('change', function () { toggleBaru('#nopol_value', '#nopol-manual-container', '#nopol_manual', 'manual'); });

        toggleBaru('#asal_value', '#asal-baru-container', '#asal_baru', 'new');
        toggleBaru('#tujuan_value', '#tujuan-baru-container', '#tujuan_baru', 'new');
        toggleBaru('#nopol_value', '#nopol-manual-container', '#nopol_manual', 'manual');

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
    });
</script>
@endsection
