@extends('layouts.app')

@section('title', 'Tambah Operator GTM')

@section('page-title', 'Tambah Operator GTM')

@section('css')
<style>
    /* Pastikan container tidak overflow */
    .content-wrapper {
        overflow-x: hidden;
    }
    
    /* Pastikan card tidak melebar */
    .card {
        max-width: 100%;
        overflow: hidden;
    }
    
    /* Pastikan form elements tidak melebar */
    .form-control, .input-group {
        max-width: 100%;
    }
    
    /* Responsive untuk form */
    @media (max-width: 768px) {
        .col-md-8 {
            padding: 0 15px;
        }
    }
</style>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Form Tambah Operator GTM</h3>
            </div>
            <!-- /.card-header -->
            
            <!-- form start -->
            <form action="{{ route('operator-gtm.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="nama">Nama <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('nama') is-invalid @enderror" id="nama" name="nama" value="{{ old('nama') }}" required>
                        @error('nama')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="lokasi_kerja">Lokasi Kerja <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('lokasi_kerja') is-invalid @enderror" id="lokasi_kerja" name="lokasi_kerja" value="{{ old('lokasi_kerja') }}" required>
                        @error('lokasi_kerja')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="gaji_pokok">Gaji Pokok <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="number" class="form-control @error('gaji_pokok') is-invalid @enderror" id="gaji_pokok" name="gaji_pokok" value="{{ old('gaji_pokok', 3500000) }}" required min="0" step="1000">
                            @error('gaji_pokok')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="jam_kerja">Jam Kerja <span class="text-danger">*</span></label>
                        <select class="form-control @error('jam_kerja') is-invalid @enderror" id="jam_kerja" name="jam_kerja" required>
                            <option value="8" {{ old('jam_kerja', 8) == 8 ? 'selected' : '' }}>8 Jam</option>
                            <option value="10" {{ old('jam_kerja') == 10 ? 'selected' : '' }}>10 Jam</option>
                        </select>
                        @error('jam_kerja')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Jam lembur akan dihitung setelah melewati jam kerja yang ditentukan.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="tanggal_bergabung">Tanggal Bergabung <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('tanggal_bergabung') is-invalid @enderror" id="tanggal_bergabung" name="tanggal_bergabung" value="{{ old('tanggal_bergabung', date('Y-m-d')) }}" required>
                        @error('tanggal_bergabung')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Tanggal bergabung akan digunakan untuk perhitungan gaji pada periode pertama.</small>
                    </div>
                </div>
                <!-- /.card-body -->

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Simpan
                    </button>
                    <a href="{{ route('operator-gtm.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                </div>
            </form>
        </div>
        <!-- /.card -->
    </div>
</div>
@endsection