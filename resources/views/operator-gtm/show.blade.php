@extends('layouts.app')

@section('title', 'Detail Operator GTM')

@section('page-title', 'Detail Operator GTM - ' . $operatorGtm->nama)

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

            {{-- Operator Summary Card --}}
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-circle mr-2"></i>
                            Informasi Operator: {{ $operatorGtm->nama }}
                        </h3>
                        <div class="card-tools">
                            @if(auth()->user()->role !== 'keuangan')
                            <a href="{{ route('operator-gtm.edit', $operatorGtm->id) }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit mr-1"></i> Edit
                            </a>
                            <form action="{{ route('operator-gtm.destroy', $operatorGtm->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus operator ini?')">
                                    <i class="fas fa-trash mr-1"></i> Hapus
                                </button>
                            </form>
                            @endif
                            <a href="{{ route('operator-gtm.index') }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left mr-1"></i> Kembali
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-xl-3 col-lg-4 col-md-4 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-user mr-1"></i> Nama Operator</strong>
                                    <p class="text-muted mb-0">
                                        {{ $operatorGtm->nama }}
                                    </p>
                                </div>
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-map-marker-alt mr-1"></i> Lokasi Kerja</strong>
                                    <p class="text-muted mb-0">
                                        {{ $operatorGtm->lokasi_kerja }}
                                    </p>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-2 col-md-4 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-wallet mr-1"></i> Gaji Pokok</strong>
                                    <p class="text-muted mb-0">
                                        Rp {{ number_format($operatorGtm->gaji_pokok, 0, ',', '.') }}
                                    </p>
                                </div>
                            </div>
                            <div class="col-xl-2 col-lg-1 col-md-6 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-clock mr-1"></i> Jam Kerja</strong>
                                    <p class="text-muted mb-0">
                                        <span class="badge badge-{{ $operatorGtm->jam_kerja == 8 ? 'primary' : 'info' }}">
                                            {{ $operatorGtm->jam_kerja ?? 8 }} Jam
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="col-xl-2 col-lg-2 col-md-6 col-sm-6">
                                <div class="mobile-summary-card">
                                    <strong><i class="fas fa-calendar-alt mr-1"></i> Tanggal Bergabung</strong>
                                    <p class="text-muted mb-0">
                                        {{ $operatorGtm->tanggal_bergabung ? \Carbon\Carbon::parse($operatorGtm->tanggal_bergabung)->format('d M Y') : $operatorGtm->created_at->format('d M Y') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filter Periode --}}
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
                        <form action="{{ route('operator-gtm.show', $operatorGtm->id) }}" method="GET" id="filter-form">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Bulan:</label>
                                        <select class="form-control" name="month" id="month">
                                            <option value="01" {{ ($selectedMonth ?? request('month')) == '01' ? 'selected' : '' }}>Januari</option>
                                            <option value="02" {{ ($selectedMonth ?? request('month')) == '02' ? 'selected' : '' }}>Februari</option>
                                            <option value="03" {{ ($selectedMonth ?? request('month')) == '03' ? 'selected' : '' }}>Maret</option>
                                            <option value="04" {{ ($selectedMonth ?? request('month')) == '04' ? 'selected' : '' }}>April</option>
                                            <option value="05" {{ ($selectedMonth ?? request('month')) == '05' ? 'selected' : '' }}>Mei</option>
                                            <option value="06" {{ ($selectedMonth ?? request('month')) == '06' ? 'selected' : '' }}>Juni</option>
                                            <option value="07" {{ ($selectedMonth ?? request('month')) == '07' ? 'selected' : '' }}>Juli</option>
                                            <option value="08" {{ ($selectedMonth ?? request('month')) == '08' ? 'selected' : '' }}>Agustus</option>
                                            <option value="09" {{ ($selectedMonth ?? request('month')) == '09' ? 'selected' : '' }}>September</option>
                                            <option value="10" {{ ($selectedMonth ?? request('month')) == '10' ? 'selected' : '' }}>Oktober</option>
                                            <option value="11" {{ ($selectedMonth ?? request('month')) == '11' ? 'selected' : '' }}>November</option>
                                            <option value="12" {{ ($selectedMonth ?? request('month')) == '12' ? 'selected' : '' }}>Desember</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Tahun:</label>
                                        <select class="form-control" name="year" id="year">
                                            @php
                                                $currentYear = date('Y');
                                                $startYear = $currentYear - 5;
                                                $endYear = $currentYear + 5;
                                            @endphp
                                            @for($year = $startYear; $year <= $endYear; $year++)
                                                <option value="{{ $year }}" {{ (($selectedYear ?? request('year')) == $year || (!request('year') && !isset($selectedYear) && $year == $currentYear)) ? 'selected' : '' }}>{{ $year }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <div class="form-group mb-0 w-100">
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fas fa-search mr-1"></i> Terapkan Filter
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-1"></i> 
                                        Periode yang dipilih akan menampilkan data dari tanggal <strong>26 bulan sebelumnya</strong> hingga <strong>25 bulan yang dipilih</strong>. Semua tanggal dalam periode akan ditampilkan meskipun belum ada data yang diinput.
                                    </div>
                                    <a href="{{ route('operator-gtm.show', $operatorGtm->id) }}" class="btn btn-default btn-sm">
                                        <i class="fas fa-sync-alt mr-1"></i> Reset Filter
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Lembur Summary Card --}}
            <div class="col-md-12">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line mr-2"></i>
                            Ringkasan Lembur
                            @php
                                // Variabel startDate dan endDate sudah disediakan dari controller
                            @endphp
                            - {{ $startDate->format('d M Y') }} s/d {{ $endDate->format('d M Y') }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 col-sm-6">
                                <div class="info-box enhanced-info-box">
                                    <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Jam Lembur</span>
                                        <span class="info-box-number">
                                            @php
                                            // Hitung total jam lembur dari allDatesInPeriod yang sudah disiapkan controller
                                            $filteredRecords = collect($allDatesInPeriod)->filter(function($record) {
                                                return $record !== null;
                                            });
                                            
                                            $totalJamLembur = $filteredRecords->sum('total_jam_lembur');
                                            echo floor($totalJamLembur / 60) . ' jam ' . ($totalJamLembur % 60) . ' menit';
                                            @endphp
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="info-box enhanced-info-box">
                                    <span class="info-box-icon bg-danger"><i class="fas fa-money-bill-wave"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Upah Lembur</span>
                                        <span class="info-box-number">
                                            @php
                                                // Gunakan data yang sama dari perhitungan sebelumnya
                                                $totalUpahLembur = $filteredRecords->sum('upah_lembur');
                                            @endphp
                                            Rp {{ number_format($totalUpahLembur, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="info-box enhanced-info-box">
                                    <span class="info-box-icon bg-primary"><i class="fas fa-calculator"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total yang Harus Dibayarkan 
                                            <a href="#" class="text-primary ml-1" data-toggle="tooltip" data-html="true" title="<b>Algoritma perhitungan:</b><br>1. Jika tanggal bergabung sebelum tanggal 26 bulan sebelumnya, maka gaji pokok penuh.<br>2. Jika tanggal bergabung di antara periode (26 bulan sebelumnya sampai akhir bulan yang dipilih), maka gaji pokok dihitung proporsional berdasarkan <b>30 hari standar</b> dari tanggal bergabung sampai akhir bulan.<br>3. Total Pembayaran = Gaji Pokok (disesuaikan) + Total Upah Lembur"><i class="fas fa-question-circle"></i></a>
                                        </span>
                                        <span class="info-box-number">
                                            @php
                                                // Default gaji pokok penuh
                                                $gajiPokok = $operatorGtm->gaji_pokok;
                                                
                                                // Tanggal bergabung operator
                                                $tanggalBergabung = $operatorGtm->tanggal_bergabung 
                                                    ? \Carbon\Carbon::parse($operatorGtm->tanggal_bergabung)
                                                    : \Carbon\Carbon::parse($operatorGtm->created_at);
                                                
                                                // Hitung hari kerja dalam periode - RESET ke nilai default
                                                $hariKerja = 30; // Default 30 hari
                                                $statusKerja = '30 hari (periode penuh)'; // Default status
                                                
                                                // Debug info
                                                echo "<!-- DEBUG: Tanggal bergabung: " . $tanggalBergabung->format('Y-m-d') . " -->";
                                                echo "<!-- DEBUG: Start date: " . $startDate->format('Y-m-d') . " -->";
                                                echo "<!-- DEBUG: End date (data lembur): " . $endDate->format('Y-m-d') . " -->";
                                                echo "<!-- DEBUG: End of month (gaji): " . $endOfMonth->format('Y-m-d') . " -->";
                                                
                                                // Jika tanggal bergabung ada di antara periode yang dipilih
                                                if ($tanggalBergabung->gt($startDate) && $tanggalBergabung->lte($endOfMonth)) {
                                                    echo "<!-- DEBUG: Masuk kondisi bergabung di tengah periode -->";
                                                    
                                                    // Hitung proporsi gaji berdasarkan jumlah hari aktif
                                                    // FIXED: Total hari periode selalu 30 hari
                                                    $totalHariPeriode = 30;
                                                    
                                                    // Gunakan tanggal bergabung sampai akhir bulan (bukan tanggal 25)
                                                    $startKerja = \Carbon\Carbon::parse($tanggalBergabung->format('Y-m-d'));
                                                    $endKerja = \Carbon\Carbon::parse($endOfMonth->format('Y-m-d'));
                                                    
                                                    // Hitung hari kerja: dari tanggal bergabung sampai akhir bulan
                                                    $hariKerjaHitung = $startKerja->diffInDays($endKerja) + 1;
                                                    
                                                    echo "<!-- DEBUG: Hari kerja dihitung: " . $hariKerjaHitung . " -->";
                                                    
                                                    // Pastikan hasil adalah integer
                                                    $hariKerja = (int) $hariKerjaHitung;
                                                    
                                                    // Proporsi gaji pokok berdasarkan jumlah hari aktif
                                                    $gajiPokok = ($operatorGtm->gaji_pokok / $totalHariPeriode) * $hariKerja;
                                                    $statusKerja = $hariKerja . ' hari';
                                                    
                                                    echo "<!-- DEBUG: Final hari kerja: " . $hariKerja . " -->";
                                                    echo "<!-- DEBUG: Final status kerja: " . $statusKerja . " -->";
                                                }
                                                // Jika tanggal bergabung setelah akhir bulan, gaji pokok = 0
                                                else if ($tanggalBergabung->gt($endOfMonth)) {
                                                    echo "<!-- DEBUG: Masuk kondisi belum bergabung -->";
                                                    $gajiPokok = 0;
                                                    $hariKerja = 0;
                                                    $statusKerja = '0 hari (belum bergabung)';
                                                }
                                                // Jika tanggal bergabung sebelum periode, full period
                                                else {
                                                    echo "<!-- DEBUG: Masuk kondisi periode penuh -->";
                                                    $hariKerja = 30; // Menggunakan standar 30 hari
                                                    $statusKerja = '30 hari (periode penuh)';
                                                }
                                                
                                                $totalPembayaran = $gajiPokok + $totalUpahLembur;
                                                
                                                echo "<!-- DEBUG: Total pembayaran: " . $totalPembayaran . " -->";
                                            @endphp
                                            Rp {{ number_format($totalPembayaran, 0, ',', '.') }}
                                        </span>
                                        <span class="text-sm text-muted">Gaji Pokok + Upah Lembur</span>
                                        <div class="text-xs mt-1">
                                            <i class="fas fa-calendar-check text-info"></i> 
                                            <strong>Hari Kerja:</strong> 
                                            @php
                                                // Debug: pastikan $statusKerja adalah string yang benar
                                                echo is_numeric($statusKerja) ? number_format($statusKerja, 0) . ' hari' : $statusKerja;
                                            @endphp
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Data Lembur Table --}}
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list-alt mr-2"></i>
                            Data Lembur
                            @php
                                // Variabel startDate dan endDate sudah disediakan dari controller
                            @endphp
                            - {{ $startDate->format('d M Y') }} s/d {{ $endDate->format('d M Y') }}
                        </h3>
                        <div class="card-tools">
                            @if(auth()->user()->role !== 'keuangan')
                            <a href="{{ route('operator-gtm.create-lembur', $operatorGtm->id) }}" class="btn btn-success btn-sm">
                                <i class="fas fa-plus mr-1"></i> Tambah Data Lembur
                            </a>
                            @endif
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-bordered table-striped table-hover" id="dataLemburTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Sesi 1</th>
                                    <th>Sesi 2</th>
                                    <th>Sesi 3</th>
                                    @php
                                        // Variabel sudah disediakan dari controller
                                        // $hasSesi4, $hasSesi5, $allDatesInPeriod
                                    @endphp
                                    @if($hasSesi4)
                                        <th>Sesi 4</th>
                                    @endif
                                    @if($hasSesi5)
                                        <th>Sesi 5</th>
                                    @endif
                                    <th>Total Jam Kerja</th>
                                    <th>Jam Lembur</th>
                                    <th>Upah Lembur</th>
                                    @if(auth()->user()->role !== 'keuangan')
                                    <th>Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php 
                                    $no = 1;
                                    // Data allDatesInPeriod sudah disiapkan dari controller
                                @endphp
                                
                                @forelse($allDatesInPeriod as $date => $record)
                                    <tr class="{{ $record ? 'has-data' : 'table-light no-data' }}">
                                        <td>{{ $no++ }}</td>
                                        <td>{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</td>
                                        <td>
                                            @if($record && $record->jam_masuk_sesi_1 && $record->jam_keluar_sesi_1)
                                                {{ substr($record->jam_masuk_sesi_1, 0, 5) }} - {{ substr($record->jam_keluar_sesi_1, 0, 5) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($record && $record->jam_masuk_sesi_2 && $record->jam_keluar_sesi_2)
                                                {{ substr($record->jam_masuk_sesi_2, 0, 5) }} - {{ substr($record->jam_keluar_sesi_2, 0, 5) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                        @if($record && $record->jam_masuk_sesi_3 && $record->jam_keluar_sesi_3)
                                        {{ substr($record->jam_masuk_sesi_3, 0, 5) }} - {{ substr($record->jam_keluar_sesi_3, 0, 5) }}
                                        @else
                                        -
                                        @endif
                                        </td>
                        @if($hasSesi4)
                            <td>
                                @if($record && $record->jam_masuk_sesi_4 && $record->jam_keluar_sesi_4)
                                    {{ substr($record->jam_masuk_sesi_4, 0, 5) }} - {{ substr($record->jam_keluar_sesi_4, 0, 5) }}
                                @else
                                    -
                                @endif
                            </td>
                        @endif
                        @if($hasSesi5)
                            <td>
                                @if($record && $record->jam_masuk_sesi_5 && $record->jam_keluar_sesi_5)
                                    {{ substr($record->jam_masuk_sesi_5, 0, 5) }} - {{ substr($record->jam_keluar_sesi_5, 0, 5) }}
                                @else
                                    -
                                @endif
                            </td>
                        @endif
                                        <td>
                                            @if($record)
                                                {{ floor($record->total_jam_kerja / 60) }} jam {{ $record->total_jam_kerja % 60 }} menit
                                                <!-- Debug info -->
                                                <!-- ID: {{ $record->id }} | Date: {{ $record->tanggal }} -->
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($record)
                                                {{ floor($record->total_jam_lembur / 60) }} jam {{ $record->total_jam_lembur % 60 }} menit
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($record)
                                                Rp {{ number_format($record->upah_lembur, 0, ',', '.') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        @if(auth()->user()->role !== 'keuangan')
                                        <td>
                                                @if($record)
                                                    <div class="btn-group">
                                                        <a href="{{ route('operator-gtm.edit-lembur', $record->id) }}" class="btn btn-warning btn-sm">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('operator-gtm.destroy-lembur', $record->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus data lembur ini?')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                @else
                                                    <a href="{{ route('operator-gtm.create-lembur', ['operatorGtm' => $operatorGtm->id, 'tanggal' => $date]) }}" class="btn btn-success btn-sm">
                                                        <i class="fas fa-plus"></i> Input Data
                                                    </a>
                                                @endif
                                        </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">Belum ada data lembur dalam periode ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    @php
                                        $totalColumns = 6; // Default: No, Tanggal, Sesi 1, 2, 3, Total Jam Kerja
                                        if ($hasSesi4) $totalColumns++;
                                        if ($hasSesi5) $totalColumns++;
                                    @endphp
                                    <th colspan="{{ $totalColumns }}" class="text-right">Total:</th>
                                    <th>
                                        @php
                                            $totalJamLembur = 0;
                                            foreach ($allDatesInPeriod as $date => $record) {
                                                if ($record) {
                                                    $totalJamLembur += $record->total_jam_lembur;
                                                }
                                            }
                                        @endphp
                                        {{ floor($totalJamLembur / 60) }} jam {{ $totalJamLembur % 60 }} menit
                                    </th>
                                    <th>
                                        @php
                                            $totalUpahLembur = 0;
                                            foreach ($allDatesInPeriod as $date => $record) {
                                                if ($record) {
                                                    $totalUpahLembur += $record->upah_lembur;
                                                }
                                            }
                                        @endphp
                                        Rp {{ number_format($totalUpahLembur, 0, ',', '.') }}
                                    </th>
                                    @if(auth()->user()->role !== 'keuangan')
                                    <th></th>
                                    @endif
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
<style>
    /* Style untuk mobile summary card */
    .mobile-summary-card {
        margin-bottom: 15px;
        padding: 15px;
        border-radius: 8px;
        background-color: #f8f9fa;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        border: 1px solid #e3e6f0;
        height: 100%;
    }
    
    .mobile-summary-card:hover {
        background-color: #e9ecef;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }
    
    .mobile-summary-card strong {
        font-size: 13px;
        color: #495057;
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .mobile-summary-card p {
        font-size: 15px;
        font-weight: 600;
        color: #212529;
        margin-bottom: 0;
        line-height: 1.3;
    }
    
    /* Responsive adjustments */
    @media (max-width: 1199.98px) {
        .mobile-summary-card {
            padding: 12px;
        }
        .mobile-summary-card strong {
            font-size: 12px;
        }
        .mobile-summary-card p {
            font-size: 14px;
        }
    }
    
    @media (max-width: 767.98px) {
        .mobile-summary-card {
            padding: 10px;
            margin-bottom: 10px;
        }
        .mobile-summary-card strong {
            font-size: 11px;
        }
        .mobile-summary-card p {
            font-size: 13px;
        }
    }
    
    /* Style untuk info box lembur summary */
    .enhanced-info-box {
        border-radius: 6px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .enhanced-info-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }
    
    /* Card tools responsiveness */
    @media (max-width: 767.98px) {
        .card-tools .btn {
            margin-bottom: 5px;
        }
        
        .card-tools {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
    }
    
    /* Table styling */
    .table-bordered th,
    .table-bordered td {
        vertical-align: middle !important;
    }
    
    .table th {
        background-color: #f4f6f9;
    }
    
    .table tfoot th {
        background-color: #e9ecef;
    }
    
    /* Highlight rows with data */
    tr.has-data {
        background-color: rgba(40, 167, 69, 0.05) !important;
    }
    
    tr.has-data:hover {
        background-color: rgba(40, 167, 69, 0.1) !important;
    }
    
    /* Style untuk keterangan hari kerja */
    .info-box-content .text-xs {
        color: #6c757d;
        line-height: 1.2;
        margin-top: 4px;
    }
    
    .info-box-content .text-xs strong {
        color: #495057;
        font-weight: 600;
    }
    
    .info-box-content .text-xs .fas {
        margin-right: 4px;
    }
</style>
@endsection

@section('js')
<script>
    $(function() {
        // Inisialisasi tooltip
        $('[data-toggle="tooltip"]').tooltip({
            html: true,
            placement: 'top',
            container: 'body',
            template: '<div class="tooltip" role="tooltip"><div class="arrow"></div><div class="tooltip-inner p-2" style="max-width: 300px; text-align: left;"></div></div>'
        });
        
        // Validasi form filter
        $('#filter-form').on('submit', function(e) {
            var month = $('#month').val();
            var year = $('#year').val();
            
            if (!month || !year) {
                e.preventDefault();
                alert('Silakan pilih bulan dan tahun untuk melakukan filter.');
                return false;
            }
        });
        
        // DataTables initialization
        var table = $("#dataLemburTable").DataTable({
            "responsive": true,
            "lengthChange": false,
            "autoWidth": false,
            "ordering": true,
            "order": [[1, 'asc']], // Sorting by date ascending
            "language": {
                "emptyTable": "Tidak ada data lembur tersedia",
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
        
        // Add animations to cards on hover
        $('.card').css('opacity', 0); // Initially hide
        $('.mobile-summary-card').css('opacity', 0); // Initially hide
        
        // Show elements with animation
        $(window).on('load', function() {
            // Animate elements one by one
            $('.card').each(function(i) {
                $(this).delay(i * 150).animate({
                    'opacity': 1
                }, 500);
            });
            $('.mobile-summary-card').each(function(i) {
                $(this).delay(i * 100).animate({
                    'opacity': 1
                }, 400);
            });
        });
    });
</script>
@endsection