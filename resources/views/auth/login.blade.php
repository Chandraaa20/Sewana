<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Sewana</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('sewana-favicon.svg') }}">

    {{-- Fonts & Icons --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    @vite([
        'resources/css/app.css',
        'resources/css/auth.css',
        'resources/js/app.js',
    ])
</head>

<body class="auth-page auth-login">

    <div class="auth-wrapper">

        <div class="auth-left">
            <div class="brand-content">
                <a href="{{ url('/') }}" class="d-flex align-items-center mb-4 text-decoration-none">
                    <div class="auth-brand-dot bg-primary rounded-circle me-2"></div>
                    <span class="fw-bold fs-4 text-white">Sewana.</span>
                </a>
                <h1>Selamat Datang<br>Kembali.</h1>
                <p>Akses dashboard Anda untuk mengelola inventaris, melacak pesanan, atau mencari busana untuk disewa
                    hari ini.</p>

                <div class="mt-5">
                    <div class="auth-security-card d-flex align-items-center p-3 rounded-4">
                        <i class="bi bi-shield-check text-success fs-3 me-3"></i>
                        <div>
                            <div class="fw-bold text-white small">Sistem Aman Terenkripsi</div>
                            <div class="auth-security-caption text-secondary">Data Anda dilindungi dengan standar
                                keamanan tinggi.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="auth-right">
            <div class="card-login">

                <div class="text-center mb-4 d-lg-none">
                    <div
                        class="auth-mobile-icon d-inline-flex align-items-center justify-content-center bg-primary rounded-circle mb-3">
                        <i class="bi bi-box-seam text-white fs-4"></i>
                    </div>
                    <h3 class="fw-bold text-white mb-1">Masuk Akun</h3>
                    <p class="text-secondary small">Silakan login untuk melanjutkan</p>
                </div>

                <div class="d-none d-lg-block mb-4">
                    <h3 class="fw-bold text-white mb-1">Masuk Akun</h3>
                    <p class="text-secondary small">Silakan masukkan kredensial Anda</p>
                </div>

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    @if ($errors->any())
                        <div
                            class="alert alert-danger bg-danger bg-opacity-10 border border-danger border-opacity-25 text-danger small p-2 mb-3 rounded-3">
                            Email atau password yang Anda masukkan salah.
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Alamat Email</label>
                        <div class="input-group-custom">
                            <input type="email" name="email" class="form-control" placeholder="nama@email.com"
                                required value="{{ old('email') }}" autofocus>
                            <i class="bi bi-envelope"></i>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-group-custom">
                            <input type="password" name="password" class="form-control"
                                placeholder="Masukkan password Anda" required>
                            <i class="bi bi-lock"></i>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
                        <div class="form-check m-0">
                            <input class="form-check-input shadow-none" type="checkbox" name="remember"
                                id="rememberCheck">
                            <label class="form-check-label mt-1" for="rememberCheck">
                                Ingat Saya
                            </label>
                        </div>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="auth-link small fw-normal">Lupa
                                Password?</a>
                        @endif
                    </div>

                    <button type="submit" class="btn-login">
                        Masuk Dashboard
                    </button>

                    <div class="text-center mt-4 text-secondary small">
                        Belum punya akun?
                        <a href="{{ route('register') }}" class="auth-link">Daftar sekarang</a>
                    </div>

                </form>
            </div>
        </div>

    </div>

</body>

</html>
