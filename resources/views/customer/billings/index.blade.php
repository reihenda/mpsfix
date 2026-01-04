@extends('layouts.app')

@section('title', 'Billing Saya')

@section('page-title', 'Billing Saya')

@section('content')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="card-title">Daftar Billing Saya</h3>
            <span class="badge badge-info">Total: {{ $billings->total() }} billing</span>
        </div>
        <div class="mt-3">
            <div class="row">
                <div class="col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" id="searchBilling" class="form-control" placeholder="Cari nomor billing..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <select id="periodFilter" class="form-control">
                        <option value="">Semua Periode</option>
                        @php
                            $currentYear = date('Y');
                            $currentMonth = date('n');
                        @endphp
                        @for($year = $currentYear; $year >= $currentYear - 2; $year--)
                            @for($month = ($year == $currentYear ? $currentMonth : 12); $month >= 1; $month--)
                                @php
                                    $monthYear = sprintf('%04d-%02d', $year, $month);
                                    $monthName = Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y');
                                @endphp
                                <option value="{{ $monthYear }}" {{ request('period') == $monthYear ? 'selected' : '' }}>
                                    {{ $monthName }}
                                </option>
                            @endfor
                        @endfor
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

        <div id="billing-table-container">
            @include('customer.billings.partials.billing-table')
        </div>
    </div>
    <div class="card-footer clearfix" id="pagination-container">
        @include('customer.billings.partials.pagination')
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        // Timer untuk debounce pencarian
        let searchTimer;
        
        // Function untuk melakukan pencarian/filter
        function performSearch() {
            const searchTerm = $('#searchBilling').val();
            const period = $('#periodFilter').val();
            
            // Tampilkan loading spinner
            $('#billing-table-container').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
            
            // Kirim request AJAX
            $.ajax({
                url: '{{ route("customer.billings") }}',
                type: 'GET',
                data: { 
                    search: searchTerm,
                    period: period
                },
                dataType: 'json',
                success: function(response) {
                    // Update tabel dan pagination
                    $('#billing-table-container').html(response.html);
                    $('#pagination-container').html(response.pagination);
                    
                    // Update URL untuk history browser
                    const url = new URL(window.location.href);
                    if (searchTerm) {
                        url.searchParams.set('search', searchTerm);
                    } else {
                        url.searchParams.delete('search');
                    }
                    if (period) {
                        url.searchParams.set('period', period);
                    } else {
                        url.searchParams.delete('period');
                    }
                    window.history.pushState({}, '', url);
                },
                error: function() {
                    // Tampilkan pesan error
                    $('#billing-table-container').html('<div class="alert alert-danger">Terjadi kesalahan saat mengambil data</div>');
                }
            });
        }
        
        // Event listener untuk input pencarian dengan debounce
        $('#searchBilling').on('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(performSearch, 300);
        });
        
        // Event listener untuk filter periode
        $('#periodFilter').on('change', function() {
            performSearch();
        });
    });
</script>
@endsection
