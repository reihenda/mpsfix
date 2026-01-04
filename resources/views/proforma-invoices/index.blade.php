@extends('layouts.app')

@section('title', 'Daftar Proforma Invoice')

@section('page-title', 'Daftar Proforma Invoice')

@section('content')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="card-title">Data Proforma Invoice</h3>
            <a href="{{ route('proforma-invoices.select-customer') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle mr-1"></i>Tambah Proforma Invoice
            </a>
        </div>
        <div class="mt-3">
            <div class="row">
                <div class="col-md-8">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" id="searchCustomer" class="form-control" placeholder="Cari customer..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-control" id="statusFilter">
                        <option value="">Semua Status</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                        <option value="converted" {{ request('status') == 'converted' ? 'selected' : '' }}>Converted</option>
                    </select>
                </div>
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

        <div id="proforma-table-container">
            @include('proforma-invoices.partials.proforma-table')
        </div>
    </div>
    <div class="card-footer clearfix" id="pagination-container">
        @include('proforma-invoices.partials.pagination')
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
                <p>Apakah Anda yakin ingin menghapus proforma invoice ini?</p>
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
    
    $(document).ready(function() {
        // Timer untuk debounce pencarian
        let searchTimer;
        
        // Event listener untuk input pencarian dan filter status
        $('#searchCustomer, #statusFilter').on('input change', function() {
            const searchTerm = $('#searchCustomer').val();
            const statusFilter = $('#statusFilter').val();
            
            // Clear timer sebelumnya
            clearTimeout(searchTimer);
            
            // Set timer baru (300ms delay)
            searchTimer = setTimeout(function() {
                // Tampilkan loading spinner
                $('#proforma-table-container').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
                
                // Kirim request AJAX
                $.ajax({
                    url: '{{ route("proforma-invoices.index") }}',
                    type: 'GET',
                    data: { 
                        search: searchTerm,
                        status: statusFilter 
                    },
                    dataType: 'json',
                    success: function(response) {
                        // Update tabel dan pagination
                        $('#proforma-table-container').html(response.html);
                        $('#pagination-container').html(response.pagination);
                        
                        // Update URL untuk history browser
                        const url = new URL(window.location.href);
                        if (searchTerm) {
                            url.searchParams.set('search', searchTerm);
                        } else {
                            url.searchParams.delete('search');
                        }
                        if (statusFilter) {
                            url.searchParams.set('status', statusFilter);
                        } else {
                            url.searchParams.delete('status');
                        }
                        window.history.pushState({}, '', url);
                    },
                    error: function() {
                        // Tampilkan pesan error
                        $('#proforma-table-container').html('<div class="alert alert-danger">Terjadi kesalahan saat mengambil data</div>');
                    }
                });
            }, 300);
        });
    });
</script>
@endsection
