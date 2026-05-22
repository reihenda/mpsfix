@extends('layouts.app')

@section('title', 'Invoice - ' . $customer->name)

@section('page-title', 'Invoice')

@section('content')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-secondary mr-2">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h3 class="card-title d-inline">Invoice - {{ $customer->name }}</h3>
            </div>
            <a href="{{ route('invoices.create', $customer) }}" class="btn btn-primary">
                <i class="fas fa-plus-circle mr-1"></i>Tambah Invoice
            </a>
        </div>
        <div class="mt-3">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                </div>
                <input type="text" id="searchInvoice" class="form-control" placeholder="Cari nomor invoice..." value="{{ request('search') }}">
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
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ $errors->first() }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div id="invoice-table-container">
            @include('invoices.partials.invoice-table', ['invoices' => $invoices, 'customer' => $customer])
        </div>
    </div>
    <div class="card-footer clearfix" id="pagination-container">
        @include('invoices.partials.pagination', ['invoices' => $invoices, 'customer' => $customer])
    </div>
</div>

<!-- Modal Upload Materai -->
<div class="modal fade" id="uploadMateraiModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white"><i class="fas fa-upload mr-2"></i>Upload Invoice Bermaterai</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="uploadMateraiForm" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <p class="text-muted mb-3">Upload file PDF invoice yang sudah diberi materai.</p>
                    <div class="form-group">
                        <label for="materai_file">File Invoice Bermaterai <span class="text-danger">*</span></label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="materai_file" name="materai_file" accept=".pdf" required>
                            <label class="custom-file-label" for="materai_file">Pilih file PDF...</label>
                        </div>
                        <small class="form-text text-muted">Format: PDF. Maksimal 10MB.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload mr-1"></i>Upload
                    </button>
                </div>
            </form>
        </div>
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
                <p>Apakah Anda yakin ingin menghapus invoice ini?</p>
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
@endsection

@section('js')
<script>
    function confirmDelete(url) {
        $('#deleteForm').attr('action', url);
        $('#deleteModal').modal('show');
    }

    function openUploadModal(uploadUrl) {
        $('#uploadMateraiForm').attr('action', uploadUrl);
        $('#materai_file').val('');
        $('.custom-file-label').text('Pilih file PDF...');
        $('#uploadMateraiModal').modal('show');
    }

    $(document).ready(function() {
        // Update label file input
        $('#materai_file').on('change', function() {
            const fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').text(fileName || 'Pilih file PDF...');
        });

        let searchTimer;
        $('#searchInvoice').on('input', function() {
            const searchTerm = $(this).val();
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                $('#invoice-table-container').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
                $.ajax({
                    url: '{{ route("invoices.customer-list", $customer) }}',
                    type: 'GET',
                    data: { search: searchTerm },
                    dataType: 'json',
                    success: function(response) {
                        $('#invoice-table-container').html(response.html);
                        $('#pagination-container').html(response.pagination);
                        const url = new URL(window.location.href);
                        if (searchTerm) {
                            url.searchParams.set('search', searchTerm);
                        } else {
                            url.searchParams.delete('search');
                        }
                        window.history.pushState({}, '', url);
                    },
                    error: function() {
                        $('#invoice-table-container').html('<div class="alert alert-danger">Terjadi kesalahan saat mengambil data</div>');
                    }
                });
            }, 300);
        });
    });
</script>
@endsection
