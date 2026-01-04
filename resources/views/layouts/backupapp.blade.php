<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistem Informasi Pencatatan')</title>

    @include('adminlte::page')
    {{-- @extends('adminlte::page') --}}


    <!-- Vite assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
                <li class="nav-item">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger">Logout</button>
                    </form>
                </li>
            </ul>
        </nav>

        {{-- Sidebar --}}
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            {{-- Brand Logo --}}
            <a href="{{ url('/') }}" class="brand-link">
                <span class="brand-text font-weight-light">Sistem Pencatatan</span>
            </a>

            {{-- Sidebar Menu --}}
            <div class="sidebar">
                {{-- Sidebar user panel --}}
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="info">
                        <a href="#" class="d-block">{{ Auth::user()->name }}</a>
                        <small class="text-muted">{{ Auth::user()->role }}</small>
                    </div>
                </div>

                {{-- Sidebar Menu --}}
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
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
                                <a href="{{ route('data-pencatatan.create') }}" class="nav-link">
                                    <i class="nav-icon fas fa-plus"></i>
                                    <p>Input Data Baru</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('data-pencatatan.index') }}" class="nav-link">
                                    <i class="nav-icon fas fa-list"></i>
                                    <p>Daftar Data</p>
                                </a>
                            </li>
                        @endif

                        @if (Auth::user()->isCustomer())
                            <li class="nav-item">
                                <a href="{{ route('customer.dashboard') }}" class="nav-link">
                                    <i class="nav-icon fas fa-tachometer-alt"></i>
                                    <p>Dashboard</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('customer.data') }}" class="nav-link">
                                    <i class="nav-icon fas fa-file-alt"></i>
                                    <p>Data Saya</p>
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
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah User Baru</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('tambah.user') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Nama</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Role</label>
                                <select name="role" class="form-control" required>
                                    <option value="admin">Admin</option>
                                    <option value="customer">Customer</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-primary">Tambah User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

</body>

</html>
