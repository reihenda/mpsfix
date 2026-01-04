@extends('layouts.app')

@section('title', 'Edit Billing')

@section('page-title', 'Edit Billing #' . $billing->billing_number)

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Form Edit Billing</h3>
    </div>
    <form action="{{ route('billings.update', $billing) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="billing_number">Nomor Billing</label>
                        <input type="text" class="form-control @error('billing_number') is-invalid @enderror" 
                            id="billing_number" name="billing_number" value="{{ old('billing_number', $billing->billing_number) }}">
                        @error('billing_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            Format: [no]/MPS/BIL-[CUSTOMER]/[bulan]/[tahun]
                        </small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="billing_date">Tanggal Billing</label>
                        <input type="date" class="form-control @error('billing_date') is-invalid @enderror" 
                            id="billing_date" name="billing_date" value="{{ old('billing_date', is_string($billing->billing_date) ? $billing->billing_date : $billing->billing_date->format('Y-m-d')) }}">
                        @error('billing_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info">
                <p class="mb-2">
                    <i class="fas fa-info-circle mr-1"></i> Periode pencatatan dan kalkulasi biaya tidak dapat diubah. Jika ingin mengubah periode, silakan buat billing baru.
                </p>
                <p class="mb-0">
                    <i class="fas fa-sync mr-1"></i> <strong>Status pembayaran dikelola melalui Invoice terkait.</strong> 
                    @if($billing->invoice)
                        Status saat ini: 
                        <span class="badge badge-{{ $billing->invoice->status == 'paid' ? 'success' : ($billing->invoice->status == 'partial' ? 'warning' : 'danger') }} ml-1">
                            {{ ucfirst($billing->invoice->status) }}
                        </span>
                    @endif
                </p>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i>Simpan Perubahan
            </button>
            <a href="{{ route('billings.show', $billing) }}" class="btn btn-secondary">
                <i class="fas fa-times mr-1"></i>Batal
            </a>
        </div>
    </form>
</div>
@endsection
