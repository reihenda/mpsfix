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
                                    <strong><i class="fas fa-wallet mr-1"></i> Saldo Total</strong>
                                    <p class="text-muted mb-0">
                                        Rp
                                        {{ number_format(($customer->total_deposit ?? 0) - ($customer->total_purchases ?? 0), 0) }}
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
                                    <strong><i class="fas fa-money-bill-wave mr-1"></i> Harga per Sm³</strong>
                                    <p class="text-muted mb-0">
                                        Rp
                                        {{ number_format($pricingInfo['harga_per_meter_kubik'] ?? ($customer->harga_per_meter_kubik ?? 0), 2) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-gas-pump mr-1"></i> Volume Periode Ini</strong>
                                    <p class="text-muted mb-0">
                                        {{ number_format($filteredVolumeSm3, 2) }} Sm³
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-money-bill-wave mr-1"></i> Pembelian Periode Ini</strong>
                                    <p class="text-muted mb-0">
                                        Rp {{ number_format($filteredTotalPurchases, 0) }}
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-wallet mr-1"></i> Deposit Periode Ini</strong>
                                    <p class="text-muted mb-0">
                                        Rp {{ number_format($filteredTotalDeposits, 0) }}
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-balance-scale mr-1"></i> Saldo Periode Bulan Ini</strong>
                                    <p class="text-muted mb-0">
                                        Rp
                                        {{ number_format($filteredTotalDeposits - $filteredTotalPurchases + $prevMonthBalance, 0) }}
                                        <span class="badge badge-info"
                                            title="Saldo untuk periode bulan yang dipilih saja"><i
                                                class="fas fa-info-circle"></i></span>
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
                                                @php
                                                    // Definisikan variabel currentYearMonth terlebih dahulu
                                                    $currentYearMonth = \Carbon\Carbon::createFromDate(
                                                        $selectedTahun,
                                                        $selectedBulan,
                                                        1,
                                                    )->format('Y-m');

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

                                                        $itemWaktuAwal = Carbon\Carbon::parse(
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
                                                    $deposits = is_string($customer->deposit_history)
                                                        ? json_decode($customer->deposit_history, true)
                                                        : $customer->deposit_history;
                                                    if (is_array($deposits)) {
                                                        foreach ($deposits as $deposit) {
                                                            if (isset($deposit['date'])) {
                                                                $depositDate = Carbon\Carbon::parse($deposit['date']);
                                                                if ($depositDate->format('Y-m') === $currentYearMonth) {
                                                                    $realTimeFilteredDeposits += floatval(
                                                                        $deposit['amount'] ?? 0,
                                                                    );
                                                                }
                                                            }
                                                        }
                                                    }

                                                    // Hitung saldo bulan ini
                                                    $realTimeCurrentMonthBalance =
                                                        $prevMonthBalance +
                                                        $realTimeFilteredDeposits -
                                                        $realTimeFilteredTotalPurchases;
                                                @endphp
                                                <tr class="font-weight-bold">
                                                    <td>= Sisa Saldo Periode Bulan Ini</td>
                                                    <td>Rp {{ number_format($realTimeCurrentMonthBalance, 0) }}</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2" class="text-muted"><small>* Saldo ini hanya
                                                            menunjukkan saldo untuk periode
                                                            {{ \Carbon\Carbon::createFromDate($selectedTahun, $selectedBulan, 1)->format('F Y') }}
                                                            saja</small></td>
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
                    <table class="table table-bordered table-striped table-hover" id="dataPencatatanTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th colspan="2">Pembacaan Awal</th>
                                <th colspan="2">Pembacaan Akhir</th>
                                <th>Volume</th>
                                <th>Sm³</th>
                                <th>Rupiah</th>
                                <th>Aksi</th>
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

                                // Buat array tanggal untuk periode penuh dan tandai tanggal yang memiliki data
                                $allDatesInPeriod = [];
                                $currentDate = clone $startDate;
                                while ($currentDate->lte($endDate)) {
                                    $dateKey = $currentDate->format('Y-m-d');
                                    $allDatesInPeriod[$dateKey] = null;
                                    $currentDate->addDay();
                                }

                                // Debug array tanggal
                                echo '<!-- DEBUG: Total dates in period: ' . count($allDatesInPeriod) . ' -->';
                                echo '<!-- DEBUG: Period date keys: ' .
                                    implode(',', array_keys($allDatesInPeriod)) .
                                    ' -->';

                                // Masukkan data pencatatan yang ada ke array berdasarkan tanggal
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
                                            $allDatesInPeriod[$recordDateStr] = $record;
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
                                                    $allDatesInPeriod[$periodDate] = $record; // gunakan key dari period
                                                    $recordsFound++;
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }
                                echo '<!-- DEBUG: Records found in period: ' . $recordsFound . ' -->';
                            @endphp

                            @forelse($allDatesInPeriod as $date => $record)
                                <tr class="{{ $record ? 'has-data' : 'table-light no-data' }}">
                                    <td>{{ $no++ }}</td>
                                    <td>
                                        @if ($record)
                                            @php
                                                $dataInput = is_string($record->data_input)
                                                    ? json_decode($record->data_input, true)
                                                    : (is_array($record->data_input)
                                                        ? $record->data_input
                                                        : []);
                                            @endphp
                                            {{ \Carbon\Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('d M Y H:i') }}
                                        @else
                                            {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
                                        @endif
                                    </td>
                                    <td>
                                        @if ($record)
                                            @php
                                                $dataInput = is_string($record->data_input)
                                                    ? json_decode($record->data_input, true)
                                                    : (is_array($record->data_input)
                                                        ? $record->data_input
                                                        : []);
                                                $pembacaanAwal = $dataInput['pembacaan_awal'] ?? ['volume' => 0];
                                            @endphp
                                            {{ number_format($pembacaanAwal['volume'] ?? 0, 2) }} m³
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($record)
                                            @php
                                                $dataInput = is_string($record->data_input)
                                                    ? json_decode($record->data_input, true)
                                                    : (is_array($record->data_input)
                                                        ? $record->data_input
                                                        : []);
                                            @endphp
                                            {{ \Carbon\Carbon::parse($dataInput['pembacaan_akhir']['waktu'])->format('d M Y H:i') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($record)
                                            @php
                                                $dataInput = is_string($record->data_input)
                                                    ? json_decode($record->data_input, true)
                                                    : (is_array($record->data_input)
                                                        ? $record->data_input
                                                        : []);
                                                $pembacaanAkhir = $dataInput['pembacaan_akhir'] ?? ['volume' => 0];
                                            @endphp
                                            {{ number_format($pembacaanAkhir['volume'] ?? 0, 2) }} m³
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($record)
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
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($record)
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
                                            {{ number_format($volumeSm3, 2) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($record)
                                            @php
                                                // Hitung Pembelian dengan harga sesuai periode
                                                $hargaPerM3 = floatval(
                                                    $itemPricingInfo['harga_per_meter_kubik'] ??
                                                        $customer->harga_per_meter_kubik,
                                                );
                                                $pembelian = $volumeSm3 * $hargaPerM3;
                                            @endphp
                                            Rp {{ number_format($pembelian, 2) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($record)
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
                                        @else
                                            @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
                                                <a href="{{ route('data-pencatatan.create-with-customer', ['customerId' => $customer->id, 'tanggal' => $date]) }}"
                                                    class="btn btn-success btn-sm">
                                                    <i class="fas fa-plus"></i> Input Data
                                                </a>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">Belum ada data pencatatan dalam periode ini.
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
                                        foreach ($allDatesInPeriod as $date => $record) {
                                            if ($record) {
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

                                                $itemPricingInfo = $customer->getPricingForYearMonth(
                                                    $waktuAwalYearMonth,
                                                    $waktuAwalDatetime,
                                                );

                                                $koreksiMeter = floatval(
                                                    $itemPricingInfo['koreksi_meter'] ?? $customer->koreksi_meter,
                                                );
                                                $volumeFlowMeter = $dataInput['volume_flow_meter'] ?? 0;
                                                $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
                                                $totalVolumeSm3Period += $volumeSm3;
                                            }
                                        }
                                    @endphp
                                    {{ number_format($totalVolumeSm3Period, 2) }}
                                </th>
                                <th>
                                    @php
                                        $totalPembelianPeriod = 0;
                                        foreach ($allDatesInPeriod as $date => $record) {
                                            if ($record) {
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

                                                $itemPricingInfo = $customer->getPricingForYearMonth(
                                                    $waktuAwalYearMonth,
                                                    $waktuAwalDatetime,
                                                );

                                                $koreksiMeter = floatval(
                                                    $itemPricingInfo['koreksi_meter'] ?? $customer->koreksi_meter,
                                                );
                                                $hargaPerM3 = floatval(
                                                    $itemPricingInfo['harga_per_meter_kubik'] ??
                                                        $customer->harga_per_meter_kubik,
                                                );
                                                $volumeFlowMeter = $dataInput['volume_flow_meter'] ?? 0;
                                                $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
                                                $pembelian = $volumeSm3 * $hargaPerM3;
                                                $totalPembelianPeriod += $pembelian;
                                            }
                                        }
                                    @endphp
                                    Rp {{ number_format($totalPembelianPeriod, 0) }}
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
                                            <td>
                                                @if ($deposit['amount'] >= 0)
                                                    <span class="text-success">Rp
                                                        {{ number_format($deposit['amount'] ?? 0, 2) }}</span>
                                                @else
                                                    <span class="text-danger">Rp
                                                        {{ number_format($deposit['amount'] ?? 0, 2) }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $deposit['deskripsi'] ?? '-' }}</td>
                                            <td>
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
                    <form action="{{ route('user.update-pricing', $customer->id) }}" method="POST" id="pricingForm">
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
                                    <th>Harga per m³</th>
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
                                        <td>Rp {{ number_format($pricing['harga_per_meter_kubik'] ?? 0, 2) }}</td>
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
    <link rel="stylesheet" href="{{ asset('css/customer-detail.css') }}?v={{ time() }}">
    <style>
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
    <script>
        // JavaScript untuk Excel telah dipindahkan ke dalam $(function() {})
        $(function() {
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

            // Hide loading animation after page fully loads
            $(window).on('load', function() {
                $('#page-loader').fadeOut(500, function() {
                    $(this).remove();
                    // Animate elements one by one
                    $('.card').each(function(i) {
                        $(this).delay(i * 150).animate({
                            'opacity': 1
                        }, 500);
                    });
                    $('.mobile-summary-card').each(function(i) {
                        $(this).delay(i * 100).animate({
                            'opacity': 1
                        }, 500);
                    });
                });
            });

            // DataTables initialization
            var table = $("#dataPencatatanTable").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "ordering": true,
                "order": [
                    [0, 'asc'] // Order by No column (ascending - dari tanggal 1 ke akhir bulan)
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
                },
                "pageLength": 31, // Menampilkan hingga 31 data (maksimal jumlah hari dalam sebulan)
                "drawCallback": function(settings) {
                    // Tambahkan styling untuk baris yang belum ada data
                    $('.table-light').find('td').css('background-color', '#f8f9fa');
                    // Tambahkan debug log
                    console.log("DataTable redrawn. Row count: " + this.api().rows().count());
                    console.log("Rows with data: " + $('.has-data').length);
                    console.log("Rows without data: " + $('.no-data').length);
                }
            });

            // Initialize DataTable for deposit history modal
            $("#depositHistoryTable").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "ordering": true,
                "order": [
                    [1, 'desc'] // Order by date column (descending)
                ],
                "columnDefs": [{
                    "type": "date",
                    "targets": 1 // Date column index
                }],
                "language": {
                    "emptyTable": "Tidak ada riwayat deposit",
                    "search": "Cari:"
                }
            });

            // Initialize DataTable for pricing history modal dengan pengecekan
            if ($.fn.DataTable.isDataTable('#pricingHistoryTable')) {
                $('#pricingHistoryTable').DataTable().destroy();
            }

            $("#pricingHistoryTable").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "ordering": true,
                "order": [
                    [1, 'desc']
                ],
                "language": {
                    "emptyTable": "Tidak ada riwayat harga",
                    "search": "Cari:"
                }
            });

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
                // Validasi dasar
                const hargaPerM3 = parseFloat($('#modalHargaPerM3').val());
                const tekananKeluar = parseFloat($('#modalTekananKeluar').val());
                const suhu = parseFloat($('#modalSuhu').val());

                let hasError = false;

                // Validasi harga
                if (isNaN(hargaPerM3) || hargaPerM3 < 0) {
                    $('#modalHargaPerM3').addClass('is-invalid');
                    hasError = true;
                } else {
                    $('#modalHargaPerM3').removeClass('is-invalid');
                }

                // Validasi tekanan
                if (isNaN(tekananKeluar) || tekananKeluar < 0) {
                    $('#modalTekananKeluar').addClass('is-invalid');
                    hasError = true;
                } else {
                    $('#modalTekananKeluar').removeClass('is-invalid');
                }

                // Validasi suhu
                if (isNaN(suhu)) {
                    $('#modalSuhu').addClass('is-invalid');
                    hasError = true;
                } else {
                    $('#modalSuhu').removeClass('is-invalid');
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

            // Add animations to cards on hover
            $('.card').css('opacity', 0); // Initially hide
            $('.mobile-summary-card').css('opacity', 0); // Initially hide

            // Auto submit form when filter changes (optional)
            /*
            $('#bulan, #tahun').on('change', function() {
                $('#filter-form').submit();
            });
            */
        });
    </script>
@endsection
