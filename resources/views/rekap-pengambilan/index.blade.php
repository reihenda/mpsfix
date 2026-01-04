@extends('layouts.app')

@section('title', 'Rekap Pengambilan')

@section('page-title', 'Rekap Pengambilan')

@section('content')
    <div class="container-fluid">
        <!-- Notifikasi -->
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

        <!-- Tombol Tambah Data -->
        <div class="row mb-3">
            <div class="col-md-12">
                <a href="{{ route('rekap-pengambilan.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus-circle mr-1"></i> Tambah Data
                </a>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filter Data</h3>
                    </div>
                    <div class="card-body py-3">
                        <form action="{{ route('rekap-pengambilan.index') }}" method="GET" id="filterForm"
                            class="d-flex align-items-center flex-wrap">
                            <div class="d-flex align-items-center mr-4 mb-2">
                                <label for="daterange" class="mb-0 mr-2 font-weight-bold">Rentang Tanggal:</label>
                                <div class="input-group" style="width: 300px;">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white">
                                            <i class="fas fa-calendar-alt"></i>
                                        </span>
                                    </div>
                                    <input type="text" id="daterange" 
                                        class="form-control"
                                        style="cursor: pointer; background-color: white;"
                                        readonly>
                                </div>
                                <input type="hidden" name="tanggal_mulai" id="tanggal_mulai" value="{{ $tanggalMulai }}">
                                <input type="hidden" name="tanggal_akhir" id="tanggal_akhir" value="{{ $tanggalAkhir }}">
                            </div>

                            <div class="mb-2">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="fas fa-search mr-1"></i> Terapkan Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informasi Ringkasan -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="info-box bg-info">
                    <span class="info-box-icon"><i class="fas fa-calendar-alt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-white">Total Volume (Rentang Terpilih)</span>
                        <span class="info-box-number">{{ number_format($totalVolumeRentang, 2) }} SM³</span>
                        <span class="info-box-text text-white">{{ \Carbon\Carbon::parse($tanggalMulai)->format('d M Y') }} - {{ \Carbon\Carbon::parse($tanggalAkhir)->format('d M Y') }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-box bg-success">
                    <span class="info-box-icon"><i class="fas fa-truck"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-white">Jumlah Data</span>
                        <span class="info-box-number">{{ $rekapPengambilan->count() }} Data</span>
                        <span class="info-box-text text-white">Dalam rentang tanggal terpilih</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Data -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="card-title">
                                <i class="fas fa-list-alt mr-1"></i>
                                Data Rekap Pengambilan
                            </h3>
                        </div>
                        <div class="mt-3">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                </div>
                                <input type="text" id="searchData" class="form-control" 
                                       placeholder="Cari customer, nopol, alamat, volume..." value="{{ request('search') }}">
                            </div>
                        </div>
                    </div>
                    <div class="card-body" id="rekap-table-container">
                        @include('rekap-pengambilan.partials.table', ['rekapPengambilan' => $rekapPengambilan])
                    </div>
                    <div class="card-footer clearfix">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<style>
    /* Hide kalender kanan saja */
    .daterangepicker .drp-calendar.right {
        display: none !important;
    }
</style>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<script>
    $(document).ready(function() {
        moment.locale('id');

        var startDate = moment('{{ $tanggalMulai }}');
        var endDate = moment('{{ $tanggalAkhir }}');
        
        // Inisialisasi daterangepicker dengan linkedCalendars
        $('#daterange').daterangepicker({
            startDate: startDate,
            endDate: endDate,
            showDropdowns: true,
            minYear: 2020,
            maxYear: parseInt(moment().format('YYYY')) + 1,
            linkedCalendars: false,  // FALSE agar ada navigasi independent
            locale: {
                format: 'DD MMMM YYYY',
                separator: ' - ',
                applyLabel: 'Terapkan',
                cancelLabel: 'Batal',
                fromLabel: 'Dari',
                toLabel: 'Sampai',
                customRangeLabel: 'Custom',
                daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                firstDay: 1
            },
            ranges: {
               'Hari Ini': [moment(), moment()],
               '7 Hari Terakhir': [moment().subtract(6, 'days'), moment()],
               '30 Hari Terakhir': [moment().subtract(29, 'days'), moment()],
               'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
               'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            maxSpan: {
                days: 90
            }
        });
        
        // Tidak perlu JavaScript tambahan, biarkan default

        // Event apply - auto submit
        $('#daterange').on('apply.daterangepicker', function(ev, picker) {
            var daysDiff = picker.endDate.diff(picker.startDate, 'days');
            
            if (daysDiff > 90) {
                Swal.fire({
                    icon: 'error',
                    title: 'Rentang Terlalu Panjang',
                    text: 'Rentang tanggal maksimal adalah 90 hari',
                    confirmButtonText: 'OK'
                });
                return false;
            }

            $('#tanggal_mulai').val(picker.startDate.format('YYYY-MM-DD'));
            $('#tanggal_akhir').val(picker.endDate.format('YYYY-MM-DD'));
            
            $('#filterForm').submit();
        });

        // Event cancel
        $('#daterange').on('cancel.daterangepicker', function(ev, picker) {
            $(this).data('daterangepicker').setStartDate(moment('{{ $tanggalMulai }}'));
            $(this).data('daterangepicker').setEndDate(moment('{{ $tanggalAkhir }}'));
        });

        // Function untuk format angka dengan pemisah ribuan
        function numberFormat(number) {
            return number.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }

        // Function untuk menghitung total volume dinamis
        function calculateTotalVolume() {
            let total = 0;
            
            // Loop semua cell volume yang visible
            $('.volume-cell').each(function() {
                const volume = parseFloat($(this).data('volume')) || 0;
                total += volume;
            });
            
            // Update display dengan format pemisah ribuan dan 2 desimal
            $('#total-volume-display').text(numberFormat(total));
        }

        // Hitung total volume saat pertama kali load
        calculateTotalVolume();

        // Search functionality
        let searchTimer;
        $('#searchData').on('input', function() {
            const searchTerm = $(this).val();
            const tanggalMulai = $('#tanggal_mulai').val();
            const tanggalAkhir = $('#tanggal_akhir').val();
            
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                $('#rekap-table-container').html(
                    '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>'
                );

                $.ajax({
                    url: '{{ route('rekap-pengambilan.index') }}',
                    type: 'GET',
                    data: { 
                        search: searchTerm,
                        tanggal_mulai: tanggalMulai,
                        tanggal_akhir: tanggalAkhir
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#rekap-table-container').html(response.html);
                        // Hitung ulang total volume setelah data baru dimuat
                        calculateTotalVolume();
                    },
                    error: function() {
                        $('#rekap-table-container').html(
                            '<div class="alert alert-danger">Terjadi kesalahan saat mengambil data</div>'
                        );
                    }
                });
            }, 300);
        });
    });
</script>
@endpush
