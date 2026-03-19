@extends('layouts.app')

@section('title', 'Detail Data Pencatatan Customer')

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
                                            <a href="{{ route('data-pencatatan.customer-detail', [
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

            {{-- Changes to Customer Summary Card for responsiveness --}}
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-circle mr-2"></i>
                            Informasi Customer: {{ $customer->name }}
                        </h3>
                        <div class="card-tools">
                            @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
                                <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#setPricingModal">
                                    <i class="fas fa-cog mr-1"></i> Atur Harga & Koreksi
                                </button>
                                <button class="btn btn-success btn-sm mr-2" data-toggle="modal"
                                    data-target="#depositHistoryModal">
                                    <i class="fas fa-money-bill-alt mr-1"></i> History Deposit
                                </button>

                                <a href="{{ route('data-pencatatan.create-with-customer', $customer->id) }}"
                                    class="btn btn-info btn-sm">
                                    <i class="fas fa-plus mr-1"></i> Tambah Data
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-tachometer-alt mr-1"></i> Total Pemakaian</strong>
                                    <p class="text-muted mb-0">
                                        {{ number_format($totalVolumeSm3, 2) }} Sm³
                                        @if($isMmbtu)
                                        <br><small>{{ number_format($totalConsumedMmbtu, 2) }} MMBTU</small>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-money-bill-wave mr-1"></i> Total Pembelian</strong>
                                    <p class="text-muted mb-0">
                                        @if($isMmbtu)
                                            $ {{ number_format($filteredTotalPurchasesUsd, 2) }}
                                        @else
                                            Rp {{ number_format($customer->total_purchases ?? 0, 0) }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-wallet mr-1"></i> Total Deposit</strong>
                                    <p class="text-muted mb-0">
                                        @if($isMmbtu)
                                            {{ number_format($totalDepositMmbtu, 2) }} MMBTU
                                        @else
                                            Rp {{ number_format($customer->total_deposit ?? 0, 0) }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-wallet mr-1"></i>
                                        @if($isMmbtu) Saldo MMBTU @else Saldo Total @endif
                                    </strong>
                                    <p class="text-muted mb-0">
                                        @if($isMmbtu)
                                            {{ number_format($totalDepositMmbtu - $totalConsumedMmbtu, 2) }} MMBTU
                                        @else
                                            Rp
                                            {{ number_format(($customer->total_deposit ?? 0) - ($customer->total_purchases ?? 0), 0) }}
                                        @endif
                                        <span class="badge badge-info" title="Saldo total dari seluruh periode"><i
                                                class="fas fa-info-circle"></i></span>
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
                        <form action="{{ route('data-pencatatan.filter-month-year', $customer->id) }}" method="POST"
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
                                            <a href="{{ route('data-pencatatan.customer-detail', $customer->id) }}"
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
            {{-- Yearly Period Information Card --}}
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            Informasi Periode Tahunan: {{ $selectedTahun }}
                        </h3>
                        <div class="card-tools">
                            <a href="{{ route('data-pencatatan.customer-detail', [
                                'customer' => $customer->id,
                                'bulan' => $selectedBulan,
                                'tahun' => $selectedTahun,
                                'refresh' => true,
                            ]) }}"
                                class="btn btn-warning btn-sm">
                                <i class="fas fa-sync-alt mr-1"></i> Selaraskan Data
                            </a>
                        </div>
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
            {{-- Changes to Period Information Card for responsiveness --}}
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line mr-2"></i>
                            Informasi Periode:
                            {{ \Carbon\Carbon::createFromDate($selectedTahun, $selectedBulan, 1)->format('F Y') }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-tachometer-alt mr-1"></i> Tekanan </strong>
                                    <p class="text-muted mb-0">
                                        {{ number_format($pricingInfo['tekanan_keluar'] ?? ($customer->tekanan_keluar ?? 0), 2) }}
                                        Bar
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-thermometer-half mr-1"></i> Suhu</strong>
                                    <p class="text-muted mb-0">
                                        {{ number_format($pricingInfo['suhu'] ?? ($customer->suhu ?? 0), 2) }} °C
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-tachometer-alt mr-1"></i> Koreksi Meter</strong>
                                    <p class="text-muted mb-0">
                                        {{ number_format($pricingInfo['koreksi_meter'] ?? ($customer->koreksi_meter ?? 1), 4) }}
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    @if($isMmbtu)
                                        <strong><i class="fas fa-money-bill-wave mr-1"></i> Harga per MMBTU</strong>
                                        <p class="text-muted mb-0">
                                            $ {{ number_format($pricingInfo['harga_per_mmbtu_usd'] ?? 0, 2) }}
                                        </p>
                                        <strong><i class="fas fa-divide mr-1"></i> Pembagi SM3 → MMBTU</strong>
                                        <p class="text-muted mb-0">
                                            {{ number_format($pricingInfo['pembagi_sm3_ke_mmbtu'] ?? $pricingInfo['pembaji_sm3_ke_mmbtu'] ?? 1, 2) }}
                                        </p>
                                    @else
                                        <strong><i class="fas fa-money-bill-wave mr-1"></i> Harga per Sm³</strong>
                                        <p class="text-muted mb-0">
                                            Rp
                                            {{ number_format($pricingInfo['harga_per_meter_kubik'] ?? ($customer->harga_per_meter_kubik ?? 0), 2) }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-gas-pump mr-1"></i> Volume Periode Ini</strong>
                                    <p class="text-muted mb-0">
                                        {{ number_format($filteredVolumeSm3, 2) }} Sm³
                                        @if($isMmbtu)
                                        <br><small>{{ number_format($filteredVolumeMmbtu, 2) }} MMBTU</small>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-money-bill-wave mr-1"></i> Pembelian Periode Ini</strong>
                                    <p class="text-muted mb-0">
                                        @if($isMmbtu)
                                            $ {{ number_format($filteredTotalPurchasesUsd, 2) }}
                                        @else
                                            Rp {{ number_format($filteredTotalPurchases, 0) }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-wallet mr-1"></i> Deposit Periode Ini</strong>
                                    <p class="text-muted mb-0">
                                        @if($isMmbtu)
                                            {{ number_format($filteredTotalDepositsMmbtu, 2) }} MMBTU
                                        @else
                                            Rp {{ number_format($filteredTotalDeposits, 0) }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-balance-scale mr-1"></i> Saldo Periode Bulan Ini</strong>
                                    <p class="text-muted mb-0">
                                        @if($isMmbtu)
                                            {{ number_format($currentMonthBalanceMmbtu, 2) }} MMBTU
                                        @else
                                            Rp
                                            {{ number_format($realTimeCurrentMonthBalance, 0) }}
                                        @endif
                                        <span class="badge badge-success"
                                            title="Saldo real-time untuk periode bulan yang dipilih"><i
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
                                            <a href="{{ route('data-pencatatan.customer-detail', [
                                                'customer' => $customer->id,
                                                'bulan' => $selectedBulan,
                                                'tahun' => $selectedTahun,
                                                'refresh' => true,
                                            ]) }}"
                                                class="btn btn-warning btn-sm" title="Selaraskan Data">
                                                <i class="fas fa-sync-alt"></i>
                                            </a>
                                        </span>
                                        <div class="table-responsive mt-2">
                                            <table class="table table-sm table-bordered">
                                                @if($isMmbtu)
                                                <tr>
                                                    <td width="60%">Saldo MMBTU Bulan Sebelumnya</td>
                                                    <td>{{ number_format($prevMonthBalanceMmbtu, 2) }} MMBTU</td>
                                                </tr>
                                                <tr>
                                                    <td>+ Deposit MMBTU Bulan Ini</td>
                                                    <td>{{ number_format($filteredTotalDepositsMmbtu, 2) }} MMBTU</td>
                                                </tr>
                                                <tr>
                                                    <td>- Pemakaian MMBTU Bulan Ini</td>
                                                    <td>{{ number_format($filteredVolumeMmbtu, 2) }} MMBTU</td>
                                                </tr>
                                                @else
                                                <tr>
                                                    <td width="60%">Saldo Bulan Sebelumnya</td>
                                                    <td>Rp {{ number_format($realTimePrevMonthBalance, 0) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>+ Deposit Bulan Ini</td>
                                                    <td>Rp {{ number_format($filteredTotalDeposits, 0) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>- Pembelian Bulan Ini</td>
                                                    <td>Rp {{ number_format($filteredTotalPurchases, 0) }}</td>
                                                </tr>
                                                @endif
                                                @if(!$isMmbtu)
                                                @php
                                                    /*
                                                     * PERBAIKAN PERHITUNGAN SALDO:
                                                     * Mengubah perhitungan 'Saldo Bulan Sebelumnya' dari menggunakan data
                                                     * monthly_balances (yang bisa tidak akurat) menjadi perhitungan real-time
                                                     * yang konsisten dengan 'Sisa Saldo Periode Bulan Ini'
                                                     */

                                                    // Definisikan variabel currentYearMonth terlebih dahulu
                                                    $currentYearMonth = \Carbon\Carbon::createFromDate(
                                                        $selectedTahun,
                                                        $selectedBulan,
                                                        1,
                                                    )->format('Y-m');

                                                    // PERBAIKAN: Hitung saldo bulan sebelumnya secara real-time
                                                    $prevDate = \Carbon\Carbon::createFromDate(
                                                        $selectedTahun,
                                                        $selectedBulan,
                                                        1,
                                                    )->subMonth();
                                                    $prevYearMonth = $prevDate->format('Y-m');

                                                    // Hitung total deposit dan pembelian sampai akhir bulan sebelumnya
                                                    $realTimePrevMonthBalance = 0;

                                                    // 1. Hitung semua deposit sampai akhir bulan sebelumnya
                                                    $deposits = is_string($customer->deposit_history)
                                                        ? json_decode($customer->deposit_history, true)
                                                        : $customer->deposit_history;
                                                    if (is_array($deposits)) {
                                                        foreach ($deposits as $deposit) {
                                                            if (isset($deposit['date'])) {
                                                                $depositDate = \Carbon\Carbon::parse($deposit['date']);
                                                                // Ambil deposit sampai akhir bulan sebelumnya
                                                                if ($depositDate->format('Y-m') <= $prevYearMonth) {
                                                                    $realTimePrevMonthBalance += floatval(
                                                                        $deposit['amount'] ?? 0,
                                                                    );
                                                                }
                                                            }
                                                        }
                                                    }

                                                    // 2. Kurangi semua pembelian sampai akhir bulan sebelumnya
                                                    $allDataPencatatan = $customer->dataPencatatan()->get();
                                                    foreach ($allDataPencatatan as $purchaseItem) {
                                                        $itemDataInput = is_string($purchaseItem->data_input)
                                                            ? json_decode($purchaseItem->data_input, true)
                                                            : $purchaseItem->data_input;
                                                        if (
                                                            empty($itemDataInput) ||
                                                            empty($itemDataInput['pembacaan_awal']['waktu'])
                                                        ) {
                                                            continue;
                                                        }

                                                        $itemWaktuAwal = \Carbon\Carbon::parse(
                                                            $itemDataInput['pembacaan_awal']['waktu'],
                                                        );

                                                        // Ambil pembelian sampai akhir bulan sebelumnya
                                                        if ($itemWaktuAwal->format('Y-m') <= $prevYearMonth) {
                                                            $volumeFlowMeter = floatval(
                                                                $itemDataInput['volume_flow_meter'] ?? 0,
                                                            );

                                                            // Ambil pricing yang sesuai (bulanan atau periode khusus)
                                                            $itemYearMonth = $itemWaktuAwal->format('Y-m');
                                                            $itemPricingInfo = $customer->getPricingForYearMonth(
                                                                $itemYearMonth,
                                                                $itemWaktuAwal,
                                                            );

                                                            // Hitung volume dan harga
                                                            $itemKoreksiMeter = floatval(
                                                                $itemPricingInfo['koreksi_meter'] ??
                                                                    $customer->koreksi_meter,
                                                            );
                                                            $itemHargaPerM3 = floatval(
                                                                $itemPricingInfo['harga_per_meter_kubik'] ??
                                                                    $customer->harga_per_meter_kubik,
                                                            );
                                                            $itemVolumeSm3 = $volumeFlowMeter * $itemKoreksiMeter;
                                                            $itemHarga = $itemVolumeSm3 * $itemHargaPerM3;

                                                            $realTimePrevMonthBalance -= $itemHarga;
                                                        }
                                                    }

                                                    // Hitung ulang saldo bulan ini secara real-time
                                                    $realTimeFilteredTotalPurchases = 0;
                                                    $realTimeFilteredDeposits = 0;

                                                    // Hitung pembelian bulan ini
                                                    foreach ($dataPencatatan as $purchaseItem) {
                                                        $itemDataInput = is_string($purchaseItem->data_input)
                                                            ? json_decode($purchaseItem->data_input, true)
                                                            : $purchaseItem->data_input;
                                                        if (
                                                            empty($itemDataInput) ||
                                                            empty($itemDataInput['pembacaan_awal']['waktu'])
                                                        ) {
                                                            continue;
                                                        }

                                                        $itemWaktuAwal = \Carbon\Carbon::parse(
                                                            $itemDataInput['pembacaan_awal']['waktu'],
                                                        );
                                                        $volumeFlowMeter = floatval(
                                                            $itemDataInput['volume_flow_meter'] ?? 0,
                                                        );

                                                        // Ambil pricing yang sesuai (bulanan atau periode khusus)
                                                        $itemYearMonth = $itemWaktuAwal->format('Y-m');
                                                        $itemPricingInfo = $customer->getPricingForYearMonth(
                                                            $itemYearMonth,
                                                            $itemWaktuAwal,
                                                        );

                                                        // Hitung volume dan harga
                                                        $itemKoreksiMeter = floatval(
                                                            $itemPricingInfo['koreksi_meter'] ??
                                                                $customer->koreksi_meter,
                                                        );
                                                        $itemHargaPerM3 = floatval(
                                                            $itemPricingInfo['harga_per_meter_kubik'] ??
                                                                $customer->harga_per_meter_kubik,
                                                        );
                                                        $itemVolumeSm3 = $volumeFlowMeter * $itemKoreksiMeter;
                                                        $itemHarga = $itemVolumeSm3 * $itemHargaPerM3;

                                                        $realTimeFilteredTotalPurchases += $itemHarga;
                                                    }

                                                    // Hitung deposit bulan ini
                                                    if (is_array($deposits)) {
                                                        foreach ($deposits as $deposit) {
                                                            if (isset($deposit['date'])) {
                                                                $depositDate = \Carbon\Carbon::parse($deposit['date']);
                                                                if ($depositDate->format('Y-m') === $currentYearMonth) {
                                                                    $realTimeFilteredDeposits += floatval(
                                                                        $deposit['amount'] ?? 0,
                                                                    );
                                                                }
                                                            }
                                                        }
                                                    }

                                                    // Hitung saldo bulan ini menggunakan saldo bulan sebelumnya yang real-time
                                                    $realTimeCurrentMonthBalance =
                                                        $realTimePrevMonthBalance +
                                                        $realTimeFilteredDeposits -
                                                        $realTimeFilteredTotalPurchases;

                                                    // Log untuk debugging (hanya untuk admin)
                                                    if (
                                                        (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin()) &&
                                                        abs($realTimePrevMonthBalance - $prevMonthBalance) > 0.01
                                                    ) {
                                                        \Log::info('Perbedaan saldo bulan sebelumnya ditemukan', [
                                                            'customer_id' => $customer->id,
                                                            'customer_name' => $customer->name,
                                                            'period' =>
                                                                $selectedTahun .
                                                                '-' .
                                                                str_pad($selectedBulan, 2, '0', STR_PAD_LEFT),
                                                            'prev_month' => \Carbon\Carbon::createFromDate(
                                                                $selectedTahun,
                                                                $selectedBulan,
                                                                1,
                                                            )
                                                                ->subMonth()
                                                                ->format('Y-m'),
                                                            'real_time_prev_balance' => $realTimePrevMonthBalance,
                                                            'database_prev_balance' => $prevMonthBalance,
                                                            'difference' =>
                                                                $realTimePrevMonthBalance - $prevMonthBalance,
                                                        ]);
                                                    }
                                                @endphp
                                                @endif
                                                <tr class="font-weight-bold">
                                                    <td>= Sisa Saldo {{ $isMmbtu ? 'MMBTU' : '' }} Periode Bulan Ini</td>
                                                    @if($isMmbtu)
                                                    <td>{{ number_format($currentMonthBalanceMmbtu, 2) }} MMBTU</td>
                                                    @else
                                                    <td>Rp {{ number_format($realTimeCurrentMonthBalance, 0) }}</td>
                                                    @endif
                                                </tr>
                                                <tr>
                                                    <td colspan="2" class="text-muted"><small>* Saldo ini hanya
                                                            menunjukkan saldo untuk periode
                                                            {{ \Carbon\Carbon::createFromDate($selectedTahun, $selectedBulan, 1)->format('F Y') }}
                                                            saja (Perhitungan Real-time)</small></td>
                                                </tr>
                                                {{-- @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
                                                <tr class="text-info" style="font-size: 0.8em;">
                                                    <td colspan="2">
                                                        <strong>Debug Info (Real-time vs Database):</strong><br>
                                                        - Saldo Bulan Sebelumnya (Real-time): Rp {{ number_format($realTimePrevMonthBalance, 2) }}<br>
                                                        - Saldo Bulan Sebelumnya (Database): Rp {{ number_format($prevMonthBalance, 2) }}<br>
                                                        - Selisih Prev: Rp {{ number_format($realTimePrevMonthBalance - $prevMonthBalance, 2) }}<br>
                                                        - Saldo Periode Ini (Real-time): Rp {{ number_format($realTimeCurrentMonthBalance, 2) }}<br>
                                                        - Saldo Periode Ini (Database): Rp {{ number_format($currentMonthBalanceDb, 2) }}<br>
                                                        - Selisih Current: Rp {{ number_format($realTimeCurrentMonthBalance - $currentMonthBalanceDb, 2) }}
                                                    </td>
                                                </tr>
                                                @endif --}}
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Data Pencatatan Table --}}
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list-alt mr-2"></i>
                            Riwayat Pencatatan
                        </h3>
                        <div class="card-tools">
                            @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-success" data-toggle="modal" data-target="#uploadExcelModal">
                                        <i class="fas fa-file-excel mr-1"></i> Upload Excel
                                    </button>
                                    <button class="btn btn-info" id="btnShowTemplate" data-toggle="modal"
                                        data-target="#templateExcelModal">
                                        <i class="fas fa-info-circle mr-1"></i> Template Excel
                                    </button>
                                    <a href="{{ asset('templates/template_pencatatan.xlsx') }}" class="btn btn-primary"
                                        target="_blank">
                                        <i class="fas fa-download mr-1"></i> Download Template
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if (session('import_errors'))
                        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                            <i class="fas fa-times-circle mr-2"></i>
                            <strong>Error saat import data Excel:</strong>
                            <ul class="mt-2 mb-0">
                                @foreach (session('import_errors') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                </div>
                <div class="card-body table-responsive p-0">
                    <style>
                        /* Menjadikan semua teks di dalam sel tabel (<th> dan <td>) menjadi rata tengah */
                        table th,
                        table td {
                            text-align: center;
                        }
                    </style>
                    <table class="table table-bordered table-striped table-hover" id="dataPencatatanTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th colspan="2">Pembacaan Awal</th>
                                <th colspan="2">Pembacaan Akhir</th>
                                <th colspan="{{ $isMmbtu ? 3 : 2 }}">Volume</th>
                                <th>{{ $isMmbtu ? 'USD' : 'Rupiah' }}</th>
                                <th>Aksi</th>
                            </tr>
                            <tr>
                                <th></th>
                                <th>Tanggal</th>
                                <th>Meter</th>
                                <th>Tanggal</th>
                                <th>Meter</th>
                                <th>flowmeter</th>
                                <th>Sm³</th>
                                @if($isMmbtu)
                                <th>MMBTU</th>
                                @endif
                                <th></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $no = 1;

                                // Buat tanggal mulai dan akhir untuk bulan yang dipilih
                                $startDate = \Carbon\Carbon::createFromDate($selectedTahun, $selectedBulan, 1);
                                $endDate = $startDate->copy()->endOfMonth();

                                // Debug Info
                                echo '<!-- DEBUG: StartDate: ' . $startDate->format('Y-m-d') . ' -->';
                                echo '<!-- DEBUG: EndDate: ' . $endDate->format('Y-m-d') . ' -->';
                                echo '<!-- DEBUG: Total Records: ' . count($dataPencatatan) . ' -->';

                                // Logging semua data pencatatan untuk debugging
                                echo '<!-- BEGIN DATA DUMP -->';
                                foreach ($dataPencatatan as $record) {
                                    $dataInput = is_string($record->data_input)
                                        ? json_decode($record->data_input, true)
                                        : (is_array($record->data_input)
                                            ? $record->data_input
                                            : []);
                                    $waktuAwal = isset($dataInput['pembacaan_awal']['waktu'])
                                        ? $dataInput['pembacaan_awal']['waktu']
                                        : 'N/A';
                                    echo '<!-- DUMP RECORD: ID=' .
                                        $record->id .
                                        ' | Waktu Awal=' .
                                        $waktuAwal .
                                        ' | Format=' .
                                        (isset($dataInput['pembacaan_awal']['waktu'])
                                            ? date('Y-m-d', strtotime($dataInput['pembacaan_awal']['waktu']))
                                            : 'N/A') .
                                        ' | Volume=' .
                                        ($dataInput['volume_flow_meter'] ?? 0) .
                                        ' | Harga=' .
                                        $record->harga_final .
                                        ' -->';
                                }
                                echo '<!-- END DATA DUMP -->';

                                // PERBAIKAN: Buat array tanggal untuk periode penuh dan simpan SEMUA data (termasuk duplikasi)
                                $allDatesInPeriod = [];
                                $currentDate = clone $startDate;
                                while ($currentDate->lte($endDate)) {
                                    $dateKey = $currentDate->format('Y-m-d');
                                    $allDatesInPeriod[$dateKey] = []; // Array kosong untuk menampung multiple records
                                    $currentDate->addDay();
                                }

                                // Debug array tanggal
                                echo '<!-- DEBUG: Total dates in period: ' . count($allDatesInPeriod) . ' -->';
                                echo '<!-- DEBUG: Period date keys: ' .
                                    implode(',', array_keys($allDatesInPeriod)) .
                                    ' -->';

                                // PERBAIKAN: Masukkan SEMUA data pencatatan ke array berdasarkan tanggal (termasuk duplikasi)
                                $recordsFound = 0;
                                foreach ($dataPencatatan as $record) {
                                    $dataInput = is_string($record->data_input)
                                        ? json_decode($record->data_input, true)
                                        : (is_array($record->data_input)
                                            ? $record->data_input
                                            : []);

                                    if (!empty($dataInput['pembacaan_awal']['waktu'])) {
                                        // Standarisasi format tanggal dengan strtotime
                                        $recordDateStr = date(
                                            'Y-m-d',
                                            strtotime($dataInput['pembacaan_awal']['waktu']),
                                        );

                                        echo '<!-- DEBUG: Record #' . $record->id . ' date: ' . $recordDateStr . ' -->';

                                        if (array_key_exists($recordDateStr, $allDatesInPeriod)) {
                                            echo '<!-- DEBUG: MATCH found for date: ' . $recordDateStr . ' -->';
                                            $allDatesInPeriod[$recordDateStr][] = $record; // PUSH ke array, bukan replace!
                                            $recordsFound++;
                                        } else {
                                            echo '<!-- DEBUG: NO MATCH for date: ' .
                                                $recordDateStr .
                                                ' (not in period keys) -->';
                                            // Cek jika tanggalnya close match (mungkin ada masalah timezone atau format)
                                            foreach (array_keys($allDatesInPeriod) as $periodDate) {
                                                $diff = abs(strtotime($recordDateStr) - strtotime($periodDate));
                                                if ($diff < 86400) {
                                                    // selisih kurang dari 1 hari (dalam detik)
                                                    echo '<!-- DEBUG: CLOSE MATCH found: record=' .
                                                        $recordDateStr .
                                                        ', period=' .
                                                        $periodDate .
                                                        ' -->';
                                                    $allDatesInPeriod[$periodDate][] = $record; // PUSH ke array
                                                    $recordsFound++;
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }
                                echo '<!-- DEBUG: Records found in period: ' . $recordsFound . ' -->';
                            @endphp

                            @forelse($allDatesInPeriod as $date => $records)
                                @if (count($records) > 0)
                                    {{-- Tampilkan SEMUA records untuk tanggal ini (termasuk duplikasi) --}}
                                    @foreach ($records as $record)
                                        <tr class="has-data">
                                            <td>{{ $no++ }}</td>
                                            <td>
                                            @php
                                                $dataInput = is_string($record->data_input)
                                                    ? json_decode($record->data_input, true)
                                                    : (is_array($record->data_input)
                                                        ? $record->data_input
                                                        : []);
                                            @endphp
                                                {{ \Carbon\Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('d M Y H:i') }}
                                            </td>
                                            <td>
                                            @php
                                                $dataInput = is_string($record->data_input)
                                                    ? json_decode($record->data_input, true)
                                                    : (is_array($record->data_input)
                                                        ? $record->data_input
                                                        : []);
                                                $pembacaanAwal = $dataInput['pembacaan_awal'] ?? ['volume' => 0];
                                            @endphp
                                                {{ number_format($pembacaanAwal['volume'] ?? 0, 2) }} m³
                                            </td>
                                            <td>
                                            @php
                                                $dataInput = is_string($record->data_input)
                                                    ? json_decode($record->data_input, true)
                                                    : (is_array($record->data_input)
                                                        ? $record->data_input
                                                        : []);
                                            @endphp
                                                {{ \Carbon\Carbon::parse($dataInput['pembacaan_akhir']['waktu'])->format('d M Y H:i') }}
                                            </td>
                                            <td>
                                            @php
                                                $dataInput = is_string($record->data_input)
                                                    ? json_decode($record->data_input, true)
                                                    : (is_array($record->data_input)
                                                        ? $record->data_input
                                                        : []);
                                                $pembacaanAkhir = $dataInput['pembacaan_akhir'] ?? ['volume' => 0];
                                            @endphp
                                                {{ number_format($pembacaanAkhir['volume'] ?? 0, 2) }} m³
                                            </td>
                                            <td>
                                            @php
                                                $dataInput = is_string($record->data_input)
                                                    ? json_decode($record->data_input, true)
                                                    : (is_array($record->data_input)
                                                        ? $record->data_input
                                                        : []);
                                                $volumeFlowMeter = $dataInput['volume_flow_meter'] ?? 0;
                                            @endphp
                                                {{ number_format($volumeFlowMeter, 2) }} m³
                                                <!-- Debug info -->
                                                <!-- ID: {{ $record->id }} | Date: {{ $date }} -->
                                            </td>
                                            <td>
                                            @php
                                                $dataInput = is_string($record->data_input)
                                                    ? json_decode($record->data_input, true)
                                                    : (is_array($record->data_input)
                                                        ? $record->data_input
                                                        : []);

                                                // Get the timestamp for pricing period
                                                $waktuAwalTimestamp = strtotime(
                                                    $dataInput['pembacaan_awal']['waktu'] ?? '',
                                                );
                                                $waktuAwalYearMonth = $waktuAwalTimestamp
                                                    ? date('Y-m', $waktuAwalTimestamp)
                                                    : date('Y-m');
                                                $waktuAwalDatetime = $waktuAwalTimestamp
                                                    ? \Carbon\Carbon::createFromTimestamp($waktuAwalTimestamp)
                                                    : null;

                                                // Dapatkan pricing info berdasarkan periode bulan dan tanggal spesifik
                                                $itemPricingInfo = $customer->getPricingForYearMonth(
                                                    $waktuAwalYearMonth,
                                                    $waktuAwalDatetime,
                                                );

                                                // Hitung Volume Sm³ dengan koreksi meter yang sesuai periode
                                                $koreksiMeter = floatval(
                                                    $itemPricingInfo['koreksi_meter'] ?? $customer->koreksi_meter,
                                                );
                                                $volumeFlowMeter = $dataInput['volume_flow_meter'] ?? 0;
                                                $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
                                            @endphp
                                                {{ number_format($volumeSm3, 2) }} sm³
                                            </td>
                                            @if($isMmbtu)
                                            <td>
                                            @php
                                                $rowPembaji = floatval($itemPricingInfo['pembagi_sm3_ke_mmbtu'] ?? $itemPricingInfo['pembaji_sm3_ke_mmbtu'] ?? 1);
                                                if ($rowPembaji == 0) $rowPembaji = 1;
                                                $rowVolumeMmbtu = $volumeSm3 / $rowPembaji;
                                            @endphp
                                                {{ number_format($rowVolumeMmbtu, 2) }} MMBTU
                                            </td>
                                            @endif
                                            <td>
                                            @php
                                                if ($isMmbtu) {
                                                    $rowHargaMmbtu = floatval($itemPricingInfo['harga_per_mmbtu_usd'] ?? 0);
                                                    $pembelian = $rowVolumeMmbtu * $rowHargaMmbtu;
                                                } else {
                                                    $hargaPerM3 = floatval(
                                                        $itemPricingInfo['harga_per_meter_kubik'] ??
                                                            $customer->harga_per_meter_kubik,
                                                    );
                                                    $pembelian = $volumeSm3 * $hargaPerM3;
                                                }
                                            @endphp
                                                @if($isMmbtu)
                                                $ {{ number_format($pembelian, 4) }}
                                                @else
                                                Rp {{ number_format($pembelian, 2) }}
                                                @endif
                                            </td>
                                            <td>
                                            <div class="btn-group">
                                                <a href="{{ route('data-pencatatan.show', $record->id) }}"
                                                    class="btn btn-info btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
                                                    <a href="{{ route('data-pencatatan.edit', $record->id) }}"
                                                        class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('data-pencatatan.destroy', $record->id) }}"
                                                        method="POST" class="d-inline"
                                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="bulan"
                                                            value="{{ $selectedBulan }}">
                                                        <input type="hidden" name="tahun"
                                                            value="{{ $selectedTahun }}">
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    {{-- Tampilkan baris kosong jika tidak ada data --}}
                                    <tr class="table-light no-data">
                                        <td>{{ $no++ }}</td>
                                        <td>{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>-</td>
                                        @if($isMmbtu)
                                        <td>-</td>
                                        @endif
                                        <td>-</td>
                                        <td>
                                            @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
                                                <a href="{{ route('data-pencatatan.create-with-customer', ['customerId' => $customer->id, 'tanggal' => $date]) }}"
                                                    class="btn btn-success btn-sm">
                                                    <i class="fas fa-plus"></i> Input Data
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="{{ $isMmbtu ? 10 : 9 }}" class="text-center">Belum ada data pencatatan dalam periode ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="6" class="text-right">Total:</th>
                                <th>
                                    @php
                                        $totalVolumeSm3Period = 0;
                                        $totalVolumeMmbtuPeriod = 0;
                                        $totalPembelianPeriod = 0;
                                        foreach ($allDatesInPeriod as $date => $records) {
                                            foreach ($records as $record) {
                                                $dataInput = is_string($record->data_input)
                                                    ? json_decode($record->data_input, true)
                                                    : (is_array($record->data_input)
                                                        ? $record->data_input
                                                        : []);

                                                $waktuAwalTimestamp = strtotime(
                                                    $dataInput['pembacaan_awal']['waktu'] ?? '',
                                                );
                                                $waktuAwalYearMonth = $waktuAwalTimestamp
                                                    ? date('Y-m', $waktuAwalTimestamp)
                                                    : date('Y-m');
                                                $waktuAwalDatetime = $waktuAwalTimestamp
                                                    ? \Carbon\Carbon::createFromTimestamp($waktuAwalTimestamp)
                                                    : null;

                                                $ftItemPricingInfo = $customer->getPricingForYearMonth(
                                                    $waktuAwalYearMonth,
                                                    $waktuAwalDatetime,
                                                );

                                                $koreksiMeter = floatval(
                                                    $ftItemPricingInfo['koreksi_meter'] ?? $customer->koreksi_meter,
                                                );
                                                $volumeFlowMeter = $dataInput['volume_flow_meter'] ?? 0;
                                                $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
                                                $totalVolumeSm3Period += $volumeSm3;

                                                if ($isMmbtu) {
                                                    $ftPembaji = floatval($ftItemPricingInfo['pembagi_sm3_ke_mmbtu'] ?? $ftItemPricingInfo['pembaji_sm3_ke_mmbtu'] ?? 1);
                                                    if ($ftPembaji == 0) $ftPembaji = 1;
                                                    $ftMmbtu = $volumeSm3 / $ftPembaji;
                                                    $totalVolumeMmbtuPeriod += $ftMmbtu;
                                                    $totalPembelianPeriod += $ftMmbtu * floatval($ftItemPricingInfo['harga_per_mmbtu_usd'] ?? 0);
                                                } else {
                                                    $hargaPerM3 = floatval(
                                                        $ftItemPricingInfo['harga_per_meter_kubik'] ??
                                                            $customer->harga_per_meter_kubik,
                                                    );
                                                    $totalPembelianPeriod += $volumeSm3 * $hargaPerM3;
                                                }
                                            }
                                        }
                                    @endphp
                                    {{ number_format($totalVolumeSm3Period, 2) }}
                                </th>
                                @if($isMmbtu)
                                <th>{{ number_format($totalVolumeMmbtuPeriod, 4) }}</th>
                                @endif
                                <th>
                                    @if($isMmbtu)
                                    $ {{ number_format($totalPembelianPeriod, 4) }}
                                    @else
                                    Rp {{ number_format($totalPembelianPeriod, 0) }}
                                    @endif
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
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
                                @if($isMmbtu)
                                <h5>Total Deposit: <span class="text-success">{{ number_format($totalDepositMmbtu, 4) }} MMBTU</span></h5>
                                <h5>Total Pemakaian: <span class="text-danger">{{ number_format($totalConsumedMmbtu, 4) }} MMBTU</span></h5>
                                <h5>Saldo MMBTU: <span class="text-primary">{{ number_format($totalDepositMmbtu - $totalConsumedMmbtu, 4) }} MMBTU</span></h5>
                                @else
                                <h5>Total Deposit: <span class="text-success">Rp
                                        {{ number_format($customer->total_deposit, 2) }}</span></h5>
                                <h5>Total Pembelian: <span class="text-danger">Rp
                                        {{ number_format($customer->total_purchases, 2) }}</span></h5>
                                <h5>Saldo Tersisa: <span class="text-primary">Rp
                                        {{ number_format($customer->total_deposit - $customer->total_purchases, 2) }}</span>
                                </h5>
                                @endif
                            </div>
                            <div class="col-md-6 col-sm-12">
                                <button class="btn btn-primary w-100 mb-2" data-toggle="modal"
                                    data-target="#tambahDepositModal">
                                    <i class="fas fa-plus mr-1"></i> Tambah Deposit
                                </button>
                                <button class="btn btn-warning w-100 mb-2" data-toggle="modal"
                                    data-target="#penguranganSaldoModal">
                                    <i class="fas fa-minus mr-1"></i> Pengurangan Saldo
                                </button>
                                <button class="btn btn-info w-100" data-toggle="modal" data-target="#printDepositFilterModal">
                                    <i class="fas fa-print mr-1"></i> Print History Deposit
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
                                        @if($isMmbtu)
                                        <th>Volume (MMBTU)</th>
                                        <th>Harga Satuan</th>
                                        <th>Total USD</th>
                                        @else
                                        <th>Jumlah Deposit</th>
                                        @endif
                                        <th>Deskripsi</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        // Ensure deposit_history is an array before looping
                                        $depositHistory = $customer->getDepositHistoryWithKeterangan();
                                    @endphp

                                    @php
                                        $no = 1;
                                        // Sort deposit history by date (newest first)
                                        $sortedDeposits = collect($depositHistory)
                                            ->map(function ($deposit, $index) {
                                                return [
                                                    'index' => $index,
                                                    'date' => $deposit['date'] ?? '',
                                                    'amount' => $deposit['amount'] ?? 0,
                                                    'mmbtu_amount' => $deposit['mmbtu_amount'] ?? null,
                                                    'harga_satuan_usd' => $deposit['harga_satuan_usd'] ?? null,
                                                    'is_mmbtu' => $deposit['is_mmbtu'] ?? false,
                                                    'keterangan' => $deposit['keterangan'] ?? 'penambahan',
                                                    'deskripsi' =>
                                                        $deposit['deskripsi'] ?? ($deposit['description'] ?? '-'),
                                                ];
                                            })
                                            ->sortByDesc('date')
                                            ->values();
                                    @endphp

                                    @foreach ($sortedDeposits as $deposit)
                                        <tr>
                                            <td>{{ $no++ }}</td>
                                            <td>
                                                @if (!empty($deposit['date']))
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
                                            @if($isMmbtu)
                                            <td>{{ number_format($deposit['mmbtu_amount'] ?? 0, 4) }} MMBTU</td>
                                            <td>$ {{ number_format($deposit['harga_satuan_usd'] ?? 0, 4) }}</td>
                                            <td>
                                                @php $totalUsdDeposit = ($deposit['mmbtu_amount'] ?? 0) * ($deposit['harga_satuan_usd'] ?? 0); @endphp
                                                <span class="{{ $totalUsdDeposit >= 0 ? 'text-success' : 'text-danger' }}">
                                                    $ {{ number_format($totalUsdDeposit, 4) }}
                                                </span>
                                            </td>
                                            @else
                                            <td>
                                                @if ($deposit['amount'] >= 0)
                                                    <span class="text-success">Rp
                                                        {{ number_format($deposit['amount'] ?? 0, 2) }}</span>
                                                @else
                                                    <span class="text-danger">Rp
                                                        {{ number_format($deposit['amount'] ?? 0, 2) }}</span>
                                                @endif
                                            </td>
                                            @endif
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

        {{-- Tambah Deposit Modal --}}
        @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
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
                        @if($isMmbtu)
                        <form action="{{ route('customer.add-deposit-mmbtu', $customer->id) }}" method="POST"
                            id="tambahDepositForm">
                            @csrf
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>Volume Deposit (MMBTU) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" step="0.0001" name="mmbtu_amount" id="depositMmbtuAmount"
                                            class="form-control" placeholder="Jumlah MMBTU" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">MMBTU</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Harga Satuan (USD/MMBTU) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" step="0.0001" name="harga_satuan_usd" id="depositHargaUsd"
                                            class="form-control" placeholder="Harga per MMBTU (USD)" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">/MMBTU</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Preview Total USD</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="text" id="depositPreviewUsd" class="form-control bg-light"
                                            placeholder="0.00" readonly>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Tanggal Deposit <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="deposit_date" class="form-control"
                                        value="{{ now()->format('Y-m-d\TH:i') }}" required>
                                </div>
                                <div class="form-group mb-0">
                                    <label>Deskripsi (Opsional)</label>
                                    <textarea name="description" class="form-control" placeholder="Deskripsi deposit (opsional)" rows="2"></textarea>
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
                        @else
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
                                    <label>Deskripsi (Opsional)</label>
                                    <textarea name="description" class="form-control" placeholder="Deskripsi deposit (opsional)" rows="2"></textarea>
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
                        @endif
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
        @endif

        {{-- Pengurangan Saldo Modal --}}
        @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
            <div class="modal fade" id="penguranganSaldoModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-minus-circle mr-2"></i>Pengurangan Saldo
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
                                <button type="button" class="btn btn-danger" data-toggle="modal"
                                    data-target="#nolkanSaldoModal" data-dismiss="modal">
                                    <i class="fas fa-ban mr-1"></i>Nol-kan Saldo
                                </button>
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
        @endif

        {{-- Nol-kan Saldo Modal --}}
        @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
            <div class="modal fade" id="nolkanSaldoModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-ban mr-2"></i>Nol-kan Saldo
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form action="{{ route('customer.zero-balance', $customer->id) }}" method="POST"
                            id="nolkanSaldoForm">
                            @csrf
                            <div class="modal-body">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    <strong>Peringatan!</strong> Tindakan ini akan membuat saldo menjadi nol (0).
                                    <br><strong>Saldo Saat Ini:</strong> Rp
                                    {{ number_format($customer->getCurrentBalance(), 2) }}
                                </div>

                                <div class="form-group">
                                    <label>Tanggal <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="zero_date" class="form-control"
                                        value="{{ now()->format('Y-m-d\TH:i') }}" required>
                                </div>
                                <div class="form-group mb-0">
                                    <label>Deskripsi (Opsional)</label>
                                    <textarea name="description" class="form-control" placeholder="Alasan nol-kan saldo (opsional)" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                    <i class="fas fa-times mr-1"></i>Batal
                                </button>
                                <button type="submit" class="btn btn-danger"
                                    onclick="return confirm('Apakah Anda yakin ingin menol-kan saldo? Tindakan ini tidak dapat dibatalkan!')">
                                    <i class="fas fa-ban mr-1"></i>Ya, Nol-kan Saldo
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endif

    <!-- Modal untuk Setting Pricing and Correction yang diperbarui -->
    @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
        <div class="modal fade" id="setPricingModal" tabindex="-1" role="dialog"
            aria-labelledby="setPricingModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="setPricingModalLabel">
                            <i class="fas fa-cog mr-2"></i>Atur Harga & Koreksi Meter untuk Periode Baru
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <!-- Button Buat Periode Khusus di luar form -->
                    <div class="text-right p-3 bg-light">
                        <button type="button" class="btn btn-info" id="btnBuatPeriodeKhusus">
                            <i class="fas fa-calendar-alt mr-1"></i> Buat Periode Khusus
                        </button>
                    </div>
                    <form action="{{ $isMmbtu ? route('user.update-pricing-mmbtu', $customer->id) : route('user.update-pricing', $customer->id) }}" method="POST" id="pricingForm">
                        @csrf
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                Pengaturan harga dan koreksi meter ini akan disimpan untuk periode yang dipilih dan akan
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

                            <div class="row">
                                @if($isMmbtu)
                                <!-- Harga per MMBTU (USD) -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="modalHargaPerMmbtu"><strong>Harga per MMBTU (USD)</strong></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">$</span>
                                            </div>
                                            <input type="number" step="0.0001" name="harga_per_mmbtu_usd"
                                                id="modalHargaPerMmbtu"
                                                class="form-control"
                                                value="{{ old('harga_per_mmbtu_usd', $pricingInfo['harga_per_mmbtu_usd'] ?? 0) }}"
                                                placeholder="Harga per MMBTU dalam USD" required>
                                            <div class="input-group-append">
                                                <span class="input-group-text">/MMBTU</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pembagi SM3 ke MMBTU -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="modalPembaji"><strong>Pembagi SM3 → MMBTU</strong></label>
                                        <div class="input-group">
                                            <input type="number" step="0.000001" name="pembagi_sm3_ke_mmbtu"
                                                id="modalPembaji"
                                                class="form-control"
                                                value="{{ old('pembagi_sm3_ke_mmbtu', $pricingInfo['pembagi_sm3_ke_mmbtu'] ?? 1) }}"
                                                placeholder="Nilai pembagi SM3 ke MMBTU" required>
                                        </div>
                                    </div>
                                </div>
                                @else
                                <!-- Harga per meter kubik -->
                                <div class="col-md-6">
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
                                </div>
                                @endif

                                <!-- Tekanan keluar -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="modalTekananKeluar"><strong>Tekanan Keluar (Bar)</strong></label>
                                        <div class="input-group">
                                            <input type="number" step="0.0000000001" name="tekanan_keluar"
                                                id="modalTekananKeluar"
                                                class="form-control @error('tekanan_keluar') is-invalid @enderror"
                                                value="{{ old('tekanan_keluar', $customer->tekanan_keluar ?? 0) }}"
                                                placeholder="Tekanan Keluar" required>
                                            <div class="input-group-append">
                                                <span class="input-group-text">Bar</span>
                                            </div>
                                            @error('tekanan_keluar')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Suhu -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="modalSuhu"><strong>Suhu (°C)</strong></label>
                                        <div class="input-group">
                                            <input type="number" step="0.1" name="suhu" id="modalSuhu"
                                                class="form-control @error('suhu') is-invalid @enderror"
                                                value="{{ old('suhu', $customer->suhu ?? 0) }}" placeholder="Suhu"
                                                required>
                                            <div class="input-group-append">
                                                <span class="input-group-text">°C</span>
                                            </div>
                                            @error('suhu')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Hasil koreksi meter -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="modalHasilKoreksi">
                                            <strong>Hasil Koreksi Meter</strong>
                                            <i class="fas fa-info-circle text-info" data-toggle="tooltip"
                                                title="Koreksi meter dihitung otomatis berdasarkan tekanan keluar dan suhu"></i>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" step="0.0001" name="koreksi_meter"
                                                id="modalHasilKoreksi"
                                                class="form-control @error('koreksi_meter') is-invalid @enderror"
                                                value="{{ old('koreksi_meter', $customer->koreksi_meter ?? 1) }}"
                                                readonly>
                                            @error('koreksi_meter')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
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

        <!-- Modal untuk Setting Periode Khusus -->
        <div class="modal fade" id="setPeriodeKhususModal" tabindex="-1" role="dialog"
            aria-labelledby="setPeriodeKhususModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title" id="setPeriodeKhususModalLabel">
                            <i class="fas fa-calendar-alt mr-2"></i>Atur Harga & Koreksi Meter untuk Periode Khusus
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('user.update-pricing-khusus', $customer->id) }}" method="POST"
                        id="pricingKhususForm">
                        @csrf
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                Pengaturan harga dan koreksi meter ini akan disimpan untuk periode khusus yang dipilih dan
                                akan
                                berlaku untuk pencatatan pada rentang tanggal tersebut. Pengaturan ini lebih prioritas
                                dibandingkan
                                pengaturan periode bulanan.
                            </div>

                            <!-- Periode Khusus -->
                            <div class="form-group">
                                <label for="rangeDatePicker"><strong>Rentang Tanggal</strong></label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                            </div>
                                            <input type="date" name="start_date" id="startDate"
                                                class="form-control @error('start_date') is-invalid @enderror"
                                                value="{{ now()->format('Y-m-d') }}" required>
                                            @error('start_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                            </div>
                                            <input type="date" name="end_date" id="endDate"
                                                class="form-control @error('end_date') is-invalid @enderror"
                                                value="{{ now()->format('Y-m-d') }}" required>
                                            @error('end_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Harga per meter kubik -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="modalHargaPerM3Khusus"><strong>Harga per m³</strong></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Rp</span>
                                            </div>
                                            <input type="number" step="0.01" name="harga_per_meter_kubik"
                                                id="modalHargaPerM3Khusus"
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
                                </div>

                                <!-- Tekanan keluar -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="modalTekananKeluarKhusus"><strong>Tekanan Keluar (Bar)</strong></label>
                                        <div class="input-group">
                                            <input type="number" step="0.0000000001" name="tekanan_keluar"
                                                id="modalTekananKeluarKhusus"
                                                class="form-control @error('tekanan_keluar') is-invalid @enderror"
                                                value="{{ old('tekanan_keluar', $customer->tekanan_keluar ?? 0) }}"
                                                placeholder="Tekanan Keluar" required>
                                            <div class="input-group-append">
                                                <span class="input-group-text">Bar</span>
                                            </div>
                                            @error('tekanan_keluar')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Suhu -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="modalSuhuKhusus"><strong>Suhu (°C)</strong></label>
                                        <div class="input-group">
                                            <input type="number" step="0.1" name="suhu" id="modalSuhuKhusus"
                                                class="form-control @error('suhu') is-invalid @enderror"
                                                value="{{ old('suhu', $customer->suhu ?? 0) }}" placeholder="Suhu"
                                                required>
                                            <div class="input-group-append">
                                                <span class="input-group-text">°C</span>
                                            </div>
                                            @error('suhu')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Hasil koreksi meter -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="modalHasilKoreksiKhusus">
                                            <strong>Hasil Koreksi Meter</strong>
                                            <i class="fas fa-info-circle text-info" data-toggle="tooltip"
                                                title="Koreksi meter dihitung otomatis berdasarkan tekanan keluar dan suhu"></i>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" step="0.0001" name="koreksi_meter"
                                                id="modalHasilKoreksiKhusus"
                                                class="form-control @error('koreksi_meter') is-invalid @enderror"
                                                value="{{ old('koreksi_meter', $customer->koreksi_meter ?? 1) }}"
                                                readonly>
                                            @error('koreksi_meter')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer dengan tombol aksi -->
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                <i class="fas fa-times mr-1"></i>Batal
                            </button>
                            <button type="submit" class="btn btn-info">
                                <i class="fas fa-save mr-1"></i>Simpan Periode Khusus
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- History Pricing Modal --}}
    @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
        <div class="modal fade" id="pricingHistoryModal" tabindex="-1" role="dialog"
            aria-labelledby="pricingHistoryModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title" id="pricingHistoryModalLabel">
                            <i class="fas fa-history mr-2"></i>Riwayat Harga & Koreksi Meter
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
                                    <th>Tipe</th>
                                    <th>Periode</th>
                                    @if($isMmbtu)
                                    <th>Harga/MMBTU (USD)</th>
                                    <th>Pembagi SM3→MMBTU</th>
                                    @else
                                    <th>Harga per m³</th>
                                    @endif
                                    <th>Tekanan (Bar)</th>
                                    <th>Suhu (°C)</th>
                                    <th>Koreksi Meter</th>
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
                                        // Untuk periode khusus, gunakan start_date
                                        // Untuk bulanan, gunakan date atau year_month
                                        $dateA = null;
                                        $dateB = null;

                                        if (isset($a['type']) && $a['type'] === 'custom_period') {
                                            $dateA = $a['start_date'] ?? ($a['date'] ?? null);
                                        } else {
                                            $dateA = $a['date'] ?? ($a['year_month'] ?? null);
                                        }

                                        if (isset($b['type']) && $b['type'] === 'custom_period') {
                                            $dateB = $b['start_date'] ?? ($b['date'] ?? null);
                                        } else {
                                            $dateB = $b['date'] ?? ($b['year_month'] ?? null);
                                        }

                                        // Konversi ke timestamp untuk perbandingan
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
                                            @if (isset($pricing['type']) && $pricing['type'] === 'custom_period')
                                                <span class="badge badge-info">Periode Khusus</span>
                                            @else
                                                <span class="badge badge-primary">Bulanan</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if (isset($pricing['type']) && $pricing['type'] === 'custom_period')
                                                @if (isset($pricing['start_date']) && isset($pricing['end_date']))
                                                    {{ \Carbon\Carbon::parse($pricing['start_date'])->format('d M Y') }} -
                                                    {{ \Carbon\Carbon::parse($pricing['end_date'])->format('d M Y') }}
                                                @else
                                                    Periode khusus tidak tersedia
                                                @endif
                                            @else
                                                @if (isset($pricing['date']))
                                                    {{ \Carbon\Carbon::parse($pricing['date'])->format('F Y') }}
                                                @else
                                                    Periode tidak tersedia
                                                @endif
                                            @endif
                                        </td>
                                        @if($isMmbtu)
                                        <td>$ {{ number_format($pricing['harga_per_mmbtu_usd'] ?? 0, 2) }}</td>
                                        <td>{{ number_format($pricing['pembagi_sm3_ke_mmbtu'] ?? $pricing['pembaji_sm3_ke_mmbtu'] ?? 1, 2) }}</td>
                                        @else
                                        <td>Rp {{ number_format($pricing['harga_per_meter_kubik'] ?? 0, 2) }}</td>
                                        @endif
                                        <td>{{ number_format($pricing['tekanan_keluar'] ?? 0, 2) }} Bar</td>
                                        <td>{{ number_format($pricing['suhu'] ?? 0, 2) }} °C</td>
                                        <td>{{ number_format($pricing['koreksi_meter'] ?? 1, 8) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif


    {{-- Upload Excel Modal --}}
    @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
        <div class="modal fade" id="uploadExcelModal" tabindex="-1" role="dialog"
            aria-labelledby="uploadExcelModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="uploadExcelModalLabel">
                            <i class="fas fa-file-excel mr-2"></i>Upload Data Excel
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('data-pencatatan.import-excel', $customer->id) }}" method="POST"
                        enctype="multipart/form-data" id="formUploadExcel">
                        @csrf
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                Upload file Excel dengan format yang sesuai. File akan diproses dan data akan ditambahkan ke
                                riwayat pencatatan. Waktu pembacaan awal dan akhir yang sama diperbolehkan.
                                Data yang diinputkan akan terinput sesuai dengan tanggal nya.
                            </div>

                            @if (session('import_errors'))
                                <div class="alert alert-danger">
                                    <i class="fas fa-times-circle mr-2"></i>
                                    <strong>Error saat import data:</strong>
                                    <ul class="mt-2 mb-0">
                                        @foreach (session('import_errors') as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <div class="form-group">
                                <label for="excelFile">Pilih File Excel <span class="text-danger">*</span></label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="excelFile" name="excel_file"
                                        accept=".xlsx,.xls" required>
                                    <label class="custom-file-label" for="excelFile">Pilih file...</label>
                                </div>
                                <small class="form-text text-muted">File harus berformat .xlsx atau .xls</small>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="skipValidation"
                                        name="skip_validation">
                                    <label class="custom-control-label" for="skipValidation">Lewati validasi volume (tidak
                                        disarankan)</label>
                                </div>
                                <small class="form-text text-muted">Hanya gunakan jika yakin data Excel sudah benar</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-info" id="btnViewTemplate">
                                <i class="fas fa-info-circle mr-1"></i>Lihat Template
                            </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                <i class="fas fa-times mr-1"></i>Batal
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-file-import mr-1"></i>Import Data
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Print Deposit History Filter Modal --}}
        <div class="modal fade" id="printDepositFilterModal" tabindex="-1" role="dialog"
            aria-labelledby="printDepositFilterModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title" id="printDepositFilterModalLabel">
                            <i class="fas fa-print mr-2"></i>Print History Deposit
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('customer.print-deposit-history', $customer->id) }}" method="GET" id="printDepositFilterForm" target="_blank">
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                Pilih rentang tanggal untuk mencetak history deposit.
                            </div>
                            <div class="form-group">
                                <label for="deposit_tanggal_mulai"><strong>Tanggal Mulai:</strong></label>
                                <input type="date" name="tanggal_mulai" id="deposit_tanggal_mulai" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="deposit_tanggal_akhir"><strong>Tanggal Akhir:</strong></label>
                                <input type="date" name="tanggal_akhir" id="deposit_tanggal_akhir" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                <i class="fas fa-times mr-1"></i>Batal
                            </button>
                            <button type="submit" class="btn btn-info">
                                <i class="fas fa-print mr-1"></i>Print
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Template Excel Modal --}}
        <div class="modal fade" id="templateExcelModal" tabindex="-1" role="dialog"
            aria-labelledby="templateExcelModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title" id="templateExcelModalLabel">
                            <i class="fas fa-info-circle mr-2"></i>Format Template Excel
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            Pastikan format Excel Anda sesuai dengan format berikut:
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th colspan="3">Pembacaan Awal</th>
                                        <th colspan="3">Pembacaan Akhir</th>
                                    </tr>
                                    <tr>
                                        <th></th>
                                        <th>Tanggal</th>
                                        <th>Jam</th>
                                        <th>Meter</th>
                                        <th>Tanggal</th>
                                        <th>Jam</th>
                                        <th>Meter</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>1-May-24</td>
                                        <td>7:00</td>
                                        <td>1,928.20</td>
                                        <td>1-May-24</td>
                                        <td>18:00</td>
                                        <td>1,928.20</td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td>2-May-24</td>
                                        <td>7:00</td>
                                        <td>1,928.20</td>
                                        <td>2-May-24</td>
                                        <td>18:00</td>
                                        <td>2,057.98</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Catatan penting:</strong>
                            <ul class="mb-0 mt-1">
                                <li>Format tanggal harus sesuai (dd-MMM-yy atau format tanggal lainnya yang konsisten)</li>
                                <li>Pembacaan akhir tidak boleh lebih kecil dari pembacaan awal pada baris yang sama</li>
                                <li>Pembacaan awal harus sama dengan pembacaan akhir dari baris sebelumnya</li>
                                <li>Volume meter harus mengikuti format angka dengan pemisah ribuan koma (,)</li>
                                <li>Waktu pembacaan awal dan waktu pembacaan akhir yang sama diperbolehkan</li>
                            </ul>
                        </div>


                    </div>
                    <div class="modal-footer">

                        <button type="button" class="btn btn-info" id="btnDownloadTemplate">
                            <i class="fas fa-download mr-1"></i>Download Template
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('css')
    <!-- Date Range Picker CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <link rel="stylesheet" href="{{ asset('css/customer-detail.css') }}?v={{ time() }}">
    <style>
        /* Daterangepicker z-index untuk modal */
        .daterangepicker {
            z-index: 10000 !important;
        }
        .table-bordered th,
        .table-bordered td {
            vertical-align: middle !important;
        }

        /* Highlight filtered rows */
        tr.filtered-row {
            background-color: #e8f4ff !important;
        }

        /* Highlight rows with data */
        tr.has-data {
            background-color: rgba(40, 167, 69, 0.05) !important;
            /* slight green tint */
        }

        tr.has-data:hover {
            background-color: rgba(40, 167, 69, 0.1) !important;
        }

        /* Style untuk baris tanpa data */
        tr.no-data {
            background-color: #f8f9fa !important;
        }

        tr.no-data:hover {
            background-color: #e9ecef !important;
        }

        #setPricingModal .form-group {
            margin-bottom: 1.5rem;
        }

        #setPricingModal .input-group-text {
            background-color: #f8f9fa;
            border-color: #ced4da;
        }

        #setPricingModal .form-control {
            height: calc(2.25rem + 2px);
            font-size: 1rem;
        }

        #setPricingModal label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #495057;
        }

        #setPricingModal .alert-info {
            background-color: #e3f2fd;
            border-color: #b3e5fc;
            color: #0277bd;
        }

        #setPricingModal #modalHasilKoreksi {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
        }

        #setPricingModal .btn {
            border-radius: 4px;
            padding: 0.375rem 1rem;
        }

        #setPricingModal .input-group-prepend .input-group-text,
        #setPricingModal .input-group-append .input-group-text {
            min-width: 40px;
            justify-content: center;
        }

        #setPricingModal .modal-header {
            padding: 1rem;
            background: linear-gradient(135deg, #007bff, #0055cc);
        }

        #setPricingModal .modal-footer {
            border-top: 1px solid #e9ecef;
            padding: 1rem;
        }

        #setPricingModal .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        /* Styling untuk badge info */
        .badge-info {
            background-color: #17a2b8;
            color: white;
            cursor: help;
            margin-left: 5px;
            padding: 2px 5px;
        }

        /* Styling untuk tombol selaraskan data */
        .btn-warning.btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.765625rem;
            line-height: 1.5;
            border-radius: 0.2rem;
        }

        /* Styling untuk button deposit dan pengurangan saldo */
        .btn-primary.w-100.mb-2 {
            margin-bottom: 0.5rem !important;
        }

        .btn-warning.w-100 {
            border-color: #ffc107;
            background-color: #ffc107;
            color: #212529;
        }

        .btn-warning.w-100:hover {
            border-color: #e0a800;
            background-color: #e0a800;
            color: #212529;
        }

        /* Styling untuk badge keterangan */
        .badge-success {
            background-color: #28a745;
        }

        .badge-danger {
            background-color: #dc3545;
        }

        /* Styling untuk amount text colors */
        .text-success {
            color: #28a745 !important;
        }

        .text-danger {
            color: #dc3545 !important;
        }

        /* Modal styling improvements */
        .modal-header.bg-warning {
            background-color: #ffc107 !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
        }

        .modal-header.bg-danger {
            background-color: #dc3545 !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
        }

        .modal-header.bg-warning .modal-title,
        .modal-header.bg-danger .modal-title {
            color: #fff;
        }

        /* Alert styling dalam modal */
        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }

        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }

        /* Memperbaiki tampilan input type="month" pada berbagai browser */
        input[type="month"] {
            -webkit-appearance: none;
            appearance: none;
            padding: 0.375rem 0.75rem;
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
    </style>
@endsection

@section('js')
    <!-- Moment.js and Date Range Picker JS -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    
    <script>
        // Function untuk navigasi ke bulan tertentu dan membuka modal pricing
        function navigateAndOpenPricing(month, year) {
            // Set value di form filter
            $('#bulan').val(month);
            $('#tahun').val(year);

            // Set value di modal pricing
            var yearMonth = year + '-' + month.toString().padStart(2, '0');
            $('#pricingDate').val(yearMonth);

            // Submit form filter
            $('#filter-form').submit();

            // Setelah halaman reload, modal akan dibuka via URL parameter
            // Alternatif: gunakan sessionStorage untuk membuka modal setelah reload
            sessionStorage.setItem('openPricingModal', 'true');
            sessionStorage.setItem('pricingMonth', month);
            sessionStorage.setItem('pricingYear', year);
        }

        // MMBTU deposit USD preview calculator
        @if($isMmbtu)
        $(document).on('input', '#depositMmbtuAmount, #depositHargaUsd', function() {
            var mmbtu = parseFloat($('#depositMmbtuAmount').val()) || 0;
            var harga = parseFloat($('#depositHargaUsd').val()) || 0;
            var total = mmbtu * harga;
            $('#depositPreviewUsd').val(total.toFixed(4));
        });
        @endif

        // JavaScript untuk Excel telah dipindahkan ke dalam $(function() {})
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
            // File name display for custom file input
            $(document).on('change', '.custom-file-input', function() {
                let fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass('selected').html(fileName);
            });

            // Initialize tooltips
            $('[data-toggle="tooltip"], [title]').tooltip();

            // Show template modal when clicking on template link
            $('#btnShowTemplate, #btnViewTemplate').on('click', function(e) {
                e.preventDefault();
                $('#uploadExcelModal').modal('hide'); // Hide upload modal if open
                setTimeout(function() {
                    $('#templateExcelModal').modal('show');
                }, 500);
            });

            // Handle download template button dengan metode yang lebih sederhana
            $('#btnDownloadTemplate').on('click', function(e) {
                e.preventDefault();
                window.open('{{ asset('templates/template_pencatatan.xlsx') }}', '_blank');
            });

            // Tombol untuk membuka modal periode khusus - pastikan event handler bekerja
            $(document).on('click', '#btnBuatPeriodeKhusus', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Tombol periode khusus diklik');
                $('#setPricingModal').modal('hide');
                setTimeout(function() {
                    $('#setPeriodeKhususModal').modal('show');
                }, 500);
            });

            // Menangani penutupan modal periode khusus
            $('#setPeriodeKhususModal').on('hidden.bs.modal', function(e) {
                // Hapus semua backdrop modal yang mungkin tersisa
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
                $('body').css('padding-right', '');
            });

            // Tombol batal pada modal periode khusus
            $(document).on('click', '#setPeriodeKhususModal button[data-dismiss="modal"]', function(e) {
                e.preventDefault();
                $('#setPeriodeKhususModal').modal('hide');
                // Hapus backdrop dan kembalikan halaman ke normal
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
                $('body').css('padding-right', '');
            });

            // Handle form submission with loading indicator
            $('#formUploadExcel').on('submit', function() {
                // Show loading spinner
                $(this).find('button[type="submit"]').html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' +
                    'Mengimport...'
                ).prop('disabled', true);

                // Continue with form submission
                return true;
            });

            // Check if we need to show error modal
            @if (session('import_errors'))
                $('#uploadExcelModal').modal('show');
            @endif

            // // Show loading animation
            // $('body').append('<div id="page-loader" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.9); z-index: 9999; display: flex; justify-content: center; align-items: center;"><div style="text-align: center;"><div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div><p style="margin-top: 10px; font-weight: bold; color: #4e73df;">Loading...</p></div></div>');

            // Page fully loaded - remove any leftover loader
            $(window).on('load', function() {
                $('#page-loader').remove();
            });

            // DataTables initialization
            if (typeof $.fn.DataTable !== 'undefined') {
                // Suppress DataTables warning popups
                $.fn.DataTable.ext.errMode = 'none';

                ['#dataPencatatanTable', '#depositHistoryTable', '#pricingHistoryTable'].forEach(function(id) {
                    if ($.fn.DataTable.isDataTable(id)) {
                        $(id).DataTable().destroy();
                    }
                });

                var table = $("#dataPencatatanTable").DataTable({
                    "responsive": true,
                    "lengthChange": false,
                    "autoWidth": false,
                    "ordering": true,
                    "order": [[0, 'asc']],
                    "language": {
                        "emptyTable": "Tidak ada data pencatatan tersedia",
                        "zeroRecords": "Tidak ada data yang cocok ditemukan",
                        "info": "Menampilkan _START_ hingga _END_ dari _TOTAL_ entri",
                        "infoEmpty": "Menampilkan 0 hingga 0 dari 0 entri",
                        "infoFiltered": "(disaring dari _MAX_ total entri)",
                        "search": "Cari:",
                        "paginate": {"first": "Pertama", "last": "Terakhir", "next": "Selanjutnya", "previous": "Sebelumnya"}
                    },
                    "pageLength": 31,
                    "drawCallback": function(settings) {
                        $('.table-light').find('td').css('background-color', '#f8f9fa');
                    }
                });

                $("#depositHistoryTable").DataTable({
                    "responsive": true, "lengthChange": false, "autoWidth": false,
                    "ordering": true, "order": [[1, 'desc']],
                    "columnDefs": [{"type": "date", "targets": 1}],
                    "language": {"emptyTable": "Tidak ada riwayat deposit", "search": "Cari:"}
                });

                $("#pricingHistoryTable").DataTable({
                    "responsive": true, "lengthChange": false, "autoWidth": false,
                    "ordering": true, "order": [[1, 'desc']],
                    "language": {"emptyTable": "Tidak ada riwayat harga", "search": "Cari:"}
                });
            } // end DataTable check

            function formatNumber(number, decimals = 2) {
                return number.toLocaleString('id-ID', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals
                });
            }
            // Fungsi untuk menghitung koreksi meter
            function hitungKoreksiMeter() {
                // Ambil nilai tekanan keluar dan suhu
                const tekananKeluar = parseFloat($('#modalTekananKeluar').val()) || 0;
                const suhu = parseFloat($('#modalSuhu').val()) || 0;

                // Perhitungan koreksi meter
                const A = (tekananKeluar + 1.01325) / 1.01325;
                const B = 300 / (suhu + 273);
                const C = 1 + (0.002 * tekananKeluar);

                const hasilKoreksi = A * B * C;

                // Update readonly field
                $('#modalHasilKoreksi').val(hasilKoreksi.toFixed(8));
            }

            // Trigger calculation on input changes in modal
            $('#modalTekananKeluar, #modalSuhu').on('input', function() {
                hitungKoreksiMeter();
            });

            // Trigger initial calculation when modal opens
            $('#setPricingModal').on('show.bs.modal', function() {
                hitungKoreksiMeter();

                // Fokus pada input pertama setelah modal terbuka
                setTimeout(function() {
                    $('#pricingDate').focus();
                }, 500);
            });
            // Validasi input tanggal pricing
            $('#pricingDate').on('change', function() {
                // Memastikan bahwa tanggal yang dipilih valid
                const selectedDate = $(this).val();
                if (!selectedDate) {
                    $(this).addClass('is-invalid');
                    $(this).after('<div class="invalid-feedback">Periode harus diisi</div>');
                } else {
                    $(this).removeClass('is-invalid');
                    $(this).siblings('.invalid-feedback').remove();
                }
            });
            $('#pricingForm').on('submit', function(e) {
                const tekananKeluar = parseFloat($('#modalTekananKeluar').val());
                const suhu = parseFloat($('#modalSuhu').val());
                let hasError = false;

                // Validasi harga — cek field mana yang ada
                const $hargaM3 = $('#modalHargaPerM3');
                const $hargaMmbtu = $('#modalHargaPerMmbtu');
                const $pembagi = $('#modalPembaji');

                if ($hargaMmbtu.length > 0) {
                    // MMBTU customer
                    const hargaPerMmbtu = parseFloat($hargaMmbtu.val());
                    if (isNaN(hargaPerMmbtu) || hargaPerMmbtu < 0) {
                        $hargaMmbtu.addClass('is-invalid');
                        hasError = true;
                    } else {
                        $hargaMmbtu.removeClass('is-invalid');
                    }
                    if ($pembagi.length > 0) {
                        const pembagi = parseFloat($pembagi.val());
                        if (isNaN(pembagi) || pembagi <= 0) {
                            $pembagi.addClass('is-invalid');
                            hasError = true;
                        } else {
                            $pembagi.removeClass('is-invalid');
                        }
                    }
                } else if ($hargaM3.length > 0) {
                    // Regular customer
                    const hargaPerM3 = parseFloat($hargaM3.val());
                    if (isNaN(hargaPerM3) || hargaPerM3 < 0) {
                        $hargaM3.addClass('is-invalid');
                        hasError = true;
                    } else {
                        $hargaM3.removeClass('is-invalid');
                    }
                }

                // Validasi tekanan & suhu — hanya untuk regular customer (bukan MMBTU)
                if ($('#modalTekananKeluar').length > 0) {
                    if (isNaN(tekananKeluar) || tekananKeluar < 0) {
                        $('#modalTekananKeluar').addClass('is-invalid');
                        hasError = true;
                    } else {
                        $('#modalTekananKeluar').removeClass('is-invalid');
                    }
                }

                if ($('#modalSuhu').length > 0) {
                    if (isNaN(suhu)) {
                        $('#modalSuhu').addClass('is-invalid');
                        hasError = true;
                    } else {
                        $('#modalSuhu').removeClass('is-invalid');
                    }
                }

                if (hasError) {
                    e.preventDefault();
                    return false;
                }
            });

            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Tambah tombol lihat riwayat pricing pada header
            $('.card-tools').prepend(
                '<button class="btn btn-info btn-sm mr-2" data-toggle="modal" data-target="#pricingHistoryModal">' +
                '<i class="fas fa-history mr-1"></i> Riwayat Harga' +
                '</button>'
            );

            // Menghitung koreksi meter untuk modal periode khusus
            function hitungKoreksiMeterKhusus() {
                // Ambil nilai tekanan keluar dan suhu
                const tekananKeluar = parseFloat($('#modalTekananKeluarKhusus').val()) || 0;
                const suhu = parseFloat($('#modalSuhuKhusus').val()) || 0;

                // Perhitungan koreksi meter
                const A = (tekananKeluar + 1.01325) / 1.01325;
                const B = 300 / (suhu + 273);
                const C = 1 + (0.002 * tekananKeluar);

                const hasilKoreksi = A * B * C;

                // Update readonly field
                $('#modalHasilKoreksiKhusus').val(hasilKoreksi.toFixed(8));
            }

            // Trigger calculation on input changes in modal periode khusus
            $('#modalTekananKeluarKhusus, #modalSuhuKhusus').on('input', function() {
                hitungKoreksiMeterKhusus();
            });

            // Trigger initial calculation when modal opens
            $('#setPeriodeKhususModal').on('show.bs.modal', function() {
                hitungKoreksiMeterKhusus();

                // Set tanggal default (hari ini sampai akhir bulan)
                const today = new Date();
                const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);

                $('#startDate').val(today.toISOString().split('T')[0]);
                $('#endDate').val(endOfMonth.toISOString().split('T')[0]);
            });

            // Validasi form periode khusus
            $('#pricingKhususForm').on('submit', function(e) {
                const startDate = new Date($('#startDate').val());
                const endDate = new Date($('#endDate').val());
                const hargaPerM3 = parseFloat($('#modalHargaPerM3Khusus').val());
                const tekananKeluar = parseFloat($('#modalTekananKeluarKhusus').val());
                const suhu = parseFloat($('#modalSuhuKhusus').val());

                let hasError = false;

                // Validasi tanggal
                if (startDate > endDate) {
                    $('#startDate, #endDate').addClass('is-invalid');
                    alert('Tanggal awal tidak boleh lebih besar dari tanggal akhir');
                    hasError = true;
                } else {
                    $('#startDate, #endDate').removeClass('is-invalid');
                }

                // Validasi harga
                if (isNaN(hargaPerM3) || hargaPerM3 < 0) {
                    $('#modalHargaPerM3Khusus').addClass('is-invalid');
                    hasError = true;
                } else {
                    $('#modalHargaPerM3Khusus').removeClass('is-invalid');
                }

                // Validasi tekanan
                if (isNaN(tekananKeluar) || tekananKeluar < 0) {
                    $('#modalTekananKeluarKhusus').addClass('is-invalid');
                    hasError = true;
                } else {
                    $('#modalTekananKeluarKhusus').removeClass('is-invalid');
                }

                // Validasi suhu
                if (isNaN(suhu)) {
                    $('#modalSuhuKhusus').addClass('is-invalid');
                    hasError = true;
                } else {
                    $('#modalSuhuKhusus').removeClass('is-invalid');
                }

                if (hasError) {
                    e.preventDefault();
                    return false;
                }

                // Tambahan: bersihkan backdrop modal saat form di-submit
                $(this).find('button[type="submit"]').html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' +
                    'Menyimpan...'
                ).prop('disabled', true);

                // Hapus event listener default agar tidak terjadi konflik
                $('#setPeriodeKhususModal').off('hidden.bs.modal');
            });

            // Cards visible by default (page-loader animation removed)

            // Set default date values untuk print deposit modal
            $('#printDepositFilterModal').on('shown.bs.modal', function() {
                // Set default tanggal ke bulan ini jika belum ada value
                if (!$('#deposit_tanggal_mulai').val()) {
                    var today = new Date();
                    var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                    var lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);

                    $('#deposit_tanggal_mulai').val(firstDay.toISOString().split('T')[0]);
                    $('#deposit_tanggal_akhir').val(lastDay.toISOString().split('T')[0]);
                }
            });

            // Auto submit form when filter changes (optional)
            /*
            $('#bulan, #tahun').on('change', function() {
                $('#filter-form').submit();
            });
            */
        });

        // SIMPLE: Handle Edit Deposit - Outside $(function) to ensure it always works
        $(document).ready(function() {
            $(document).on('click', '.btn-edit-deposit', function() {
                console.log('Edit button clicked!');
                
                var index = $(this).attr('data-index');
                var date = $(this).attr('data-date');
                var amount = $(this).attr('data-amount');
                var keterangan = $(this).attr('data-keterangan');
                var deskripsi = $(this).attr('data-deskripsi');
                
                console.log('Data:', index, date, amount, keterangan, deskripsi);
                
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
