@extends('layouts.app')

@section('title', 'Tambah Rekap Pengambilan')

@section('page-title', 'Tambah Data Rekap Pengambilan')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-plus-circle mr-2"></i>
                            Form Tambah Data Pengambilan
                        </h3>
                        <div class="card-tools">
                            <a href="{{ route('nomor-polisi.index') }}" class="btn btn-info btn-sm">
                                <i class="fas fa-truck mr-1"></i> Halaman Nomor Polisi
                            </a>

                        </div>
                    </div>

                    <form action="{{ route('rekap-pengambilan.store') }}" method="POST" id="formTambahData">
                        @csrf
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

                            @if(isset($selectedCustomer))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    <strong>Customer terpilih:</strong> {{ $selectedCustomer->name }} ({{ ucfirst($selectedCustomer->role) }})
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="customer_id">Customer <span class="text-danger">*</span></label>
                                        <select name="customer_id" id="customer_id" class="form-control select2" required
                                            {{ isset($selectedCustomer) ? 'readonly' : '' }}>
                                            <option value="">-- Pilih Customer --</option>
                                            @foreach ($customers as $customer)
                                                @php
                                                    $selected = false;
                                                    if (isset($selectedCustomer) && $selectedCustomer->id == $customer->id) {
                                                        $selected = true;
                                                    } elseif (!isset($selectedCustomer) && old('customer_id') == $customer->id) {
                                                        $selected = true;
                                                    }
                                                @endphp
                                                <option value="{{ $customer->id }}" {{ $selected ? 'selected' : '' }}>
                                                    {{ $customer->name }} ({{ ucfirst($customer->role) }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tanggal">Tanggal dan Waktu <span class="text-danger">*</span></label>
                                        @php
                                            $defaultDate = old('tanggal');
                                            if (!$defaultDate && session('preset_date')) {
                                                $defaultDate = \Carbon\Carbon::parse(session('preset_date'))->format('Y-m-d\TH:i');
                                            } elseif (!$defaultDate) {
                                                $defaultDate = now()->format('Y-m-d\TH:i');
                                            }
                                        @endphp
                                        <input type="datetime-local" name="tanggal" id="tanggal"
                                            class="form-control @error('tanggal') is-invalid @enderror"
                                            value="{{ $defaultDate }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nopol">Nomor Polisi <span class="text-danger">*</span></label>
                                        <select name="nopol" id="nopol" class="form-control select2" required>
                                            <option value="">-- Pilih Nomor Polisi --</option>
                                            @foreach ($nomorPolisList as $nopol)
                                                <option value="{{ $nopol->nopol }}"
                                                    {{ old('nopol') == $nopol->nopol ? 'selected' : '' }}>
                                                    {{ $nopol->nopol }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="volume">Volume (SM³) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" name="volume" id="volume"
                                            class="form-control @error('volume') is-invalid @enderror"
                                            value="{{ old('volume', 0) }}" min="0" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group alamat-pengambilan-container">
                                <label for="alamat_pengambilan_id">Alamat Pengambilan</label>
                                <select name="alamat_pengambilan_id" id="alamat_pengambilan_id" class="form-control">
                                    <option value="">-- Pilih Alamat Pengambilan --</option>
                                    @foreach ($alamatList as $alamat)
                                        <option value="{{ $alamat->id }}" {{ old('alamat_pengambilan_id') == $alamat->id ? 'selected' : '' }}>{{ $alamat->nama_alamat }}</option>
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
                                <input type="hidden" name="alamat_pengambilan" id="alamat_pengambilan" value="{{ old('alamat_pengambilan') }}">
                            </div>

                            <div class="form-group">
                                <label for="keterangan">Keterangan</label>
                                <textarea name="keterangan" id="keterangan" rows="3"
                                    class="form-control @error('keterangan') is-invalid @enderror" placeholder="Tambahkan keterangan jika diperlukan">{{ old('keterangan') }}</textarea>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Simpan
                            </button>
                            @if(request('return_to_fob') || session('return_to_fob'))
                                <a href="{{ route('data-pencatatan.fob-detail', $selectedCustomer->id) }}" class="btn btn-secondary">
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
        // Tunggu DOM ready tanpa jQuery
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM Ready - Pure JavaScript');
            
            // Get elements
            const alamatDropdown = document.getElementById('alamat_pengambilan_id');
            const alamatContainer = document.getElementById('alamat-baru-container');
            const alamatNewInput = document.getElementById('alamat_new');
            const alamatHiddenInput = document.getElementById('alamat_pengambilan');
            
            console.log('Elements found:', {
                dropdown: alamatDropdown ? 'YES' : 'NO',
                container: alamatContainer ? 'YES' : 'NO',
                newInput: alamatNewInput ? 'YES' : 'NO',
                hiddenInput: alamatHiddenInput ? 'YES' : 'NO'
            });
            
            // Handle dropdown change
            if (alamatDropdown) {
                alamatDropdown.addEventListener('change', function() {
                    console.log('Dropdown changed to:', this.value);
                    
                    if (this.value === 'tambah_baru') {
                        console.log('Showing container...');
                        if (alamatContainer) {
                            alamatContainer.style.display = 'block';
                            if (alamatNewInput) {
                                alamatNewInput.focus();
                            }
                        }
                    } else {
                        console.log('Hiding container...');
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
        
        // Fallback dengan jQuery jika tersedia
        if (typeof jQuery !== 'undefined') {
            jQuery(document).ready(function($) {
                console.log('jQuery is available, version:', $.fn.jquery);
                
                // Skip Select2 initialization karena ada masalah compatibility
                console.log('Skipping Select2 due to compatibility issues');

                // Skip DataTable initialization if not available
                if ($.fn.DataTable) {
                    // Initialize DataTable for modal
                    let modalNopolTable = $('#modalNopolTable').DataTable({
                        "paging": true,
                        "lengthChange": false,
                        "searching": true,
                        "ordering": true,
                        "info": true,
                        "autoWidth": false,
                        "responsive": true,
                        "pageLength": 5
                    });
                } else {
                    console.log('DataTable not available');
                }

                // Load nomor polisi list when modal is opened
                $('#kelolaNopolModal').on('shown.bs.modal', function() {
                    console.log('Modal opened, API URL:', '{{ route('nomor-polisi.getAll') }}');
                    loadNopolList();
                });

                // Skip all modal-related handlers since DataTable is not available
                console.log('Skipping modal handlers due to missing dependencies');
            });
        }
    </script>
@endsection
