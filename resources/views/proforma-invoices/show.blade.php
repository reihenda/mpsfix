@extends('layouts.app')

@section('title', 'Detail Proforma Invoice')

@section('page-title', 'Detail Proforma Invoice')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <!-- Header Card sama seperti Invoice biasa -->
                <div class="card-header bg-gradient-primary text-white">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                        <div class="mb-2 mb-md-0">
                            <h5 class="m-0 font-weight-bold">Proforma Invoice #{{ $proformaInvoice->proforma_number }}</h5>
                            <small>
                                @php
                                    $statusClass = [
                                        'draft' => 'badge-secondary',
                                        'sent' => 'badge-info',
                                        'expired' => 'badge-danger',
                                        'converted' => 'badge-success'
                                    ];
                                    $statusText = [
                                        'draft' => 'Draft',
                                        'sent' => 'Terkirim',
                                        'expired' => 'Kadaluarsa',
                                        'converted' => 'Dikonversi'
                                    ];
                                @endphp
                                <span class="badge badge-pill {{ $statusClass[$proformaInvoice->status] ?? 'badge-secondary' }} mt-1">
                                    {{ $statusText[$proformaInvoice->status] ?? ucfirst($proformaInvoice->status) }}
                                </span>
                                <span class="badge badge-pill badge-light mt-1">{{ $periode_bulan }}</span>
                                @if($proformaInvoice->validity_date)
                                    <span class="badge badge-pill badge-warning mt-1">
                                        <i class="fas fa-clock mr-1"></i>Berlaku s/d {{ $proformaInvoice->validity_date->format('d/m/Y') }}
                                    </span>
                                @endif
                            </small>
                        </div>
                        <div class="btn-group">
                            <button onclick="window.print();" class="btn btn-sm btn-light">
                                <i class="fas fa-print mr-1"></i><span class="d-none d-sm-inline">Cetak</span>
                            </button>
                            <a href="{{ route('proforma-invoices.edit', $proformaInvoice) }}" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit mr-1"></i><span class="d-none d-sm-inline">Edit</span>
                            </a>
                            <a href="{{ route('proforma-invoices.index') }}" class="btn btn-sm btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i><span class="d-none d-sm-inline">Kembali</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Body dengan konten proforma -->
                <div class="card-body p-0">
                    <div class="invoice-print p-3">
                        <!-- Watermark PROFORMA -->
                        <div class="proforma-watermark">PROFORMA</div>
                        
                        <!-- Kop Perusahaan sama seperti Invoice -->
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

                        <!-- Judul PROFORMA INVOICE -->
                        <div class="row">
                            <div class="col-12 text-center">
                                <h3 class="text-uppercase font-weight-bold p-2 border border-dark">PROFORMA INVOICE</h3>
                            </div>
                        </div>

                        <!-- Bagian lainnya akan ditambahkan step by step -->
                        <div class="mt-4">
                            <!-- Informasi Proforma Invoice -->
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <tr class="bg-light">
                                        <td width="150px"><strong>Nomor</strong></td>
                                        <td width="10px">:</td>
                                        <td><span class="text-primary font-weight-bold">{{ $proformaInvoice->proforma_number }}</span></td>
                                        <td rowspan="4" class="align-middle bg-light text-center" width="200px">
                                            <strong>Kepada :</strong><br>
                                            <h5 class="font-weight-bold text-primary">{{ strtoupper($customer->name) }}</h5>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tanggal</strong></td>
                                        <td>:</td>
                                        <td>{{ $proformaInvoice->proforma_date->format('d-M-Y') }}</td>
                                    </tr>
                                    <tr class="bg-light">
                                        <td><strong>Jatuh Tempo</strong></td>
                                        <td>:</td>
                                        <td>
                                            <span class="badge badge-info p-2">
                                                {{ $proformaInvoice->due_date->format('d-M-Y') }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>No Kontrak</strong></td>
                                        <td>:</td>
                                        <td>{{ $proformaInvoice->no_kontrak }}</td>
                                    </tr>
                                    <tr class="bg-light">
                                        <td colspan="3"></td>
                                        <td class="text-center">
                                            <strong>ID Pelanggan :</strong>
                                            <span class="badge badge-secondary p-2">{{ $proformaInvoice->id_pelanggan }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="3"></td>
                                        <td class="text-center">
                                            <strong>Saldo per {{ $tanggal_saldo->format('d M Y') }} :</strong><br>
                                            <span class="badge badge-{{ $saldo_per_tanggal >= 0 ? 'success' : 'danger' }} p-2">
                                                Rp {{ number_format($saldo_per_tanggal, 0, ',', '.') }}
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Tabel Pemakaian Gas -->
                            <div class="mt-3">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead style="background-color: #20B2AA !important;">
                                            <tr>
                                                <th style="background-color: #20B2AA; color: white;">Keterangan</th>
                                                <th style="background-color: #20B2AA; color: white;">Periode</th>
                                                <th style="background-color: #20B2AA; color: white;">Volume Pemakaian (Sm3)</th>
                                                <th style="background-color: #20B2AA; color: white;">Harga Satuan (Rp)</th>
                                                <th style="background-color: #20B2AA; color: white;">Total (Rp)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($pemakaian_gas as $item)
                                                <tr>
                                                    <td>
                                                        <i class="fas fa-gas-pump text-primary mr-2"></i>
                                                        <strong>{{ $proformaInvoice->description ?: 'Pemakaian CNG' }}</strong>
                                                        <small class="text-warning d-block font-weight-bold">(ESTIMASI)</small>
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
                            
                            <!-- Terbilang dan Totals -->
                            <div class="mt-2">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <tr>
                                            <td class="bg-light"><strong>Terbilang:</strong></td>
                                            <td class="font-italic">{{ ucfirst($terbilang) }} rupiah</td>
                                            <td class="bg-light"><strong>Sub Total</strong></td>
                                            <td>:</td>
                                            <td class="text-right font-weight-bold">
                                                Rp {{ number_format($total_biaya, 0, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" rowspan="3" class="align-middle">
                                                <div class="alert alert-warning mb-0">
                                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                                    <small><strong>PERHATIAN:</strong> Ini adalah <strong>PROFORMA INVOICE</strong> (invoice sementara). 
                                                    Dokumen ini belum dapat digunakan sebagai bukti pembayaran resmi.</small>
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
                                            <td class="bg-danger text-white"><strong>TOTAL ESTIMASI</strong></td>
                                            <td class="bg-danger text-white">:</td>
                                            <td class="text-right bg-danger text-white font-weight-bold">
                                                Rp {{ number_format($total_biaya, 0, ',', '.') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Informasi Bank -->
                            <div class="row mt-4">
                                <div class="col-12 col-md-7 mb-4 mb-md-0">
                                    <div class="border p-3 bg-light shadow-sm rounded">
                                        <p class="mb-1 text-primary"><strong><i class="fas fa-university mr-2"></i>Bank Transfer:</strong></p>
                                        <div class="table-responsive">
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
                                        <div class="alert alert-info mt-3 mb-0">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            <small><strong>CATATAN:</strong> Pembayaran dapat dilakukan setelah menerima invoice resmi.</small>
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CSS lengkap sama seperti Invoice biasa -->
    <style>
        .proforma-watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 5rem;
            font-weight: bold;
            color: rgba(220, 53, 69, 0.08);
            z-index: 1;
            pointer-events: none;
            user-select: none;
        }
        
        .invoice-print {
            position: relative;
            z-index: 2;
            font-size: 14px;
        }

        .table td, .table th {
            vertical-align: middle;
        }

        .table-bordered td, .table-bordered th {
            border-color: #dee2e6;
        }

        .bg-gradient-primary {
            background: linear-gradient(to right, #4e73df, #224abe);
        }

        .shadow-sm {
            box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075) !important;
        }

        .rounded {
            border-radius: .25rem !important;
        }
        
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
            
            .proforma-watermark {
                font-size: 3rem;
            }
            
            .table-responsive {
                overflow-x: auto;
                -ms-overflow-style: none;
                scrollbar-width: none;
            }
            
            .table-responsive::-webkit-scrollbar {
                display: none;
            }
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                font-size: 12pt;
            }

            .proforma-watermark {
                font-size: 8rem;
                color: rgba(220, 53, 69, 0.12) !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .card {
                border: none !important;
            }

            .card-header, .card-footer {
                display: none !important;
            }

            .main-header, .main-sidebar, .content-header, footer.main-footer {
                display: none !important;
            }

            .content-wrapper {
                margin-left: 0 !important;
                padding-top: 0 !important;
            }

            .invoice-print {
                width: 100%;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 12pt !important;
            }

            .table thead th {
                background-color: #20B2AA !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .bg-danger {
                background-color: #dc3545 !important;
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

            .badge-warning {
                background-color: #f6c23e !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .badge-info {
                background-color: #17a2b8 !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .table-responsive {
                overflow-x: visible !important;
                overflow-y: hidden !important;
                white-space: normal !important;
            }

            .shadow, .shadow-sm {
                box-shadow: none !important;
            }
        }
    </style>
@endsection
