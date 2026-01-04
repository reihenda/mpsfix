@extends('layouts.app')

@section('title', 'Pilih Customer - Proforma Invoice')

@section('page-title', 'Pilih Customer untuk Proforma Invoice')

@section('content')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="card-title">Pilih Customer</h3>
            <a href="{{ route('proforma-invoices.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i>Kembali
            </a>
        </div>
    </div>
    <div class="card-body">
        @if($customers->count() > 0)
            <div class="row">
                @foreach($customers as $customer)
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card customer-card h-100">
                            <div class="card-body text-center">
                                <div class="customer-avatar mx-auto mb-3">
                                    {{ substr($customer->name, 0, 1) }}
                                </div>
                                <h5 class="card-title">{{ $customer->name }}</h5>
                                <p class="card-text text-muted">
                                    <i class="fas fa-envelope mr-1"></i>{{ $customer->email }}<br>
                                    <i class="fas fa-tag mr-1"></i>{{ ucfirst($customer->role) }}
                                </p>
                                <a href="{{ route('proforma-invoices.create', $customer) }}" 
                                   class="btn btn-primary btn-block">
                                    <i class="fas fa-file-contract mr-1"></i>Buat Proforma Invoice
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-users fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">Belum Ada Customer</h4>
                <p class="text-muted">Silahkan tambah customer terlebih dahulu untuk membuat proforma invoice.</p>
                @if(Auth::user()->isSuperAdmin())
                    <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#tambahUserModal">
                        <i class="fas fa-user-plus mr-1"></i>Tambah Customer
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>

<style>
.customer-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid #e3e6f0;
}

.customer-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.customer-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(45deg, #007bff, #0056b3);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 24px;
}
</style>
@endsection
