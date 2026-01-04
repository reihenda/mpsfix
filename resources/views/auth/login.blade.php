<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login - Sistem Pencatatan</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- jQuery -->
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>

    <!-- Bootstrap dan plugin lainnya -->
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">

    <!-- Responsive styles -->
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">

    <!-- Vite assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #224abe;
            --accent-color: #f8f9fc;
        }

        body {
            background: #ffffff;
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-box {
            width: 100%;
            max-width: 400px;
            margin: 0 auto 30px;
            box-shadow: 0 15px 25px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
            z-index: 10;
            position: relative;
        }

        .login-logo {
            margin-bottom: 20px;
            text-align: center;
        }

        .login-logo a {
            color: #4e73df;
            font-size: 32px;
            text-shadow: none;
            font-weight: 700;
        }

        .company-logo {
            text-align: center;
            margin-bottom: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }

        .company-logo img {
            max-height: 180px;
            width: auto;
            display: block;
            margin: 0 auto;
        }

        .card {
            border: none;
            border-radius: 10px;
            overflow: hidden;
        }

        .login-card-body {
            padding: 35px;
            border-radius: 10px;
        }

        .login-box-msg {
            font-size: 18px;
            font-weight: 500;
            color: #5a5c69;
            margin-bottom: 25px;
            text-align: center;
        }

        .input-group {
            margin-bottom: 25px !important;
        }

        .input-group-text {
            border-radius: 0 5px 5px 0;
            background-color: var(--primary-color);
            color: white;
            border: none;
        }

        .form-control {
            border-radius: 5px 0 0 5px;
            height: 45px;
            font-size: 16px;
            border: 1px solid #d1d3e2;
        }

        .form-control:focus {
            border-color: #bac8f3;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 15px;
            font-weight: 600;
            border-radius: 5px;
            box-shadow: 0 4px 10px rgba(78, 115, 223, 0.25);
            transition: all 0.2s;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(78, 115, 223, 0.3);
        }

        .alert-danger {
            border-radius: 5px;
            border-left: 4px solid #e74a3b;
            background-color: #fff;
            color: #e74a3b;
        }

        .icheck-primary label {
            font-size: 14px;
            color: #5a5c69;
        }

        .mb-1 a {
            color: var(--primary-color);
            font-weight: 500;
            transition: all 0.2s;
        }

        .mb-1 a:hover {
            color: var(--secondary-color);
            text-decoration: none;
        }

        /* Bottom wave effect */
        .wave-container {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 20vh;
            z-index: 0;
        }

        .wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="white" fill-opacity="0.4" d="M0,224L48,213.3C96,203,192,181,288,176C384,171,480,181,576,197.3C672,213,768,235,864,229.3C960,224,1056,192,1152,170.7C1248,149,1344,139,1392,133.3L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            background-repeat: no-repeat;
        }

        .wave-bottom {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 50px;
            background-color: rgba(255, 255, 255, 0.2);
        }

        /* Company branding footer */
        .company-footer {
            position: relative;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
            z-index: 10;
            margin-top: auto;
            padding-bottom: 15px;
            width: 100%;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 576px) {
            body {
                padding: 15px;
            }

            .login-box {
                margin-top: 5vh;
                margin-bottom: 20px;
            }

            .login-logo a {
                font-size: 28px;
            }

            .company-logo img {
                max-height: 120px;
            }

            .company-logo {
                margin-bottom: 10px;
            }

            .login-card-body {
                padding: 25px 20px;
            }

            .login-box-msg {
                font-size: 16px;
                margin-bottom: 20px;
            }

            .form-control {
                height: 42px;
                font-size: 15px;
            }

            .row {
                margin-left: -5px;
                margin-right: -5px;
            }

            .col-8,
            .col-4 {
                padding-left: 5px;
                padding-right: 5px;
            }

            .icheck-primary label {
                font-size: 13px;
            }

            .company-footer {
                font-size: 12px;
                padding-bottom: 20px;
                position: relative;
            }

            .wave-container {
                height: 15vh;
            }
        }

        /* Landscape orientation on small devices */
        @media (max-height: 500px) {
            body {
                justify-content: flex-start;
            }

            .login-box {
                margin-top: 20px;
                margin-bottom: 20px;
            }

            .wave-container {
                height: 10vh;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .login-card-body {
                background-color: #343a40;
                color: #f8f9fc;
            }

            .login-box-msg {
                color: #f8f9fc;
            }

            .form-control {
                background-color: #454d55;
                border-color: #6c757d;
                color: #f8f9fc;
            }

            .form-control:focus {
                background-color: #454d55;
                color: #f8f9fc;
            }

            .icheck-primary label {
                color: #d1d3e2;
            }

            .alert-danger {
                background-color: #343a40;
                color: #f8a9a2;
            }
        }

        /* Touch optimizations */
        @media (pointer: coarse) {

            .btn-primary,
            input[type="checkbox"]+label {
                min-height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .icheck-primary label {
                padding: 10px 0;
            }
        }
    </style>
</head>

<body>
    <!-- Wave effect removed -->

    <div class="company-logo">
        <img src="{{ asset('img/mps.png') }}" alt="PT Mosafa Prima Sinergi Logo">
    </div>

    <div class="login-box">
        <div class="login-logo">
            <a href="{{ url('/') }}">
                {{-- <i class="fas fa-chart-line mr-2"></i> --}}
                <b>PT Mosafa Prima Sinergi</b>
            </a>
        </div>

        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">Selamat Datang Kembali!</p>

                @if ($errors->any())
                    <div class="alert alert-danger mb-4">
                        @foreach ($errors->all() as $error)
                            <p class="mb-0"><i class="fas fa-exclamation-circle mr-2"></i>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('login') }}" method="post" id="loginForm">
                    @csrf
                    <div class="input-group">
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                            placeholder="Email" value="{{ old('email') }}" required autofocus>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                        @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="input-group">
                        <input type="password" name="password"
                            class="form-control @error('password') is-invalid @enderror" placeholder="Password"
                            required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="row mb-4">
                        <div class="col-7">
                            <div class="icheck-primary">
                                <input type="checkbox" id="remember" name="remember"
                                    {{ old('remember') ? 'checked' : '' }}>
                                <label for="remember">
                                    Ingat Saya
                                </label>
                            </div>
                        </div>
                        <div class="col-5">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-sign-in-alt mr-2"></i>Masuk
                            </button>
                        </div>
                    </div>
                </form>

                @if (Route::has('password.request'))
                    <p class="mb-1 text-center">
                        <a href="{{ route('password.request') }}">Lupa password?</a>
                    </p>
                @endif
            </div>
        </div>
    </div>

    <div class="company-footer">
        <strong>&copy; {{ date('Y') }} Sistem Pencatatan Perusahaan</strong>
    </div>

    <!-- AdminLTE JS -->
    <script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Fix for iOS devices to prevent zoom on input focus
            document.addEventListener('touchstart', function(e) {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                    if (e.target.type !== 'checkbox' && e.target.type !== 'radio') {
                        const viewportMeta = document.querySelector('meta[name="viewport"]');
                        if (viewportMeta) {
                            viewportMeta.setAttribute('content',
                                'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0'
                            );
                        }
                    }
                }
            }, false);

            document.addEventListener('touchend', function(e) {
                const viewportMeta = document.querySelector('meta[name="viewport"]');
                if (viewportMeta) {
                    viewportMeta.setAttribute('content',
                        'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
                }
            }, false);

            // Animation effect on page load
            $('.login-box').hide().fadeIn(1000);

            // Validation for login form
            const loginForm = $('#loginForm');
            const emailInput = $('input[name="email"]');
            const passwordInput = $('input[name="password"]');

            function validateForm() {
                let isValid = true;

                // Validate email
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailInput.val().trim() || !emailRegex.test(emailInput.val())) {
                    emailInput.addClass('is-invalid');
                    isValid = false;
                } else {
                    emailInput.removeClass('is-invalid');
                }

                // Validate password
                if (!passwordInput.val()) {
                    passwordInput.addClass('is-invalid');
                    isValid = false;
                } else {
                    passwordInput.removeClass('is-invalid');
                }

                return isValid;
            }

            // Real-time validation
            loginForm.find('input').on('input change', function() {
                $(this).removeClass('is-invalid');

                // Check if Enter key was pressed in any field
                $(this).keypress(function(e) {
                    if (e.which == 13) {
                        e.preventDefault();
                        loginForm.submit();
                    }
                });
            });

            // Form submission
            loginForm.on('submit', function(e) {
                if (!validateForm()) {
                    e.preventDefault();

                    // Vibration feedback for mobile if available
                    if (navigator.vibrate) {
                        navigator.vibrate(100);
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Validasi Gagal',
                        text: 'Mohon periksa kembali data yang dimasukkan',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                } else {
                    // Show loading indicator when form is valid
                    const btn = $(this).find('button[type="submit"]');
                    btn.html('<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...');
                    btn.prop('disabled', true);
                }
            });

            // Adjust layout for landscape mode on mobile
            function checkOrientation() {
                if (window.innerHeight < 500) {
                    $('body').addClass('landscape');
                } else {
                    $('body').removeClass('landscape');
                }
            }

            // Check on page load and orientation change
            checkOrientation();
            window.addEventListener('resize', checkOrientation);
            window.addEventListener('orientationchange', checkOrientation);
        });
    </script>
</body>

</html>
