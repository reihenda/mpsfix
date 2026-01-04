@extends('layouts.app')

@section('title', 'Tambah Data Pencatatan FOB')

@section('page-title', 'Tambah Data Pencatatan FOB')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-plus-circle mr-2"></i>
                            Tambah Data Pencatatan FOB
                        </h3>
                        <div class="card-tools">
                            <a href="{{ route('nomor-polisi.index') }}" class="btn btn-info btn-sm">
                                <i class="fas fa-truck mr-1"></i> Halaman Nomor Polisi
                            </a>
                        </div>
                    </div>
                    <form action="{{ route('data-pencatatan.fob.store') }}" method="POST" id="formTambahData">
                        @csrf
                        <div class="card-body">
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    {{ session('success') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    {{ session('error') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-times-circle mr-2"></i>
                                    <strong>Kesalahan Validasi:</strong>
                                    <ul class="mt-2">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="customer_id">Pilih FOB <span class="text-danger">*</span></label>
                                        <select name="customer_id" id="customer_id" class="form-control select2"
                                            required {{ isset($selectedCustomer) ? 'disabled' : '' }}>
                                            <option value="">-- Pilih FOB --</option>
                                            @foreach ($fobs as $fob)
                                                <option value="{{ $fob->id }}"
                                                    {{ (isset($selectedCustomer) && $selectedCustomer->id == $fob->id) || old('customer_id') == $fob->id ? 'selected' : '' }}>
                                                    {{ $fob->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if (isset($selectedCustomer))
                                            <input type="hidden" name="customer_id" value="{{ $selectedCustomer->id }}">
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="waktu">Tanggal dan Waktu Pencatatan<span class="text-danger">*</span></label>
                                        <input type="datetime-local" class="form-control" id="waktu"
                                            name="data_input[waktu]" required
                                            value="{{ old('data_input.waktu') ?? now()->format('Y-m-d\TH:i') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nopol">Nomor Polisi <span class="text-danger">*</span></label>
                                        <select name="nopol" id="nopol" class="form-control select2" required>
                                            <option value="">-- Pilih Nomor Polisi --</option>
                                            @foreach($nomorPolisList as $nopol)
                                                <option value="{{ $nopol->nopol }}" {{ old('nopol') == $nopol->nopol ? 'selected' : '' }}>
                                                    {{ $nopol->nopol }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="volume_sm3">Volume Sm³ <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control" id="volume_sm3"
                                                name="data_input[volume_sm3]" required
                                                value="{{ old('data_input.volume_sm3') ?? 0 }}" min="0">
                                            <div class="input-group-append">
                                                <span class="input-group-text">Sm³</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="alamat_pengambilan">Alamat Pengambilan</label>
                                        <input type="text" class="form-control" id="alamat_pengambilan"
                                            name="alamat_pengambilan" 
                                            value="{{ old('alamat_pengambilan') }}"
                                            placeholder="Masukkan alamat lokasi pengambilan">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="keterangan">Keterangan (Opsional)</label>
                                        <textarea class="form-control" id="keterangan" name="data_input[keterangan]" rows="3"
                                            placeholder="Tambahkan keterangan jika ada">{{ old('data_input.keterangan') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i>
                                Simpan Data
                            </button>
                            @if(isset($selectedCustomer))
                                <a href="{{ route('data-pencatatan.fob-detail', $selectedCustomer->id) }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left mr-1"></i>
                                    Kembali
                                </a>
                            @else
                                <a href="{{ route('data-pencatatan.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left mr-1"></i>
                                    Kembali
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
        $(function() {
            $('.select2').select2({
                theme: 'bootstrap4',
                placeholder: "Pilih",
                allowClear: true
            });
        });
    </script>
@endsection
