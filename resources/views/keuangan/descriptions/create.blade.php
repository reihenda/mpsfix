@extends('layouts.app')

@section('title', 'Tambah Deskripsi Transaksi')

@section('page-title', 'Tambah Deskripsi Transaksi')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Form Tambah Deskripsi Transaksi</h3>
    </div>
    <form action="{{ route('keuangan.descriptions.store') }}" method="POST">
        @csrf
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="form-group">
                <label for="description">Deskripsi <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('description') is-invalid @enderror" 
                    id="description" name="description" value="{{ old('description') }}" 
                    placeholder="Contoh: Pembayaran Listrik, Penerimaan Piutang, dll">
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Masukkan deskripsi transaksi yang akan digunakan.</small>
            </div>

            <div class="form-group">
                <label for="category">Kategori <span class="text-danger">*</span></label>
                <select class="form-control @error('category') is-invalid @enderror" id="category" name="category">
                    <option value="">-- Pilih Kategori --</option>
                    <option value="kas" {{ old('category') == 'kas' ? 'selected' : '' }}>Kas</option>
                    <option value="bank" {{ old('category') == 'bank' ? 'selected' : '' }}>Bank</option>
                    <option value="both" {{ old('category') == 'both' ? 'selected' : '' }}>Keduanya</option>
                </select>
                @error('category')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Pilih kategori di mana deskripsi ini akan digunakan.</small>
            </div>

            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                    <label class="custom-control-label" for="is_active">Aktif</label>
                </div>
                <small class="form-text text-muted">Deskripsi yang tidak aktif tidak akan muncul di dropdown saat menambah transaksi.</small>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i> Simpan
            </button>
            <a href="{{ route('keuangan.descriptions.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Kembali
            </a>
        </div>
    </form>
</div>
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('css/customer-detail.css') }}">
@endsection
