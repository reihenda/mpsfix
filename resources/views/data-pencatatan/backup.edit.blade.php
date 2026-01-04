@extends('layouts.app')

@section('title', 'Edit Data Pencatatan')

@section('page-title', 'Edit Data Pencatatan')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title">Form Edit Data Pencatatan</h3>
                </div>
                <form action="{{ route('data-pencatatan.update', $dataPencatatan->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        {{-- Customer Selection --}}
                        <div class="form-group">
                            <label>Pilih Customer</label>
                            <select name="customer_id" class="form-control" required>
                                <option value="">Pilih Customer</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}"
                                        {{ $dataPencatatan->customer_id == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->name }} ({{ $customer->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('customer_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        {{-- Decode existing data input --}}
                        @php
                            $dataInput = json_decode($dataPencatatan->data_input, true);
                        @endphp

                        {{-- Data Input Fields --}}
                        <div class="form-group">
                            <label>Data Input</label>
                            <div id="data-input-container">
                                {{-- Volume Input --}}
                                <div class="input-group mb-2">
                                    <input type="text" name="data_input[volume]" class="form-control"
                                        placeholder="Volume" value="{{ $dataInput['volume'] ?? '' }}" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">m³</span>
                                    </div>
                                </div>

                                {{-- Kompleksitas Input --}}
                                <div class="input-group mb-2">
                                    <input type="text" name="data_input[kompleksitas]" class="form-control"
                                        placeholder="Tingkat Kompleksitas" value="{{ $dataInput['kompleksitas'] ?? '' }}"
                                        required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">Skala 1-10</span>
                                    </div>
                                </div>

                                {{-- Catatan Tambahan Input --}}
                                <div class="input-group mb-2">
                                    <input type="text" name="data_input[catatan_tambahan]" class="form-control"
                                        placeholder="Catatan Tambahan (Opsional)"
                                        value="{{ $dataInput['catatan_tambahan'] ?? '' }}">
                                </div>
                            </div>
                        </div>

                        {{-- Current Status Information --}}
                        <div class="form-group">
                            <label>Informasi Saat Ini</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Tanggal Dibuat:</strong>
                                        {{ $dataPencatatan->created_at->format('d M Y H:i:s') }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p>
                                        <strong>Status Pembayaran:</strong>
                                        @switch($dataPencatatan->status_pembayaran)
                                            @case('lunas')
                                                <span class="badge badge-success">Lunas</span>
                                            @break

                                            @case('belum_lunas')
                                                <span class="badge badge-warning">Belum Lunas</span>
                                            @break

                                            @default
                                                <span class="badge badge-secondary">Tidak Diketahui</span>
                                        @endswitch
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="card-footer">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> Update Data
                        </button>
                        <a href="{{ route('data-pencatatan.index') }}" class="btn btn-secondary ml-2">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(function() {
            // Form validation
            $('form').on('submit', function(e) {
                const volume = $('input[name="data_input[volume]"]').val();
                const kompleksitas = $('input[name="data_input[kompleksitas]"]').val();

                if (isNaN(volume) || isNaN(kompleksitas)) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Kesalahan Input',
                        text: 'Volume dan Kompleksitas harus berupa angka!'
                    });
                }
            });
        });
    </script>
@endsection
