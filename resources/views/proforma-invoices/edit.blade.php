@extends('layouts.app')

@section('title', 'Edit Proforma Invoice')

@section('page-title', 'Edit Proforma Invoice - ' . $customer->name)

@section('content')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="card-title">Edit Proforma Invoice</h3>
            <a href="{{ route('proforma-invoices.show', $proformaInvoice) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i>Kembali
            </a>
        </div>
    </div>
    <form action="{{ route('proforma-invoices.update', $proformaInvoice) }}" method="POST">
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

            <div class="row">
                <!-- Customer Info -->
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-user mr-2"></i>Informasi Customer
                            </h5>
                            <p class="card-text">
                                <strong>Nama:</strong> {{ $customer->name }}<br>
                                <strong>Email:</strong> {{ $customer->email }}<br>
                                <strong>Role:</strong> {{ ucfirst($customer->role) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Proforma Info -->
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-info-circle mr-2"></i>Info Proforma
                            </h5>
                            <div class="form-group">
                                <label for="proforma_number">Nomor Proforma</label>
                                <input type="text" class="form-control @error('proforma_number') is-invalid @enderror" 
                                       id="proforma_number" name="proforma_number" 
                                       value="{{ old('proforma_number', $proformaInvoice->proforma_number) }}" required>
                                <small class="text-muted">Anda bisa mengubah nomor proforma sesuai kebutuhan</small>
                                @error('proforma_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tanggal Section -->
            <div class="row mt-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="proforma_date">Tanggal Proforma <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('proforma_date') is-invalid @enderror" 
                               id="proforma_date" name="proforma_date" 
                               value="{{ old('proforma_date', $proformaInvoice->proforma_date->format('Y-m-d')) }}" required>
                        @error('proforma_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="due_date">Tanggal Jatuh Tempo <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('due_date') is-invalid @enderror" 
                               id="due_date" name="due_date" 
                               value="{{ old('due_date', $proformaInvoice->due_date->format('Y-m-d')) }}" required>
                        @error('due_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="validity_date">Berlaku Sampai</label>
                        <input type="date" class="form-control @error('validity_date') is-invalid @enderror" 
                               id="validity_date" name="validity_date" 
                               value="{{ old('validity_date', $proformaInvoice->validity_date ? $proformaInvoice->validity_date->format('Y-m-d') : '') }}">
                        @error('validity_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Opsional - tanggal berlaku proforma invoice</small>
                    </div>
                </div>
            </div>

            @if(!$isMmbtu)
            <!-- Periode Data -->
            <div class="row">
                <div class="col-md-12">
                    <h5 class="mt-3 mb-3">
                        <i class="fas fa-calendar-alt mr-2"></i>Periode Data Pencatatan
                    </h5>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="period_start_date">Tanggal Mulai <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('period_start_date') is-invalid @enderror"
                               id="period_start_date" name="period_start_date"
                               value="{{ old('period_start_date', $proformaInvoice->period_start_date ? $proformaInvoice->period_start_date->format('Y-m-d') : '') }}" required>
                        @error('period_start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="period_end_date">Tanggal Selesai <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('period_end_date') is-invalid @enderror"
                               id="period_end_date" name="period_end_date"
                               value="{{ old('period_end_date', $proformaInvoice->period_end_date ? $proformaInvoice->period_end_date->format('Y-m-d') : '') }}" required>
                        @error('period_end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Maksimal 60 hari dari tanggal mulai</small>
                    </div>
                </div>
            </div>
            @endif

            <!-- Input Manual Volume dan Harga -->
            <div class="row">
                <div class="col-md-12">
                    <h5 class="mt-3 mb-3">
                        <i class="fas fa-calculator mr-2"></i>Input Manual Perhitungan
                    </h5>
                </div>
                @if($isMmbtu)
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="volume_mmbtu">Volume (MMBTU) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="0.0001" class="form-control @error('volume_mmbtu') is-invalid @enderror"
                                   id="volume_mmbtu" name="volume_mmbtu"
                                   value="{{ old('volume_mmbtu', $proformaInvoice->volume_mmbtu ?? '0') }}" required>
                            <div class="input-group-append">
                                <span class="input-group-text">MMBTU</span>
                            </div>
                        </div>
                        @error('volume_mmbtu')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="price_per_mmbtu_usd">Harga Satuan (USD/MMBTU) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">$</span>
                            </div>
                            <input type="number" step="0.0001" class="form-control @error('price_per_mmbtu_usd') is-invalid @enderror"
                                   id="price_per_mmbtu_usd" name="price_per_mmbtu_usd"
                                   value="{{ old('price_per_mmbtu_usd', $proformaInvoice->price_per_mmbtu_usd ?? '0') }}" required>
                            <div class="input-group-append">
                                <span class="input-group-text">/MMBTU</span>
                            </div>
                        </div>
                        @error('price_per_mmbtu_usd')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="kurs_usd">Kurs USD (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="number" step="0.01" class="form-control @error('kurs_usd') is-invalid @enderror"
                                   id="kurs_usd" name="kurs_usd"
                                   value="{{ old('kurs_usd', $proformaInvoice->kurs_usd ?? '0') }}" required>
                            <div class="input-group-append">
                                <span class="input-group-text">/USD</span>
                            </div>
                        </div>
                        @error('kurs_usd')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                @else
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="volume_per_day">
                            Volume Pemakaian (Sm3) / Hari
                            <span class="text-danger">*</span>
                            <i class="fas fa-info-circle text-info ml-1"
                               title="Volume pemakaian gas per hari dalam satuan Sm3."
                               data-toggle="tooltip"></i>
                        </label>
                        <input type="number" step="0.001" class="form-control @error('volume_per_day') is-invalid @enderror"
                               id="volume_per_day" name="volume_per_day"
                               value="{{ old('volume_per_day', $proformaInvoice->volume_per_day ?? '0') }}" required>
                        @error('volume_per_day')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Contoh: 1.500 (untuk 1.5 Sm3 per hari)</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="price_per_sm3">Harga Satuan (Rp) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control @error('price_per_sm3') is-invalid @enderror"
                               id="price_per_sm3" name="price_per_sm3"
                               value="{{ old('price_per_sm3', $proformaInvoice->price_per_sm3 ?? $customer->harga_per_meter_kubik ?? '0') }}" required>
                        @error('price_per_sm3')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Harga per Sm3, bisa diedit</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="total_days">Total Hari (otomatis)</label>
                        <input type="number" class="form-control" id="total_days" name="total_days" readonly
                               value="{{ old('total_days', $proformaInvoice->total_days ?? '0') }}">
                        <small class="text-muted">Dihitung otomatis dari periode tanggal</small>
                    </div>
                </div>
                @endif
            </div>

            <!-- Preview Perhitungan -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title"><i class="fas fa-calculator mr-2"></i>Preview Perhitungan</h6>
                            @if($isMmbtu)
                            <div class="row">
                                <div class="col-md-3">
                                    <p class="mb-1"><strong>Volume MMBTU:</strong> <span id="preview_volume_mmbtu">{{ number_format($proformaInvoice->volume_mmbtu ?? 0, 4) }}</span> MMBTU</p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-1"><strong>Harga/MMBTU:</strong> $ <span id="preview_price_mmbtu">{{ number_format($proformaInvoice->price_per_mmbtu_usd ?? 0, 4) }}</span></p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-1"><strong>Total USD:</strong> $ <span id="preview_total_usd">{{ number_format($proformaInvoice->total_usd ?? 0, 4) }}</span></p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-1"><strong>Total IDR:</strong> Rp <span id="preview_total_idr">{{ number_format($proformaInvoice->total_amount ?? 0, 0, ',', '.') }}</span></p>
                                </div>
                            </div>
                            @else
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Total Volume:</strong> <span id="preview_total_volume">{{ number_format($proformaInvoice->total_volume ?? 0, 2) }}</span> Sm3</p>
                                    <p class="mb-0"><small class="text-muted">Volume per hari × Total hari</small></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Total Amount:</strong> Rp <span id="preview_total_amount">{{ number_format($proformaInvoice->total_amount ?? 0, 0, ',', '.') }}</span></p>
                                    <p class="mb-0"><small class="text-muted">Total volume × Harga per Sm3</small></p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="no_kontrak">No. Kontrak <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('no_kontrak') is-invalid @enderror" 
                               id="no_kontrak" name="no_kontrak" 
                               value="{{ old('no_kontrak', $proformaInvoice->no_kontrak) }}" required>
                        <small class="text-muted">Diambil dari data customer, bisa diedit</small>
                        @error('no_kontrak')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="id_pelanggan">ID Pelanggan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('id_pelanggan') is-invalid @enderror" 
                               id="id_pelanggan" name="id_pelanggan" 
                               value="{{ old('id_pelanggan', $proformaInvoice->id_pelanggan) }}" required>
                        @error('id_pelanggan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Status dan Deskripsi -->
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                            <option value="draft" {{ old('status', $proformaInvoice->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="sent" {{ old('status', $proformaInvoice->status) == 'sent' ? 'selected' : '' }}>Sent</option>
                            <option value="expired" {{ old('status', $proformaInvoice->status) == 'expired' ? 'selected' : '' }}>Expired</option>
                            <option value="converted" {{ old('status', $proformaInvoice->status) == 'converted' ? 'selected' : '' }}>Converted</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="description">Deskripsi</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3" 
                                  placeholder="Deskripsi tambahan untuk proforma invoice...">{{ old('description', $proformaInvoice->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <div class="d-flex justify-content-between">
                <a href="{{ route('proforma-invoices.show', $proformaInvoice) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i>Kembali
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i>Update Proforma Invoice
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    @if($isMmbtu)
    $('#volume_mmbtu, #price_per_mmbtu_usd, #kurs_usd').on('input', function() {
        const volume = parseFloat($('#volume_mmbtu').val()) || 0;
        const price = parseFloat($('#price_per_mmbtu_usd').val()) || 0;
        const kurs = parseFloat($('#kurs_usd').val()) || 0;
        const totalUsd = volume * price;
        const totalIdr = totalUsd * kurs;
        $('#preview_volume_mmbtu').text(volume.toFixed(4));
        $('#preview_price_mmbtu').text(price.toFixed(4));
        $('#preview_total_usd').text(new Intl.NumberFormat('en-US', {minimumFractionDigits: 4}).format(totalUsd));
        $('#preview_total_idr').text(new Intl.NumberFormat('id-ID').format(Math.round(totalIdr)));
    });
    @else
    // Event listener untuk perhitungan real-time
    $('#volume_per_day, #price_per_sm3').on('input', function() {
        calculatePreview();
    });

    $('#period_start_date, #period_end_date').change(function() {
        calculateDaysAndPreview();
    });

    function calculateDaysAndPreview() {
        const startDate = $('#period_start_date').val();
        const endDate = $('#period_end_date').val();

        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            $('#total_days').val(diffDays);
            calculatePreview();

            if (diffDays > 60) {
                alert('Periode maksimal adalah 60 hari!');
                $('#period_end_date').val('');
            }
        }
    }

    function calculatePreview() {
        const volumePerDay = parseFloat($('#volume_per_day').val()) || 0;
        const pricePerSm3 = parseFloat($('#price_per_sm3').val()) || 0;
        const totalDays = parseFloat($('#total_days').val()) || 0;

        const totalVolume = volumePerDay * totalDays;
        const totalAmount = totalVolume * pricePerSm3;

        $('#preview_total_volume').text(totalVolume.toFixed(2));
        $('#preview_total_amount').text(new Intl.NumberFormat('id-ID').format(Math.round(totalAmount)));
    }

    $('#period_start_date').change(function() {
        const selectedDate = $(this).val();
        $('#period_end_date').attr('min', selectedDate);
        calculateDaysAndPreview();
    });

    calculateDaysAndPreview();
    $('#period_end_date').attr('min', $('#period_start_date').val());
    @endif

    // Set minimum dates
    $('#proforma_date').change(function() {
        const selectedDate = $(this).val();
        $('#due_date').attr('min', selectedDate);
        $('#validity_date').attr('min', selectedDate);
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Initialize minimum dates
    $('#due_date').attr('min', $('#proforma_date').val());
    $('#validity_date').attr('min', $('#proforma_date').val());
});
</script>
@endsection