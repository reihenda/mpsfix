@extends('layouts.app')

@section('title', 'Kelola Harga Gagas')

@section('page-title', 'Kelola Harga Gagas')

@section('content')
    <!-- Alert Messages -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div></div>
                <a href="{{ route('rekap.pembelian.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Rekap Pembelian
                </a>
            </div>
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filter Periode</h3>
                </div>
                <div class="card-body py-3">
                    <form action="{{ route('rekap.pembelian.kelola-harga-gagas') }}" method="GET"
                        class="d-flex align-items-center">
                        <div class="d-flex align-items-center mr-4">
                            <label for="tahun" class="mb-0 mr-2 font-weight-bold">Tahun:</label>
                            <select name="tahun" id="tahun" class="form-control form-control-sm" style="width: 100px;"
                                onchange="this.form.submit()">
                                @for ($i = date('Y'); $i >= 2020; $i--)
                                    <option value="{{ $i }}" {{ $selectedTahun == $i ? 'selected' : '' }}>
                                        {{ $i }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="d-flex align-items-center">
                            <label for="bulan" class="mb-0 mr-2 font-weight-bold">Bulan:</label>
                            <select name="bulan" id="bulan" class="form-control form-control-sm" style="width: 150px;"
                                onchange="this.form.submit()">
                                @for ($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ $selectedBulan == $i ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Rate Info dan Form Input Harga Gagas -->
    <div class="row mb-4">
        <!-- Form Input Harga Gagas -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-warning text-white">
                    <h3 class="card-title"><i class="fas fa-cog mr-2"></i>Input Harga Gagas</h3>
                </div>
                <div class="card-body">
                    <!-- Copy from Previous Period -->
                    @if (isset($previousPeriodData) && $previousPeriodData)
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <h6><i class="fas fa-copy mr-2"></i>Data Periode Sebelumnya Tersedia</h6>
                            <p class="mb-2">Ditemukan data harga gagas dari
                                <strong>{{ $previousPeriodData['periode'] }}</strong></p>
                            <div class="row">
                                <div class="col-md-4">
                                    <small><strong>Harga USD:</strong>
                                        ${{ number_format($previousPeriodData['data']->harga_usd, 2) }}</small>
                                </div>
                                <div class="col-md-4">
                                    <small><strong>Kalori:</strong>
                                        @if(floor($previousPeriodData['data']->kalori) == $previousPeriodData['data']->kalori)
                                            {{ number_format($previousPeriodData['data']->kalori, 0) }}
                                        @else
                                            {{ rtrim(rtrim(number_format($previousPeriodData['data']->kalori, 16, '.', ''), '0'), '.') }}
                                        @endif
                                    </small>
                                </div>
                                <div class="col-md-4">
                                    <small><strong>Rate:</strong>
                                        {{ number_format($previousPeriodData['data']->rate_konversi_idr, 2) }}</small>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-info btn-sm" onclick="copyFromPrevious()">
                                    <i class="fas fa-copy mr-1"></i> Salin Data Periode Sebelumnya
                                </button>
                            </div>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <form action="{{ route('rekap.pembelian.update-harga-gagas') }}" method="POST" id="harga-gagas-form">
                        @csrf
                        <input type="hidden" name="tahun" value="{{ $selectedTahun }}">
                        <input type="hidden" name="bulan" value="{{ $selectedBulan }}">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="harga_usd">Harga USD <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" class="form-control @error('harga_usd') is-invalid @enderror"
                                            id="harga_usd" name="harga_usd" step="0.01" min="0"
                                            value="{{ old('harga_usd', $hargaGagas->harga_usd ?? '') }}" required>
                                    </div>
                                    @error('harga_usd')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="kalori">Kalori <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('kalori') is-invalid @enderror"
                                        id="kalori" name="kalori" 
                                        value="{{ old('kalori', $hargaGagas->kalori ?? '') }}" 
                                        required
                                        placeholder="Masukkan nilai kalori (hingga 16 angka di belakang koma)"
                                        pattern="^[0-9]+([.][0-9]{1,16})?$"
                                        title="Nilai kalori harus berupa angka dengan maksimal 16 angka di belakang koma">
                                    @error('kalori')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Untuk konversi ke MMBTU (presisi hingga 16 angka di belakang koma)</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="rate_option">Rate Konversi IDR</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="rate_option"
                                            id="use_realtime" value="realtime" checked>
                                        <label class="form-check-label" for="use_realtime">
                                            Gunakan Rate Realtime ({{ number_format($currentUsdToIdr, 0, ',', '.') }})
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="rate_option"
                                            id="use_manual" value="manual">
                                        <label class="form-check-label" for="use_manual">
                                            Input Manual
                                        </label>
                                    </div>
                                    <div class="mt-2" id="manual_rate_input" style="display: none;">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Rp</span>
                                            </div>
                                            <input type="number" class="form-control" id="manual_rate"
                                                name="manual_rate" step="0.01" min="0"
                                                placeholder="Masukkan rate manual">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> Simpan Harga Gagas
                                </button>
                                <button type="button" class="btn btn-info ml-2" id="calculate-preview">
                                    <i class="fas fa-calculator mr-1"></i> Hitung Preview
                                </button>
                                @if (isset($previousPeriodData) && $previousPeriodData)
                                    <button type="button" class="btn btn-success ml-2" onclick="copyFromPrevious()">
                                        <i class="fas fa-copy mr-1"></i> Salin dari {{ $previousPeriodData['periode'] }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Current Rate Info -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h3 class="card-title"><i class="fas fa-exchange-alt mr-2"></i>Informasi Rate USD ke IDR</h3>
                </div>
                <div class="card-body">
                    <div class="info-box bg-primary mb-3">
                        <span class="info-box-icon"><i class="fas fa-dollar-sign"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text text-white">Rate Saat Ini (Realtime)</span>
                            <span class="info-box-number text-white" id="current-rate">
                                {{ number_format($currentUsdToIdr, 2, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    <div class="info-box bg-secondary mb-3">
                        <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text text-white">Terakhir Update</span>
                            <span class="info-box-number text-white" style="font-size: 12px;" id="last-update">
                                {{ $rateInfo['last_update']->format('d/m/Y H:i:s') }}
                            </span>
                        </div>
                    </div>

                    <div class="text-center">
                        <button type="button" class="btn btn-warning btn-sm" id="refresh-rate">
                            <i class="fas fa-sync-alt mr-1"></i> Refresh Rate
                        </button>
                        <small class="text-muted d-block mt-2">
                            Sumber: {{ $rateInfo['source'] }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Perhitungan -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h3 class="card-title">
                        <i class="fas fa-calculator mr-2"></i>
                        Perhitungan untuk {{ date('F', mktime(0, 0, 0, $selectedBulan, 1)) }} {{ $selectedTahun }}
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-box bg-info position-relative">
                                <span class="info-box-icon"><i class="fas fa-truck-loading"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text text-white">Total Volume Pengambilan</span>
                                    <span class="info-box-number text-white">
                                        {{ number_format($totalVolume, 2) }} SM³
                                    </span>
                                </div>
                                <!-- Info Icon dengan Tooltip -->
                                <span class="position-absolute" style="top: 8px; right: 8px;">
                                    <i class="fas fa-info-circle text-white" data-toggle="tooltip" data-placement="top"
                                        data-html="true"
                                        title="<strong>Sumber Data:</strong><br>• Data pengambilan periode {{ date('F', mktime(0, 0, 0, $selectedBulan, 1)) }} {{ $selectedTahun }}<br>• Total semua volume pengambilan customer<br>• Satuan: Standar Meter Kubik (SM³)"></i>
                                </span>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="info-box bg-warning position-relative">
                                <span class="info-box-icon"><i class="fas fa-fire"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text text-dark">Total MMBTU</span>
                                    <span class="info-box-number text-dark" id="total-mmbtu">
                                        {{ number_format($totalMMBTU, 4) }}
                                    </span>
                                </div>
                                <!-- Info Icon dengan Tooltip -->
                                <span class="position-absolute" style="top: 8px; right: 8px;">
                                    <i class="fas fa-info-circle text-dark" data-toggle="tooltip" data-placement="top"
                                        data-html="true"
                                        title="<strong>Rumus Perhitungan:</strong><br>• MMBTU = Total Volume SM³ ÷ Kalori<br>• {{ number_format($totalVolume, 2) }} ÷ {{ $hargaGagas ? (floor($hargaGagas->kalori) == $hargaGagas->kalori ? number_format($hargaGagas->kalori, 0) : rtrim(rtrim(number_format($hargaGagas->kalori, 16, '.', ''), '0'), '.')) : '0' }} = {{ number_format($totalMMBTU, 4) }}<br><strong>Kalori:</strong><br>• Nilai kalori gas untuk konversi<br>• Periode: {{ date('F', mktime(0, 0, 0, $selectedBulan, 1)) }} {{ $selectedTahun }}"></i>
                                </span>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="info-box bg-primary position-relative">
                                <span class="info-box-icon"><i class="fas fa-dollar-sign"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text text-white">Harga per MMBTU (USD)</span>
                                    <span class="info-box-number text-white" id="harga-usd-display">
                                        ${{ number_format($hargaGagas->harga_usd ?? 0, 2) }}
                                    </span>
                                </div>
                                <!-- Info Icon dengan Tooltip -->
                                <span class="position-absolute" style="top: 8px; right: 8px;">
                                    <i class="fas fa-info-circle text-white" data-toggle="tooltip" data-placement="top"
                                        data-html="true"
                                        title="<strong>Sumber Data:</strong><br>• Harga gagas periode {{ date('F', mktime(0, 0, 0, $selectedBulan, 1)) }} {{ $selectedTahun }}<br>• Input manual oleh admin<br>• Rate konversi: {{ number_format($hargaGagas->rate_konversi_idr ?? 0, 2, ',', '.') }} IDR/USD<br>• Satuan: US Dollar per MMBTU"></i>
                                </span>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="info-box bg-success position-relative">
                                <span class="info-box-icon"><i class="fas fa-shopping-cart"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text text-white">Total Pembelian (IDR)</span>
                                    <span class="info-box-number text-white" id="total-pembelian">
                                        Rp {{ number_format($totalPembelian, 0, ',', '.') }}
                                    </span>
                                </div>
                                <!-- Info Icon dengan Tooltip -->
                                <span class="position-absolute" style="top: 8px; right: 8px;">
                                    <i class="fas fa-info-circle text-white" data-toggle="tooltip" data-placement="top"
                                        data-html="true"
                                        title="<strong>Rumus Perhitungan:</strong><br>• Total Pembelian = MMBTU × Harga USD × Rate IDR<br>• {{ number_format($totalMMBTU, 4) }} × ${{ number_format($hargaGagas->harga_usd ?? 0, 2) }} × {{ number_format($hargaGagas->rate_konversi_idr ?? 0, 2, ',', '.') }}<br>• = Rp {{ number_format($totalPembelian, 0, ',', '.') }}<br><strong>Komponen:</strong><br>• MMBTU: Dari perhitungan volume/kalori<br>• Harga USD: Input manual admin<br>• Rate IDR: Real-time API atau manual"></i>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Rumus Perhitungan -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle mr-2"></i>Rumus Perhitungan:</h5>
                                <ol>
                                    <li><strong>Total MMBTU</strong> = Total Volume SM³ ÷ Kalori</li>
                                    <li><strong>Harga IDR per MMBTU</strong> = Harga USD × Rate Konversi IDR</li>
                                    <li><strong>Total Pembelian</strong> = Total MMBTU × Harga IDR per MMBTU</li>
                                </ol>
                                <div class="mt-3" id="calculation-details">
                                    <strong>Detail Perhitungan Saat Ini:</strong><br>
                                    <span id="calculation-formula">
                                        @if ($hargaGagas && $hargaGagas->kalori > 0)
                                            {{ number_format($totalVolume, 2) }} ÷
                                            {{ $hargaGagas ? (floor($hargaGagas->kalori) == $hargaGagas->kalori ? number_format($hargaGagas->kalori, 0) : rtrim(rtrim(number_format($hargaGagas->kalori, 16, '.', ''), '0'), '.')) : '0' }} =
                                            {{ number_format($totalMMBTU, 4) }} MMBTU<br>
                                            ${{ number_format($hargaGagas->harga_usd, 2) }} ×
                                            {{ number_format($hargaGagas->rate_konversi_idr, 2) }} = Rp
                                            {{ number_format($hargaGagas->harga_usd * $hargaGagas->rate_konversi_idr, 2) }}
                                            per MMBTU<br>
                                            {{ number_format($totalMMBTU, 4) }} × Rp
                                            {{ number_format($hargaGagas->harga_usd * $hargaGagas->rate_konversi_idr, 2) }}
                                            = Rp {{ number_format($totalPembelian, 0, ',', '.') }}
                                        @else
                                            <em>Masukkan data harga gagas untuk melihat perhitungan</em>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- History Harga Gagas -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h3 class="card-title"><i class="fas fa-history mr-2"></i>History Harga Gagas</h3>
                </div>
                <div class="card-body">
                    @if ($historyHargaGagas->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th class="text-center">Periode</th>
                                        <th class="text-center">Harga USD</th>
                                        <th class="text-center">Rate IDR</th>
                                        <th class="text-center">Harga IDR per MMBTU</th>
                                        <th class="text-center">Kalori</th>
                                        <th class="text-center">Tanggal Input</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($historyHargaGagas as $index => $history)
                                        <tr
                                            class="{{ $history->periode_tahun == $selectedTahun && $history->periode_bulan == $selectedBulan ? 'table-primary' : '' }}">
                                            <td class="text-center font-weight-bold">
                                                {{ $history->periode_format }}
                                                @if ($history->periode_tahun == $selectedTahun && $history->periode_bulan == $selectedBulan)
                                                    <br><small class="badge badge-primary">Periode Aktif</small>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="font-weight-bold text-primary">
                                                    ${{ number_format($history->harga_usd, 2) }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="text-success">
                                                    {{ number_format($history->rate_konversi_idr, 2, ',', '.') }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="font-weight-bold text-warning">
                                                    Rp {{ number_format($history->harga_idr, 2, ',', '.') }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="text-info" title="{{ $history->kalori }}">
                                                    @if(floor($history->kalori) == $history->kalori)
                                                        {{ number_format($history->kalori, 0) }}
                                                    @else
                                                        {{ rtrim(rtrim(number_format($history->kalori, 16, '.', ''), '0'), '.') }}
                                                    @endif
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <small class="text-muted">
                                                    {{ $history->created_at->format('d/m/Y H:i:s') }}
                                                    <br>
                                                    <em>{{ $history->created_at->diffForHumans() }}</em>
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                @if ($index === 0)
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-star mr-1"></i>Terbaru
                                                    </span>
                                                @elseif($history->periode_tahun == date('Y') && $history->periode_bulan == date('n'))
                                                    <span class="badge badge-info">
                                                        <i class="fas fa-calendar-check mr-1"></i>Bulan Ini
                                                    </span>
                                                @else
                                                    <span class="badge badge-secondary">
                                                        <i class="fas fa-archive mr-1"></i>Arsip
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-danger btn-sm"
                                                    onclick="confirmDelete({{ $history->id }}, '{{ $history->periode_format }}')"
                                                    title="Hapus data ini">
                                                    <i class="fas fa-trash"></i>
                                                </button>

                                                <form id="delete-form-{{ $history->id }}"
                                                    action="{{ route('rekap.pembelian.delete-harga-gagas', $history->id) }}"
                                                    method="POST" style="display: none;">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle mr-2"></i>Informasi History:</h6>
                                <ul class="mb-0 small">
                                    <li><strong>Periode Aktif</strong>: Data harga gagas yang sedang digunakan untuk periode
                                        yang dipilih</li>
                                    <li><strong>Terbaru</strong>: Data harga gagas yang paling baru diinput</li>
                                    <li><strong>Bulan Ini</strong>: Data untuk bulan berjalan</li>
                                    <li><strong>Unik per Periode</strong>: Setiap periode (bulan-tahun) hanya memiliki satu
                                        data harga</li>
                                    <li><strong>Update Otomatis</strong>: Jika input data pada periode yang sama, data lama
                                        akan di-replace</li>
                                </ul>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Belum Ada History</h5>
                            <p class="text-muted">Silakan input harga gagas untuk membuat history pertama</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden form untuk copy dari periode sebelumnya -->
    @if (isset($previousPeriodData) && $previousPeriodData)
        <form id="copy-previous-form" action="{{ route('rekap.pembelian.copy-from-previous') }}" method="POST"
            style="display: none;">
            @csrf
            <input type="hidden" name="tahun" value="{{ $selectedTahun }}">
            <input type="hidden" name="bulan" value="{{ $selectedBulan }}">
        </form>
    @endif
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        const totalVolume = {{ $totalVolume }};
        const currentUsdToIdr = {{ $currentUsdToIdr }};

        @if (isset($previousPeriodData) && $previousPeriodData)
            const previousData = {
                harga_usd: {{ $previousPeriodData['data']->harga_usd }},
                kalori: {{ $previousPeriodData['data']->kalori }},
                periode: '{{ $previousPeriodData['periode'] }}'
            };
        @endif

        // Inisialisasi Bootstrap Tooltips
        $('[data-toggle="tooltip"]').tooltip({
            html: true,
            container: 'body'
        });

        // Toggle manual rate input
        $('input[name="rate_option"]').change(function() {
            if ($(this).val() === 'manual') {
                $('#manual_rate_input').show();
            } else {
                $('#manual_rate_input').hide();
            }
        });

        // Refresh rate button
        $('#refresh-rate').click(function() {
            const button = $(this);
            const originalText = button.html();

            button.html('<i class="fas fa-spinner fa-spin mr-1"></i> Memuat...');
            button.prop('disabled', true);

            $.ajax({
                url: '{{ route('rekap.pembelian.get-current-rate') }}',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#current-rate').text(response.formatted_rate);
                        $('#last-update').text(response.last_update);

                        // Update radio button label
                        $('label[for="use_realtime"]').text('Gunakan Rate Realtime (' +
                            response.rate.toLocaleString('id-ID') + ')');

                        Swal.fire({
                            icon: 'success',
                            title: 'Rate Berhasil Diperbarui',
                            text: 'Rate USD ke IDR telah diperbarui ke: ' + response
                                .formatted_rate,
                            timer: 3000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Memperbarui Rate',
                            text: response.message ||
                                'Terjadi kesalahan saat memperbarui rate'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Memperbarui Rate',
                        text: 'Terjadi kesalahan koneksi: ' + error
                    });
                },
                complete: function() {
                    button.html(originalText);
                    button.prop('disabled', false);
                }
            });
        });

        // Calculate preview
        $('#calculate-preview').click(function() {
            const hargaUsd = parseFloat($('#harga_usd').val()) || 0;
            const kalori = parseFloat($('#kalori').val()) || 0;

            let rateKonversi;
            if ($('#use_manual').is(':checked')) {
                rateKonversi = parseFloat($('#manual_rate').val()) || 0;
            } else {
                rateKonversi = currentUsdToIdr;
            }

            if (hargaUsd <= 0 || kalori <= 0 || rateKonversi <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Data Tidak Lengkap',
                    text: 'Mohon isi semua field dengan nilai yang valid'
                });
                return;
            }

            // Hitung
            const totalMMBTU = totalVolume / kalori;
            const hargaIdrPerMMBTU = hargaUsd * rateKonversi;
            const totalPembelian = totalMMBTU * hargaIdrPerMMBTU;

            // Update display
            $('#total-mmbtu').text(totalMMBTU.toLocaleString('id-ID', {
                minimumFractionDigits: 4,
                maximumFractionDigits: 4
            }));

            $('#harga-usd-display').text('$' + hargaUsd.toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));

            $('#total-pembelian').text('Rp ' + totalPembelian.toLocaleString('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }));

            // Update calculation details
            const calculationFormula = `
            ${totalVolume.toLocaleString('id-ID', { minimumFractionDigits: 2 })} ÷ ${kalori.toLocaleString('id-ID', { minimumFractionDigits: 2 })} = ${totalMMBTU.toLocaleString('id-ID', { minimumFractionDigits: 4 })} MMBTU<br>
            ${hargaUsd.toLocaleString('id-ID', { minimumFractionDigits: 2 })} × ${rateKonversi.toLocaleString('id-ID', { minimumFractionDigits: 2 })} = Rp ${hargaIdrPerMMBTU.toLocaleString('id-ID', { minimumFractionDigits: 2 })} per MMBTU<br>
            ${totalMMBTU.toLocaleString('id-ID', { minimumFractionDigits: 4 })} × Rp ${hargaIdrPerMMBTU.toLocaleString('id-ID', { minimumFractionDigits: 2 })} = Rp ${totalPembelian.toLocaleString('id-ID', { minimumFractionDigits: 0 })}
        `;

            $('#calculation-formula').html(calculationFormula);

            // Update tooltips dengan data baru
            updateTooltips(totalVolume, kalori, totalMMBTU, hargaUsd, rateKonversi, totalPembelian);

            Swal.fire({
                icon: 'success',
                title: 'Perhitungan Selesai',
                text: 'Preview perhitungan telah diperbarui',
                timer: 2000,
                showConfirmButton: false
            });
        });

        // Input validation untuk kalori dengan presisi 16 angka di belakang koma
        $('#kalori').on('input', function() {
            let value = $(this).val();
            
            // Remove any character that's not a digit or decimal point
            value = value.replace(/[^0-9.]/g, '');
            
            // Ensure only one decimal point
            const decimalCount = (value.match(/\./g) || []).length;
            if (decimalCount > 1) {
                value = value.substring(0, value.lastIndexOf('.'));
            }
            
            // Limit decimal places to 16
            if (value.includes('.')) {
                const parts = value.split('.');
                if (parts[1] && parts[1].length > 16) {
                    parts[1] = parts[1].substring(0, 16);
                    value = parts.join('.');
                }
            }
            
            $(this).val(value);
        });

        // Form validation
        $('#harga-gagas-form').submit(function(e) {
            const hargaUsd = parseFloat($('#harga_usd').val()) || 0;
            const kaloriInput = $('#kalori').val();
            const kalori = parseFloat(kaloriInput) || 0;

            if (hargaUsd <= 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Harga USD Tidak Valid',
                    text: 'Mohon masukkan harga USD yang valid (lebih dari 0)'
                });
                return false;
            }

            // Validasi kalori dengan pattern yang lebih ketat
            const kaloriPattern = /^[0-9]+([.][0-9]{1,16})?$/;
            if (!kaloriPattern.test(kaloriInput) || kalori <= 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Kalori Tidak Valid',
                    text: 'Mohon masukkan nilai kalori yang valid (lebih dari 0, maksimal 16 angka di belakang koma)'
                });
                $('#kalori').focus();
                return false;
            }

            if ($('#use_manual').is(':checked')) {
                const manualRate = parseFloat($('#manual_rate').val()) || 0;
                if (manualRate <= 0) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Rate Manual Tidak Valid',
                        text: 'Mohon masukkan rate konversi manual yang valid'
                    });
                    return false;
                }
            }
        });
    });

    // Function untuk konfirmasi delete
    function confirmDelete(id, periode) {
        Swal.fire({
            title: 'Hapus Data Harga Gagas?',
            text: `Apakah Anda yakin ingin menghapus data harga gagas untuk periode ${periode}? Tindakan ini tidak dapat dibatalkan.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit form delete
                document.getElementById(`delete-form-${id}`).submit();
            }
        });
    }

    // Function untuk copy data dari periode sebelumnya
    @if (isset($previousPeriodData) && $previousPeriodData)
        function copyFromPrevious() {
            Swal.fire({
                title: 'Salin Data Periode Sebelumnya?',
                text: `Apakah Anda yakin ingin menyalin data harga gagas dari ${previousData.periode}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Salin Data',
                cancelButtonText: 'Batal',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return new Promise((resolve) => {
                        // Submit form copy
                        document.getElementById('copy-previous-form').submit();
                        resolve();
                    });
                }
            });
        }

        function fillFormWithPreviousData() {
            $('#harga_usd').val(previousData.harga_usd);
            $('#kalori').val(previousData.kalori);

            // Set rate option ke realtime untuk menggunakan rate terbaru
            $('#use_realtime').prop('checked', true);
            $('#manual_rate_input').hide();

            // Trigger calculate preview
            $('#calculate-preview').click();

            Swal.fire({
                icon: 'success',
                title: 'Data Berhasil Disalin',
                text: `Data dari ${previousData.periode} telah diisi ke form. Rate USD akan menggunakan nilai terbaru.`,
                timer: 3000,
                showConfirmButton: false
            });
        }
    @endif

    // Function untuk update tooltips setelah calculate preview
    function updateTooltips(totalVolume, kalori, totalMMBTU, hargaUsd, rateKonversi, totalPembelian) {
        // Update tooltip MMBTU
        $('#total-mmbtu').parent().siblings('span').find('i').attr('data-original-title',
            `<strong>Rumus Perhitungan:</strong><br>• MMBTU = Total Volume SM³ ÷ Kalori<br>• ${totalVolume.toLocaleString('id-ID', { minimumFractionDigits: 2 })} ÷ ${kalori.toLocaleString('id-ID', { minimumFractionDigits: 2 })} = ${totalMMBTU.toLocaleString('id-ID', { minimumFractionDigits: 4 })}<br><strong>Data Kalori:</strong><br>• Dari input preview<br>• Nilai: ${kalori.toLocaleString('id-ID', { minimumFractionDigits: 2 })}`
        );

        // Update tooltip Harga USD
        $('#harga-usd-display').parent().siblings('span').find('i').attr('data-original-title',
            `<strong>Sumber Data:</strong><br>• Dari input preview<br>• Harga USD: ${hargaUsd.toLocaleString('id-ID', { minimumFractionDigits: 2 })}<br>• Rate konversi: ${rateKonversi.toLocaleString('id-ID', { minimumFractionDigits: 2 })} IDR/USD<br>• Harga IDR per MMBTU: Rp ${(hargaUsd * rateKonversi).toLocaleString('id-ID', { minimumFractionDigits: 2 })}`
        );

        // Update tooltip Total Pembelian
        $('#total-pembelian').parent().siblings('span').find('i').attr('data-original-title',
            `<strong>Rumus Perhitungan (Preview):</strong><br>• Total Pembelian = MMBTU × Harga USD × Rate IDR<br>• ${totalMMBTU.toLocaleString('id-ID', { minimumFractionDigits: 4 })} × ${hargaUsd.toLocaleString('id-ID', { minimumFractionDigits: 2 })} × ${rateKonversi.toLocaleString('id-ID', { minimumFractionDigits: 2 })}<br>• = Rp ${totalPembelian.toLocaleString('id-ID', { minimumFractionDigits: 0 })}<br><strong>Status:</strong><br>• Data preview (belum disimpan)`
        );
    }
</script>
@endpush
