@extends('layouts.app')

@section('title', 'Detail Invoice')

@section('page-title', 'Detail Invoice')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-gradient-primary text-white">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                        <div class="mb-2 mb-md-0">
                            <h5 class="m-0 font-weight-bold">Invoice #{{ $invoice->invoice_number }}</h5>
                            <small>
                                <span
                                    class="badge badge-pill {{ $invoice->status == 'paid' ? 'badge-success' : ($invoice->status == 'partial' ? 'badge-warning' : ($invoice->status == 'cancelled' ? 'badge-danger' : 'badge-secondary')) }} mt-1">
                                    {{ $invoice->status == 'paid' ? 'Lunas' : ($invoice->status == 'partial' ? 'Sebagian' : ($invoice->status == 'cancelled' ? 'Dibatalkan' : 'Belum Lunas')) }}
                                </span>
                                <span class="badge badge-pill badge-light mt-1">{{ $periode_bulan }}</span>
                            </small>
                        </div>
                        <div class="btn-group">
                            @if($invoice->billing)
                                <a href="{{ route('billings.show', $invoice->billing) }}" class="btn btn-sm btn-info" title="Lihat Billing Terkait">
                                    <i class="fas fa-file-invoice-dollar mr-1"></i><span class="d-none d-sm-inline">Billing</span>
                                </a>
                            @endif
                            <button onclick="window.print();" class="btn btn-sm btn-light">
                                <i class="fas fa-print mr-1"></i><span class="d-none d-sm-inline">Cetak</span>
                            </button>
                            @if(!Auth::user()->isCustomer() && !Auth::user()->isFOB())
                                <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit mr-1"></i><span class="d-none d-sm-inline">Edit</span>
                                </a>
                            @endif
                            @if(Auth::user()->isCustomer() || Auth::user()->isFOB())
                                <a href="{{ route('customer.invoices') }}" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-arrow-left mr-1"></i><span class="d-none d-sm-inline">Kembali</span>
                                </a>
                            @else
                                <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-arrow-left mr-1"></i><span class="d-none d-sm-inline">Kembali</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="invoice-print p-3">
                        <!-- Kop Perusahaan -->
                        <div class="row mb-4">
                            <div class="col-12 col-md-5 text-center text-md-left mb-3 mb-md-0">
                                <div class="d-flex align-items-center justify-content-center justify-content-md-start">
                                    <img src="{{ asset('img/mps.png') }}" alt="Logo"
                                        class="img-fluid" style="max-height: 110px;">
                                </div>
                            </div>
                            <div class="col-12 col-md-7 text-center text-md-right">
                                <p class="mb-0">Gedung Graha Mampang Lt. 2</p>
                                <p class="mb-0">Jl. Mampang Prapatan Raya No. 100</p>
                                <p class="mb-0">Duren Tiga, Pancoran, Jakarta Selatan 12760</p>
                                <p class="mb-0">Tel.021 798 8953</p>
                            </div>
                        </div>

                        <!-- Judul Invoice -->
                        <div class="row">
                            <div class="col-12 text-center">
                                <h3 class="text-uppercase font-weight-bold p-2 border border-dark">INVOICE</h3>
                            </div>
                        </div>

                        <!-- Informasi Invoice -->
                        <div class="row">
                            <div class="col-12">
                                <div class="table-responsive" style="overflow-y: hidden; white-space: nowrap;">
                                    <table class="table table-bordered mb-0 info-invoice">
                                        <tr class="bg-light">
                                            <td width="150px"><strong>Nomor</strong></td>
                                            <td width="10px">:</td>
                                            <td><span
                                                    class="text-primary font-weight-bold">{{ $invoice->invoice_number }}</span>
                                            </td>
                                            <td rowspan="6" class="align-middle bg-light d-none d-md-table-cell kepada-cell" width="200px" style="width: 200px;">
                                                <strong>Kepada :</strong><br>
                                                <h5 class="font-weight-bold text-center text-primary">
                                                    {{ strtoupper($customer->name) }}</h5>
                                                @if($customer->alamat)
                                                    <p class="mb-1 text-muted small text-center">
                                                        <i class="fas fa-map-marker-alt mr-1"></i>{{ $customer->alamat }}
                                                    </p>
                                                @endif
                                                @if($customer->nomor_tlpn)
                                                    <p class="mb-0 text-muted small text-center">
                                                        <i class="fas fa-phone mr-1"></i>{{ $customer->nomor_tlpn }}
                                                    </p>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Tanggal Cetak</strong></td>
                                            <td>:</td>
                                            <td>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d-M-Y') }}</td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td><strong>Tanggal Jatuh Tempo</strong></td>
                                            <td>:</td>
                                            <td>
                                                <span
                                                    class="badge badge-{{ \Carbon\Carbon::parse($invoice->due_date)->isPast() && $invoice->status != 'paid' ? 'danger' : 'info' }} p-2">
                                                    {{ \Carbon\Carbon::parse($invoice->due_date)->format('d-M-Y') }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>No Kontrak</strong></td>
                                            <td>:</td>
                                            <td>{{ $invoice->no_kontrak }}</td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td><strong>Alamat</strong></td>
                                            <td>:</td>
                                            <td>{{ $customer->alamat ?: 'Alamat tidak tersedia' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Telepon</strong></td>
                                            <td>:</td>
                                            <td>{{ $customer->nomor_tlpn ?: 'Telepon tidak tersedia' }}</td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td colspan="3"></td>
                                            <td class="d-none d-md-table-cell">
                                                <div style="white-space: nowrap; display: flex; align-items: center;">
                                                    <strong style="min-width: 110px; display: inline-block;">ID Pelanggan</strong>
                                                    <span style="margin: 0 5px;">:</span>
                                                    <span class="badge badge-secondary p-2">{{ $invoice->id_pelanggan }}</span>
                                                </div>
                                            </td>
                                        </tr>
                                        
                                        <!-- Informasi Customer untuk Mobile -->
                                        <tr class="d-md-none">
                                            <td colspan="3" class="text-center bg-light">
                                                <strong>Kepada :</strong><br>
                                                <h5 class="font-weight-bold text-primary mb-2">{{ strtoupper($customer->name) }}</h5>
                                                <span class="badge badge-secondary p-2 mb-2">ID: {{ $invoice->id_pelanggan }}</span><br>
                                                @if($customer->alamat)
                                                    <small class="text-muted d-block mb-1">
                                                        <i class="fas fa-map-marker-alt mr-1"></i>{{ $customer->alamat }}
                                                    </small>
                                                @endif
                                                @if($customer->nomor_tlpn)
                                                    <small class="text-muted d-block">
                                                        <i class="fas fa-phone mr-1"></i>{{ $customer->nomor_tlpn }}
                                                    </small>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Tabel Pemakaian -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="table-responsive" style="overflow-y: hidden; white-space: nowrap;">
                                    <table class="table table-bordered table-striped">
                                        <thead class="bg-gradient-info text-white"
                                            style="background-color: #20B2AA !important;">
                                            <tr>
                                                <th style="background-color: #20B2AA; color: white;">Keterangan
                                                </th>
                                                <th style="background-color: #20B2AA; color: white;">Periode</th>
                                                <th style="background-color: #20B2AA; color: white;">Volume
                                                    Pemakaian (Sm3)</th>
                                                <th style="background-color: #20B2AA; color: white;">Harga Satuan
                                                    (Rp)</th>
                                                <th style="background-color: #20B2AA; color: white;">Total (Rp)
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($pemakaian_gas as $item)
                                                <tr>
                                                    <td>
                                                        <i class="fas fa-gas-pump text-primary mr-2"></i>
                                                        <strong>{{ $invoice->description ?: 'Pemakaian CNG' }}</strong>
                                                    </td>
                                                    <td>
                                                        <i class="far fa-calendar-alt text-secondary mr-2"></i>
                                                        {{ $item['periode_pemakaian'] }}
                                                    </td>
                                                    <td class="text-right font-weight-bold">
                                                        {{ number_format($item['volume_sm3'], 2, ',', '.') }}
                                                    </td>
                                                    <td class="text-right">
                                                        {{ number_format($item['harga_gas'], 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-right bg-light font-weight-bold">
                                                        Rp {{ number_format($item['biaya_pemakaian'], 0, ',', '.') }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                            <!-- Tambahkan beberapa baris kosong agar terlihat seperti template -->
                                            @for ($i = 0; $i < 2; $i++)
                                                <tr class="table-light">
                                                    <td>&nbsp;</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                            @endfor
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Terbilang dan Totals -->
                        <div class="row mt-2">
                            <div class="col-12">
                                <div class="table-responsive" style="overflow-y: hidden; white-space: nowrap;">
                                    <table class="table table-bordered">
                                        <tr>
                                            <td class="bg-light"><strong>Terbilang:</strong></td>
                                            <td class="font-italic">{{ ucfirst($terbilang) }} rupiah</td>
                                            <td class="bg-light"><strong>Sub Total</strong></td>
                                            <td>:</td>
                                            <td class="text-right font-weight-bold">
                                                {{ number_format($total_biaya, 0, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" rowspan="3" class="align-middle">
                                                <div class="alert alert-info mb-0">
                                                    <i class="fas fa-info-circle mr-2"></i>
                                                    <small>Harap lakukan pembayaran sebelum tanggal jatuh tempo untuk kelancaran
                                                        layanan.</small>
                                                </div>
                                            </td>
                                            <td class="bg-light"><strong>PPN 11%</strong></td>
                                            <td>:</td>
                                            <td class="text-right">-</td>
                                        </tr>
                                        <tr>
                                            <td class="bg-light"><strong>Biaya Materai</strong></td>
                                            <td>:</td>
                                            <td class="text-right">-</td>
                                        </tr>
                                        <tr>
                                            <td class="bg-primary text-white"><strong>Total Tagihan</strong></td>
                                            <td class="bg-primary text-white">:</td>
                                            <td class="text-right bg-primary text-white font-weight-bold">
                                                {{ number_format($total_biaya, 0, ',', '.') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Informasi Bank -->
                        <div class="row mt-4">
                            <div class="col-12 col-md-7 mb-4 mb-md-0">
                                <div class="border p-3 bg-light shadow-sm rounded">
                                    <p class="mb-1 text-primary"><strong><i class="fas fa-university mr-2"></i>Bank
                                            Transfer:</strong></p>
                                    <div class="table-responsive" style="overflow-y: hidden; white-space: nowrap;">
                                        <table class="table table-sm mb-0">
                                            <tr>
                                                <td class="border-0"><strong>Nama Bank</strong></td>
                                                <td class="border-0">:</td>
                                                <td class="border-0">Bank MANDIRI</td>
                                            </tr>
                                            <tr>
                                                <td class="border-0"><strong>No. Rekening</strong></td>
                                                <td class="border-0">:</td>
                                                <td class="border-0 font-weight-bold">006-00-1170431-3</td>
                                            </tr>
                                            <tr>
                                                <td class="border-0"><strong>Atas Nama</strong></td>
                                                <td class="border-0">:</td>
                                                <td class="border-0">PT MOSAFA PRIMA SINERGI</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-5 text-center text-md-right">
                                <div class="p-3 border-bottom">
                                    <p class="text-primary mb-3"><strong>Finance</strong></p>
                                    <div style="height: 80px;" class="d-flex align-items-end justify-content-center justify-content-md-end">
                                        <img src="{{ asset('img/signature.png') }}" alt="Tanda Tangan"
                                            style="height: 60px; max-width: 120px;" class="mb-2"
                                            onerror="this.style.display='none'">
                                    </div>
                                    <p class="font-weight-bold mb-0">Pujianti</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Informasi Sinkronisasi -->
                        @if($invoice->billing)
                        <div class="row mt-4 d-print-none">
                            <div class="col-12">
                                <div class="alert alert-info shadow-sm">
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3">
                                            <i class="fas fa-sync fa-2x"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="alert-heading mb-1">Dokumen Tersinkronisasi</h6>
                                            <p class="mb-0">Invoice ini terhubung dengan <strong>Billing {{ $invoice->billing->billing_number }}</strong>. 
                                            @if($invoice->billing->period_type === 'monthly')
                                                Saldo saat ini: 
                                                <span class="badge badge-{{ $invoice->billing->current_balance < 0 ? 'danger' : 'success' }} ml-1">
                                                    Rp {{ number_format($invoice->billing->current_balance, 0, ',', '.') }}
                                                </span>
                                            @else
                                                Total yang harus dibayar: 
                                                <span class="badge badge-warning ml-1">
                                                    Rp {{ number_format($invoice->billing->amount_to_pay, 0, ',', '.') }}
                                                </span>
                                            @endif
                                            </p>
                                        </div>
                                        <div>
                                            <a href="{{ route('billings.show', $invoice->billing) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-external-link-alt mr-1"></i>Lihat Billing
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .invoice-print {
            font-size: 14px;
        }

        .table td,
        .table th {
            vertical-align: middle;
        }

        .table-bordered td,
        .table-bordered th {
            border-color: #dee2e6;
        }

        .bg-gradient-primary {
            background: linear-gradient(to right, #4e73df, #224abe);
        }

        .bg-gradient-info {
            background: linear-gradient(to right, #36b9cc, #258391);
        }

        .shadow-sm {
            box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075) !important;
        }

        .rounded {
            border-radius: .25rem !important;
        }
        
        /* Tambahan CSS untuk responsif */
        @media (max-width: 767.98px) {
            .invoice-print {
                font-size: 12px;
            }
            
            .table th, .table td {
                padding: 0.5rem;
            }
            
            .btn-sm {
                padding: 0.25rem 0.4rem;
                font-size: 0.75rem;
            }
            
            /* Menghilangkan scrollbar */
            .table-responsive {
                overflow-x: auto;
                -ms-overflow-style: none;  /* IE and Edge */
                scrollbar-width: none;  /* Firefox */
            }
            
            .table-responsive::-webkit-scrollbar {
                display: none; /* Chrome, Safari, Opera */
            }
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                font-size: 12pt;
            }

            /* Reset semua perubahan responsif saat cetak */
            .d-none, .d-md-none {
                display: none !important;
            }
            
            .d-md-table-cell {
                display: table-cell !important;
            }
            
            /* Mengatur tata letak untuk tabel invoice */
            .info-invoice {
                table-layout: fixed !important;
                width: 100% !important;
            }
            
            .info-invoice td:first-child {
                width: 150px !important;
            }
            
            .info-invoice td:nth-child(2) {
                width: 10px !important;
            }
            
            .info-invoice td:nth-child(3) {
                width: calc(100% - 360px) !important;
            }
            
            .info-invoice td:nth-child(4) {
                width: 200px !important;
            }
            
            .table thead tr th:nth-child(1) {
                width: 430px !important;
            }
            
            .table thead tr th:nth-child(2) {
                width: 250px !important;
            }
            
            .table thead tr th:nth-child(3),
            .table thead tr th:nth-child(4),
            .table thead tr th:nth-child(5) {
                width: 130px !important;
            }
            
            /* Layout untuk cetak */
            .col-md-5, .col-12.col-md-5 {
                width: 41.66667% !important;
                flex: 0 0 41.66667% !important;
                max-width: 41.66667% !important;
                text-align: left !important;
            }
            
            .col-md-7, .col-12.col-md-7 {
                width: 58.33333% !important;
                flex: 0 0 58.33333% !important;
                max-width: 58.33333% !important;
                text-align: right !important;
            }
            
            .row {
                display: flex !important;
                flex-wrap: wrap !important;
            }
            
            .table-responsive {
                overflow-x: visible !important;
                overflow-y: hidden !important;
                white-space: normal !important;
            }
            
            .justify-content-md-start {
                justify-content: flex-start !important;
            }
            
            .justify-content-md-end {
                justify-content: flex-end !important;
            }
            
            .text-md-left {
                text-align: left !important;
            }
            
            .text-md-right {
                text-align: right !important;
            }
            
            /* Mengatur ID Pelanggan agar tidak pecah */
            [style*="white-space: nowrap"] {
                white-space: nowrap !important;
            }

            .card {
                border: none !important;
            }

            .card-header,
            .card-footer {
                display: none !important;
            }

            .main-header,
            .main-sidebar,
            .content-header,
            footer.main-footer {
                display: none !important;
            }

            .content-wrapper {
                margin-left: 0 !important;
                padding-top: 0 !important;
            }

            a {
                text-decoration: none !important;
                color: #000 !important;
            }

            .invoice-print {
                width: 100%;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 12pt !important;
            }

            /* Memastikan header tabel tetap berwarna saat cetak */
            .table thead th,
            .bg-gradient-info th,
            .table-bordered thead th {
                background-color: #20B2AA !important;
                /* Warna teal/hijau kebiruan */
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Force warna pada semua elemen dengan warna tertentu */
            *[style*="background"] {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .bg-gradient-primary,
            .bg-primary {
                background-color: #4e73df !important;
                background: #4e73df !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .bg-gradient-info,
            .bg-info {
                background-color: #36b9cc !important;
                background: #36b9cc !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .text-white {
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .text-primary {
                color: #4e73df !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .bg-light {
                background-color: #f8f9fc !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .badge-success {
                background-color: #1cc88a !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .badge-warning {
                background-color: #f6c23e !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .badge-danger {
                background-color: #e74a3b !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .badge-secondary {
                background-color: #858796 !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .shadow,
            .shadow-sm {
                box-shadow: none !important;
            }
        }
    </style>
@endsection