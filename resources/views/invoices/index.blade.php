@extends('layouts.app')

@section('title', 'Invoice')

@section('page-title', 'Invoice')

@section('content')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="card-title">Daftar Customer</h3>
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
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div id="customer-table-container">
            @include('invoices.partials.customer-table')
        </div>
    </div>
    <div class="card-footer clearfix" id="pagination-container">
        @include('invoices.partials.pagination-customers')
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        let searchTimer;

        $('#searchCustomer').on('input', function() {
            const searchTerm = $(this).val();
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                $('#customer-table-container').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
                $.ajax({
                    url: '{{ route("invoices.index") }}',
                    type: 'GET',
                    data: { search: searchTerm },
                    dataType: 'json',
                    success: function(response) {
                        $('#customer-table-container').html(response.html);
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
                        $('#customer-table-container').html('<div class="alert alert-danger">Terjadi kesalahan saat mengambil data</div>');
                    }
                });
            }, 300);
        });
    });
</script>
@endsection
