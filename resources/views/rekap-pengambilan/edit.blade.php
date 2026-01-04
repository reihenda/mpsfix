@extends('layouts.app')

@section('title', 'Edit Rekap Pengambilan')

@section('page-title', 'Edit Data Rekap Pengambilan')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit mr-2"></i>
                        Form Edit Data Pengambilan
                    </h3>
                </div>
                
                <form action="{{ route('rekap-pengambilan.update', $rekapPengambilan->id) }}" method="POST" id="formEditData">
                    @csrf
                    @method('PUT')
                    @if(request('return_to_fob') || session('return_to_fob'))
                        <input type="hidden" name="return_to_fob" value="1">
                    @endif
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>Kesalahan:</strong>
                                <ul class="mt-2 mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        @if(session('info'))
                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                <i class="fas fa-info-circle mr-2"></i>
                                {{ session('info') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        @if(request('return_to_fob') || session('return_to_fob'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-sync-alt mr-2"></i>
                                <strong>Mode Sinkronisasi FOB:</strong> Data akan disinkronkan dengan data FOB setelah disimpan.
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="customer_id">Customer <span class="text-danger">*</span></label>
                                    <select name="customer_id" id="customer_id" class="form-control select2" required>
                                        <option value="">-- Pilih Customer --</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ (old('customer_id', $rekapPengambilan->customer_id) == $customer->id) ? 'selected' : '' }}>
                                                {{ $customer->name }} ({{ ucfirst($customer->role) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tanggal">Tanggal dan Waktu <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="tanggal" id="tanggal" class="form-control @error('tanggal') is-invalid @enderror" 
                                        value="{{ old('tanggal', $rekapPengambilan->tanggal->format('Y-m-d\TH:i')) }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nopol">Nomor Polisi <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <select name="nopol" id="nopol_select" class="form-control select2 @error('nopol') is-invalid @enderror">
                                            @foreach($nomorPolisList as $nomorPolisi)
                                                <option value="{{ $nomorPolisi->nopol }}" {{ old('nopol', $rekapPengambilan->nopol) == $nomorPolisi->nopol ? 'selected' : '' }}>
                                                    {{ $nomorPolisi->nopol }}
                                                </option>
                                            @endforeach
                                            <option value="new">+ Tambah Nomor Polisi Baru</option>
                                        </select>
                                        <input type="text" name="nopol_new" id="nopol_new" class="form-control @error('nopol') is-invalid @enderror" 
                                               placeholder="Masukkan nomor polisi baru" style="display: none;">
                                        <div class="input-group-append" id="nopol_toggle_group" style="display: none;">
                                            <button type="button" id="toggle_nopol_input" class="btn btn-default">
                                                <i class="fas fa-undo"></i> Kembali
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="volume">Volume (SM³) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="volume" id="volume" class="form-control @error('volume') is-invalid @enderror" 
                                        value="{{ old('volume', $rekapPengambilan->volume) }}" min="0" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group alamat-pengambilan-container">
                            <label for="alamat_pengambilan_id">Alamat Pengambilan</label>
                            <select name="alamat_pengambilan_id" id="alamat_pengambilan_id" class="form-control">
                                <option value="">-- Pilih Alamat Pengambilan --</option>
                                @foreach ($alamatList as $alamat)
                                    <option value="{{ $alamat->id }}" {{ old('alamat_pengambilan_id', $rekapPengambilan->alamat_pengambilan_id) == $alamat->id ? 'selected' : '' }}>{{ $alamat->nama_alamat }}</option>
                                @endforeach
                                <option value="tambah_baru">+ Tambah Alamat Baru</option>
                            </select>
                            <div class="alamat-pengambilan-baru-container mt-2" id="alamat-baru-container" style="display: none; background-color: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; border-radius: 5px;">
                                <label for="alamat_new">Alamat Baru:</label>
                                <input type="text" name="alamat_new" id="alamat_new" class="form-control"
                                    placeholder="Masukkan alamat pengambilan baru">
                                <small class="text-muted">Alamat baru akan disimpan saat form disubmit</small>
                            </div>
                            <!-- Keep the old field for backward compatibility -->
                            <input type="hidden" name="alamat_pengambilan" id="alamat_pengambilan" value="{{ old('alamat_pengambilan', $rekapPengambilan->alamat_pengambilan) }}">
                        </div>

                        <div class="form-group">
                            <label for="keterangan">Keterangan</label>
                            <textarea name="keterangan" id="keterangan" rows="3" class="form-control @error('keterangan') is-invalid @enderror" 
                                placeholder="Tambahkan keterangan jika diperlukan">{{ old('keterangan', $rekapPengambilan->keterangan) }}</textarea>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Simpan Perubahan
                        </button>
                        @if(request('return_to_fob') || session('return_to_fob'))
                            <a href="{{ route('data-pencatatan.fob-detail', $rekapPengambilan->customer_id) }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Kembali ke FOB Detail
                            </a>
                        @else
                            <a href="{{ route('rekap-pengambilan.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Kembali
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(function () {
        // Initialize Select2
        $('.select2').select2({
            theme: 'bootstrap4',
            placeholder: "Pilih",
            allowClear: true
        });
        
        // Handle nopol selection
        $('#nopol_select').on('change', function() {
            if ($(this).val() === 'new') {
                $(this).hide();
                $('#nopol_new').show().focus().attr('required', true);
                $('#nopol_toggle_group').show();
            }
        });
        
        // Toggle back to dropdown
        $('#toggle_nopol_input').on('click', function() {
            $('#nopol_select').val('{{ $rekapPengambilan->nopol }}').trigger('change').show();
            $('#nopol_new').hide().val('').removeAttr('required');
            $('#nopol_toggle_group').hide();
        });
        
        // Pure JavaScript untuk handle alamat pengambilan dropdown
        document.addEventListener('DOMContentLoaded', function() {
            const alamatDropdown = document.getElementById('alamat_pengambilan_id');
            const alamatContainer = document.getElementById('alamat-baru-container');
            const alamatNewInput = document.getElementById('alamat_new');
            const alamatHiddenInput = document.getElementById('alamat_pengambilan');
            
            if (alamatDropdown) {
                alamatDropdown.addEventListener('change', function() {
                    if (this.value === 'tambah_baru') {
                        if (alamatContainer) {
                            alamatContainer.style.display = 'block';
                            if (alamatNewInput) {
                                alamatNewInput.focus();
                            }
                        }
                    } else {
                        if (alamatContainer) {
                            alamatContainer.style.display = 'none';
                        }
                        if (alamatNewInput) {
                            alamatNewInput.value = '';
                        }
                        
                        // Set hidden input value
                        if (this.value && alamatHiddenInput) {
                            const selectedOption = this.options[this.selectedIndex];
                            alamatHiddenInput.value = selectedOption.text;
                        } else if (alamatHiddenInput) {
                            alamatHiddenInput.value = '';
                        }
                    }
                });
            }
            
            // Handle new alamat input
            if (alamatNewInput) {
                alamatNewInput.addEventListener('input', function() {
                    if (alamatHiddenInput) {
                        alamatHiddenInput.value = this.value;
                    }
                });
            }
        });
        
        // Form submission handler
        $('#formEditData').on('submit', function(e) {
            // If using new nopol input, copy value to nopol field
            if ($('#nopol_new').is(':visible') && $('#nopol_new').val()) {
                e.preventDefault(); // Prevent default submission temporarily
                
                // Set the value directly to nopol field
                $('<input>').attr({
                    type: 'hidden',
                    name: 'nopol',
                    value: $('#nopol_new').val()
                }).appendTo(this);
                
                // Now submit the form
                this.submit();
            }
        });
    });
</script>
@endsection
