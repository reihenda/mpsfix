<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="base-url" content="{{ url('') }}">
    <title>@yield('title', 'Sistem Informasi Pencatatan')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- @include('adminlte::page') --}}
    {{-- @extends('adminlte::page') --}}
    <!-- jQuery HARUS dimuat pertama -->
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>

    <!-- Bootstrap dan plugin lainnya -->
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('vendor/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/datatables-responsive/css/responsive.bootstrap4.min.css') }}">

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    <!-- Responsive styles -->
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">
    <!-- Custom Sidebar CSS -->
    <link rel="stylesheet" href="{{ asset('css/custom-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customer-detail.css') }}?v={{ time() }}">
    <!-- Modal Fix CSS -->
    <link rel="stylesheet" href="{{ asset('css/modal-fix.css') }}?v={{ time() }}">
    <style>
        /* Efek hover dan active untuk sidebar menu */
        .nav-sidebar .nav-link {
            color: white !important;
            transition: all 0.3s ease;
        }

        .nav-sidebar .nav-link:hover,
        .nav-sidebar .nav-link.active {
            color: white !important;
            background-color: #28a745 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Pastikan efek hover dan active juga bekerja untuk submenu */
        .nav-treeview .nav-link:hover,
        .nav-treeview .nav-link.active {
            color: white !important;
            background-color: #28a745 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
    </style>
    <!-- Tambahkan plugin AdminLTE yang mungkin diperlukan -->
    <!-- Load Chart.js from CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="{{ asset('vendor/sparklines/sparkline.js') }}"></script>
    <script src="{{ asset('vendor/jquery-knob/jquery.knob.min.js') }}"></script>
    <script src="{{ asset('vendor/moment/moment.min.js') }}"></script>
    <script src="{{ asset('vendor/daterangepicker/daterangepicker.js') }}"></script>
    <script src="{{ asset('vendor/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>

    <!-- Moment.js dari CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/locale/id.min.js"></script>

    <!-- Vite assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Yield untuk custom JS scripts -->
    @yield('js')
</head>

<body class="hold-transition sidebar-mini">

    <div class="wrapper">
        {{-- Navbar --}}
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            {{-- Left navbar links --}}
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i
                            class="fas fa-bars"></i></a>
                </li>
            </ul>

            {{-- Right navbar links --}}
            <ul class="navbar-nav ml-auto">
                {{-- Notification Bell untuk Admin/SuperAdmin --}}
                @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
                    @php
                        // Ambil semua customer (termasuk FOB) yang memiliki bulan tanpa harga
                        $allMonthsWithoutPricing = [];
                        $customersWithIssues = \App\Models\User::whereIn('role', ['customer', 'fob'])->get();
                        foreach ($customersWithIssues as $cust) {
                            $monthsWithoutPricing = $cust->getMonthsWithoutPricing();
                            if (count($monthsWithoutPricing) > 0) {
                                $allMonthsWithoutPricing[] = [
                                    'customer' => $cust,
                                    'months' => $monthsWithoutPricing,
                                    'count' => count($monthsWithoutPricing),
                                    'is_fob' => $cust->isFOB()
                                ];
                            }
                        }
                        $totalNotifications = array_sum(array_column($allMonthsWithoutPricing, 'count'));
                    @endphp

                    <li class="nav-item dropdown" id="notificationDropdown">
                        <a class="nav-link" href="#" role="button" id="notificationBell" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-bell {{ $totalNotifications > 0 ? 'text-warning' : '' }}"></i>
                            @if ($totalNotifications > 0)
                                <span class="badge badge-danger navbar-badge">{{ $totalNotifications }}</span>
                            @endif
                        </a>
                        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" aria-labelledby="notificationBell" style="max-height: 400px; overflow-y: auto; min-width: 320px;">
                            <span class="dropdown-header">
                                <i class="fas fa-exclamation-triangle text-warning mr-1"></i>
                                Harga Belum Diinput ({{ $totalNotifications }} bulan)
                            </span>
                            <div class="dropdown-divider"></div>

                            @forelse($allMonthsWithoutPricing as $item)
                                <div class="px-3 py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong class="{{ $item['is_fob'] ? 'text-info' : 'text-primary' }}">
                                            <i class="fas {{ $item['is_fob'] ? 'fa-truck' : 'fa-user' }} mr-1"></i>{{ Str::limit($item['customer']->name, 20) }}
                                            @if ($item['is_fob'])
                                                <span class="badge badge-info" style="font-size: 0.6rem;">FOB</span>
                                            @endif
                                        </strong>
                                        <span class="badge badge-warning">{{ $item['count'] }} bulan</span>
                                    </div>
                                    <div class="mt-1">
                                        @foreach (array_slice($item['months'], 0, 3) as $monthData)
                                            @if ($item['is_fob'])
                                                <a href="{{ route('data-pencatatan.fob-detail', [
                                                    'customer' => $item['customer']->id,
                                                    'bulan' => $monthData['month'],
                                                    'tahun' => $monthData['year']
                                                ]) }}"
                                                   class="badge mr-1 mb-1"
                                                   style="font-size: 0.7rem; border: 1px solid #ffc107; color: #856404; background-color: #fff3cd;">
                                                    {{ $monthData['display'] }}
                                                </a>
                                            @else
                                                <a href="{{ route('data-pencatatan.customer-detail', [
                                                    'customer' => $item['customer']->id,
                                                    'bulan' => $monthData['month'],
                                                    'tahun' => $monthData['year']
                                                ]) }}"
                                                   class="badge mr-1 mb-1"
                                                   style="font-size: 0.7rem; border: 1px solid #ffc107; color: #856404; background-color: #fff3cd;">
                                                    {{ $monthData['display'] }}
                                                </a>
                                            @endif
                                        @endforeach
                                        @if (count($item['months']) > 3)
                                            <span class="badge badge-secondary" style="font-size: 0.7rem;">
                                                +{{ count($item['months']) - 3 }} lainnya
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="dropdown-divider"></div>
                            @empty
                                <div class="px-3 py-2 text-muted">
                                    <i class="fas fa-check-circle text-success mr-2"></i>
                                    Semua harga sudah diinput
                                </div>
                            @endforelse

                            @if ($totalNotifications > 0)
                                <a href="{{ route('admin.dashboard') }}" class="dropdown-item dropdown-footer bg-light text-center">
                                    <i class="fas fa-list mr-1"></i> Lihat Semua Customer
                                </a>
                            @endif
                        </div>
                    </li>
                @endif

                <li class="nav-item">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger">Logout</button>
                    </form>
                </li>
            </ul>
        </nav>

        {{-- Sidebar --}}
        <aside class="main-sidebar sidebar-gradient elevation-4">
            {{-- Brand Logo --}}
            <a href="{{ url('/') }}" class="brand-link text-center">
                <i class="fas fa-clipboard-check brand-image-icon"></i>
                <span class="brand-text font-weight-bold">Sistem Pencatatan</span>
            </a>

            {{-- Sidebar Menu --}}
            <div class="sidebar">
                {{-- Sidebar user panel --}}
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image">
                        <div class="user-initial">{{ substr(Auth::user()->name, 0, 1) }}</div>
                    </div>
                    <div class="info">
                        <a href="#" class="d-block text-bold">{{ Auth::user()->name }}</a>
                        <span class="user-role-badge">{{ ucfirst(Auth::user()->role) }}</span>
                    </div>
                </div>

                {{-- Sidebar Menu --}}
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column custom-sidebar-menu" data-widget="treeview"
                        role="menu">
                        @if (Auth::user()->isSuperAdmin())
                            <li class="nav-item">
                                <a href="{{ route('superadmin.dashboard') }}" class="nav-link">
                                    <i class="nav-icon fas fa-tachometer-alt"></i>
                                    <p>Dashboard</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link" data-toggle="modal" data-target="#tambahUserModal">
                                    <i class="nav-icon fas fa-user-plus"></i>
                                    <p>Tambah User</p>
                                </a>
                            </li>
                        @endif

                        @if (Auth::user()->isAdmin())
                            <li class="nav-item">
                                <a href="{{ route('admin.dashboard') }}" class="nav-link">
                                    <i class="nav-icon fas fa-tachometer-alt"></i>
                                    <p>Dashboard</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('user.index') }}" class="nav-link">
                                    <i class="nav-icon fas fa-users"></i>
                                    <p>Kelola User</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('nomor-polisi.index') }}" class="nav-link">
                                    <i class="nav-icon fas fa-car"></i>
                                    <p>Kelola Mobil/NOPOL</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('data-pencatatan.create') }}" class="nav-link">
                                    <i class="nav-icon fas fa-file-medical"></i>
                                    <p>Input Data Baru</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('data-pencatatan.index') }}" class="nav-link">
                                    <i class="nav-icon fas fa-clipboard-list"></i>
                                    <p>Pencatatan Data Customer</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('rekap-pengambilan.index') }}" class="nav-link">
                                    <i class="nav-icon fas fa-truck-loading"></i>
                                    <p>Rekap Pengambilan</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('rekap.penjualan.index') }}" class="nav-link">
                                    <i class="nav-icon fas fa-chart-line"></i>
                                    <p>Rekap Penjualan</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('rekap.pembelian.index') }}" class="nav-link">
                                    <i class="nav-icon fas fa-shopping-cart"></i>
                                    <p>Rekap Pembelian</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('operator-gtm.index') }}" class="nav-link">
                                    <i class="nav-icon fas fa-user-clock"></i>
                                    <p>Lembur Operator GTM</p>
                                </a>
                            </li>
                            <li class="nav-item has-treeview">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fas fa-money-bill-wave"></i>
                                    <p>
                                        Keuangan
                                        <i class="fas fa-angle-left right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('keuangan.index') }}" class="nav-link">
                                            <i class="fas fa-chart-line nav-icon"></i>
                                            <p>Dashboard Keuangan</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('keuangan.kas.index') }}" class="nav-link">
                                            <i class="fas fa-wallet nav-icon"></i>
                                            <p>Kas</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('keuangan.bank.index') }}" class="nav-link">
                                            <i class="fas fa-university nav-icon"></i>
                                            <p>Bank</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('keuangan.accounts.index') }}" class="nav-link">
                                            <i class="fas fa-list-alt nav-icon"></i>
                                            <p>Kelola Akun</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('keuangan.descriptions.index') }}" class="nav-link">
                                            <i class="fas fa-tags nav-icon"></i>
                                            <p>Kelola Deskripsi</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="nav-item has-treeview">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fas fa-file-invoice-dollar"></i>
                                    <p>
                                        Invoice & Billing
                                        <i class="fas fa-angle-left right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('invoices.index') }}" class="nav-link">
                                            <i class="far fa-file-alt nav-icon"></i>
                                            <p>Daftar Invoice</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('billings.index') }}" class="nav-link">
                                            <i class="far fa-money-bill-alt nav-icon"></i>
                                            <p>Daftar Billing</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('proforma-invoices.index') }}" class="nav-link">
                                            <i class="far fa-file-powerpoint nav-icon"></i>
                                            <p>Proforma Invoice</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('invoices.select-customer') }}" class="nav-link">
                                            <i class="fas fa-file-invoice nav-icon"></i>
                                            <p>Tambah Invoice</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('billings.select-customer') }}" class="nav-link">
                                            <i class="fas fa-plus-circle nav-icon"></i>
                                            <p>Tambah Billing</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ route('proforma-invoices.select-customer') }}" class="nav-link">
                                            <i class="fas fa-file-contract nav-icon"></i>
                                            <p>Tambah Proforma</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif

                        @if (Auth::user()->isKeuangan())
                            <li class="nav-item">
                                <a href="{{ route('keuangan.index') }}" class="nav-link">
                                    <i class="nav-icon fas fa-tachometer-alt"></i>
                                    <p>Dashboard</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('data-pencatatan.index') }}" class="nav-link">
                                    <i class="nav-icon fas fa-clipboard-list"></i>
                                    <p>Pencatatan Data Customer</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('operator-gtm.index') }}" class="nav-link">
                                    <i class="nav-icon fas fa-user-clock"></i>
                                    <p>Lembur Operator GTM</p>
                                </a>
                            </li>
                            <li class="nav-item has-treeview">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fas fa-money-bill-wave"></i>
                                    <p>
                                        Keuangan
                                        <i class="fas fa-angle-left right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('keuangan.index') }}" class="nav-link">
                                            <i class="fas fa-chart-line nav-icon"></i>
                                            <p>Dashboard Keuangan</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('keuangan.kas.index') }}" class="nav-link">
                                            <i class="fas fa-wallet nav-icon"></i>
                                            <p>Kas</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('keuangan.bank.index') }}" class="nav-link">
                                            <i class="fas fa-university nav-icon"></i>
                                            <p>Bank</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('keuangan.accounts.index') }}" class="nav-link">
                                            <i class="fas fa-list-alt nav-icon"></i>
                                            <p>Kelola Akun</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('keuangan.descriptions.index') }}" class="nav-link">
                                            <i class="fas fa-tags nav-icon"></i>
                                            <p>Kelola Deskripsi</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="nav-item has-treeview">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fas fa-file-invoice-dollar"></i>
                                    <p>
                                        Invoice & Billing
                                        <i class="fas fa-angle-left right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('invoices.index') }}" class="nav-link">
                                            <i class="far fa-file-alt nav-icon"></i>
                                            <p>Daftar Invoice</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('billings.index') }}" class="nav-link">
                                            <i class="far fa-money-bill-alt nav-icon"></i>
                                            <p>Daftar Billing</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('proforma-invoices.index') }}" class="nav-link">
                                            <i class="far fa-file-powerpoint nav-icon"></i>
                                            <p>Proforma Invoice</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('invoices.select-customer') }}" class="nav-link">
                                            <i class="fas fa-file-invoice nav-icon"></i>
                                            <p>Tambah Invoice</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('billings.select-customer') }}" class="nav-link">
                                            <i class="fas fa-plus-circle nav-icon"></i>
                                            <p>Tambah Billing</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('proforma-invoices.select-customer') }}" class="nav-link">
                                            <i class="fas fa-file-contract nav-icon"></i>
                                            <p>Tambah Proforma</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif

                        @if (Auth::user()->isStaff())
                            <li class="nav-item">
                                <a href="{{ route('data-pencatatan.index') }}" class="nav-link">
                                    <i class="nav-icon fas fa-clipboard-list"></i>
                                    <p>Pencatatan Data Customer</p>
                                </a>
                            </li>
                        @endif

                        @if (Auth::user()->isCustomer() || Auth::user()->isFOB())
                            <li class="nav-item">
                                <a href="{{ Auth::user()->isCustomer() ? route('customer.dashboard') : route('fob.dashboard') }}" class="nav-link">
                                    <i class="nav-icon fas fa-tachometer-alt"></i>
                                    <p>Dashboard</p>
                                </a>
                            </li>
                            <li class="nav-item has-treeview">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fas fa-file-invoice-dollar"></i>
                                    <p>
                                        Invoice & Billing
                                        <i class="fas fa-angle-left right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('customer.invoices') }}" class="nav-link">
                                            <i class="far fa-file-alt nav-icon"></i>
                                            <p>Invoice Saya</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('customer.billings') }}" class="nav-link">
                                            <i class="far fa-money-bill-alt nav-icon"></i>
                                            <p>Billing Saya</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif

                        @if (Auth::user()->isDemo())
                            <li class="nav-item">
                                <a href="{{ route('demo.admin') }}" class="nav-link">
                                    <i class="nav-icon fas fa-user-cog"></i>
                                    <p>Demo Admin</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('demo.customer') }}" class="nav-link">
                                    <i class="nav-icon fas fa-user"></i>
                                    <p>Demo Customer</p>
                                </a>
                            </li>
                        @endif
                    </ul>
                </nav>
            </div>
        </aside>

        {{-- Content Wrapper --}}
        <div class="content-wrapper">
            {{-- Content Header --}}
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">@yield('page-title')</h1>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Main content --}}
            <div class="content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <footer class="main-footer">
            <strong>© {{ date('Y') }} Sistem Pencatatan Perusahaan</strong>
        </footer>
    </div>

    {{-- Modal Tambah User (hanya untuk SuperAdmin) --}}
    @if (Auth::user()->isSuperAdmin())
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
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="email">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                    id="email" name="email" value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="password">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                    id="password" name="password" required>
                                <small class="text-muted">Minimal 6 karakter</small>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group mb-0">
                                <label for="role">Role <span class="text-danger">*</span></label>
                                <select class="form-control @error('role') is-invalid @enderror" id="role"
                                    name="role" required>
                                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin
                                    </option>
                                    <option value="keuangan" {{ old('role') == 'keuangan' ? 'selected' : '' }}>Keuangan
                                    </option>
                                    <option value="customer" {{ old('role') == 'customer' ? 'selected' : '' }}>
                                        Customer</option>
                                    <option value="demo" {{ old('role') == 'demo' ? 'selected' : '' }}>
                                        Demo</option>
                                </select>
                                @error('role')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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
    @endif
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}"></script>
    <script src="{{ asset('js/sidebar-effects.js') }}"></script>
    <script src="{{ asset('js/content.js') }}"></script>

    <!-- DataTables JS -->
    <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>

    <!-- Stack untuk scripts custom dari child views -->
    @stack('scripts')



    <!-- Scripts tambahan untuk perhitungan -->
    <script>
        // Tambahkan script inisialisasi AdminLTE yang mungkin hilang
        $(document).ready(function() {
            // Script untuk mendeteksi dan memberi class active pada menu sidebar
            function setActiveMenu() {
                // Mendapatkan URL saat ini
                const currentUrl = window.location.href;

                // Mencari semua link di sidebar
                $('.nav-sidebar .nav-link').each(function() {
                    const linkUrl = $(this).attr('href');

                    // Jika link URL cocok dengan current URL atau current URL mengandung link URL
                    // dan link URL bukan '#' (untuk dropdown)
                    if (linkUrl && linkUrl !== '#' && (currentUrl === linkUrl || currentUrl.indexOf(
                            linkUrl) > -1)) {
                        $(this).addClass('active');

                        // Jika berada di dalam submenu, buka parent menu-nya
                        if ($(this).parents('.nav-treeview').length) {
                            $(this).parents('.has-treeview').addClass('menu-open');
                            $(this).parents('.has-treeview').find('> .nav-link').addClass('active');
                        }
                    }
                });
            }

            // Jalankan fungsi setActiveMenu
            setActiveMenu();

            const userForm = $('#tambahUserForm');
            const submitButton = userForm.find('button[type="submit"]');

            // Input fields
            const nameInput = userForm.find('input[name="name"]');
            const emailInput = userForm.find('input[name="email"]');
            const passwordInput = userForm.find('input[name="password"]');

            // Validation feedback elements
            const nameError = $('<div class="invalid-feedback">Nama harus diisi</div>');
            const emailError = $('<div class="invalid-feedback">Email tidak valid</div>');
            const passwordError = $('<div class="invalid-feedback">Password minimal 3 karakter</div>');

            // Add feedback elements after inputs
            nameInput.after(nameError);
            emailInput.after(emailError);
            passwordInput.after(passwordError);

            // Real-time validation function
            function validateForm() {
                let isValid = true;

                // Validate name (required)
                if (!nameInput.val().trim()) {
                    nameInput.addClass('is-invalid').removeClass('is-valid');
                    nameError.show();
                    isValid = false;
                } else {
                    nameInput.removeClass('is-invalid').addClass('is-valid');
                    nameError.hide();
                }

                // Validate email (required, valid format)
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailInput.val().trim() || !emailRegex.test(emailInput.val())) {
                    emailInput.addClass('is-invalid').removeClass('is-valid');
                    emailError.show();
                    isValid = false;
                } else {
                    emailInput.removeClass('is-invalid').addClass('is-valid');
                    emailError.hide();
                }

                // Validate password (required, min 3 chars)
                if (!passwordInput.val() || passwordInput.val().length < 3) {
                    passwordInput.addClass('is-invalid').removeClass('is-valid');
                    passwordError.show();
                    isValid = false;
                } else {
                    passwordInput.removeClass('is-invalid').addClass('is-valid');
                    passwordError.hide();
                }

                // Enable/disable submit button based on validation
                submitButton.prop('disabled', !isValid);

                return isValid;
            }

            // Initial validation
            validateForm();

            // Validate on input
            userForm.find('input, select').on('input change', validateForm);

            // Form submission handler
            userForm.on('submit', function(e) {
                if (!validateForm()) {
                    e.preventDefault();

                    // Show toast notification for invalid form
                    Swal.fire({
                        icon: 'error',
                        title: 'Validasi Gagal',
                        text: 'Mohon periksa kembali data yang dimasukkan',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            });
            // Inisialisasi komponen AdminLTE
            // Only initialize DataTables that don't already have specific initialization
            if (typeof $.fn.dataTable !== 'undefined') {
                $('.dataTable:not(.custom-datatable)').dataTable();
            }

            // Inisialisasi lainnya yang mungkin diperlukan
            if (typeof $.fn.daterangepicker !== 'undefined') {
                $('.daterangepicker-input').daterangepicker();
            }

            // Inisialisasi AdminLTE
            if (typeof $.fn.Layout !== 'undefined') {
                $('body').Layout();
            }

            // Inisialisasi Notification Bell Dropdown
            $('#notificationBell').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $dropdown = $(this).parent('.dropdown');
                var $menu = $dropdown.find('.dropdown-menu');

                // Toggle dropdown
                if ($menu.hasClass('show')) {
                    $menu.removeClass('show');
                    $dropdown.removeClass('show');
                } else {
                    // Tutup dropdown lain yang mungkin terbuka
                    $('.dropdown-menu.show').removeClass('show');
                    $('.dropdown.show').removeClass('show');

                    $menu.addClass('show');
                    $dropdown.addClass('show');
                }
            });

            // Tutup dropdown saat klik di luar
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#notificationDropdown').length) {
                    $('#notificationDropdown .dropdown-menu').removeClass('show');
                    $('#notificationDropdown').removeClass('show');
                }
            });

            // Jangan tutup dropdown saat klik di dalam menu (kecuali link)
            $('#notificationDropdown .dropdown-menu').on('click', function(e) {
                if (!$(e.target).is('a')) {
                    e.stopPropagation();
                }
            });

            // Custom script untuk fungsi perhitungan Anda
            @yield('custom-scripts')
        });
    </script>
    <!-- Responsive JavaScript -->
    <script src="{{ asset('js/responsive.js') }}"></script>
</body>

</html>
