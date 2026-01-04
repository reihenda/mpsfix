@extends('layouts.app')

@section('title', 'Edit Invoice')

@section('page-title', 'Edit Invoice #' . $invoice->invoice_number)

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Form Edit Invoice</h3>
    </div>
    <form action="{{ route('invoices.update', $invoice) }}" method="POST">
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
                        <label for="invoice_number">Nomor Invoice</label>
                        <input type="text" class="form-control @error('invoice_number') is-invalid @enderror" 
                            id="invoice_number" name="invoice_number" value="{{ old('invoice_number', $invoice->invoice_number) }}">
                        @error('invoice_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            Format: [no]/MPS/INV-[CUSTOMER]/[bulan]/[tahun]
                        </small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="invoice_date">Tanggal Invoice</label>
                        <input type="date" class="form-control @error('invoice_date') is-invalid @enderror" 
                            id="invoice_date" name="invoice_date" value="{{ old('invoice_date', $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') : '') }}">
                        @error('invoice_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="no_kontrak">No Kontrak</label>
                        <input type="text" class="form-control @error('no_kontrak') is-invalid @enderror" 
                            id="no_kontrak" name="no_kontrak" value="{{ old('no_kontrak', $invoice->no_kontrak) }}">
                        @error('no_kontrak')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="id_pelanggan">ID Pelanggan</label>
                        <input type="text" class="form-control @error('id_pelanggan') is-invalid @enderror" 
                            id="id_pelanggan" name="id_pelanggan" value="{{ old('id_pelanggan', $invoice->id_pelanggan) }}">
                        @error('id_pelanggan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="due_date">Tanggal Jatuh Tempo</label>
                        <input type="date" class="form-control @error('due_date') is-invalid @enderror" 
                            id="due_date" name="due_date" value="{{ old('due_date', $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('Y-m-d') : '') }}">
                        @error('due_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="status">Status Pembayaran</label>
                        <select class="form-control @error('status') is-invalid @enderror" id="status" name="status">
                            <option value="unpaid" {{ old('status', $invoice->status) == 'unpaid' ? 'selected' : '' }}>Belum Lunas</option>
                            <option value="partial" {{ old('status', $invoice->status) == 'partial' ? 'selected' : '' }}>Sebagian</option>
                            <option value="paid" {{ old('status', $invoice->status) == 'paid' ? 'selected' : '' }}>Lunas</option>
                            <option value="cancelled" {{ old('status', $invoice->status) == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Keterangan (Opsional)</label>
                <textarea class="form-control @error('description') is-invalid @enderror" 
                    id="description" name="description" rows="3">{{ old('description', $invoice->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="alert alert-info">
                <p class="mb-0">
                    <i class="fas fa-info-circle mr-1"></i> Periode pencatatan dan total biaya tidak dapat diubah. Jika ingin mengubah periode, silakan buat invoice baru.
                </p>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i>Simpan Perubahan
            </button>
            <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-secondary">
                <i class="fas fa-times mr-1"></i>Batal
            </a>
        </div>
    </form>
</div>
@endsection