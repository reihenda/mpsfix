                        <small class="form-text text-muted">
                            @if($customer->no_kontrak)
                                Menggunakan nomor kontrak dari data customer
                            @else
                                Default: 001/PJBG-MPS/I/{{ date('Y') }}
                            @endif
                        </small>@push('styles')
<style>
.card.border-primary {
    border-color: #007bff !important;
}

.card.border-primary .card-header.bg-primary {
    background-color: #007bff !important;
    border-color: #007bff !important;
}

.period-section {
    transition: all 0.3s ease;
}

.btn-group .btn {
    border-radius: 0;
}

.btn-group .btn:first-child {
    border-top-left-radius: 0.25rem;
    border-bottom-left-radius: 0.25rem;
}

.btn-group .btn:last-child {
    border-top-right-radius: 0.25rem;
    border-bottom-right-radius: 0.25rem;
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}
</style>
@endpush

@extends('layouts.app')

@section('title', 'Buat Invoice Baru')

@section('page-title', 'Buat Invoice Baru untuk ' . $customer->name)

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Form Invoice Baru</h3>
    </div>
    <form action="{{ route('invoices.store', $customer) }}" method="POST">
        @csrf
        <div class="card-body">
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <!-- PERIODE PENCATATAN - DILETAKKAN PALING ATAS -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-calendar-alt mr-2"></i>Periode Pencatatan</h6>
                        </div>
                        <div class="card-body">
                            <!-- Tab Buttons untuk Tipe Periode -->
                            <div class="mb-3">
                                <div class="btn-group" role="group" aria-label="Period Type">
                                    <button type="button" class="btn btn-outline-primary period-btn" id="monthlyPeriodBtn" data-period="monthly">
                                        <i class="fas fa-calendar mr-1"></i>Periode Bulanan
                                    </button>
                                    <button type="button" class="btn btn-outline-primary period-btn" id="customPeriodBtn" data-period="custom">
                                        <i class="fas fa-calendar-week mr-1"></i>Periode Khusus
                                    </button>
                                </div>
                                <input type="hidden" name="period_type" id="period_type" value="{{ old('period_type', 'monthly') }}">
                            </div>
                            
                            <!-- Periode Bulanan -->
                            <div id="monthly-period" class="period-section">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="month"><strong>Bulan</strong></label>
                                        <select class="form-control @error('month') is-invalid @enderror" name="month">
                                            @for ($i = 1; $i <= 12; $i++)
                                                <option value="{{ $i }}" {{ old('month', $month) == $i ? 'selected' : '' }}>
                                                    {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                                </option>
                                            @endfor
                                        </select>
                                        @error('month')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="year"><strong>Tahun</strong></label>
                                        <select class="form-control @error('year') is-invalid @enderror" name="year">
                                            @for ($i = date('Y') - 3; $i <= date('Y') + 1; $i++)
                                                <option value="{{ $i }}" {{ old('year', $year) == $i ? 'selected' : '' }}>
                                                    {{ $i }}
                                                </option>
                                            @endfor
                                        </select>
                                        @error('year')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Periode Khusus -->
                            <div id="custom-period" class="period-section" style="display: none;">
                                <div class="row">
                                    <div class="col-md-5">
                                        <label for="custom_start_date"><strong>Dari Tanggal</strong></label>
                                        <input type="date" class="form-control @error('custom_start_date') is-invalid @enderror" 
                                            id="custom_start_date" name="custom_start_date" value="{{ old('custom_start_date') }}">
                                        @error('custom_start_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-5">
                                        <label for="custom_end_date"><strong>Sampai Tanggal</strong></label>
                                        <input type="date" class="form-control @error('custom_end_date') is-invalid @enderror" 
                                            id="custom_end_date" name="custom_end_date" value="{{ old('custom_end_date') }}">
                                        @error('custom_end_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-2">
                                        <label>&nbsp;</label>
                                        <div class="form-control-plaintext">
                                            <small id="date-range-info" class="text-muted"></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DETAIL INVOICE -->
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="invoice_number">Nomor Invoice</label>
                        <input type="text" class="form-control @error('invoice_number') is-invalid @enderror" 
                            id="invoice_number" name="invoice_number" value="{{ old('invoice_number', $invoiceNumber) }}">
                        @error('invoice_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            Format: [no]/MPS/INV-{{ strtoupper($customer->name) }}/[bulan]/[tahun]
                        </small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="invoice_date">Tanggal Invoice</label>
                        <input type="date" class="form-control @error('invoice_date') is-invalid @enderror" 
                            id="invoice_date" name="invoice_date" value="{{ old('invoice_date', date('Y-m-d')) }}">
                        @error('invoice_date')
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
                            id="due_date" name="due_date" value="{{ old('due_date', date('Y-m-d', strtotime('+10 days'))) }}">
                        @error('due_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="description">Keterangan (Opsional)</label>
                        <input type="text" class="form-control @error('description') is-invalid @enderror" 
                            id="description" name="description" value="{{ old('description', 'Pemakaian CNG') }}">
                        @error('description')
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
                            id="no_kontrak" name="no_kontrak" value="{{ old('no_kontrak', $customer->no_kontrak ?: ('001/PJBG-MPS/I/' . date('Y'))) }}">
                        @error('no_kontrak')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="id_pelanggan">ID Pelanggan</label>
                        <input type="text" class="form-control @error('id_pelanggan') is-invalid @enderror" 
                            id="id_pelanggan" name="id_pelanggan" value="{{ old('id_pelanggan', sprintf('03C%04d', $customer->id)) }}">
                        @error('id_pelanggan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info">
                <p class="mb-2">
                    <i class="fas fa-info-circle mr-1"></i> Total biaya akan dihitung otomatis berdasarkan data pencatatan untuk periode yang dipilih.
                </p>
                <p class="mb-0">
                    <i class="fas fa-sync mr-1"></i> <strong>Billing akan dibuat secara otomatis</strong> dengan nomor yang sama saat Anda menyimpan invoice ini.
                </p>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i>Simpan
            </button>
            <a href="{{ route('invoices.select-customer') }}" class="btn btn-secondary">
                <i class="fas fa-times mr-1"></i>Batal
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize period type berdasarkan value yang ada
    updatePeriodDisplay();
    
    // Handle period button clicks
    $('.period-btn').click(function() {
        const selectedPeriod = $(this).data('period');
        
        // Update button states
        $('.period-btn').removeClass('btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('btn-primary');
        
        // Update hidden field
        $('#period_type').val(selectedPeriod);
        
        // Update display
        updatePeriodDisplay();
        updateInvoiceNumber();
    });
    
    // Handle date range calculation
    $('#custom_start_date, #custom_end_date').change(function() {
        calculateDateRange();
        if ($('#period_type').val() === 'custom' && $('#custom_start_date').val()) {
            updateInvoiceNumber();
        }
    });
    
    // Handle monthly period change
    $('#monthly-period select').change(function() {
        if ($('#period_type').val() === 'monthly') {
            updateInvoiceNumber();
        }
    });
    
    function updatePeriodDisplay() {
        const periodType = $('#period_type').val();
        
        // Update button states based on current period type
        $('.period-btn').removeClass('btn-primary').addClass('btn-outline-primary');
        if (periodType === 'custom') {
            $('#customPeriodBtn').removeClass('btn-outline-primary').addClass('btn-primary');
            $('#monthly-period').hide();
            $('#custom-period').show();
            
            // Disable monthly fields
            $('#monthly-period select').prop('disabled', true);
            // Enable custom fields
            $('#custom-period input').prop('disabled', false);
        } else {
            $('#monthlyPeriodBtn').removeClass('btn-outline-primary').addClass('btn-primary');
            $('#monthly-period').show();
            $('#custom-period').hide();
            
            // Enable monthly fields
            $('#monthly-period select').prop('disabled', false);
            // Disable custom fields
            $('#custom-period input').prop('disabled', true);
        }
        
        calculateDateRange();
    }
    
    function calculateDateRange() {
        const startDate = $('#custom_start_date').val();
        const endDate = $('#custom_end_date').val();
        const infoElement = $('#date-range-info');
        
        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            
            if (start > end) {
                infoElement.html('<span class="text-danger">Tanggal awal tidak boleh lebih dari tanggal akhir</span>');
                $('#custom_end_date').addClass('is-invalid');
            } else if (diffDays > 60) {
                infoElement.html('<span class="text-warning">' + diffDays + ' hari (maksimal 60 hari)</span>');
                $('#custom_end_date').addClass('is-invalid');
            } else {
                infoElement.html('<span class="text-success">' + diffDays + ' hari</span>');
                $('#custom_end_date').removeClass('is-invalid');
            }
        } else {
            infoElement.html('');
            $('#custom_end_date').removeClass('is-invalid');
        }
    }
    
    function updateInvoiceNumber() {
        const periodType = $('#period_type').val();
        let data = {
            period_type: periodType,
            _token: $('meta[name="csrf-token"]').attr('content')
        };
        
        if (periodType === 'custom') {
            data.custom_start_date = $('#custom_start_date').val();
        }
        
        // Only make request if we have necessary data
        if (periodType === 'monthly' || (periodType === 'custom' && data.custom_start_date)) {
            $.ajax({
                url: '{{ route("invoices.generate-number", $customer) }}',
                method: 'POST',
                data: data,
                success: function(response) {
                    $('#invoice_number').val(response.invoice_number);
                },
                error: function(xhr) {
                    console.log('Error generating invoice number:', xhr);
                }
            });
        }
    }
    
    // Form validation sebelum submit
    $('form').submit(function(e) {
        const periodType = $('#period_type').val();
        
        if (periodType === 'custom') {
            const startDate = $('#custom_start_date').val();
            const endDate = $('#custom_end_date').val();
            
            if (!startDate || !endDate) {
                e.preventDefault();
                alert('Silakan pilih tanggal awal dan tanggal akhir untuk periode khusus.');
                return false;
            }
            
            const start = new Date(startDate);
            const end = new Date(endDate);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            
            if (start > end) {
                e.preventDefault();
                alert('Tanggal awal tidak boleh lebih dari tanggal akhir.');
                return false;
            }
            
            if (diffDays > 60) {
                e.preventDefault();
                alert('Periode maksimal adalah 60 hari.');
                return false;
            }
        }
    });
});
</script>
@endpush