@extends('layouts.app')

@section('title', 'Perbaikan Saldo Bulanan')

@section('page-title', 'Perbaikan Saldo Bulanan')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check mr-2"></i>
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-times mr-2"></i>
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif
        </div>
        
        <div class="col-lg-6">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-tools mr-2"></i>
                        Alat Perbaikan Saldo
                    </h3>
                </div>
                <div class="card-body">
                    <p>Alat ini digunakan untuk memperbaiki perhitungan saldo bulanan pada aplikasi. Gunakan ini jika ada masalah dengan perhitungan saldo.</p>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Catatan:</strong> Perbaikan saldo dilakukan dengan menghitung ulang saldo bulanan berdasarkan data yang ada di database.
                    </div>
                    
                    <div class="form-group">
                        <a href="{{ route('fix.all.balances') }}" class="btn btn-primary btn-lg btn-block">
                            <i class="fas fa-sync-alt mr-2"></i>
                            Perbaiki Semua Saldo Customer
                        </a>
                        <small class="form-text text-muted">Proses ini akan memperbaiki saldo bulanan untuk semua customer yang ada di sistem.</small>
                    </div>
                    
                    <hr>
                    
                    <h5>Perbaiki Saldo Customer Tertentu</h5>
                    <div class="form-group">
                        <label for="customerId">Pilih Customer:</label>
                        <select id="customerId" class="form-control">
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button id="fixSelectedCustomer" class="btn btn-success btn-block">
                            <i class="fas fa-user-cog mr-2"></i>
                            Perbaiki Saldo Customer Terpilih
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line mr-2"></i>
                        Hasil Perbaikan
                    </h3>
                </div>
                <div class="card-body">
                    <div id="result-container">
                        <div class="text-center py-5">
                            <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                            <p class="lead">Hasil akan ditampilkan di sini setelah proses perbaikan dijalankan.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(function() {
        // Fix selected customer
        $('#fixSelectedCustomer').on('click', function() {
            const customerId = $('#customerId').val();
            const resultContainer = $('#result-container');
            
            // Show loading
            resultContainer.html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="lead">Sedang memperbaiki saldo...</p>
                </div>
            `);
            
            // Call API
            $.ajax({
                url: "{{ url('/fix-balance') }}/" + customerId,
                method: 'GET',
                success: function(response) {
                    // Show success
                    let html = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle mr-2"></i>
                            <strong>Sukses!</strong> Saldo bulanan berhasil diperbaiki.
                        </div>
                        
                        <h5>Detail Saldo Bulanan:</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Periode</th>
                                        <th>Saldo</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;
                    
                    // Sort months by date
                    const monthlyBalances = response.monthly_balances || {};
                    const sortedMonths = Object.keys(monthlyBalances).sort();
                    
                    // Add rows
                    $.each(sortedMonths, function(index, month) {
                        const balance = parseFloat(monthlyBalances[month]);
                        html += `
                            <tr>
                                <td>${formatMonth(month)}</td>
                                <td class="${balance < 0 ? 'text-danger' : 'text-success'}">
                                    Rp ${formatNumber(balance)}
                                </td>
                            </tr>
                        `;
                    });
                    
                    html += `
                                </tbody>
                            </table>
                        </div>
                    `;
                    
                    resultContainer.html(html);
                },
                error: function(xhr) {
                    // Show error
                    resultContainer.html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle mr-2"></i>
                            <strong>Error!</strong> Terjadi kesalahan saat memperbaiki saldo.
                            <p class="mt-2 mb-0">${xhr.responseJSON?.message || 'Unknown error'}</p>
                        </div>
                    `);
                }
            });
        });
        
        // Format month (YYYY-MM to Month Year)
        function formatMonth(yearMonth) {
            const [year, month] = yearMonth.split('-');
            const date = new Date(year, month - 1, 1);
            
            return date.toLocaleDateString('id-ID', {
                month: 'long',
                year: 'numeric'
            });
        }
        
        // Format number to currency
        function formatNumber(number) {
            return new Intl.NumberFormat('id-ID').format(number);
        }
    });
</script>
@endsection