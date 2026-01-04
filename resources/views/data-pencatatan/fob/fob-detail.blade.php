@extends('layouts.app')

@section('title', 'Detail Data Pencatatan FOB')

@section('page-title', 'Data Pencatatan - ' . $customer->name)

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

                {{-- Notifikasi Bulan Tanpa Harga --}}
                @if (isset($monthsWithoutPricing) && count($monthsWithoutPricing) > 0)
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-exclamation-triangle mr-3 mt-1" style="font-size: 1.5rem;"></i>
                            <div class="flex-grow-1">
                                <strong><i class="fas fa-bell mr-1"></i> Perhatian! Harga Belum Diinput</strong>
                                <p class="mb-2 mt-1">
                                    Terdapat <strong>{{ count($monthsWithoutPricing) }} bulan</strong> yang memiliki data pencatatan tetapi belum diinputkan harganya.
                                    Harga untuk bulan-bulan tersebut saat ini adalah <strong class="text-danger">Rp 0</strong>.
                                </p>
                                <div class="row">
                                    @foreach ($monthsWithoutPricing as $monthData)
                                        <div class="col-md-3 col-sm-6 mb-2">
                                            <a href="{{ route('data-pencatatan.fob-detail', [
                                                'customer' => $customer->id,
                                                'bulan' => $monthData['month'],
                                                'tahun' => $monthData['year']
                                            ]) }}"
                                               class="btn btn-outline-warning btn-sm btn-block text-left"
                                               onclick="event.preventDefault(); navigateAndOpenPricing('{{ $monthData['month'] }}', '{{ $monthData['year'] }}');">
                                                <i class="fas fa-calendar-alt mr-1"></i>
                                                {{ $monthData['display'] }}
                                                <i class="fas fa-arrow-right float-right mt-1"></i>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                                <small class="text-muted mt-2 d-block">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Klik pada bulan untuk langsung menginput harga pada periode tersebut.
                                </small>
                            </div>
                        </div>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
            </div>

            {{-- FOB Summary Card --}}
            <div class="col-md-12">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-circle mr-2"></i>
                            Informasi FOB: {{ $customer->name }}
                        </h3>
                        <div class="card-tools">
                            @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
                                <button class="btn btn-info btn-sm mr-2" data-toggle="modal"
                                    data-target="#pricingHistoryModal">
                                    <i class="fas fa-history mr-1"></i> Riwayat Harga
                                </button>
                                <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#setPricingModal">
                                    <i class="fas fa-cog mr-1"></i> Atur Harga
                                </button>
                                <button class="btn btn-success btn-sm" data-toggle="modal"
                                    data-target="#depositHistoryModal">
                                    <i class="fas fa-money-bill-alt mr-1"></i> History Deposit
                                </button>
                                <a href="{{ route('rekap-pengambilan.create-with-customer', $customer->id) }}?return_to_fob=1"
                                    class="btn btn-info btn-sm">
                                    <i class="fas fa-plus mr-1"></i> Tambah Data
                                </a>
                                <a href="{{ route('data-pencatatan.fob.sync-data', $customer->id) }}"
                                    class="btn btn-warning btn-sm"
                                    onclick="return confirm('Sinkronisasi akan memastikan semua data di rekap-pengambilan juga ada di data-pencatatan. Lanjutkan?')">
                                    <i class="fas fa-sync-alt mr-1"></i> Sinkronkan Data
                                </a>
                                <div class="btn-group">

                                    <div class="dropdown-menu">
                                        <button class="dropdown-item" onclick="analyzeAndFixData()">
                                            <i class="fas fa-search mr-1"></i> Analisis & Perbaiki Data
                                        </button>
                                        <button class="dropdown-item" onclick="debugDataSync()">
                                            <i class="fas fa-bug mr-1"></i> Debug Data Sync
                                        </button>
                                        <div class="dropdown-divider"></div>
                                        <small class="dropdown-header">Gunakan dengan hati-hati</small>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        @if ($selectedBulan == date('m') && $selectedTahun == date('Y'))
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Periode Saat Ini:</strong> Data yang ditampilkan hanya mencakup aktivitas FOB dalam
                                bulan
                                {{ \Carbon\Carbon::createFromDate($selectedTahun, $selectedBulan, 1)->format('F Y') }}.
                                Jika ada ketidaksesuaian, gunakan tombol <strong>"Sinkronkan Data Periode Ini"</strong>
                                untuk memastikan semua data sudah sinkron.
                            </div>
                        @endif

                        @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
                            @php
                                // Periksa perbedaan saldo antara total saldo dan saldo bulan terakhir
                                $monthlyBalances = $customer->monthly_balances ?: [];
                                $currentYearMonth = \Carbon\Carbon::now()->format('Y-m');
                                $latestMonthBalance = isset($monthlyBalances[$currentYearMonth])
                                    ? floatval($monthlyBalances[$currentYearMonth])
                                    : null;
                                $currentTotalBalance =
                                    ($customer->total_deposit ?? 0) - ($customer->total_purchases ?? 0);
                                $hasSaldoDifference =
                                    $latestMonthBalance !== null &&
                                    abs($currentTotalBalance - $latestMonthBalance) > 100;
                            @endphp

                            {{-- Menghilangkan peringatan perbedaan saldo dan tombol sinkronisasi --}}
                        @endif
                        <div class="row">
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-tachometer-alt mr-1"></i> Total Pemakaian</strong>
                                    <p class="text-muted mb-0">
                                        {{ number_format($totalVolumeSm3, 2) }} Sm³
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-money-bill-wave mr-1"></i> Total Pembelian</strong>
                                    <p class="text-muted mb-0">
                                        Rp {{ number_format($customer->total_purchases ?? 0, 0) }}
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-wallet mr-1"></i> Total Deposit</strong>
                                    <p class="text-muted mb-0">
                                        Rp {{ number_format($customer->total_deposit ?? 0, 0) }}
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-wallet mr-1"></i> Saldo</strong>
                                    <p class="text-muted mb-0">
                                        Rp
                                        {{ number_format(($customer->total_deposit ?? 0) - ($customer->total_purchases ?? 0), 0) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Yearly Period Information Card --}}
            <div class="col-md-12">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            Informasi Periode Tahunan: {{ $selectedTahun }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-tachometer-alt mr-1"></i> Total Pemakaian Tahunan</strong>
                                    <p class="text-muted mb-0">
                                        {{ number_format($totalPemakaianTahunan, 2) }} Sm³
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-money-bill-wave mr-1"></i> Total Pembelian Tahunan</strong>
                                    <p class="text-muted mb-0">
                                        Rp {{ number_format($totalPembelianTahunan, 0) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Mobile-optimized Filter Form --}}
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
                        <form action="{{ route('data-pencatatan.fob.filter-month-year', $customer->id) }}" method="POST"
                            id="filter-form">
                            @csrf
                            <div class="row">
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label>Bulan:</label>
                                        <select class="form-control" name="bulan" id="bulan">
                                            @for ($i = 1; $i <= 12; $i++)
                                                <option value="{{ $i }}"
                                                    {{ $selectedBulan == $i ? 'selected' : '' }}>
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
                                                    {{ $selectedTahun == $year ? 'selected' : '' }}>
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
                                            <a href="{{ route('data-pencatatan.fob-detail', $customer->id) }}"
                                                class="btn btn-default">
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

            {{-- Period Information Card (Bulanan) --}}
            <div class="col-md-12">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line mr-2"></i>
                            Informasi Periode:
                            {{ \Carbon\Carbon::createFromDate($selectedTahun, $selectedBulan, 1)->format('F Y') }}
                            @if ($selectedBulan == date('m') && $selectedTahun == date('Y'))
                                <span class="badge badge-success ml-2">Periode Saat Ini</span>
                            @endif
                        </h3>
                        {{-- Menghilangkan tombol sinkronisasi dari card-tools --}}
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-money-bill-wave mr-1"></i> Harga per Sm³</strong>
                                    <p class="text-muted mb-0">
                                        Rp
                                        {{ number_format($pricingInfo['harga_per_meter_kubik'] ?? ($customer->harga_per_meter_kubik ?? 0), 2) }}
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-gas-pump mr-1"></i> Volume Periode Ini</strong>
                                    <p class="text-muted mb-0">
                                        {{ number_format($filteredVolumeSm3, 2) }} Sm³
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-money-bill-wave mr-1"></i> Pembelian Periode Ini</strong>
                                    <p class="text-muted mb-0">
                                        Rp {{ number_format($filteredTotalPurchases, 0) }}
                                        <span class="badge badge-success"
                                            title="Perhitungan real-time menggunakan pricing sesuai periode"><i
                                                class="fas fa-sync-alt"></i> Real-time</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6 col-sm-12">
                                <div class="info-box enhanced-info-box"
                                    style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                                    <span class="info-box-icon bg-info"><i class="fas fa-calculator"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text d-flex justify-content-between align-items-center">
                                            <span>Informasi Saldo Bulan
                                                {{ \Carbon\Carbon::createFromDate($selectedTahun, $selectedBulan, 1)->format('F Y') }}</span>
                                        </span>
                                        <div class="table-responsive mt-2">
                                            {{-- PERBAIKAN: Gunakan data dari Controller, tidak ada perhitungan di View --}}
                                            <table class="table table-sm table-bordered">
                                                <tr>
                                                    <td width="60%">Saldo Bulan Sebelumnya</td>
                                                    <td>Rp {{ number_format($prevMonthBalance, 0) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>+ Deposit Bulan Ini</td>
                                                    <td>Rp {{ number_format($filteredTotalDeposits, 0) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>- Pembelian Bulan Ini</td>
                                                    <td>Rp {{ number_format($filteredTotalPurchases, 0) }}</td>
                                                </tr>
                                                <tr class="font-weight-bold">
                                                    <td>= Sisa Saldo Periode Bulan Ini</td>
                                                    <td>Rp {{ number_format($currentMonthBalance, 0) }}</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2" class="text-muted"><small>* Saldo ini hanya
                                                            menunjukkan saldo untuk periode
                                                            {{ \Carbon\Carbon::createFromDate($selectedTahun, $selectedBulan, 1)->format('F Y') }}
                                                            saja (Perhitungan dari Controller)</small></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Data Pencatatan Table for FOB (simplified) --}}
            <div class="col-md-12">
                <div class="card print-table-card">


                    {{-- Print Info - Only visible when printing --}}

                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list-alt mr-2"></i>
                            Riwayat Pencatatan
                        </h3>
                        <div class="card-tools">
                            <button onclick="openPrintPage()" class="btn btn-info btn-sm" title="Cetak Tabel">
                                <i class="fas fa-print mr-1"></i> Print Tabel
                            </button>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-bordered table-striped custom-datatable" id="dataPencatatanTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>No Pol</th>
                                    <th>Volume Sm³</th>
                                    <th>Alamat Pengambilan</th>
                                    <th>Rupiah</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $no = 1; @endphp
                                @foreach ($dataPencatatan as $item)
                                    @php
                                        // PERBAIKAN: Gunakan data dari RekapPengambilan langsung
                                        $volumeSm3 = floatval($item->volume);
                                        $tanggalItem = \Carbon\Carbon::parse($item->tanggal);
                                        $tanggalFilter = $tanggalItem->format('Y-m-d');
                                        $waktuYearMonth = $tanggalItem->format('Y-m');

                                        // Ambil pricing info berdasarkan tanggal spesifik
                                        $itemPricingInfo = $customer->getPricingForYearMonth(
                                            $waktuYearMonth,
                                            $tanggalItem,
                                        );

                                        // Hitung Pembelian dengan harga sesuai periode
                                        $hargaPerM3 = floatval(
                                            $itemPricingInfo['harga_per_meter_kubik'] ??
                                                $customer->harga_per_meter_kubik,
                                        );
                                        $pembelian = $volumeSm3 * $hargaPerM3;
                                    @endphp
                                    <tr data-tanggal="{{ $tanggalFilter }}">
                                        <td>{{ $no++ }}</td>
                                        <td>{{ $tanggalItem->format('d M Y H:i') }}</td>
                                        <td>{{ $item->nopol ?? '-' }}</td>
                                        <td>{{ number_format($volumeSm3, 2) }}</td>
                                        <td>{{ $item->alamat_pengambilan ?? '-' }}</td>
                                        <td>Rp {{ number_format($pembelian, 2) }}</td>
                                        <td>
                                            <div class="btn-group">
                                                @php
                                                    // PERBAIKAN: Ambil ID rekap yang tepat dari mapping
                                                    $rekapId = $rekapMapping[$tanggalFilter][$volumeSm3] ?? null;
                                                @endphp

                                                @if ($rekapId)
                                                    <a href="{{ route('rekap-pengambilan.show', $rekapId) }}"
                                                        class="btn btn-info btn-sm" title="Lihat Rekap Pengambilan">
                                                        <i class="fas fa-eye"></i>
                                                    </a>

                                                    @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
                                                        <a href="{{ route('rekap-pengambilan.edit', $rekapId) }}?return_to_fob=1"
                                                            class="btn btn-warning btn-sm" title="Edit Rekap Pengambilan">
                                                            <i class="fas fa-edit"></i>
                                                        </a>

                                                        <form action="{{ route('rekap-pengambilan.destroy', $rekapId) }}"
                                                            method="POST" class="d-inline"
                                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="return_to_fob" value="1">
                                                            <button type="submit" class="btn btn-danger btn-sm">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                @else
                                                    <!-- Fallback jika tidak ditemukan mapping -->
                                                    <a href="{{ route('rekap-pengambilan.find-by-date', [$customer->id, $tanggalFilter]) }}"
                                                        class="btn btn-info btn-sm" title="Lihat/Edit Rekap Pengambilan">
                                                        <i class="fas fa-eye"></i>
                                                    </a>

                                                    @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
                                                        <a href="{{ route('rekap-pengambilan.find-by-date', [$customer->id, $tanggalFilter]) }}"
                                                            class="btn btn-warning btn-sm" title="Edit Rekap Pengambilan">
                                                            <i class="fas fa-edit"></i>
                                                        </a>

                                                        <button class="btn btn-danger btn-sm" disabled
                                                            title="Data tidak ditemukan">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        @php
                            // PERBAIKAN: Hitung total volume dari RekapPengambilan yang difilter
                            $totalVolumeFiltered = $dataPencatatan->sum('volume');
                        @endphp
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-chart-bar text-info mr-2" style="font-size: 1.2em;"></i>
                                    <div>
                                        <strong>Total Volume Periode Ini:</strong>
                                        <span class="text-primary ml-2" style="font-size: 1.1em; font-weight: bold;">
                                            {{ number_format($totalVolumeFiltered, 2) }} Sm³
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 text-right">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Total dari {{ $dataPencatatan->count() }} data pencatatan
                                    @if ($selectedBulan && $selectedTahun)
                                        untuk periode
                                        {{ \Carbon\Carbon::createFromDate($selectedTahun, $selectedBulan, 1)->format('F Y') }}
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Deposit History Modal --}}
        @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
            <div class="modal fade" id="depositHistoryModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title" id="depositHistoryModalLabel">
                                <i class="fas fa-money-bill-alt mr-2"></i>History Deposit
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-6 col-sm-12 mb-3">
                                    <h5>Total Deposit: <span class="text-success">Rp
                                            {{ number_format($customer->total_deposit, 2) }}</span></h5>
                                    <h5>Total Pembelian: <span class="text-danger">Rp
                                            {{ number_format($customer->total_purchases, 2) }}</span></h5>
                                    <h5>Saldo Tersisa: <span class="text-primary">Rp
                                            {{ number_format($customer->total_deposit - $customer->total_purchases, 2) }}</span>
                                    </h5>
                                </div>
                                <div class="col-md-6 col-sm-12">
                                    <button class="btn btn-primary w-100 mb-2" data-toggle="modal"
                                        data-target="#tambahDepositModal">
                                        <i class="fas fa-plus mr-1"></i> Tambah Deposit
                                    </button>
                                    <button class="btn btn-warning w-100" data-toggle="modal"
                                        data-target="#penguranganSaldoModal">
                                        <i class="fas fa-minus mr-1"></i> Pengurangan Saldo
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="depositHistoryTable">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Tanggal</th>
                                            <th>Keterangan</th>
                                            <th>Jumlah Deposit</th>
                                            <th>Deskripsi</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            // Ensure deposit_history is an array before looping
                                            // Using the $depositHistory passed from controller, but double check it
                                            if (!isset($depositHistory) || !is_array($depositHistory)) {
                                                $depositHistory = $customer->deposit_history;
                                                if (is_string($depositHistory)) {
                                                    $depositHistory = json_decode($depositHistory, true) ?? [];
                                                }
                                                // If it's still not an array (could be null), make it an empty array
                                                if (!is_array($depositHistory)) {
                                                    $depositHistory = [];
                                                }
                                            }
                                        @endphp

                                        @php
                                            // Pastikan depositHistory selalu menjadi array
                                            // Ini adalah safety measure kedua selain yang sudah dilakukan di controller
                                            if (!isset($depositHistory) || !is_array($depositHistory)) {
                                                $depositHistory = [];
                                            }

                                            $no = 1;
                                            // Sort deposit history by date (newest first)
                                            $sortedDeposits = collect($depositHistory)
                                                ->map(function ($deposit, $index) {
                                                    return [
                                                        'index' => $index,
                                                        'date' => $deposit['date'] ?? '',
                                                        'amount' => $deposit['amount'] ?? 0,
                                                        'keterangan' => $deposit['keterangan'] ?? 'penambahan',
                                                        'deskripsi' => $deposit['deskripsi'] ?? ($deposit['description'] ?? '-'),
                                                    ];
                                                })
                                                ->sortByDesc('date')
                                                ->values();
                                        @endphp

                                        @foreach ($sortedDeposits as $deposit)
                                            <tr>
                                                <td>{{ $no++ }}</td>
                                                <td>
                                                    @if (isset($deposit['date']))
                                                        {{ date('d M Y H:i', strtotime($deposit['date'])) }}
                                                    @else
                                                        Tanggal tidak tersedia
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($deposit['keterangan'] === 'penambahan')
                                                        <span class="badge badge-success">Penambahan</span>
                                                    @else
                                                        <span class="badge badge-danger">Pengurangan</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($deposit['amount'] >= 0)
                                                        <span class="text-success">Rp {{ number_format($deposit['amount'] ?? 0, 2) }}</span>
                                                    @else
                                                        <span class="text-danger">Rp {{ number_format($deposit['amount'] ?? 0, 2) }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ $deposit['deskripsi'] ?? '-' }}</td>
                                                <td>
                                                    <button type="button" class="btn btn-warning btn-sm btn-edit-deposit"
                                                        data-index="{{ $deposit['index'] }}"
                                                        data-date="{{ \Carbon\Carbon::parse($deposit['date'])->format('Y-m-d\TH:i') }}"
                                                        data-amount="{{ abs($deposit['amount']) }}"
                                                        data-keterangan="{{ $deposit['keterangan'] }}"
                                                        data-deskripsi="{{ $deposit['deskripsi'] ?? '' }}"
                                                        title="Edit Deposit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form action="{{ route('customer.remove-deposit', $customer->id) }}"
                                                        method="POST" class="d-inline"
                                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus entry ini?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="deposit_index"
                                                            value="{{ $deposit['index'] }}">
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pengurangan Saldo Modal untuk FOB --}}
            <div class="modal fade" id="penguranganSaldoModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-minus-circle mr-2"></i>Pengurangan Saldo FOB
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form action="{{ route('customer.reduce-balance', $customer->id) }}" method="POST"
                            id="penguranganSaldoForm">
                            @csrf
                            <div class="modal-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    <strong>Saldo Saat Ini:</strong> Rp
                                    {{ number_format($customer->getCurrentBalance(), 2) }}
                                </div>

                                <div class="form-group">
                                    <label>Jumlah Pengurangan <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input type="number" step="0.01" name="amount" class="form-control"
                                            placeholder="Jumlah yang akan dikurangi" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Tanggal Pengurangan <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="reduction_date" class="form-control"
                                        value="{{ now()->format('Y-m-d\TH:i') }}" required>
                                </div>
                                <div class="form-group mb-0">
                                    <label>Deskripsi (Opsional)</label>
                                    <textarea name="description" class="form-control" placeholder="Alasan pengurangan saldo (opsional)" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                    <i class="fas fa-times mr-1"></i>Batal
                                </button>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-save mr-1"></i>Kurangi Saldo
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Tambah Deposit Modal --}}
            <div class="modal fade" id="tambahDepositModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-plus-circle mr-2"></i>Tambah Deposit
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form action="{{ route('customer.add-deposit', $customer->id) }}" method="POST"
                            id="tambahDepositForm">
                            @csrf
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>Jumlah Deposit <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input type="number" step="0.01" name="amount" class="form-control"
                                            placeholder="Jumlah deposit" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Tanggal Deposit <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="deposit_date" class="form-control"
                                        value="{{ now()->format('Y-m-d\TH:i') }}" required>
                                </div>
                                <div class="form-group mb-0">
                                    <label>Keterangan (Opsional)</label>
                                    <textarea name="description" class="form-control" placeholder="Keterangan deposit (opsional)" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                    <i class="fas fa-times mr-1"></i>Batal
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i>Simpan
                                </button>
                            </div>
                        </form>
                    </div>
                    </div>
                    </div>

        {{-- Edit Deposit Modal --}}
        <div class="modal fade" id="editDepositModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-edit mr-2"></i>Edit Deposit
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('customer.update-deposit', $customer->id) }}" method="POST" id="editDepositForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="deposit_index" id="edit_deposit_index">
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Tanggal <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="deposit_date" id="edit_deposit_date" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Keterangan <span class="text-danger">*</span></label>
                                <select name="keterangan" id="edit_keterangan" class="form-control" required>
                                    <option value="penambahan">Penambahan</option>
                                    <option value="pengurangan">Pengurangan</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Jumlah <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" step="0.01" name="amount" id="edit_amount" class="form-control" placeholder="Jumlah" required>
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                <label>Deskripsi (Opsional)</label>
                                <textarea name="description" id="edit_description" class="form-control" placeholder="Deskripsi (opsional)" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                <i class="fas fa-times mr-1"></i>Batal
                            </button>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save mr-1"></i>Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
            {{-- Riwayat Harga FOB Modal --}}
            <div class="modal fade" id="pricingHistoryModal" tabindex="-1" role="dialog"
                aria-labelledby="pricingHistoryModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title" id="pricingHistoryModalLabel">
                                <i class="fas fa-history mr-2"></i>Riwayat Harga
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <table class="table table-bordered table-striped" id="pricingHistoryTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Periode</th>
                                        <th>Harga per m³</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        // Ensure pricing_history is an array before looping
                                        $pricingHistory = $customer->pricing_history;
                                        if (is_string($pricingHistory)) {
                                            $pricingHistory = json_decode($pricingHistory, true) ?? [];
                                        }
                                        // If it's still not an array (could be null), make it an empty array
                                        if (!is_array($pricingHistory)) {
                                            $pricingHistory = [];
                                        }

                                        // Urutkan dari yang paling recent ke yang paling lama
                                        usort($pricingHistory, function($a, $b) {
                                            $dateA = $a['date'] ?? ($a['year_month'] ?? null);
                                            $dateB = $b['date'] ?? ($b['year_month'] ?? null);

                                            $timestampA = $dateA ? strtotime($dateA) : 0;
                                            $timestampB = $dateB ? strtotime($dateB) : 0;

                                            // Descending (terbaru dulu)
                                            return $timestampB - $timestampA;
                                        });

                                        $no = 1;
                                    @endphp

                                    @foreach ($pricingHistory as $pricing)
                                        <tr>
                                            <td>{{ $no++ }}</td>
                                            <td>
                                                @if (isset($pricing['date']))
                                                    {{ \Carbon\Carbon::parse($pricing['date'])->format('F Y') }}
                                                @else
                                                    Periode tidak tersedia
                                                @endif
                                            </td>
                                            <td>Rp {{ number_format($pricing['harga_per_meter_kubik'] ?? 0, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Modal untuk Setting Pricing (simplified for FOB) -->
        @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
            <div class="modal fade" id="setPricingModal" tabindex="-1" role="dialog"
                aria-labelledby="setPricingModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="setPricingModalLabel">
                                <i class="fas fa-cog mr-2"></i>Atur Harga FOB untuk Periode Baru
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form action="{{ route('fob.update-pricing', $customer->id) }}" method="POST">
                            @csrf
                            <div class="modal-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Pengaturan harga ini akan disimpan untuk periode yang dipilih dan akan
                                    berlaku untuk semua pencatatan pada periode tersebut.
                                </div>

                                <!-- Periode -->
                                <div class="form-group">
                                    <label for="pricingDate"><strong>Periode</strong></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                        </div>
                                        <input type="month" name="pricing_date" id="pricingDate"
                                            class="form-control @error('pricing_date') is-invalid @enderror"
                                            value="{{ now()->format('Y-m') }}" required>
                                        @error('pricing_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Harga per meter kubik -->
                                <div class="form-group">
                                    <label for="modalHargaPerM3"><strong>Harga per m³</strong></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input type="number" step="0.01" name="harga_per_meter_kubik"
                                            id="modalHargaPerM3"
                                            class="form-control @error('harga_per_meter_kubik') is-invalid @enderror"
                                            value="{{ old('harga_per_meter_kubik', $customer->harga_per_meter_kubik ?? 0) }}"
                                            placeholder="Masukkan harga per m³" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">/m³</span>
                                        </div>
                                        @error('harga_per_meter_kubik')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Menambahkan input hidden untuk tekanan keluar dan suhu -->
                                <input type="hidden" name="tekanan_keluar" value="0">
                                <input type="hidden" name="suhu" value="0">
                            </div>

                            <!-- Footer dengan tombol aksi -->
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                    <i class="fas fa-times mr-1"></i>Batal
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i>Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@section('css')
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .table-bordered th,
        .table-bordered td {
            vertical-align: middle !important;
        }

        /* Highlight filtered rows */
        tr.filtered-row {
            background-color: #e8f4ff !important;
        }

        /* Responsive styling */
        @media (max-width: 767.98px) {
            #setPricingModal .modal-dialog {
                margin: 0.5rem;
            }

            #setPricingModal .col-md-6 {
                margin-bottom: 1rem;
            }
        }

        /* Styling untuk info-box */
        .info-box {
            display: flex;
            min-height: 80px;
            background: #fff;
            width: 100%;
            box-shadow: 0 1px 1px rgba(0, 0, 0, .1);
            border-radius: 0.25rem;
            margin-bottom: 1rem;
        }

        .info-box-icon {
            border-top-left-radius: 0.25rem;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            border-bottom-left-radius: 0.25rem;
            display: block;
            width: 80px;
            height: 100%;
            text-align: center;
            font-size: 30px;
            line-height: 80px;
            background: rgba(0, 0, 0, 0.2);
        }

        .bg-info {
            background-color: #17a2b8 !important;
            color: white;
        }

        .info-box-content {
            padding: 5px 10px;
            flex: 1;
        }

        .info-box-text {
            font-size: 14px;
            font-weight: bold;
            margin-top: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Styling tambahan untuk tabel */
        .table-sm td,
        .table-sm th {
            padding: 0.3rem;
        }

        .table-bordered {
            border: 1px solid #dee2e6;
        }

        /* Helper classes */
        .mt-2 {
            margin-top: 0.5rem !important;
        }

        .mt-3 {
            margin-top: 1rem !important;
        }

        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .font-weight-bold {
            font-weight: bold !important;
        }

        .text-muted {
            color: #6c757d !important;
        }

        /* Memperbaiki tampilan pada perangkat mobile */
        @media (max-width: 767.98px) {
            .enhanced-info-box {
                margin-bottom: 1.5rem;
            }

            .info-box-icon {
                width: 60px;
                font-size: 24px;
                line-height: 60px;
            }
        }

        /* FORCE CACHE BUST - Print-specific styles v2.0 */
        @media print {

            /* NUCLEAR OPTION - Hide absolutely everything first */
            html,
            body,
            * {
                visibility: hidden !important;
                display: none !important;
            }

            /* Force show ONLY print content */
            .print-table-card {
                display: block !important;
                visibility: visible !important;
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                width: 100vw !important;
                height: 100vh !important;
                z-index: 9999 !important;
                background: white !important;
                padding: 30px !important;
                margin: 0 !important;
                box-sizing: border-box !important;
            }

            .print-table-card * {
                display: block !important;
                visibility: visible !important;
            }

            /* Body override */
            body {
                display: block !important;
                visibility: visible !important;
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
                color: black !important;
                font-family: Arial, sans-serif !important;
                font-size: 12pt !important;
            }

            html {
                display: block !important;
                visibility: visible !important;
            }

            /* Print header */
            .print-header {
                display: block !important;
                visibility: visible !important;
                text-align: center !important;
                margin-bottom: 25px !important;
                border-bottom: 3px solid black !important;
                padding-bottom: 15px !important;
            }

            .print-header h2 {
                display: block !important;
                visibility: visible !important;
                font-size: 20pt !important;
                font-weight: bold !important;
                margin: 0 0 10px 0 !important;
                color: black !important;
                text-transform: uppercase !important;
            }

            .print-header h3 {
                display: block !important;
                visibility: visible !important;
                font-size: 16pt !important;
                margin: 0 !important;
                color: black !important;
            }

            /* Print info box */
            .print-info {
                display: block !important;
                visibility: visible !important;
                margin-bottom: 25px !important;
                padding: 20px !important;
                border: 2px solid black !important;
                background: #f5f5f5 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .print-info div {
                display: block !important;
                visibility: visible !important;
                line-height: 1.8 !important;
                color: black !important;
            }

            .print-info strong {
                display: inline !important;
                visibility: visible !important;
                font-weight: bold !important;
                color: black !important;
            }

            /* Table container */
            .card-body {
                display: block !important;
                visibility: visible !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .table-responsive {
                display: block !important;
                visibility: visible !important;
                overflow: visible !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Main table styling */
            #dataPencatatanTable {
                display: table !important;
                visibility: visible !important;
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 12pt !important;
                margin: 0 !important;
                border: 3px solid black !important;
            }

            #dataPencatatanTable thead {
                display: table-header-group !important;
                visibility: visible !important;
            }

            #dataPencatatanTable tbody {
                display: table-row-group !important;
                visibility: visible !important;
            }

            #dataPencatatanTable tr {
                display: table-row !important;
                visibility: visible !important;
            }

            #dataPencatatanTable th {
                display: table-cell !important;
                visibility: visible !important;
                background: #e0e0e0 !important;
                border: 1px solid black !important;
                padding: 12px 8px !important;
                text-align: center !important;
                font-weight: bold !important;
                color: black !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            #dataPencatatanTable td {
                display: table-cell !important;
                visibility: visible !important;
                border: 1px solid black !important;
                padding: 10px 8px !important;
                text-align: left !important;
                color: black !important;
                background: white !important;
                vertical-align: middle !important;
            }

            /* Column specific alignments */
            #dataPencatatanTable td:first-child {
                text-align: center !important;
                font-weight: bold !important;
            }

            #dataPencatatanTable td:nth-child(4),
            #dataPencatatanTable td:nth-child(6) {
                text-align: right !important;
            }

            /* HIDE Action column completely */
            #dataPencatatanTable th:nth-child(7),
            #dataPencatatanTable td:nth-child(7) {
                display: none !important;
                visibility: hidden !important;
            }

            /* Footer section */
            .card-footer {
                display: block !important;
                visibility: visible !important;
                margin-top: 25px !important;
                padding: 20px !important;
                border: 2px solid black !important;
                background: #f5f5f5 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .card-footer .row {
                display: flex !important;
                visibility: visible !important;
                align-items: center !important;
            }

            .card-footer .col-md-6 {
                display: block !important;
                visibility: visible !important;
            }

            .card-footer .col-md-6:first-child {
                flex: 1 !important;
                text-align: left !important;
            }

            .card-footer .col-md-6:last-child {
                text-align: right !important;
                font-size: 11pt !important;
                color: #666 !important;
            }

            .card-footer strong {
                display: inline !important;
                visibility: visible !important;
                font-size: 16pt !important;
                font-weight: bold !important;
                color: black !important;
            }

            .card-footer .text-primary {
                display: inline !important;
                visibility: visible !important;
                color: black !important;
                font-weight: bold !important;
                font-size: 18pt !important;
            }

            .card-footer .fas {
                display: inline !important;
                visibility: visible !important;
                margin-right: 6px !important;
            }

            /* Page setup */
            @page {
                size: A4 landscape !important;
                margin: 15mm !important;
            }

            /* Force hide everything else with higher specificity */
            .main-header,
            .main-sidebar,
            .main-footer,
            .content-header,
            .navbar,
            .breadcrumb,
            .alert,
            .modal,
            .card:not(.print-table-card) {
                display: none !important;
                visibility: hidden !important;
            }
        }

        /* Print header - hidden by default, shown only when printing */
        .print-header {
            display: none;
        }

        .print-info {
            display: none;
        }
    </style>
@endsection

@section('js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        // Function untuk navigasi ke bulan tertentu dan membuka modal pricing
        function navigateAndOpenPricing(month, year) {
            // Set value di form filter
            $('#bulan').val(month);
            $('#tahun').val(year);

            // Set value di modal pricing
            var yearMonth = year + '-' + month.toString().padStart(2, '0');
            $('#pricingDate').val(yearMonth);

            // Setelah halaman reload, modal akan dibuka via sessionStorage
            sessionStorage.setItem('openPricingModal', 'true');
            sessionStorage.setItem('pricingMonth', month);
            sessionStorage.setItem('pricingYear', year);

            // Submit form filter
            $('#filter-form').submit();
        }

        // Set up CSRF token for AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(function() {
            // Cek apakah perlu membuka modal pricing setelah navigasi
            if (sessionStorage.getItem('openPricingModal') === 'true') {
                var month = sessionStorage.getItem('pricingMonth');
                var year = sessionStorage.getItem('pricingYear');
                var yearMonth = year + '-' + month.toString().padStart(2, '0');

                // Set value di modal pricing
                $('#pricingDate').val(yearMonth);

                // Buka modal
                setTimeout(function() {
                    $('#setPricingModal').modal('show');
                }, 500);

                // Clear sessionStorage
                sessionStorage.removeItem('openPricingModal');
                sessionStorage.removeItem('pricingMonth');
                sessionStorage.removeItem('pricingYear');
            }
            // DataTables initialization
            var table = $("#dataPencatatanTable").DataTable({
                "responsive": true,
                "lengthChange": true,
                "autoWidth": false,
                "ordering": false, // Disable client-side ordering since we're using server-side ordering
                "stateSave": false, // Disable state saving to prevent caching issues
                "pageLength": 10, // Default entries per page
                "lengthMenu": [
                    [10, 25, 50, -1],
                    [10, 25, 50, "Semua"]
                ], // Length menu options
                "language": {
                    "emptyTable": "Tidak ada data pencatatan tersedia",
                    "zeroRecords": "Tidak ada data yang cocok ditemukan",
                    "info": "Menampilkan _START_ hingga _END_ dari _TOTAL_ entri",
                    "infoEmpty": "Menampilkan 0 hingga 0 dari 0 entri",
                    "infoFiltered": "(disaring dari _MAX_ total entri)",
                    "search": "Cari:",
                    "lengthMenu": "Tampilkan _MENU_ entri",
                    "paginate": {
                        "first": "Pertama",
                        "last": "Terakhir",
                        "next": "Selanjutnya",
                        "previous": "Sebelumnya"
                    }
                },
                "paging": true,
                "info": true,
                "searching": true,
                "destroy": true, // Allow re-initialization
                "initComplete": function(settings, json) {
                    console.log("DataTable initialized successfully with", this.api().data().length,
                        "records");
                }
            });

            // Initialize DataTable for deposit history modal
            $("#depositHistoryTable").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "ordering": true,
                "order": [
                    [1, 'desc']
                ],
                "language": {
                    "emptyTable": "Tidak ada riwayat deposit",
                    "search": "Cari:"
                }
            });

            // Initialize DataTable for pricing history modal
            $("#pricingHistoryTable").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "ordering": true,
                "order": [
                    [0, 'asc']
                ],
                "language": {
                    "emptyTable": "Tidak ada riwayat harga",
                    "search": "Cari:"
                }
            });

            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Menghilangkan kode AJAX yang membingungkan
            // Biarkan form submit secara native untuk memudahkan debugging
            /*
            $("#pricingForm").on("submit", function() {
                console.log("Form submitted directly (non-AJAX)");
                // Tampilkan indikator loading pada tombol
                $("#savePricingButton").prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');
                return true; // Allow normal form submission
            });
            */


        });

        // Simple function to open print page
        function openPrintPage() {
            console.log('Opening print page...');

            // Get current filter parameters
            const bulan = '{{ $selectedBulan }}';
            const tahun = '{{ $selectedTahun }}';
            const customerId = '{{ $customer->id }}';

            // Build URL with parameters
            const printUrl = `/data-pencatatan/fob/${customerId}/print?bulan=${bulan}&tahun=${tahun}`;

            // Open in new window/tab
            window.open(printUrl, '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');
        }

        // Advanced Fix Functions
        function analyzeAndFixData() {
            if (!confirm(
                    'Ini akan menganalisis dan memperbaiki data yang tidak sinkron. Proses ini mungkin memakan waktu beberapa menit. Lanjutkan?'
                    )) {
                return;
            }

            // Show loading
            Swal.fire({
                title: 'Menganalisis Data...',
                text: 'Sedang memeriksa dan memperbaiki data yang tidak sinkron',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: '{{ route('data-sync.fob.analyze-and-fix', $customer->id) }}',
                method: 'POST',
                success: function(response) {
                    Swal.fire({
                        title: 'Analisis Selesai!',
                        html: `
                            <div class="text-left">
                                <p><strong>Hasil Analisis:</strong></p>
                                <ul>
                                    <li>Data Rekap: ${response.rekap_count}</li>
                                    <li>Data Pencatatan: ${response.pencatatan_count}</li>
                                    <li>Data Dibuat: ${response.created_pencatatan}</li>
                                    <li>Data Dihapus: ${response.deleted_orphaned}</li>
                                    <li>Relasi Diperbaiki: ${response.fixed_relations}</li>
                                </ul>
                                <p><strong>Total Pembelian Baru: Rp ${new Intl.NumberFormat('id-ID').format(response.new_total_purchases)}</strong></p>
                            </div>
                        `,
                        icon: 'success',
                        confirmButtonText: 'Refresh Halaman'
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    let errorMsg = 'Terjadi kesalahan';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMsg = xhr.responseJSON.error;
                    }
                    Swal.fire({
                        title: 'Error!',
                        text: errorMsg,
                        icon: 'error'
                    });
                }
            });
        }

        function debugDataSync() {
            // Show loading
            Swal.fire({
                title: 'Mengambil Data Debug...',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: '{{ route('data-sync.fob.debug', $customer->id) }}',
                method: 'GET',
                success: function(response) {
                    // Format data untuk ditampilkan
                    let debugInfo = `
                        <div class="text-left" style="max-height: 400px; overflow-y: auto;">
                            <h5>Customer Info:</h5>
                            <p><strong>ID:</strong> ${response.customer_info.id}</p>
                            <p><strong>Name:</strong> ${response.customer_info.name}</p>
                            <p><strong>Total Purchases:</strong> Rp ${new Intl.NumberFormat('id-ID').format(response.customer_info.total_purchases)}</p>

                            <h5>Data Rekap (${response.rekap_data.length} records):</h5>
                            <div style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin: 5px 0;">
                    `;

                    response.rekap_data.forEach(function(item) {
                        debugInfo +=
                            `<p><strong>ID ${item.id}:</strong> ${item.tanggal} - ${item.volume} Sm³ ${item.has_pencatatan ? '✓' : '✗'}</p>`;
                    });

                    debugInfo += `
                            </div>
                            <h5>Data Pencatatan (${response.pencatatan_data.length} records):</h5>
                            <div style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin: 5px 0;">
                    `;

                    response.pencatatan_data.forEach(function(item) {
                        debugInfo +=
                            `<p><strong>ID ${item.id}:</strong> ${item.waktu} - ${item.volume_sm3} Sm³ (Rekap ID: ${item.rekap_pengambilan_id || 'None'})</p>`;
                    });

                    debugInfo += `
                            </div>
                        </div>
                    `;

                    Swal.fire({
                        title: 'Debug Info',
                        html: debugInfo,
                        width: '80%',
                        showConfirmButton: true,
                        confirmButtonText: 'Tutup'
                    });
                },
                error: function(xhr) {
                    let errorMsg = 'Terjadi kesalahan';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMsg = xhr.responseJSON.error;
                    }
                    Swal.fire({
                        title: 'Error!',
                        text: errorMsg,
                        icon: 'error'
                    });
                }
            });
        }

        // SIMPLE: Handle Edit Deposit for FOB
        $(document).ready(function() {
            $(document).on('click', '.btn-edit-deposit', function() {
                console.log('Edit FOB button clicked!');
                
                var index = $(this).attr('data-index');
                var date = $(this).attr('data-date');
                var amount = $(this).attr('data-amount');
                var keterangan = $(this).attr('data-keterangan');
                var deskripsi = $(this).attr('data-deskripsi');
                
                console.log('Data FOB:', index, date, amount, keterangan, deskripsi);
                
                // Populate fields
                $('#edit_deposit_index').val(index);
                $('#edit_deposit_date').val(date);
                $('#edit_amount').val(amount);
                $('#edit_keterangan').val(keterangan);
                $('#edit_description').val(deskripsi);
                
                // Close deposit history modal first
                $('#depositHistoryModal').modal('hide');
                
                // Wait for backdrop to clear, then show edit modal
                setTimeout(function() {
                    $('#editDepositModal').modal('show');
                    // Focus on first input after modal shown
                    setTimeout(function() {
                        $('#edit_deposit_date').focus();
                    }, 500);
                }, 500);
            });
        });
    </script>
@endsection
