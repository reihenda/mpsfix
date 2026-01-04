@extends('layouts.app')

@section('title', 'Daftar Invoice')

@section('page-title', 'Daftar Invoice')

@section('content')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="card-title">Data Invoice</h3>
            <a href="{{ route('invoices.select-customer') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle mr-1"></i>Tambah Invoice
            </a>
        </div>
        <div class="mt-3">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                </div>
                <input type="text" id="searchCustomer" class="form-control" placeholder="Cari customer..." value="{{ request('search') }}">
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

        <div id="invoice-table-container">
            @include('invoices.partials.invoice-table')
        </div>
    </div>
    <div class="card-footer clearfix" id="pagination-container">
        @include('invoices.partials.pagination')
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
    
    $(document).ready(function() {
        // Timer untuk debounce pencarian
        let searchTimer;
        
        // Event listener untuk input pencarian
        $('#searchCustomer').on('input', function() {
            const searchTerm = $(this).val();
            
            // Clear timer sebelumnya
            clearTimeout(searchTimer);
            
            // Set timer baru (300ms delay)
            searchTimer = setTimeout(function() {
                // Tampilkan loading spinner
                $('#invoice-table-container').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
                
                // Kirim request AJAX
                $.ajax({
                    url: '{{ route("invoices.index") }}',
                    type: 'GET',
                    data: { search: searchTerm },
                    dataType: 'json',
                    success: function(response) {
                        // Update tabel dan pagination
                        $('#invoice-table-container').html(response.html);
                        $('#pagination-container').html(response.pagination);
                        
                        // Update URL untuk history browser
                        const url = new URL(window.location.href);
                        if (searchTerm) {
                            url.searchParams.set('search', searchTerm);
                        } else {
                            url.searchParams.delete('search');
                        }
                        window.history.pushState({}, '', url);
                    },
                    error: function() {
                        // Tampilkan pesan error
                        $('#invoice-table-container').html('<div class="alert alert-danger">Terjadi kesalahan saat mengambil data</div>');
                    }
                });
            }, 300);
        });
    });
</script>
@endsection
