@extends('layouts.app')

@section('title', 'Demo Customer')

@section('page-title', 'Demo Customer Dashboard')

@section('content')
    <div class="container-fluid">
        <div class="row">
            {{-- Notifications Section --}}
            <div class="col-12">
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

            {{-- Customer Summary Card --}}
            <div class="col-md-12">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-circle mr-2"></i>
                            Informasi Customer: {{ Auth::user()->name }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-tachometer-alt mr-1"></i> Total Pemakaian</strong>
                                    <p class="text-muted mb-0">
                                        {{ number_format($totalVolumeSm3 ?? 0, 2) }} Sm³
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-money-bill-wave mr-1"></i> Total Pembelian</strong>
                                    <p class="text-muted mb-0">
                                        Rp {{ number_format(Auth::user()->total_purchases ?? 0, 2) }}
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-wallet mr-1"></i> Jumlah Deposit</strong>
                                    <p class="text-muted mb-0">
                                        Rp {{ number_format(Auth::user()->total_deposit ?? 0, 2) }}
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-wallet mr-1"></i> Saldo</strong>
                                    <p class="text-muted mb-0">
                                        Rp
                                        {{ number_format((Auth::user()->total_deposit ?? 0) - (Auth::user()->total_purchases ?? 0), 2) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Period Information Card --}}
            <div class="col-md-12">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line mr-2"></i>
                            Informasi Periode:
                            {{ \Carbon\Carbon::createFromDate($selectedTahun ?? date('Y'), $selectedBulan ?? date('m'), 1)->format('F Y') }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-tachometer-alt mr-1"></i> Tekanan </strong>
                                    <p class="text-muted mb-0">
                                        {{ number_format(Auth::user()->tekanan_keluar ?? 0, 2) }}
                                        Bar
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-thermometer-half mr-1"></i> Suhu</strong>
                                    <p class="text-muted mb-0">
                                        {{ number_format(Auth::user()->suhu ?? 0, 2) }} °C
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-tachometer-alt mr-1"></i> Koreksi Meter</strong>
                                    <p class="text-muted mb-0">
                                        {{ number_format(Auth::user()->koreksi_meter ?? 1, 4) }}
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-money-bill-wave mr-1"></i> Harga per Sm³</strong>
                                    <p class="text-muted mb-0">
                                        Rp
                                        {{ number_format(Auth::user()->harga_per_meter_kubik ?? 0, 2) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-4 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-gas-pump mr-1"></i> Volume Periode Ini</strong>
                                    <p class="text-muted mb-0">
                                        {{ number_format($filteredVolumeSm3 ?? 0, 2) }} Sm³
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-money-bill-wave mr-1"></i> Pembelian Periode Ini</strong>
                                    <p class="text-muted mb-0">
                                        Rp {{ number_format($filteredTotalPurchases ?? 0, 2) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filter Form --}}
            <div class="col-md-12">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-filter mr-1"></i>
                            Filter Periode
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('demo.filter') }}" method="POST" id="filter-form">
                            @csrf
                            <input type="hidden" name="view_type" value="customer">
                            <div class="row">
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label>Bulan:</label>
                                        <select class="form-control" name="bulan" id="bulan">
                                            @for ($i = 1; $i <= 12; $i++)
                                                <option value="{{ $i }}"
                                                    {{ isset($selectedBulan) && $selectedBulan == $i ? 'selected' : ($i == date('n') && !isset($selectedBulan) ? 'selected' : '') }}>
                                                    {{ \Carbon\Carbon::create(null, $i, 1)->format('F') }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label>Tahun:</label>
                                        <select class="form-control" name="tahun" id="tahun">
                                            @for ($year = date('Y'); $year >= 2020; $year--)
                                                <option value="{{ $year }}"
                                                    {{ isset($selectedTahun) && $selectedTahun == $year ? 'selected' : ($year == date('Y') && !isset($selectedTahun) ? 'selected' : '') }}>
                                                    {{ $year }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <div class="d-flex flex-column flex-md-row">
                                            <button type="submit" class="btn btn-primary mb-2 mb-md-0 mr-md-2">
                                                <i class="fas fa-search mr-1"></i> Terapkan Filter
                                            </button>
                                            <a href="{{ route('demo.customer') }}" class="btn btn-default">
                                                <i class="fas fa-sync-alt mr-1"></i> Reset
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Data Pencatatan Table --}}
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list-alt mr-2"></i>
                            Riwayat Pencatatan
                        </h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-bordered table-striped" id="dataPencatatanTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th colspan="2">Pembacaan Awal</th>
                                    <th colspan="2">Pembacaan Akhir</th>
                                    <th>Volume</th>
                                    <th>Sm³</th>
                                    <th>Harga</th>
                                </tr>
                                <tr>
                                    <th></th>
                                    <th>Tanggal</th>
                                    <th>Meter</th>
                                    <th>Tanggal</th>
                                    <th>Meter</th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $no = 1; @endphp
                                @foreach ($dataPencatatan as $item)
                                    @php
                                        $dataInput = is_string($item->data_input)
                                            ? json_decode($item->data_input, true)
                                            : (is_array($item->data_input)
                                                ? $item->data_input
                                                : []);

                                        $pembacaanAwal = $dataInput['pembacaan_awal'] ?? [
                                            'volume' => 0,
                                            'tanggal' => null,
                                        ];
                                        $pembacaanAkhir = $dataInput['pembacaan_akhir'] ?? [
                                            'volume' => 0,
                                            'tanggal' => null,
                                        ];
                                        $volumeFlowMeter = $dataInput['volume_flow_meter'] ?? 0;

                                        // Hitung Volume Sm³
                                        $volumeSm3 = $volumeFlowMeter * (Auth::user()->koreksi_meter ?? 1);

                                        // Hitung Pembelian
                                        $pembelian = $volumeSm3 * (Auth::user()->harga_per_meter_kubik ?? 0);

                                        // Get the timestamp for data-filter attribute
                                        $waktuAwalTimestamp = strtotime($dataInput['pembacaan_awal']['waktu'] ?? '');
                                        $tanggalAwalFilter = $waktuAwalTimestamp
                                            ? date('Y-m-d', $waktuAwalTimestamp)
                                            : '';
                                    @endphp
                                    <tr data-tanggal-awal="{{ $tanggalAwalFilter }}">
                                        <td>{{ $no++ }}</td>
                                        <td>{{ \Carbon\Carbon::parse($dataInput['pembacaan_awal']['waktu'] ?? now())->format('d M Y H:i') }}
                                        </td>
                                        <td>{{ number_format($pembacaanAwal['volume'] ?? 0, 2) }} m³</td>
                                        <td>{{ \Carbon\Carbon::parse($dataInput['pembacaan_akhir']['waktu'] ?? now())->format('d M Y H:i') }}
                                        </td>
                                        <td>{{ number_format($pembacaanAkhir['volume'] ?? 0, 2) }} m³</td>
                                        <td>{{ number_format($volumeFlowMeter, 2) }} m³</td>
                                        <td>{{ number_format($volumeSm3, 2) }}</td>
                                        <td>Rp {{ number_format($pembelian, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <style>
        .table-bordered th,
        .table-bordered td {
            vertical-align: middle !important;
        }

        /* Highlight filtered rows */
        tr.filtered-row {
            background-color: #e8f4ff !important;
        }

        /* Mobile Summary Card Styling */
        .mobile-summary-card {
            padding: 15px;
            margin-bottom: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .mobile-summary-card strong {
            display: block;
            margin-bottom: 5px;
            color: #495057;
        }

        .mobile-summary-card p {
            font-size: 1.2rem;
            font-weight: 500;
        }

        /* Responsive adjustments */
        @media (max-width: 767.98px) {
            .card-title {
                font-size: 1.1rem;
            }

            .mobile-summary-card {
                padding: 10px;
            }

            .card-body {
                padding: 0.75rem;
            }
        }
    </style>
@endsection

@section('js')
    <script>
        $(function() {
            // DataTables initialization
            var table = $("#dataPencatatanTable").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "ordering": true,
                "order": [
                    [1, 'desc']
                ],
                "language": {
                    "emptyTable": "Tidak ada data pencatatan tersedia",
                    "zeroRecords": "Tidak ada data yang cocok ditemukan",
                    "info": "Menampilkan _START_ hingga _END_ dari _TOTAL_ entri",
                    "infoEmpty": "Menampilkan 0 hingga 0 dari 0 entri",
                    "infoFiltered": "(disaring dari _MAX_ total entri)",
                    "search": "Cari:",
                    "paginate": {
                        "first": "Pertama",
                        "last": "Terakhir",
                        "next": "Selanjutnya",
                        "previous": "Sebelumnya"
                    }
                }
            });

            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
@endsection
