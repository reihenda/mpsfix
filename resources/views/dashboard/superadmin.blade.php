@extends('layouts.app')

@section('title', 'Dashboard SuperAdmin')

@section('page-title', 'Dashboard SuperAdmin')

@section('content')
    <div class="container-fluid">
        <div class="row">
            {{-- Notifications Section --}}
            <div class="col-12">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle mr-2"></i>
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
            </div>

            {{-- Dashboard Summary --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line mr-2"></i>
                            Ringkasan Data
                        </h3>
                    </div>
                    <div class="card-body dashboard-summary">
                        <div class="row">
                            {{-- Total Users Box --}}
                            <div class="col-md-4 col-sm-6">
                                <div class="info-box">
                                    <span class="info-box-icon bg-info">
                                        <i class="fas fa-users"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total User</span>
                                        <span class="info-box-number format-number">{{ $totalUsers }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Total Pencatatan Box --}}
                            <div class="col-md-4 col-sm-6">
                                <div class="info-box">
                                    <span class="info-box-icon bg-success">
                                        <i class="fas fa-clipboard-list"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Pencatatan</span>
                                        <span class="info-box-number format-number">{{ $totalPencatatan }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Total Pendapatan Box --}}
                            <div class="col-md-4 col-sm-6">
                                <div class="info-box">
                                    <span class="info-box-icon bg-warning">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Pendapatan</span>
                                        <span class="info-box-number">Rp {{ number_format($totalPendapatan, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Customer List --}}
            <div class="col-12">
                <div class="card customer-list-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-friends mr-2"></i>
                            Daftar Customer
                        </h3>
                        <div class="card-tools">
                            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#tambahUserModal">
                                <i class="fas fa-user-plus mr-1"></i> Tambah User
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="customersTable">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Email</th>
                                        <th>Total Pembelian</th>
                                        <th>Total Deposit</th>
                                        <th>Saldo</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($customers as $customer)
                                        <tr>
                                            <td>{{ $customer->name }}</td>
                                            <td>{{ $customer->email }}</td>
                                            <td>Rp {{ number_format($customer->total_purchases ?? 0, 2) }}</td>
                                            <td>Rp {{ number_format($customer->total_deposit ?? 0, 2) }}</td>
                                            <td>Rp
                                                {{ number_format(($customer->total_deposit ?? 0) - ($customer->total_purchases ?? 0), 2) }}
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('data-pencatatan.customer-detail', $customer->id) }}"
                                                        class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('data-pencatatan.create', $customer->id) }}"
                                                        class="btn btn-success btn-sm">
                                                        <i class="fas fa-plus"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
            
        {{-- FOB List --}}
        <div class="col-12 mt-4">
            <div class="card fob-list-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-friends mr-2"></i>
                        Daftar FOB
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="fobsTable">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Total Pembelian</th>
                                    <th>Total Deposit</th>
                                    <th>Saldo</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($fobs as $fob)
                                    <tr>
                                        <td>{{ $fob->name }}</td>
                                        <td>{{ $fob->email }}</td>
                                        <td>Rp {{ number_format($fob->total_purchases ?? 0, 2) }}</td>
                                        <td>Rp {{ number_format($fob->total_deposit ?? 0, 2) }}</td>
                                        <td>Rp
                                            {{ number_format(($fob->total_deposit ?? 0) - ($fob->total_purchases ?? 0), 2) }}
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('data-pencatatan.fob-detail', $fob->id) }}"
                                                    class="btn btn-info btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('data-pencatatan.fob.create-with-fob', $fob->id) }}"
                                                    class="btn btn-success btn-sm">
                                                    <i class="fas fa-plus"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah User --}}
    <div class="modal fade" id="tambahUserModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus mr-2"></i>
                        Tambah User Baru
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="tambahUserForm" action="{{ route('tambah.user') }}" method="POST" novalidate>
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="name">Nama <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <small class="text-muted">Minimal 3 karakter</small>
                        </div>
                        <div class="form-group">
                            <label for="role">Role <span class="text-danger">*</span></label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="customer">Customer</option>
                                <option value="fob">FOB</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Tutup
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
    $(function() {
        // Initialize DataTable for customersTable
        $("#customersTable").DataTable({
            "responsive": true,
            "lengthChange": false,
            "autoWidth": false,
        });
        
        // Initialize DataTable for fobsTable
        $("#fobsTable").DataTable({
            "responsive": true,
            "lengthChange": false,
            "autoWidth": false,
        });
    });
</script>
@endsection
