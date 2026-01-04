@extends('layouts.app')

@section('title', 'Kas')

@section('page-title', 'Transaksi Kas')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line mr-1"></i>
                        Account Summary
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mobile-summary-card" style="border-left: 5px solid #28a745;">
                                <strong><i class="fas fa-arrow-down mr-1 text-success"></i> Total Amount Credited</strong>
                                <p class="text-success mb-0"
                                    style="font-size: 1.3rem; font-weight: 700; margin-left: 25px;">
                                    Rp {{ number_format($totalSummary['total_credit'], 0, ',', '.') }}
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mobile-summary-card" style="border-left: 5px solid #dc3545;">
                                <strong><i class="fas fa-arrow-up mr-1 text-danger"></i> Total Amount Debited</strong>
                                <p class="text-danger mb-0" style="font-size: 1.3rem; font-weight: 700; margin-left: 25px;">
                                    Rp {{ number_format($totalSummary['total_debit'], 0, ',', '.') }}
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mobile-summary-card">
                                <strong><i class="fas fa-wallet mr-1"></i> Balance</strong>
                                <p class="text-muted mb-0">
                                    Rp {{ number_format($totalSummary['balance'], 0, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-wallet mr-1"></i>
                        Account Summary ({{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }})
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mobile-summary-card" style="border-left: 5px solid #28a745;">
                                <strong><i class="fas fa-arrow-down mr-1 text-success"></i> Total Credited</strong>
                                <p class="text-success mb-0"
                                    style="font-size: 1.3rem; font-weight: 700; margin-left: 25px;">
                                    Rp {{ number_format($monthlySummary['total_credit'], 0, ',', '.') }}
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mobile-summary-card" style="border-left: 5px solid #dc3545;">
                                <strong><i class="fas fa-arrow-up mr-1 text-danger"></i> Total Debited</strong>
                                <p class="text-danger mb-0" style="font-size: 1.3rem; font-weight: 700; margin-left: 25px;">
                                    Rp {{ number_format($monthlySummary['total_debit'], 0, ',', '.') }}
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mobile-summary-card">
                                <strong><i class="fas fa-coins mr-1"></i> Closing Balance</strong>
                                <p class="text-muted mb-0">
                                    Rp {{ number_format($monthlySummary['closing_balance'], 0, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                    <i class="fas fa-list mr-1"></i>
                    Daftar Transaksi Kas
                </h3>
                <div class="btn-group">
                    <a href="{{ route('keuangan.kas.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus-circle mr-1"></i>Tambah Transaksi
                    </a>
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#uploadExcelModal">
                        <i class="fas fa-file-upload mr-1"></i>Upload Excel
                    </button>
                    <a href="{{ route('keuangan.kas.download-template') }}" class="btn btn-info">
                        <i class="fas fa-download mr-1"></i>Download Template
                    </a>
                    <a href="{{ route('keuangan.kas.recalculate-all') }}" class="btn btn-danger"
                        onclick="return confirm('PERHATIAN!\\n\\nAnda yakin ingin menghitung ulang SELURUH saldo kas dari awal?\\n\\nProses ini akan memperbaiki data yang salah, tapi mungkin memakan waktu beberapa saat dan tidak dapat dibatalkan.')">
                        <i class="fas fa-calculator mr-1"></i>Hitung Ulang Saldo
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if ($errors->has('excel_file'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Error Upload Excel:</strong>
                    {{ $errors->first('excel_file') }}
                    @if (session('excel_errors'))
                        <ul class="mt-2 mb-0">
                            @foreach (session('excel_errors') as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-filter mr-1"></i> Filter Transaksi
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('keuangan.kas.index') }}" method="GET" class="form-row align-items-end">
                        <div class="col-md-5 mb-2">
                            <label for="month"><strong>Bulan:</strong></label>
                            <select name="month" id="month" class="form-control select2">
                                @for ($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ $i == $month ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::createFromDate(null, $i, 1)->format('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-5 mb-2">
                            <label for="year"><strong>Tahun:</strong></label>
                            <select name="year" id="year" class="form-control select2">
                                @foreach ($availableYears as $y)
                                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>
                                        {{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search mr-1"></i> Tampilkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th style="width: 10px">No</th>
                            <th>Tanggal</th>
                            <th>Voucher</th>
                            <th>Account</th>
                            <th>Deskripsi</th>
                            <th>Credit</th>
                            <th>Debit</th>
                            <th>Balance</th>
                            <th style="width: 120px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($previousBalance > 0)
                            <tr class="bg-light text-dark">
                                <td colspan="5"><strong>Saldo Awal</strong></td>
                                <td>-</td>
                                <td>-</td>
                                <td><strong>Rp {{ number_format($previousBalance, 0, ',', '.') }}</strong></td>
                                <td>-</td>
                            </tr>
                        @endif

                        @forelse ($transactions as $index => $transaction)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                                <td>{{ $transaction->voucher_number }}</td>
                                <td>{{ $transaction->account->account_name }}</td>
                                <td>{{ $transaction->description ?: '-' }}</td>
                                <td class="text-success">
                                    @if ($transaction->credit > 0)
                                        Rp {{ number_format($transaction->credit, 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-danger">
                                    @if ($transaction->debit > 0)
                                        Rp {{ number_format($transaction->debit, 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td><strong>Rp {{ number_format($transaction->balance, 0, ',', '.') }}</strong></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('keuangan.kas.edit', $transaction) }}"
                                            class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger"
                                            onclick="confirmDelete('{{ route('keuangan.kas.destroy', $transaction) }}')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">Belum ada transaksi kas untuk bulan ini</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer clearfix">
            {{ $transactions->appends(['month' => $month, 'year' => $year])->links() }}
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white">Konfirmasi Hapus</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus transaksi kas ini?</p>
                    <p><strong>Perhatian:</strong> Menghapus transaksi akan menyebabkan perubahan saldo pada semua transaksi
                        setelahnya.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <form id="deleteForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Upload Excel -->
    <div class="modal fade" id="uploadExcelModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('keuangan.kas.upload-excel') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header bg-success">
                        <h5 class="modal-title text-white">
                            <i class="fas fa-file-upload mr-2"></i>Upload Excel Transaksi Kas
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Petunjuk Upload:</h6>
                            <ul class="mb-0">
                                <li>File harus berformat <strong>.xlsx</strong> atau <strong>.xls</strong></li>
                                <li>Maksimal ukuran file: <strong>5MB</strong></li>
                                <li><strong>Maksimal 50 baris</strong> per file untuk menghindari timeout</li>
                                <li>Format tanggal: <strong>DD/MM/YYYY</strong> (contoh: 04/06/2025)</li>
                                <li>Gunakan template yang tersedia untuk format yang benar</li>
                                <li>Voucher boleh dikosongkan, sistem akan generate otomatis</li>
                                <li>Jika ada error, semua data tidak akan diproses sampai error diperbaiki</li>
                            </ul>
                        </div>

                        <div class="form-group">
                            <label for="excel_file"><strong>Pilih File Excel:</strong> <span
                                    class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="excel_file" name="excel_file"
                                accept=".xlsx,.xls" required>
                            <small class="form-text text-muted">
                                File harus berformat .xlsx atau .xls
                            </small>
                            <small class="form-text text-muted">
                                Belum punya template?
                                <a href="{{ route('keuangan.kas.download-template') }}" class="text-primary">
                                    <i class="fas fa-download"></i> Download Template Excel
                                </a>
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-success" id="uploadBtn">
                            <i class="fas fa-upload"></i> Upload & Proses
                        </button>
                    </div>

                    <!-- Progress bar (hidden by default) -->
                    <div id="uploadProgress" class="progress" style="display: none; margin: 10px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                            style="width: 100%">
                            Memproses file Excel...
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/customer-detail.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single {
            height: 38px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }

        /* File input styling sederhana seperti customer-detail */
        .form-control[type="file"] {
            padding: 0.375rem 0.75rem;
            line-height: 1.5;
        }
    </style>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        function confirmDelete(url) {
            $('#deleteForm').attr('action', url);
            $('#deleteModal').modal('show');
        }

        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2({
                width: '100%'
            });

            // Add animations to cards on hover
            $('.card').css('opacity', 0); // Initially hide
            $('.mobile-summary-card').css('opacity', 0); // Initially hide

            // Animate elements one by one when page loads
            $(window).on('load', function() {
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

            // Auto-submit form when selection changes (optional)
            /*
            $('#month, #year').on('change', function() {
                $(this).closest('form').submit();
            });
            */

            // Simple file input handling (customer-detail approach)
            // Tidak perlu JavaScript khusus - browser akan menampilkan nama file otomatis

            // Reset file input when modal is opened
            $('#uploadExcelModal').on('show.bs.modal', function() {
                $('#excel_file').val('');
            });

            // Reset file input when modal is closed
            $('#uploadExcelModal').on('hidden.bs.modal', function() {
                $('#excel_file').val('');

                // Reset progress indicators
                $('#uploadProgress').hide();
                $('#uploadBtn').prop('disabled', false).html(
                    '<i class="fas fa-upload"></i> Upload & Proses');
                $('.modal-footer .btn-secondary').prop('disabled', false);
            });

            // Form validation dan progress handling (customer-detail approach)
            $('#uploadExcelModal form').on('submit', function(e) {
                var fileInput = $('#excel_file')[0];
                if (!fileInput.files || fileInput.files.length === 0) {
                    e.preventDefault();
                    alert('Silakan pilih file Excel terlebih dahulu.');
                    return false;
                }

                // Show progress and disable submit button
                $('#uploadProgress').show();
                $('#uploadBtn').prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin"></i> Memproses...');
                $('.modal-footer .btn-secondary').prop('disabled', true);
            });

            // Show modal if there are Excel errors (customer-detail approach)
            @if ($errors->has('excel_file'))
                $('#uploadExcelModal').modal('show');
            @endif
        });
    </script>
@endsection
