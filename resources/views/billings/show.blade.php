@extends('layouts.app')

@section('title', 'Detail Billing')

@section('page-title', 'Detail Billing')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-gradient-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="m-0 font-weight-bold">Billing #{{ $billing->billing_number }}</h5>
                            <p class="mb-0 mt-1 text-white-50"><i class="far fa-calendar-alt mr-1"></i>Periode:
                                {{ $periode_bulan }}</p>
                        </div>
                        <div class="btn-group">
                            @if($billing->invoice)
                                <a href="{{ route('invoices.show', $billing->invoice) }}" class="btn btn-sm btn-success" title="Lihat Invoice Terkait">
                                    <i class="fas fa-file-invoice mr-1"></i><span class="d-none d-sm-inline">Invoice</span>
                                </a>
                            @endif
                            <button onclick="window.print();" class="btn btn-sm btn-light">
                                <i class="fas fa-print mr-1"></i><span class="d-none d-sm-inline">Cetak</span>
                            </button>
                            @if(!Auth::user()->isCustomer() && !Auth::user()->isFOB())
                                <a href="{{ route('billings.edit', $billing) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit mr-1"></i><span class="d-none d-sm-inline">Edit</span>
                                </a>
                            @endif
                            @if(Auth::user()->isCustomer() || Auth::user()->isFOB())
                                <a href="{{ route('customer.billings') }}" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-arrow-left mr-1"></i><span class="d-none d-sm-inline">Kembali</span>
                                </a>
                            @else
                                <a href="{{ route('billings.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-arrow-left mr-1"></i><span class="d-none d-sm-inline">Kembali</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Header content -->
                    <div class="row mb-4">
                        <div class="col-12 col-md-6 text-center text-md-left mb-3 mb-md-0">
                            <h3 class="text-primary mb-1">{{ $customer->name }}</h3>
                            @if($customer->alamat)
                                <p class="mb-1 text-muted"><i
                                        class="fas fa-map-marker-alt mr-1"></i>{{ $customer->alamat }}
                                </p>
                            @else
                                <p class="mb-1 text-muted"><i
                                        class="fas fa-map-marker-alt mr-1"></i>Alamat tidak tersedia
                                </p>
                            @endif
                            @if($customer->nomor_tlpn)
                                <p class="mb-0 text-muted"><i
                                        class="fas fa-phone mr-1"></i>{{ $customer->nomor_tlpn }}</p>
                            @else
                                <p class="mb-0 text-muted"><i
                                        class="fas fa-phone mr-1"></i>Telepon tidak tersedia</p>
                            @endif
                        </div>

                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-header bg-gradient-primary text-white">
                                    <div class="d-flex justify-content-between align-items-center"
                                        style="padding-left: 15px;">
                                        <h5 class="mb-0 font-weight-bold"><i
                                                class="fas fa-file-invoice mr-2"></i>PERHITUNGAN TAGIHAN GAS</h5>
                                        <span class="badge badge-light p-2">Periode {{ $periode_bulan }}</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive no-scroll-text">
                                        <table class="table table-striped table-bordered">
                                            <thead class="thead-dark">
                                                <tr>
                                                    <th class="text-center" style="width: 25px;">No</th>
                                                    <th class="text-center" style="width: 60px;">Periode</th>
                                                    <th class="text-center" style="width: 55px;">Volume</th>
                                                    <th class="text-center" style="width: 60px;">Harga</th>
                                                    <th class="text-center" style="width: 70px;">Biaya</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($pemakaian_gas as $item)
                                                    @if ($item['no'] != 'Total')
                                                        <tr>
                                                            <td class="text-center">{{ $item['no'] }}</td>
                                                            <td class="text-center">
                                                                {{ \Carbon\Carbon::createFromFormat('d/m/Y', $item['periode_pemakaian'])->format('d-M-y') }}
                                                            </td>
                                                            <td class="text-right">
                                                                {{ number_format($item['volume_sm3'], 2, ',', '.') }}</td>
                                                            <td class="text-right">Rp
                                                                {{ number_format($item['harga_gas'], 0, ',', '.') }}</td>
                                                            <td class="text-right text-primary">Rp
                                                                {{ number_format($item['biaya_pemakaian'], 0, ',', '.') }}
                                                            </td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                                <tr class="bg-light">
                                                    <th colspan="2" class="text-center">Total</th>
                                                    <th class="text-right">
                                                        {{ number_format($billing->total_volume, 2, ',', '.') }}</th>
                                                    <th class="text-center"></th>
                                                    <th class="text-right text-primary">Rp
                                                        {{ number_format($billing->total_amount, 0, ',', '.') }}</th>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="row">
                                @if($billing->period_type === 'monthly')
                                    <!-- Tampilkan untuk periode bulanan (dengan deposit dan saldo) -->
                                    <div class="col-md-6">
                                        <div class="card shadow-sm">
                                            <div class="card-header bg-gradient-success text-white">
                                                <h5 class="mb-0 font-weight-bold" style="padding-left: 15px;"><i
                                                        class="fas fa-money-bill-wave mr-2"></i>PENERIMAAN DEPOSIT</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="table-responsive no-scroll-text">
                                                    <table class="table table-striped table-bordered">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th class="text-center" style="width: 25px">No</th>
                                                                <th style="width: 65px">Tanggal</th>
                                                                <th class="text-right" style="width: 70px">Jumlah</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @forelse($penerimaan_deposit as $deposit)
                                                                <tr>
                                                                    <td class="text-center">{{ $deposit['no'] }}</td>
                                                                    <td>
                                                                        <span class="badge badge-success p-2">
                                                                            <i class="far fa-calendar-check mr-1"></i>
                                                                            {{ \Carbon\Carbon::createFromFormat('d/m/Y', $deposit['tanggal_deposit'])->format('d-M-y') }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="text-right font-weight-bold text-success">Rp
                                                                        {{ number_format($deposit['jumlah_penerimaan'], 0, ',', '.') }}
                                                                    </td>
                                                                </tr>
                                                            @empty
                                                                <tr>
                                                                    <td colspan="3" class="text-center">
                                                                        <span class="text-muted">
                                                                            <i class="fas fa-info-circle mr-1"></i>
                                                                            Tidak ada deposit pada periode ini
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            @endforelse
                                                        </tbody>
                                                        <tfoot>
                                                            <tr class="bg-light">
                                                                <th colspan="2" class="text-center">Total Penerimaan</th>
                                                                <th class="text-right text-success">Rp
                                                                    {{ number_format($billing->total_deposit, 0, ',', '.') }}
                                                                </th>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card shadow-sm">
                                            <div class="card-header bg-gradient-info text-white">
                                                <h5 class="mb-0 font-weight-bold" style="padding-left: 15px;"><i
                                                        class="fas fa-calculator mr-2"></i>JUMLAH TAGIHAN BULAN INI</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="table-responsive no-scroll-text">
                                                    <table class="table table-bordered">
                                                        <tbody>
                                                            <tr>
                                                                <td class="bg-light" style="width: 45%"><strong>Saldo Bulan
                                                                        Lalu</strong></td>
                                                                <td
                                                                    class="text-right {{ $billing->previous_balance < 0 ? 'text-danger' : 'text-success' }} font-weight-bold">
                                                                    Rp
                                                                    {{ number_format($billing->previous_balance, 0, ',', '.') }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Penerimaan Deposit</strong></td>
                                                                <td class="text-right text-success font-weight-bold">
                                                                    <i class="fas fa-plus-circle mr-1"></i>
                                                                    Rp
                                                                    {{ number_format($billing->total_deposit, 0, ',', '.') }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Biaya Pemakaian</strong></td>
                                                                <td class="text-right text-danger font-weight-bold">
                                                                    <i class="fas fa-minus-circle mr-1"></i>
                                                                    Rp {{ number_format($billing->total_amount, 0, ',', '.') }}
                                                                </td>
                                                            </tr>
                                                            <tr class="bg-light">
                                                                <td class="font-weight-bold">Sisa Saldo</td>
                                                                <td
                                                                    class="text-right {{ $billing->current_balance < 0 ? 'text-danger' : 'text-success' }} font-weight-bold">
                                                                    Rp
                                                                    {{ number_format($billing->current_balance, 0, ',', '.') }}
                                                                </td>
                                                            </tr>
                                                            <tr class="bg-light">
                                                                <td class="font-weight-bold">Biaya Yang Masih Harus Dibayarkan
                                                                </td>
                                                                <td class="text-right text-danger font-weight-bold">
                                                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                                                    Rp
                                                                    {{ number_format($billing->current_balance < 0 ? abs($billing->current_balance) : 0, 0, ',', '.') }}
                                                                </td>
                                                            </tr>
                                                            @if ($billing->amount_to_pay > 0)
                                                                <tr class="bg-warning">
                                                                    <td class="font-weight-bold">Jumlah Yang Harus Dibayar</td>
                                                                    <td class="text-right text-danger">
                                                                        Rp
                                                                        {{ number_format($billing->amount_to_pay, 0, ',', '.') }}
                                                                    </td>
                                                                </tr>
                                                            @endif
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <!-- Tampilkan untuk periode custom (hanya biaya yang harus dibayar) -->
                                    <div class="col-md-12">
                                        <div class="card shadow-sm">
                                            <div class="card-header bg-gradient-warning text-white">
                                                <h5 class="mb-0 font-weight-bold" style="padding-left: 15px;"><i
                                                        class="fas fa-file-invoice-dollar mr-2"></i>TOTAL BIAYA PEMAKAIAN</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6 offset-md-3">
                                                        <div class="table-responsive no-scroll-text">
                                                            <table class="table table-bordered">
                                                                <tbody>
                                                                    <tr>
                                                                        <td class="bg-light" style="width: 50%"><strong>Total Biaya Pemakaian Gas</strong></td>
                                                                        <td class="text-right text-primary font-weight-bold">
                                                                            Rp {{ number_format($billing->total_amount, 0, ',', '.') }}
                                                                        </td>
                                                                    </tr>
                                                                    <tr class="bg-warning">
                                                                        <td class="font-weight-bold"><strong>Biaya Yang Harus Dibayarkan</strong></td>
                                                                        <td class="text-right text-danger font-weight-bold" style="font-size: 1.2em;">
                                                                            <i class="fas fa-exclamation-circle mr-1"></i>
                                                                            Rp {{ number_format($billing->amount_to_pay, 0, ',', '.') }}
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row mt-3">
                                                    <div class="col-12 text-center">
                                                        <div class="alert alert-warning">
                                                            <i class="fas fa-info-circle mr-2"></i>
                                                            <strong>Catatan:</strong> Billing ini adalah untuk periode khusus ({{ $periode_bulan }}) dan tidak termasuk perhitungan saldo atau deposit.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Call to Action -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="alert alert-info shadow-sm">
                                        <div class="d-flex align-items-center">
                                            <div class="mr-3">
                                                <i class="fas fa-info-circle fa-2x"></i>
                                            </div>
                                            <div>
                                                <h5 class="alert-heading mb-1">Informasi Pembayaran</h5>
                                                <p class="mb-0">Mohon lakukan pembayaran tepat waktu untuk kelancaran
                                                    layanan. Untuk informasi lebih lanjut, silakan hubungi tim kami.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Informasi Sinkronisasi -->
                            @if($billing->invoice)
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="alert alert-success shadow-sm">
                                        <div class="d-flex align-items-center">
                                            <div class="mr-3">
                                                <i class="fas fa-sync fa-2x"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="alert-heading mb-1">Dokumen Tersinkronisasi</h6>
                                                <p class="mb-0">Billing ini terhubung dengan <strong>Invoice {{ $billing->invoice->invoice_number }}</strong>. 
                                                Status pembayaran: 
                                                <span class="badge badge-{{ $billing->invoice->status == 'paid' ? 'success' : ($billing->invoice->status == 'partial' ? 'warning' : 'danger') }} ml-1">
                                                    {{ ucfirst($billing->invoice->status) }}
                                                </span>
                                                </p>
                                            </div>
                                            <div>
                                                <a href="{{ route('invoices.show', $billing->invoice) }}" class="btn btn-sm btn-success">
                                                    <i class="fas fa-external-link-alt mr-1"></i>Lihat Invoice
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

            <style>
                /* Styling umum untuk tampilan billing */
                .card {
                    margin-bottom: 20px;
                    border-radius: 0.5rem;
                    overflow: hidden;
                }

                .card-header {
                    padding: 0.8rem 1.2rem;
                    border-bottom: 0;
                }

                .card-body {
                    padding: 1rem 1rem;
                }

                .shadow {
                    box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15) !important;
                }

                .shadow-sm {
                    box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075) !important;
                }

                .bg-gradient-primary {
                    background: linear-gradient(to right, #4e73df, #224abe);
                }

                .bg-gradient-success {
                    background: linear-gradient(to right, #1cc88a, #13855c);
                }

                .bg-gradient-info {
                    background: linear-gradient(to right, #36b9cc, #258391);
                }

                .bg-gradient-warning {
                    background: linear-gradient(to right, #f6c23e, #dba119);
                }

                .text-primary {
                    color: #4e73df !important;
                }

                .text-success {
                    color: #1cc88a !important;
                }

                .text-danger {
                    color: #e74a3b !important;
                }

                .table-striped tbody tr:nth-of-type(odd) {
                    background-color: rgba(0, 0, 0, 0.03);
                }

                .badge {
                    padding: 0.35em 0.65em;
                    border-radius: 0.25rem;
                    font-size: 0.85em;
                }

                .card-header h5 {
                    font-weight: 600;
                    margin-bottom: 0;
                }

                .table-bordered {
                    border: 1px solid #e3e6f0;
                }

                .table-bordered th,
                .table-bordered td {
                    border: 1px solid #e3e6f0;
                    padding: 0.75rem;
                    vertical-align: middle;
                }

                .thead-dark th {
                    background-color: #343a40;
                    color: white;
                    border-color: #454d55;
                }

                /* Perbaikan tampilan table */
                .table {
                    width: 100%;
                    margin-bottom: 1rem;
                    border-collapse: collapse;
                    color: #5a5c69;
                }

                .table th {
                    font-weight: bold;
                    text-align: center;
                    vertical-align: middle;
                }

                /* Menghilangkan teks "← Scroll →" pada tabel responsif */
                .table-responsive.no-scroll-text::after,
                .table-responsive::after {
                    content: none !important;
                    display: none !important;
                }

                .table-responsive {
                    display: block;
                    width: 100%;
                    overflow-x: visible !important;
                    -webkit-overflow-scrolling: touch;
                    max-width: 100% !important;
                }

                /* Alert styling */
                .alert {
                    border: none;
                    border-radius: 0.5rem;
                    padding: 1rem;
                }

                .alert-info {
                    background-color: rgba(54, 185, 204, 0.15);
                    color: #36b9cc;
                }

                /* Pengaturan ukuran kertas */
                @page {
                    size: A4 landscape;
                    margin: 2cm;
                }

                /* Tampilan cetak yang lebih baik */
                @media print {

                    html,
                    body {
                        width: 297mm;
                        height: 210mm;
                        min-width: 297mm;
                        max-width: 297mm;
                        font-size: 11pt;
                    }

                    body {
                        margin: 0;
                        padding: 5px;
                        font-size: 11pt;
                        background-color: white;
                        color: black;
                        width: 100%;
                    }

                    .container,
                    .container-fluid,
                    .content {
                        width: 100% !important;
                        max-width: 100% !important;
                        padding: 0 !important;
                        margin: 0 auto !important;
                        box-sizing: border-box !important;
                    }

                    .card {
                        border: none !important;
                        box-shadow: none !important;
                        margin-bottom: 20px !important;
                    }

                    .card-body {
                        padding: 10px 0 !important;
                    }

                    .card-header {
                        padding: 10px 0 !important;
                        border-bottom: 1px solid #000 !important;
                        margin-bottom: 10px !important;
                    }

                    .card-header h3,
                    .card-header h5 {
                        margin: 0 !important;
                        font-weight: bold !important;
                    }

                    /* Sembunyikan elemen yang tidak dibutuhkan saat cetak */
                    .main-header,
                    .main-sidebar,
                    .content-header,
                    footer.main-footer,
                    .btn,
                    .d-print-none,
                    .d-none,
                    .alert {
                        display: none !important;
                    }

                    /* Gunakan warna yang tepat saat cetak */
                    .bg-gradient-primary,
                    .bg-primary,
                    .card-header.bg-primary,
                    .card-header.bg-gradient-primary,
                    .thead-dark th {
                        background-color: #4e73df !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }

                    .bg-gradient-success,
                    .bg-success,
                    .card-header.bg-success,
                    .card-header.bg-gradient-success {
                        background-color: #1cc88a !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }

                    .bg-gradient-info,
                    .bg-info,
                    .card-header.bg-info,
                    .card-header.bg-gradient-info {
                        background-color: #36b9cc !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }

                    /* Memastikan teks pada elemen dengan background tetap putih saat cetak */
                    .card-header.bg-gradient-primary,
                    .card-header.bg-primary,
                    .thead-dark th,
                    .card-header.bg-gradient-success,
                    .card-header.bg-success,
                    .card-header.bg-gradient-info,
                    .card-header.bg-info {
                        color: white !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }

                    .card-header.bg-gradient-primary *,
                    .card-header.bg-primary *,
                    .thead-dark th *,
                    .card-header.bg-gradient-success *,
                    .card-header.bg-success *,
                    .card-header.bg-gradient-info *,
                    .card-header.bg-info * {
                        color: white !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }

                    .bg-warning {
                        background-color: #f6c23e !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }

                    .bg-light {
                        background-color: #f8f9fc !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }

                    .text-danger {
                        color: #e74a3b !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }

                    .text-success {
                        color: #1cc88a !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }

                    .text-primary {
                        color: #4e73df !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }

                    /* Perbaikan tabel saat cetak */
                    .table {
                        width: 100% !important;
                        margin-bottom: 1rem !important;
                        page-break-inside: auto !important;
                        border-collapse: collapse !important;
                        table-layout: fixed !important;
                    }

                    .table th,
                    .table td {
                        border: 1px solid #000 !important;
                        padding: 5px !important;
                        word-wrap: break-word !important;
                        overflow-wrap: break-word !important;
                    }

                    /* Hindari memotong baris tabel */
                    tr,
                    td,
                    th {
                        page-break-inside: avoid !important;
                    }

                    thead {
                        display: table-header-group !important;
                    }

                    /* Pastikan konten tidak terpotong di halaman berikutnya */
                    .page-break {
                        page-break-before: always !important;
                    }

                    /* Force warna pada semua elemen dengan warna tertentu */
                    *[style*="background"] {
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }

                    /* Menyembunyikan teks "Scroll" pada tabel responsive saat cetak */
                    .table-responsive::after,
                    .table-responsive.no-scroll-text::after {
                        content: none !important;
                        display: none !important;
                    }

                    /* Badge styling untuk cetak */
                    .badge {
                        border: 1px solid #000 !important;
                        padding: 2px 5px !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }

                    .badge-primary {
                        background-color: #4e73df !important;
                        color: white !important;
                    }

                    .badge-success {
                        background-color: #1cc88a !important;
                        color: white !important;
                    }

                    .badge-info {
                        background-color: #36b9cc !important;
                        color: white !important;
                    }

                    /* Text-white fix */
                    .text-white,
                    .text-white-50 {
                        color: white !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }
                }
            </style>
        @endsection
