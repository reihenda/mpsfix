@extends('layouts.app')

@section('title', 'Pilih Customer untuk Invoice Baru')

@section('page-title', 'Pilih Customer')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Pilih Customer untuk Membuat Invoice Baru</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th style="width: 10px">No</th>
                        <th>Nama</th>
                        <th>Tipe</th>
                        <th>Email</th>
                        <th style="width: 100px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $index => $customer)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $customer->name }}</td>
                            <td>
                                @if($customer->role == 'customer')
                                    <span class="badge badge-primary">Customer</span>
                                @elseif($customer->role == 'fob')
                                    <span class="badge badge-info">FOB</span>
                                @endif
                            </td>
                            <td>{{ $customer->email }}</td>
                            <td>
                                <a href="{{ route('invoices.create', $customer) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-file-invoice-dollar mr-1"></i>Buat Invoice
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">Tidak ada customer yang tersedia</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <a href="{{ route('invoices.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i>Kembali
        </a>
    </div>
</div>
@endsection
