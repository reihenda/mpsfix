@extends('layouts.app')

@section('title', 'Tambah Data Pencatatan (Demo)')

@section('page-title', 'Tambah Data Pencatatan Baru (Demo)')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
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
            </div>

            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-plus-circle mr-2"></i>
                            Input Data Pencatatan Baru
                        </h3>
                    </div>
                    <form action="{{ route('demo.store') }}" method="POST" id="formDataPencatatan">
                        @csrf
                        <div class="card-body">
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Info:</strong> Data ini akan disimpan dalam akun demo Anda sebagai contoh.
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="mb-3"><i class="fas fa-hourglass-start mr-2"></i>Pembacaan Awal</h5>

                                    <div class="form-group">
                                        <label for="tanggalAwal">Waktu Pembacaan Awal</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                            </div>
                                            <input type="datetime-local"
                                                class="form-control @error('data_input.pembacaan_awal.waktu') is-invalid @enderror"
                                                id="tanggalAwal" name="data_input[pembacaan_awal][waktu]"
                                                value="{{ $latestDate ? date('Y-m-d\TH:i', strtotime($latestDate)) : now()->format('Y-m-d\TH:i') }}"
                                                required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="volumeAwal">Volume Awal (m³)</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01"
                                                class="form-control @error('data_input.pembacaan_awal.volume') is-invalid @enderror"
                                                id="volumeAwal" name="data_input[pembacaan_awal][volume]"
                                                value="{{ $latestVolume ?? 0 }}" required>
                                            <div class="input-group-append">
                                                <span class="input-group-text">m³</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <h5 class="mb-3"><i class="fas fa-hourglass-end mr-2"></i>Pembacaan Akhir</h5>

                                    <div class="form-group">
                                        <label for="tanggalAkhir">Waktu Pembacaan Akhir</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                            </div>
                                            <input type="datetime-local"
                                                class="form-control @error('data_input.pembacaan_akhir.waktu') is-invalid @enderror"
                                                id="tanggalAkhir" name="data_input[pembacaan_akhir][waktu]"
                                                value="{{ now()->format('Y-m-d\TH:i') }}" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="volumeAkhir">Volume Akhir (m³)</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01"
                                                class="form-control @error('data_input.pembacaan_akhir.volume') is-invalid @enderror"
                                                id="volumeAkhir" name="data_input[pembacaan_akhir][volume]"
                                                value="{{ ($latestVolume ?? 0) + 10 }}" required>
                                            <div class="input-group-append">
                                                <span class="input-group-text">m³</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mt-4">
                                <label for="volumeFlowMeter">Selisih Volume (m³)</label>
                                <div class="input-group">
                                    <input type="number" step="0.01"
                                        class="form-control @error('data_input.volume_flow_meter') is-invalid @enderror"
                                        id="volumeFlowMeter" name="data_input[volume_flow_meter]" value="10" required
                                        readonly>
                                    <div class="input-group-append">
                                        <span class="input-group-text">m³</span>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Nilai ini dihitung otomatis (Volume Akhir - Volume
                                    Awal)</small>
                            </div>

                            <div class="form-group">
                                <label for="notes">Catatan (opsional)</label>
                                <textarea class="form-control" id="notes" name="data_input[notes]" rows="3"
                                    placeholder="Tambahkan catatan jika diperlukan"></textarea>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Simpan
                            </button>
                            <a href="{{ route('demo.admin') }}" class="btn btn-secondary">
                                <i class="fas fa-times-circle mr-1"></i> Batal
                            </a>
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
            // Calculate volume difference automatically
            function calculateVolumeDifference() {
                const volumeAwal = parseFloat($('#volumeAwal').val()) || 0;
                const volumeAkhir = parseFloat($('#volumeAkhir').val()) || 0;
                const difference = Math.max(0, volumeAkhir - volumeAwal).toFixed(2);
                $('#volumeFlowMeter').val(difference);
            }

            // Initial calculation
            calculateVolumeDifference();

            // Recalculate on input change
            $('#volumeAwal, #volumeAkhir').on('input', calculateVolumeDifference);

            // Form validation
            $('#formDataPencatatan').on('submit', function(e) {
                const volumeAwal = parseFloat($('#volumeAwal').val()) || 0;
                const volumeAkhir = parseFloat($('#volumeAkhir').val()) || 0;

                if (volumeAkhir <= volumeAwal) {
                    e.preventDefault();
                    alert('Volume akhir harus lebih besar dari volume awal!');
                    return false;
                }

                const tanggalAwal = new Date($('#tanggalAwal').val());
                const tanggalAkhir = new Date($('#tanggalAkhir').val());

                if (tanggalAkhir <= tanggalAwal) {
                    e.preventDefault();
                    alert('Waktu pembacaan akhir harus setelah waktu pembacaan awal!');
                    return false;
                }
            });
        });
    </script>
@endsection
