@extends('layouts.app')

@section('title', 'Detail Data Pencatatan FOB')

@section('page-title', 'Data Pencatatan - ' . $customer->name)

@section('content')
    <div class="container-fluid">
        {{-- DEBUG INFO --}}
        @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
            <div class="row">
                <div class="col-12">
                    <div class="card collapsed-card">

                        <div class="card-body">
                            <h5>Customer Info:</h5>
                            <ul>
                                <li>ID: {{ $customer->id }}</li>
                                <li>Name: {{ $customer->name }}</li>
                                <li>Role: {{ $customer->role }}</li>
                                <li>isFOB(): {{ $customer->isFOB() ? 'true' : 'false' }}</li>
                                <li>Current harga: {{ $customer->harga_per_meter_kubik }}</li>
                            </ul>
                            <h5>Pricing History:</h5>
                            <pre>{{ json_encode($customer->pricing_history, JSON_PRETTY_PRINT) }}</pre>
                            <p>Debug Links:</p>
                            <a href="{{ route('fob.debug', $customer->id) }}" class="btn btn-warning" target="_blank">Open
                                Debug Page</a>
                            <a href="{{ route('debug.fob-calculations', $customer->id) }}" class="btn btn-info" target="_blank">Debug & Fix Calculations</a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
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
                                        <span class="badge badge-success" title="Perhitungan real-time menggunakan pricing sesuai periode"><i class="fas fa-sync-alt"></i> Real-time</span>
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
                                            @php
                                                /*
                                                 * PERBAIKAN PERHITUNGAN SALDO FOB:
                                                 * Mengubah perhitungan dari menggunakan data monthly_balances (yang bisa tidak akurat)
                                                 * menjadi perhitungan real-time yang konsisten dengan customer detail
                                                 */

                                                // Definisikan variabel currentYearMonth terlebih dahulu
                                                $currentYearMonth = \Carbon\Carbon::createFromDate(
                                                    $selectedTahun,
                                                    $selectedBulan,
                                                    1,
                                                )->format('Y-m');

                                                // PERBAIKAN: Hitung saldo bulan sebelumnya secara real-time
                                                $prevDate = \Carbon\Carbon::createFromDate($selectedTahun, $selectedBulan, 1)->subMonth();
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
                                                                $amount = floatval($deposit['amount'] ?? 0);
                                                                $keterangan = $deposit['keterangan'] ?? 'penambahan';
                                                                
                                                                // Handle deposit dan pengurangan dengan benar
                                                                if ($keterangan === 'pengurangan') {
                                                                    $realTimePrevMonthBalance -= abs($amount);
                                                                } else {
                                                                    $realTimePrevMonthBalance += $amount;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }

                                                // 2. Kurangi semua pembelian sampai akhir bulan sebelumnya
                                                $allDataPencatatan = $customer->dataPencatatan()->get();
                                                foreach ($allDataPencatatan as $purchaseItem) {
                                                    $itemDataInput = is_string($purchaseItem->data_input)
                                                        ? json_decode($purchaseItem->data_input, true)
                                                        : (is_array($purchaseItem->data_input)
                                                            ? $purchaseItem->data_input
                                                            : []);
                                                    
                                                    // Untuk FOB, cek format waktu yang berbeda
                                                    $itemDate = null;
                                                    if (!empty($itemDataInput['waktu'])) {
                                                        $itemDate = \Carbon\Carbon::parse($itemDataInput['waktu']);
                                                    } elseif (!empty($itemDataInput['pembacaan_awal']['waktu'])) {
                                                        $itemDate = \Carbon\Carbon::parse($itemDataInput['pembacaan_awal']['waktu']);
                                                    } elseif ($purchaseItem->created_at) {
                                                        $itemDate = $purchaseItem->created_at;
                                                    }
                                                    
                                                    if (!$itemDate) {
                                                        continue;
                                                    }

                                                    // Ambil pembelian sampai akhir bulan sebelumnya
                                                    if ($itemDate->format('Y-m') <= $prevYearMonth) {
                                                        // Untuk FOB, gunakan volume_sm3 langsung atau harga_final
                                                        if ($purchaseItem->harga_final > 0) {
                                                            $realTimePrevMonthBalance -= $purchaseItem->harga_final;
                                                        } else {
                                                            $volumeSm3 = floatval($itemDataInput['volume_sm3'] ?? 0);
                                                            
                                                            // Ambil pricing yang sesuai (untuk FOB)
                                                            $itemYearMonth = $itemDate->format('Y-m');
                                                            $itemPricingInfo = $customer->getPricingForYearMonth(
                                                                $itemYearMonth,
                                                                $itemDate
                                                            );

                                                            // Hitung volume dan harga (FOB tidak menggunakan koreksi meter)
                                                            $itemHargaPerM3 = floatval(
                                                                $itemPricingInfo['harga_per_meter_kubik'] ??
                                                                    $customer->harga_per_meter_kubik
                                                            );
                                                            $itemHarga = $volumeSm3 * $itemHargaPerM3;

                                                            $realTimePrevMonthBalance -= $itemHarga;
                                                        }
                                                    }
                                                }

                                                // Hitung ulang saldo bulan ini secara real-time
                                                $realTimeFilteredTotalPurchases = $filteredTotalPurchases;
                                                $realTimeFilteredDeposits = $filteredTotalDeposits;

                                                // Hitung saldo bulan ini menggunakan saldo bulan sebelumnya yang real-time
                                                $realTimeCurrentMonthBalance =
                                                    $realTimePrevMonthBalance +
                                                    $realTimeFilteredDeposits -
                                                    $realTimeFilteredTotalPurchases;

                                                // Log untuk debugging (hanya untuk admin)
                                                if ((Auth::user()->isAdmin() || Auth::user()->isSuperAdmin()) && abs($realTimePrevMonthBalance - ($prevMonthBalance ?? 0)) > 0.01) {
                                                    \Log::info('Perbedaan saldo bulan sebelumnya FOB ditemukan', [
                                                        'customer_id' => $customer->id,
                                                        'customer_name' => $customer->name,
                                                        'period' => $selectedTahun . '-' . str_pad($selectedBulan, 2, '0', STR_PAD_LEFT),
                                                        'prev_month' => \Carbon\Carbon::createFromDate($selectedTahun, $selectedBulan, 1)->subMonth()->format('Y-m'),
                                                        'real_time_prev_balance' => $realTimePrevMonthBalance,
                                                        'database_prev_balance' => $prevMonthBalance ?? 0,
                                                        'difference' => $realTimePrevMonthBalance - ($prevMonthBalance ?? 0)
                                                    ]);
                                                }
                                            @endphp
                                            <table class="table table-sm table-bordered">
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
                                                <tr class="font-weight-bold">
                                                    <td>= Sisa Saldo Periode Bulan Ini</td>
                                                    <td>Rp {{ number_format($realTimeCurrentMonthBalance, 0) }}</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2" class="text-muted"><small>* Saldo ini hanya
                                                            menunjukkan saldo untuk periode
                                                            {{ \Carbon\Carbon::createFromDate($selectedTahun, $selectedBulan, 1)->format('F Y') }}
                                                            saja (Perhitungan Real-time)</small></td>
                                                </tr>
                                                @if(Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
                                                <tr class="text-info" style="font-size: 0.8em;">
                                                    <td colspan="2">
                                                        <strong>Debug Info FOB (Real-time vs Database):</strong><br>
                                                        - Saldo Bulan Sebelumnya (Real-time): Rp {{ number_format($realTimePrevMonthBalance, 2) }}<br>
                                                        - Saldo Bulan Sebelumnya (Database): Rp {{ number_format($prevMonthBalance ?? 0, 2) }}<br>
                                                        - Selisih Prev: Rp {{ number_format($realTimePrevMonthBalance - ($prevMonthBalance ?? 0), 2) }}<br>
                                                        - Saldo Periode Ini (Real-time): Rp {{ number_format($realTimeCurrentMonthBalance, 2) }}<br>
                                                        - Deposit Bulan Ini: Rp {{ number_format($filteredTotalDeposits, 2) }}<br>
                                                        - Pembelian Bulan Ini: Rp {{ number_format($filteredTotalPurchases, 2) }}
                                                    </td>
                                                </tr>
                                                @endif
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
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list-alt mr-2"></i>
                            Riwayat Pencatatan
                        </h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-bordered table-striped custom-datatable" id="dataPencatatanTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Volume Sm³</th>
                                    <th>Alamat Pengambilan</th>
                                    <th>Rupiah</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $no = 1; @endphp
                                @foreach ($dataPencatatan->sortBy(function ($item) {
            $dataInput = is_string($item->data_input) ? json_decode($item->data_input, true) : (is_array($item->data_input) ? $item->data_input : []);
            $waktuTimestamp = strtotime($dataInput['waktu'] ?? '');
            return $waktuTimestamp ? $waktuTimestamp : 0;
        }) as $item)
                                    @php
                                        $dataInput = is_string($item->data_input)
                                            ? json_decode($item->data_input, true)
                                            : (is_array($item->data_input)
                                                ? $item->data_input
                                                : []);

                                        $volumeSm3 = $dataInput['volume_sm3'] ?? 0;

                                        // Get the timestamp for data-filter attribute
                                        $waktuTimestamp = strtotime($dataInput['waktu'] ?? '');
                                        $tanggalFilter = $waktuTimestamp ? date('Y-m-d', $waktuTimestamp) : '';

                                        // Ambil waktu untuk mendapatkan pricing yang tepat
                                        $waktuDateTime = $waktuTimestamp
                                            ? \Carbon\Carbon::createFromTimestamp($waktuTimestamp)
                                            : null;
                                        $waktuYearMonth = $waktuTimestamp ? date('Y-m', $waktuTimestamp) : date('Y-m');

                                        // Ambil pricing info berdasarkan tanggal spesifik
                                        $itemPricingInfo = $customer->getPricingForYearMonth(
                                            $waktuYearMonth,
                                            $waktuDateTime,
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
                                        <td>{{ isset($dataInput['waktu']) ? \Carbon\Carbon::parse($dataInput['waktu'])->format('d M Y H:i') : '-' }}
                                        </td>
                                        <td>{{ number_format($volumeSm3, 2) }}</td>
                                        <td>{{ $dataInput['alamat_pengambilan'] ?? '-' }}</td>
                                        <td>Rp {{ number_format($pembelian, 2) }}</td>
                                        <td>
                                            <div class="btn-group">
                                                @php
                                                    // Extract date from data_input for finding corresponding rekap pengambilan
                                                    $waktuData = $dataInput['waktu'] ?? null;
                                                    $tanggalData = $waktuData ? \Carbon\Carbon::parse($waktuData)->format('Y-m-d') : null;
                                                @endphp
                                                
                                                @if($tanggalData)
                                                    <a href="{{ route('rekap-pengambilan.find-by-date', [$customer->id, $tanggalData]) }}"
                                                        class="btn btn-info btn-sm" title="Lihat/Edit Rekap Pengambilan">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @else
                                                    <a href="{{ route('data-pencatatan.show', $item->id) }}"
                                                        class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endif
                                                
                                                @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
                                                    @if($tanggalData)
                                                        <a href="{{ route('rekap-pengambilan.find-by-date', [$customer->id, $tanggalData]) }}"
                                                            class="btn btn-warning btn-sm" title="Edit Rekap Pengambilan">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @else
                                                        <a href="{{ route('data-pencatatan.edit', $item->id) }}"
                                                            class="btn btn-warning btn-sm">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endif
                                                    
                                                    <form action="{{ route('data-pencatatan.destroy', $item->id) }}"
                                                        method="POST" class="d-inline"
                                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="bulan"
                                                            value="{{ $selectedBulan }}">
                                                        <input type="hidden" name="tahun"
                                                            value="{{ $selectedTahun }}">
                                                        <input type="hidden" name="fob" value="1">
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
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
                                <div class="col-md-6 col-sm-12 text-right">
                                    <button class="btn btn-primary w-100 w-md-auto" data-toggle="modal"
                                        data-target="#tambahDepositModal">
                                        <i class="fas fa-plus mr-1"></i> Tambah Deposit
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="depositHistoryTable">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Tanggal</th>
                                            <th>Jumlah Deposit</th>
                                            <th>Keterangan</th>
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
                                                        'description' => $deposit['description'] ?? '-',
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
                                                <td>Rp {{ number_format($deposit['amount'] ?? 0, 2) }}</td>
                                                <td>{{ $deposit['description'] ?? '-' }}</td>
                                                <td>
                                                    <form action="{{ route('customer.remove-deposit', $customer->id) }}"
                                                        method="POST" class="d-inline"
                                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus deposit ini?');">
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

// Sort by date (newest first)
usort($pricingHistory, function ($a, $b) {
    $dateA = isset($a['date']) ? strtotime($a['date']) : 0;
    $dateB = isset($b['date']) ? strtotime($b['date']) : 0;
                                            return $dateB - $dateA;
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
    </style>
@endsection

@section('js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        // Set up CSRF token for AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(function() {
            // DataTables initialization
            var table = $("#dataPencatatanTable").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "ordering": false, // Disable client-side ordering since we're using server-side ordering
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
                "paging": true,
                "info": true,
                "searching": true,
                "initComplete": function(settings, json) {
                    console.log("DataTable initialized with ordering by date (ascending)");
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
    </script>
@endsection
