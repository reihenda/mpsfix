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

                    {{-- Decode existing data input --}}
                    @php
                        $dataInput = json_decode($dataPencatatan->data_input, true);
                    @endphp

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
                        </div>

                        {{-- Pembacaan Awal Section --}}
                        <div class="card card-secondary mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Pembacaan Awal</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Waktu Pembacaan Awal</label>
                                            <input type="datetime-local" name="data_input[pembacaan_awal][waktu]"
                                                class="form-control"
                                                value="{{ isset($dataInput['pembacaan_awal']['waktu']) ? date('Y-m-d\TH:i', strtotime($dataInput['pembacaan_awal']['waktu'])) : '' }}"
                                                required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Volume Pembacaan Awal</label>
                                            <div class="input-group">
                                                <input type="number" step="0.001"
                                                    name="data_input[pembacaan_awal][volume]" class="form-control"
                                                    value="{{ $dataInput['pembacaan_awal']['volume'] ?? '' }}"
                                                    placeholder="Volume Awal" required>
                                                <div class="input-group-append">
                                                    <span class="input-group-text">m³</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Pembacaan Akhir Section --}}
                        <div class="card card-secondary mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Pembacaan Akhir</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Waktu Pembacaan Akhir</label>
                                            <input type="datetime-local" name="data_input[pembacaan_akhir][waktu]"
                                                class="form-control"
                                                value="{{ isset($dataInput['pembacaan_akhir']['waktu']) ? date('Y-m-d\TH:i', strtotime($dataInput['pembacaan_akhir']['waktu'])) : '' }}"
                                                required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Volume Pembacaan Akhir</label>
                                            <div class="input-group">
                                                <input type="number" step="0.001"
                                                    name="data_input[pembacaan_akhir][volume]" class="form-control"
                                                    value="{{ $dataInput['pembacaan_akhir']['volume'] ?? '' }}"
                                                    placeholder="Volume Akhir" required>
                                                <div class="input-group-append">
                                                    <span class="input-group-text">m³</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>



                        {{-- Pembacaan Flow Meter Section (Calculated) --}}
                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title">Pembacaan Flow Meter (Otomatis)</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Volume Flow Meter</label>
                                    <div class="input-group">
                                        <input type="text" name="data_input[volume_flow_meter]" class="form-control"
                                            value="{{ $dataInput['volume_flow_meter'] ?? '' }}" readonly>
                                        <div class="input-group-append">
                                            <span class="input-group-text">m³</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

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
            // Calculate Volume Flow Meter automatically
            $('input[name="data_input[pembacaan_akhir][volume]"], input[name="data_input[pembacaan_awal][volume]"]')
                .on('input', function() {
                    const volumeAwal = parseFloat($('input[name="data_input[pembacaan_awal][volume]"]')
                        .val()) || 0;
                    const volumeAkhir = parseFloat($('input[name="data_input[pembacaan_akhir][volume]"]')
                        .val()) || 0;


                    // Calculate volume flow meter
                    const volumeFlowMeter = volumeAkhir - volumeAwal;

                    // Update the readonly field
                    $('input[name="data_input[volume_flow_meter]"]').val(volumeFlowMeter.toFixed(3));
                });

            // Trigger calculation on page load
            $('input[name="data_input[pembacaan_akhir][volume]"]').trigger('input');

            // Prevent form submission if volumes are invalid
            $('form').on('submit', function(e) {
                const volumeAwal = parseFloat($('input[name="data_input[pembacaan_awal][volume]"]').val());
                const volumeAkhir = parseFloat($('input[name="data_input[pembacaan_akhir][volume]"]')
                    .val());

                if (volumeAkhir < volumeAwal) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Kesalahan Input',
                        text: 'Volume akhir tidak boleh kurang dari volume awal!'
                    });
                }
            });
        });
    </script>
@endsection
