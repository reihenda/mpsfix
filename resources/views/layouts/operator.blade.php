<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Absen Trip')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    <style>
        body {
            background-color: #f4f6f9;
        }
        .operator-navbar {
            background-color: #28a745;
        }
        .operator-navbar .navbar-brand,
        .operator-navbar .btn-link {
            color: #fff !important;
        }
        .operator-content {
            max-width: 480px;
            margin: 0 auto;
            padding: 1rem;
        }
    </style>
    @yield('css')
</head>

<body>
    <nav class="navbar navbar-expand operator-navbar shadow-sm">
        <span class="navbar-brand mb-0 h1">
            <i class="fas fa-truck mr-1"></i> @yield('page-title', 'Absen Trip')
        </span>
        <div class="ml-auto">
            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-link p-0">
                    <i class="fas fa-sign-out-alt mr-1"></i> Keluar
                </button>
            </form>
        </div>
    </nav>

    <div class="operator-content">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @yield('content')
    </div>

    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    @yield('js')
</body>

</html>
