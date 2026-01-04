@extends('layouts.app')

@section('title', 'Input Data Pencatatan')

@section('page-title', 'Input Data Pencatatan Baru')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Form Input Data Pencatatan</h3>
                </div>
                <form id="pencatatanForm" action="{{ route('data-pencatatan.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        {{-- Customer Selection --}}
                        <div class="form-group">
                            <label>Pilih Customer</label>
                            <select name="customer_id" id="customer_id" class="form-control" required>
                                <option value="">Pilih Customer</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}"
                                        {{ isset($selectedCustomer) && $selectedCustomer->id == $customer->id ? 'selected' : '' }}>
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
                                                value="{{ $prefilledTime ?? '' }}" 
                                                required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Volume Pembacaan Awal</label>
                                            <div class="input-group">
                                                <input type="number" step="0.001"
                                                    name="data_input[pembacaan_awal][volume]" class="form-control"
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
                                                value="{{ $prefilledEndTime ?? '' }}" 
                                                required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Volume Pembacaan Akhir</label>
                                            <div class="input-group">
                                                <input type="number" step="0.001"
                                                    name="data_input[pembacaan_akhir][volume]" class="form-control"
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
                                            readonly>
                                        <div class="input-group-append">
                                            <span class="input-group-text">m³</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Data
                        </button>
                        <a href="{{ isset($selectedCustomer) ? route('data-pencatatan.customer-detail', $selectedCustomer->id) : route('data-pencatatan.index') }}"
                            class="btn btn-secondary ml-2">
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
            // Set default date-time values only if not already set from server
            const waktuAwalField = $('input[name="data_input[pembacaan_awal][waktu]"]');
            const waktuAkhirField = $('input[name="data_input[pembacaan_akhir][waktu]"]');
            
            // Only set default values if fields are empty (no prefilled data)
            if (!waktuAwalField.val()) {
                const today = new Date();
                const todayFormatted = today.toISOString().split('T')[0];
                waktuAwalField.val(todayFormatted + 'T00:00');
            }
            
            if (!waktuAkhirField.val()) {
                const today = new Date();
                const todayFormatted = today.toISOString().split('T')[0];
                waktuAkhirField.val(todayFormatted + 'T23:59');
            }

            // Function to calculate volume flow meter
            function calculateVolumeFlowMeter() {
                const volumeAwal = parseFloat($('input[name="data_input[pembacaan_awal][volume]"]').val()) || 0;
                const volumeAkhir = parseFloat($('input[name="data_input[pembacaan_akhir][volume]"]').val()) || 0;

                // Calculate volume flow meter
                const volumeFlowMeter = volumeAkhir - volumeAwal;

                // Update the readonly field
                $('input[name="data_input[volume_flow_meter]"]').val(volumeFlowMeter.toFixed(3));
            }

            // Calculate on input changes
            $('input[name="data_input[pembacaan_akhir][volume]"], input[name="data_input[pembacaan_awal][volume]"]')
                .on('input', calculateVolumeFlowMeter);

            // Form validation
            $('#pencatatanForm').on('submit', function(e) {
                const volumeAwal = parseFloat($('input[name="data_input[pembacaan_awal][volume]"]').val());
                const volumeAkhir = parseFloat($('input[name="data_input[pembacaan_akhir][volume]"]')
                    .val());
                const waktuAwal = $('input[name="data_input[pembacaan_awal][waktu]"]').val();
                const waktuAkhir = $('input[name="data_input[pembacaan_akhir][waktu]"]').val();

                // Validate volume
                if (volumeAkhir < volumeAwal) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Kesalahan Input',
                        text: 'Volume akhir tidak boleh kurang dari volume awal!'
                    });
                    return;
                }

                // Validate time
                if (waktuAkhir <= waktuAwal) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Kesalahan Input',
                        text: 'Waktu pembacaan akhir harus lebih besar dari waktu pembacaan awal!'
                    });
                    return;
                }

                // Trigger calculation one last time before submission
                calculateVolumeFlowMeter();
            });

            // Jalankan perhitungan flow meter saat halaman dimuat
            calculateVolumeFlowMeter();
            
            // Auto-set waktu akhir berdasarkan waktu awal jika ada prefilled data
            waktuAwalField.on('change', function() {
                const waktuAwal = $(this).val();
                if (waktuAwal && !waktuAkhirField.val()) {
                    // Set waktu akhir ke akhir hari yang sama
                    const tanggal = waktuAwal.split('T')[0];
                    waktuAkhirField.val(tanggal + 'T23:59');
                }
            });
        });
    </script>

    @if (isset($latestVolume))
        <script>
            // Set default volume awal dari data terakhir
            $(document).ready(function() {
                $('input[name="data_input[pembacaan_awal][volume]"]').val({{ $latestVolume }});
                console.log("Set default volume to: {{ $latestVolume }} from {{ $latestDate }}");

                // Tampilkan notifikasi sukses
                Swal.fire({
                    icon: 'success',
                    title: 'Data Ditemukan',
                    text: 'Data pembacaan terakhir ({{ $latestDate }}) berhasil diambil',
                    timer: 2000,
                    showConfirmButton: false
                });
            });
        </script>
    @endif
@endsection
