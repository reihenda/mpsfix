@extends('layouts.app')

@section('title', 'Rekap Pembelian')

@section('page-title', 'Rekap Pembelian')

@php
    // Debug untuk melihat customer
    $debugMode = true; // Set ke false untuk mematikan debug
@endphp

@section('content')
    <!-- Warning Section untuk Fallback Data -->
    @if(isset($fallbackWarnings) && count($fallbackWarnings) > 0)
        <div class="row mb-4">
            <div class="col-md-12">
                @php
                    $monthlyWarnings = collect($fallbackWarnings)->where('type', 'monthly')->first();
                    $noDataWarnings = collect($fallbackWarnings)->where('type', 'no_data')->first();
                    $yearlyFallbacks = collect($fallbackWarnings)->whereIn('type', ['yearly_fallback']);
                    $yearlyNoData = collect($fallbackWarnings)->whereIn('type', ['yearly_no_data']);
                @endphp
                
                @if($monthlyWarnings)
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <h5><i class="fas fa-exclamation-triangle mr-2"></i>Peringatan Data Harga Gagas</h5>
                        <p class="mb-2">{{ $monthlyWarnings['message'] }}</p>
                        <div class="mt-3">
                            <a href="{{ route('rekap.pembelian.kelola-harga-gagas', ['tahun' => $selectedTahun, 'bulan' => $selectedBulan]) }}" 
                               class="btn btn-warning btn-sm">
                                <i class="fas fa-cog mr-1"></i> Atur Harga Gagas
                            </a>
                        </div>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
                
                @if($noDataWarnings)
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h5><i class="fas fa-times-circle mr-2"></i>Data Harga Gagas Tidak Tersedia</h5>
                        <p class="mb-2">{{ $noDataWarnings['message'] }}</p>
                        <p class="mb-0 text-sm">Perhitungan pembelian tidak dapat dilakukan.</p>
                        <div class="mt-3">
                            <a href="{{ route('rekap.pembelian.kelola-harga-gagas', ['tahun' => $selectedTahun, 'bulan' => $selectedBulan]) }}" 
                               class="btn btn-danger btn-sm">
                                <i class="fas fa-plus mr-1"></i> Tambah Harga Gagas
                            </a>
                        </div>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
                
                @if($yearlyFallbacks->count() > 0 || $yearlyNoData->count() > 0)
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <h5><i class="fas fa-info-circle mr-2"></i>Status Harga Gagas Tahunan - {{ $selectedTahun }}</h5>
                        
                        @if($yearlyFallbacks->count() > 0)
                            <div class="mb-2">
                                <strong>Bulan menggunakan data fallback:</strong>
                                <div class="row mt-2">
                                    @foreach($yearlyFallbacks as $warning)
                                        <div class="col-md-6 mb-1">
                                            <span class="badge badge-warning">{{ $warning['message'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        
                        @if($yearlyNoData->count() > 0)
                            <div class="mb-2">
                                <strong>Bulan tanpa data:</strong>
                                <div class="row mt-2">
                                    @foreach($yearlyNoData as $warning)
                                        <div class="col-md-4 mb-1">
                                            <span class="badge badge-danger">{{ $warning['message'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        
                        <div class="mt-3">
                            <a href="{{ route('rekap.pembelian.kelola-harga-gagas', ['tahun' => $selectedTahun]) }}" 
                               class="btn btn-info btn-sm">
                                <i class="fas fa-calendar-alt mr-1"></i> Kelola Harga Gagas Tahunan
                            </a>
                        </div>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif
    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filter Data</h3>
                    <div>
                        <a href="{{ route('rekap.pembelian.kelola-harga-gagas', ['tahun' => $selectedTahun, 'bulan' => $selectedBulan]) }}" 
                           class="btn btn-warning btn-sm mr-2">
                            <i class="fas fa-cog mr-1"></i> Kelola Harga Gagas
                        </a>
                        <a href="{{ route('rekap.pembelian.cetak', ['tahun' => $selectedTahun]) }}" 
                           class="btn btn-success btn-sm" target="_blank">
                            <i class="fas fa-print mr-1"></i> Cetak Rekap Pembelian
                        </a>
                    </div>
                </div>
                <div class="card-body py-3">
                    <form action="{{ route('rekap.pembelian.index') }}" method="GET" class="d-flex align-items-center">
                        <div class="d-flex align-items-center mr-4">
                            <label for="tahun" class="mb-0 mr-2 font-weight-bold">Tahun:</label>
                            <select name="tahun" id="tahun" class="form-control form-control-sm" style="width: 100px;"
                                onchange="this.form.submit()">
                                @foreach ($availableYears as $year)
                                    <option value="{{ $year }}" {{ $selectedTahun == $year ? 'selected' : '' }}>
                                        {{ $year }}</option>
                                @endforeach
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

    <!-- Yearly Summary -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title">Rekap Pembelian Tahunan - {{ $selectedTahun }}</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-truck-loading"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text text-white">Total Pengambilan (SM3)</span>
                                    <span
                                        class="info-box-number">{{ number_format($pembelianTahunanData['total_pengambilan'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-shopping-cart"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text text-white">Total Pembelian (Rp)</span>
                                    <span
                                        class="info-box-number text-white">{{ number_format($pembelianTahunanData['total_pembelian']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Grafik Tahunan -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-truck-loading mr-1"></i> Pengambilan Bulanan
                                        (SM3) - {{ $selectedTahun }}</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="yearlyUsageChart"
                                        style="min-height: 250px; height: 250px; max-height: 300px; max-width: 100%;"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card card-outline card-success">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-shopping-cart mr-1"></i> Pembelian Bulanan
                                        (Juta Rp) - {{ $selectedTahun }}</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="yearlySalesChart"
                                        style="min-height: 250px; height: 250px; max-height: 300px; max-width: 100%;"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Summary -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h3 class="card-title">Rekap Pembelian Bulanan - {{ date('F', mktime(0, 0, 0, $selectedBulan, 1)) }}
                        {{ $selectedTahun }}</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-truck-loading"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text text-white">Total Pengambilan (SM3)</span>
                                    <span
                                        class="info-box-number text-white">{{ number_format($pembelianBulananData['total_pengambilan'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-shopping-cart"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text text-white">Total Pembelian (Rp)</span>
                                    <span
                                        class="info-box-number text-white">{{ number_format($pembelianBulananData['total_pembelian']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Grafik Bulanan -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card card-outline card-info">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-truck-loading mr-1"></i> Pengambilan Harian (SM3)
                                        - {{ date('F', mktime(0, 0, 0, $selectedBulan, 1)) }}</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="monthlyUsageChart"
                                        style="min-height: 250px; height: 250px; max-height: 300px; max-width: 100%;"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card card-outline card-success">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-shopping-cart mr-1"></i> Pembelian Harian
                                        (Juta Rp) - {{ date('F', mktime(0, 0, 0, $selectedBulan, 1)) }}</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="monthlySalesChart"
                                        style="min-height: 250px; height: 250px; max-height: 300px; max-width: 100%;"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Details Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h3 class="card-title"><i class="fas fa-users mr-1"></i>Data Pembelian Per Customer</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th class="text-primary">Nama Customer</th>
                                <th class="text-primary">Tipe</th>
                                <th class="text-primary">Total Pengambilan (SM3)</th>
                                <th class="text-primary">Total Pembelian (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (count($customerPembelianData) > 0)
                                @foreach ($customerPembelianData as $data)
                                    <tr>
                                        <td>{{ $data['nama'] }}</td>
                                        <td>
                                            @if ($data['role'] == 'customer')
                                                <span class="badge badge-primary">Customer</span>
                                            @else
                                                <span class="badge badge-warning">FOB</span>
                                            @endif
                                        </td>
                                        <td>
                                            <p class="mb-0">Tahun: {{ number_format($data['pengambilan_tahun'], 2) }}</p>
                                            <small class="text-info">Bulan:
                                                {{ number_format($data['pengambilan_bulan'], 2) }}</small>
                                        </td>
                                        <td>
                                            <p class="mb-0">Tahun: {{ number_format($data['pembelian_tahun']) }}</p>
                                            <small class="text-success">Bulan:
                                                {{ number_format($data['pembelian_bulan']) }}</small>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="4" class="text-center">Tidak ada data untuk ditampilkan.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Periksa apakah Chart.js dimuat
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded!');
                return;
            }

            // Persiapkan data grafik dengan aman
            var yearlyChartData = @json($yearlyChartData) || {
                pengambilan: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                pembelian: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
            };
            var monthlyChartData = @json($monthlyChartData) || {
                pengambilan: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                    0, 0, 0
                ],
                pembelian: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                    0, 0, 0
                ]
            };
            // Warna dan gradien untuk grafik
            var months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
            var daysInMonth = new Date({{ $selectedTahun }}, {{ $selectedBulan }}, 0).getDate();
            var days = Array.from({
                length: daysInMonth
            }, (_, i) => i + 1);

            // Ciptakan gradien untuk grafik pengambilan
            var yearlyUsageCtx = document.getElementById('yearlyUsageChart');
            if (yearlyUsageCtx) {
                try {
                    var gradient1 = yearlyUsageCtx.getContext('2d').createLinearGradient(0, 0, 0, 250);
                    gradient1.addColorStop(0, 'rgba(66, 133, 244, 0.6)');
                    gradient1.addColorStop(1, 'rgba(66, 133, 244, 0.1)');

                    // Grafik pengambilan tahunan
                    new Chart(yearlyUsageCtx.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: months,
                            datasets: [{
                                label: 'Pengambilan (SM3)',
                                data: yearlyChartData.pengambilan || [],
                                borderColor: 'rgba(66, 133, 244, 1)',
                                backgroundColor: gradient1,
                                pointRadius: 4,
                                pointBackgroundColor: 'rgba(66, 133, 244, 1)',
                                pointBorderColor: '#fff',
                                pointHoverRadius: 6,
                                pointHoverBackgroundColor: '#fff',
                                pointHoverBorderColor: 'rgba(66, 133, 244, 1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    bodyFont: {
                                        size: 14
                                    },
                                    callbacks: {
                                        label: function(context) {
                                            return 'Pengambilan: ' + context.parsed.y.toLocaleString(
                                                'id-ID') + ' SM³';
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(200, 200, 200, 0.2)'
                                    },
                                    ticks: {
                                        font: {
                                            size: 12
                                        },
                                        callback: function(value) {
                                            return value.toLocaleString('id-ID');
                                        }
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        font: {
                                            size: 12
                                        }
                                    }
                                }
                            }
                        }
                    });
                    console.log('Yearly usage chart initialized successfully');
                } catch (error) {
                    console.error('Error initializing yearly usage chart:', error);
                }
            }

            // Grafik pembelian tahunan
            var yearlySalesCtx = document.getElementById('yearlySalesChart');
            if (yearlySalesCtx) {
                try {
                    var gradient2 = yearlySalesCtx.getContext('2d').createLinearGradient(0, 0, 0, 250);
                    gradient2.addColorStop(0, 'rgba(40, 167, 69, 0.6)');
                    gradient2.addColorStop(1, 'rgba(40, 167, 69, 0.1)');

                    new Chart(yearlySalesCtx.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: months,
                            datasets: [{
                                label: 'Pembelian (Juta Rp)',
                                data: yearlyChartData.pembelian || [],
                                borderColor: 'rgba(40, 167, 69, 1)',
                                backgroundColor: gradient2,
                                pointRadius: 4,
                                pointBackgroundColor: 'rgba(40, 167, 69, 1)',
                                pointBorderColor: '#fff',
                                pointHoverRadius: 6,
                                pointHoverBackgroundColor: '#fff',
                                pointHoverBorderColor: 'rgba(40, 167, 69, 1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    bodyFont: {
                                        size: 14
                                    },
                                    callbacks: {
                                        label: function(context) {
                                            return 'Pembelian: Rp ' + context.parsed.y.toLocaleString(
                                                'id-ID') + ' Juta';
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(200, 200, 200, 0.2)'
                                    },
                                    ticks: {
                                        font: {
                                            size: 12
                                        },
                                        callback: function(value) {
                                            return 'Rp ' + value.toLocaleString('id-ID') + ' Jt';
                                        }
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        font: {
                                            size: 12
                                        }
                                    }
                                }
                            }
                        }
                    });
                    console.log('Yearly sales chart initialized successfully');
                } catch (error) {
                    console.error('Error initializing yearly sales chart:', error);
                }
            }

            // Grafik pengambilan bulanan (harian)
            var monthlyUsageCtx = document.getElementById('monthlyUsageChart');
            if (monthlyUsageCtx) {
                try {
                    var gradient3 = monthlyUsageCtx.getContext('2d').createLinearGradient(0, 0, 0, 250);
                    gradient3.addColorStop(0, 'rgba(23, 162, 184, 0.6)');
                    gradient3.addColorStop(1, 'rgba(23, 162, 184, 0.1)');

                    new Chart(monthlyUsageCtx.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: days,
                            datasets: [{
                                label: 'Pengambilan Harian (SM3)',
                                data: monthlyChartData.pengambilan ? monthlyChartData.pengambilan.slice(
                                    0, daysInMonth) : [],
                                borderColor: 'rgba(23, 162, 184, 1)',
                                backgroundColor: gradient3,
                                pointRadius: 3,
                                pointBackgroundColor: 'rgba(23, 162, 184, 1)',
                                pointBorderColor: '#fff',
                                pointHoverRadius: 5,
                                pointHoverBackgroundColor: '#fff',
                                pointHoverBorderColor: 'rgba(23, 162, 184, 1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    bodyFont: {
                                        size: 14
                                    },
                                    callbacks: {
                                        title: function(tooltipItems) {
                                            return 'Tanggal: ' + tooltipItems[0].label + ' ' + ['Jan',
                                                'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags',
                                                'Sep', 'Okt', 'Nov', 'Des'
                                            ][{{ $selectedBulan }} - 1];
                                        },
                                        label: function(context) {
                                            return 'Pengambilan: ' + context.parsed.y.toLocaleString(
                                                'id-ID') + ' SM³';
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(200, 200, 200, 0.2)'
                                    },
                                    ticks: {
                                        font: {
                                            size: 12
                                        },
                                        callback: function(value) {
                                            return value.toLocaleString('id-ID');
                                        }
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        font: {
                                            size: 11
                                        },
                                        maxRotation: 0,
                                        autoSkip: true,
                                        maxTicksLimit: 15
                                    }
                                }
                            }
                        }
                    });
                    console.log('Monthly usage chart initialized successfully');
                } catch (error) {
                    console.error('Error initializing monthly usage chart:', error);
                }
            }

            // Grafik pembelian bulanan (harian)
            var monthlySalesCtx = document.getElementById('monthlySalesChart');
            if (monthlySalesCtx) {
                try {
                    var gradient4 = monthlySalesCtx.getContext('2d').createLinearGradient(0, 0, 0, 250);
                    gradient4.addColorStop(0, 'rgba(40, 167, 69, 0.6)');
                    gradient4.addColorStop(1, 'rgba(40, 167, 69, 0.1)');

                    new Chart(monthlySalesCtx.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: days,
                            datasets: [{
                                label: 'Pembelian Harian (Juta Rp)',
                                data: monthlyChartData.pembelian ? monthlyChartData.pembelian.slice(
                                    0, daysInMonth) : [],
                                borderColor: 'rgba(40, 167, 69, 1)',
                                backgroundColor: gradient4,
                                pointRadius: 3,
                                pointBackgroundColor: 'rgba(40, 167, 69, 1)',
                                pointBorderColor: '#fff',
                                pointHoverRadius: 5,
                                pointHoverBackgroundColor: '#fff',
                                pointHoverBorderColor: 'rgba(40, 167, 69, 1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    bodyFont: {
                                        size: 14
                                    },
                                    callbacks: {
                                        title: function(tooltipItems) {
                                            return 'Tanggal: ' + tooltipItems[0].label + ' ' + ['Jan',
                                                'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags',
                                                'Sep', 'Okt', 'Nov', 'Des'
                                            ][{{ $selectedBulan }} - 1];
                                        },
                                        label: function(context) {
                                            return 'Pembelian: Rp ' + context.parsed.y.toLocaleString(
                                                'id-ID') + ' Juta';
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(200, 200, 200, 0.2)'
                                    },
                                    ticks: {
                                        font: {
                                            size: 12
                                        },
                                        callback: function(value) {
                                            return 'Rp ' + value.toLocaleString('id-ID') + ' Jt';
                                        }
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        font: {
                                            size: 11
                                        },
                                        maxRotation: 0,
                                        autoSkip: true,
                                        maxTicksLimit: 15
                                    }
                                }
                            }
                        }
                    });
                    console.log('Monthly sales chart initialized successfully');
                } catch (error) {
                    console.error('Error initializing monthly sales chart:', error);
                }
            }
        });
    </script>
@endpush
