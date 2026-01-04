@extends('layouts.app')

@section('title', 'Dashboard Keuangan')

@section('page-title', 'Dashboard Keuangan')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <!-- Main Financial Summary -->
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
                            <p class="text-success mb-0" style="font-size: 1.3rem; font-weight: 700; margin-left: 25px;">
                                Rp {{ number_format($totalFinancials['total_credit'], 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mobile-summary-card" style="border-left: 5px solid #dc3545;">
                            <strong><i class="fas fa-arrow-up mr-1 text-danger"></i> Total Amount Debited</strong>
                            <p class="text-danger mb-0" style="font-size: 1.3rem; font-weight: 700; margin-left: 25px;">
                                Rp {{ number_format($totalFinancials['total_debit'], 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mobile-summary-card">
                            <strong><i class="fas fa-wallet mr-1"></i> Balance</strong>
                            <p class="text-muted mb-0">
                                Rp {{ number_format($totalFinancials['balance'], 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <!-- KAS Summary -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-wallet mr-1"></i>
                    Kas Summary
                </h3>
                <div class="card-tools">
                    <a href="{{ route('keuangan.kas.index') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-list"></i> View All
                    </a>
                    <a href="{{ route('keuangan.kas.create') }}" class="btn btn-sm btn-success">
                        <i class="fas fa-plus"></i> Add Transaction
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mobile-summary-card" style="border-left: 5px solid #28a745;">
                            <strong><i class="fas fa-arrow-down mr-1 text-success"></i> Total Credited</strong>
                            <p class="text-success mb-0" style="font-size: 1.3rem; font-weight: 700; margin-left: 25px;">
                                Rp {{ number_format($kasSummary['total_credit'], 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mobile-summary-card" style="border-left: 5px solid #dc3545;">
                            <strong><i class="fas fa-arrow-up mr-1 text-danger"></i> Total Debited</strong>
                            <p class="text-danger mb-0" style="font-size: 1.3rem; font-weight: 700; margin-left: 25px;">
                                Rp {{ number_format($kasSummary['total_debit'], 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="mobile-summary-card">
                            <strong><i class="fas fa-coins mr-1"></i> Current Balance</strong>
                            <p class="text-muted mb-0">
                                Rp {{ number_format($kasSummary['balance'], 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </div>
                @if($kasSummary['latest_transaction'])
                <div class="mt-3">
                    <h5>Latest Transaction:</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Voucher</th>
                                    <th>Description</th>
                                    <th>Credit</th>
                                    <th>Debit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $kasSummary['latest_transaction']->transaction_date->format('d M Y') }}</td>
                                    <td>{{ $kasSummary['latest_transaction']->voucher_number }}</td>
                                    <td>{{ $kasSummary['latest_transaction']->description ?: '-' }}</td>
                                    <td class="text-success">
                                        @if($kasSummary['latest_transaction']->credit > 0)
                                            Rp {{ number_format($kasSummary['latest_transaction']->credit, 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-danger">
                                        @if($kasSummary['latest_transaction']->debit > 0)
                                            Rp {{ number_format($kasSummary['latest_transaction']->debit, 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <!-- BANK Summary -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-university mr-1"></i>
                    Bank Summary
                </h3>
                <div class="card-tools">
                    <a href="{{ route('keuangan.bank.index') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-list"></i> View All
                    </a>
                    <a href="{{ route('keuangan.bank.create') }}" class="btn btn-sm btn-success">
                        <i class="fas fa-plus"></i> Add Transaction
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mobile-summary-card" style="border-left: 5px solid #28a745;">
                            <strong><i class="fas fa-arrow-down mr-1 text-success"></i> Total Credited</strong>
                            <p class="text-success mb-0" style="font-size: 1.3rem; font-weight: 700; margin-left: 25px;">
                                Rp {{ number_format($bankSummary['total_credit'], 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mobile-summary-card" style="border-left: 5px solid #dc3545;">
                            <strong><i class="fas fa-arrow-up mr-1 text-danger"></i> Total Debited</strong>
                            <p class="text-danger mb-0" style="font-size: 1.3rem; font-weight: 700; margin-left: 25px;">
                                Rp {{ number_format($bankSummary['total_debit'], 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="mobile-summary-card">
                            <strong><i class="fas fa-coins mr-1"></i> Current Balance</strong>
                            <p class="text-muted mb-0">
                                Rp {{ number_format($bankSummary['balance'], 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </div>
                @if($bankSummary['latest_transaction'])
                <div class="mt-3">
                    <h5>Latest Transaction:</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Voucher</th>
                                    <th>Description</th>
                                    <th>Credit</th>
                                    <th>Debit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $bankSummary['latest_transaction']->transaction_date->format('d M Y') }}</td>
                                    <td>{{ $bankSummary['latest_transaction']->voucher_number }}</td>
                                    <td>{{ $bankSummary['latest_transaction']->description ?: '-' }}</td>
                                    <td class="text-success">
                                        @if($bankSummary['latest_transaction']->credit > 0)
                                            Rp {{ number_format($bankSummary['latest_transaction']->credit, 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-danger">
                                        @if($bankSummary['latest_transaction']->debit > 0)
                                            Rp {{ number_format($bankSummary['latest_transaction']->debit, 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list-alt mr-1"></i>
                    Financial Accounts
                </h3>
                <div class="card-tools">
                    <a href="{{ route('keuangan.accounts.index') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-list"></i> View All
                    </a>
                    <a href="{{ route('keuangan.accounts.create') }}" class="btn btn-sm btn-success">
                        <i class="fas fa-plus"></i> Add Account
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-1"></i>
                    Click on "View All" to manage financial accounts including Kas and Bank accounts.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('css/customer-detail.css') }}">
@endsection

@section('js')
<script>
    $(document).ready(function() {
        // Aktivasi menu sidebar
        $('li.nav-item.has-treeview').each(function() {
            if ($(this).find('a[href="{{ route("keuangan.index") }}"]').length > 0) {
                $(this).addClass('menu-open');
                $(this).find('> a').addClass('active');
            }
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
    });
</script>
@endsection
