@extends('layouts.app')

@section('title', 'Edit Transaksi Bank')

@section('page-title', 'Edit Transaksi Bank')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Form Edit Transaksi Bank</h3>
    </div>
    <form action="{{ route('keuangan.bank.update', $transaction) }}" method="POST">
        @csrf
        @method('PUT')
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

            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                <strong>Perhatian:</strong> Mengubah transaksi akan menyebabkan perubahan saldo pada semua transaksi setelahnya.
            </div>

            <div class="form-group">
                <label for="voucher_number">Nomor Voucher <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('voucher_number') is-invalid @enderror" 
                    id="voucher_number" name="voucher_number" value="{{ old('voucher_number', $transaction->voucher_number) }}" readonly>
                @error('voucher_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-group">
                <label for="transaction_date">Tanggal Transaksi <span class="text-danger">*</span></label>
                <input type="date" class="form-control @error('transaction_date') is-invalid @enderror" 
                    id="transaction_date" name="transaction_date" value="{{ old('transaction_date', $transaction->transaction_date->format('Y-m-d')) }}">
                @error('transaction_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="account_id">Akun <span class="text-danger">*</span></label>
                <div class="row">
                    <div class="col-md-9 mb-2">
                        <select class="form-control select2 @error('account_id') is-invalid @enderror" id="account_id" name="account_id">
                            <option value="">-- Pilih Akun Bank --</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" {{ old('account_id', $transaction->account_id) == $account->id ? 'selected' : '' }}>
                                    {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('account_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="{{ route('keuangan.accounts.create') }}" class="btn btn-success btn-block" target="_blank">
                            <i class="fas fa-plus"></i> Tambah Akun
                        </a>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Deskripsi</label>
                <div class="row">
                    <div class="col-md-9 mb-2">
                        <select class="form-control select2 @error('description') is-invalid @enderror" 
                            id="description_select" name="description_select">
                            <option value="">-- Pilih Deskripsi --</option>
                            <!-- Deskripsi akan diisi via AJAX -->
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="{{ route('keuangan.descriptions.create') }}" class="btn btn-success btn-block" target="_blank">
                            <i class="fas fa-plus"></i> Tambah Deskripsi
                        </a>
                    </div>
                </div>
                
                <textarea class="form-control mt-2 @error('description') is-invalid @enderror" 
                    id="description" name="description" rows="2" placeholder="Masukkan deskripsi transaksi atau pilih dari dropdown di atas">{{ old('description', $transaction->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Anda dapat memilih deskripsi dari dropdown atau menuliskan deskripsi secara manual.</small>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="credit">Credit (Masuk) <span class="text-success">+</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" class="form-control money @error('credit') is-invalid @enderror" 
                                id="credit" name="credit" value="{{ old('credit', $transaction->credit_formatted) }}" 
                                data-thousands="." data-decimal="," placeholder="0">
                        </div>
                        @error('credit')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="debit">Debit (Keluar) <span class="text-danger">-</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" class="form-control money @error('debit') is-invalid @enderror" 
                                id="debit" name="debit" value="{{ old('debit', $transaction->debit_formatted) }}" 
                                data-thousands="." data-decimal="," placeholder="0">
                        </div>
                        @error('debit')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-1"></i>
                Minimal salah satu dari Credit atau Debit harus diisi dengan nilai lebih dari 0.
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i> Simpan Perubahan
            </button>
            <a href="{{ route('keuangan.bank.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Kembali
            </a>
        </div>
    </form>
</div>
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('css/customer-detail.css') }}">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container .select2-selection--single {
        height: 38px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
</style>
@endsection

@section('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2({
            width: '100%'
        });
        
        // Format input uang dengan ribuan separator
        $('.money').mask('000.000.000.000.000', {reverse: true});
        
        // Pastikan hanya salah satu input yang diisi
        $('#credit').on('input', function() {
            if ($(this).val() !== '0' && $(this).val() !== '') {
                $('#debit').val('0');
            }
        });
        
        $('#debit').on('input', function() {
            if ($(this).val() !== '0' && $(this).val() !== '') {
                $('#credit').val('0');
            }
        });
        
        // Load deskripsi transaksi berdasarkan kategori (bank)
        loadDescriptions('bank');
        
        // Ketika memilih deskripsi dari dropdown
        $('#description_select').on('change', function() {
            const selectedDesc = $(this).val();
            if (selectedDesc) {
                $('#description').val(selectedDesc);
            }
        });
        
        // Fungsi untuk memuat deskripsi transaksi
        function loadDescriptions(category) {
            $.ajax({
                url: "{{ route('keuangan.descriptions.by-category') }}",
                type: "GET",
                data: {
                    category: category
                },
                success: function(data) {
                    let options = '<option value="">-- Pilih Deskripsi --</option>';
                    
                    $.each(data, function(index, description) {
                        options += `<option value="${description.description}">${description.description}</option>`;
                    });
                    
                    $('#description_select').html(options);
                    
                    // Jika ada nilai description di form, pilih opsi yang sesuai
                    const currentDescription = $('#description').val();
                    if (currentDescription) {
                        if ($('#description_select option[value="'+currentDescription+'"]').length > 0) {
                            $('#description_select').val(currentDescription).trigger('change');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading descriptions:', error);
                }
            });
        }
        
        // Reload deskripsi ketika halaman di-refresh
        $(window).on('load', function() {
            setTimeout(function() {
                loadDescriptions('bank');
            }, 500);
        });
    });
</script>
@endsection
