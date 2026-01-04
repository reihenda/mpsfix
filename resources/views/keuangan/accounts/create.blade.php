@extends('layouts.app')

@section('title', 'Tambah Akun Keuangan')

@section('page-title', 'Tambah Akun Keuangan')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Form Tambah Akun Keuangan</h3>
    </div>
    <form action="{{ route('keuangan.accounts.store') }}" method="POST">
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
                <label for="account_name">Nama Akun <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('account_name') is-invalid @enderror" 
                    id="account_name" name="account_name" value="{{ old('account_name') }}" placeholder="Masukkan nama akun">
                @error('account_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <input type="hidden" name="account_code" value="AUTO{{ rand(1000, 9999) }}">

            <div class="form-group">
                <label for="account_type">Jenis Akun <span class="text-danger">*</span></label>
                <select class="form-control @error('account_type') is-invalid @enderror" id="account_type" name="account_type">
                    <option value="">-- Pilih Jenis Akun --</option>
                    <option value="kas" {{ old('account_type') == 'kas' ? 'selected' : '' }}>Kas</option>
                    <option value="bank" {{ old('account_type') == 'bank' ? 'selected' : '' }}>Bank</option>
                </select>
                @error('account_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="description">Deskripsi</label>
                <textarea class="form-control @error('description') is-invalid @enderror" 
                    id="description" name="description" rows="3" placeholder="Masukkan deskripsi (opsional)">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                    <label class="custom-control-label" for="is_active">Aktif</label>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i> Simpan
            </button>
            <a href="{{ route('keuangan.accounts.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Kembali
            </a>
        </div>
    </form>
</div>
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('css/customer-detail.css') }}">
@endsection
