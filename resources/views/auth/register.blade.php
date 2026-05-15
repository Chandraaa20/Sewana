<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Akun - Sewana</title>
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

<body class="auth-page auth-register">

    <div class="auth-wrapper">

        <div class="auth-left">
            <div class="brand-content">
                <a href="{{ url('/') }}" class="d-flex align-items-center mb-4 text-decoration-none">
                    <div class="auth-brand-dot bg-primary rounded-circle me-2"></div>
                    <span class="fw-bold fs-4 text-white">Sewana.</span>
                </a>
                <h1>Mulai Perjalanan<br>Bersama Kami.</h1>
                <p>Bergabunglah hari ini untuk pengalaman menyewa busana premium dengan cara yang lebih modern, cepat,
                    dan transparan.</p>

                <div class="mt-5 d-flex align-items-center gap-3">
                    <div class="d-flex flex-column">
                        <div class="d-flex">
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                        </div>
                        <span class="small text-secondary mt-1">Pengalaman sewa yang praktis dan transparan</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="auth-right">
            <div class="card-register">

                <div class="text-center mb-4 d-lg-none">
                    <div class="auth-mobile-icon d-inline-flex align-items-center justify-content-center bg-primary rounded-circle mb-3">
                        <i class="bi bi-box-seam text-white fs-4"></i>
                    </div>
                    <h3 class="fw-bold text-white mb-1">Daftar Akun</h3>
                    <p class="text-secondary small">Buat akun untuk mulai menyewa</p>
                </div>

                <div class="d-none d-lg-block mb-4">
                    <h3 class="fw-bold text-white mb-1">Buat Akun Baru</h3>
                    <p class="text-secondary small">Lengkapi data diri Anda di bawah ini</p>
                </div>

                <form method="POST" action="{{ route('register') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <div class="input-group-custom">
                            <input type="text" name="name" class="form-control" placeholder="Masukkan nama Anda"
                                required value="{{ old('name') }}">
                            <i class="bi bi-person"></i>
                        </div>
                        @error('name')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alamat Email</label>
                        <div class="input-group-custom">
                            <input type="email" name="email" class="form-control" placeholder="nama@email.com"
                                required value="{{ old('email') }}">
                            <i class="bi bi-envelope"></i>
                        </div>
                        @error('email')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-group-custom">
                            <input type="password" name="password" class="form-control" placeholder="Minimal 8 karakter"
                                required>
                            <i class="bi bi-lock"></i>
                        </div>
                        @error('password')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Konfirmasi Password</label>
                        <div class="input-group-custom">
                            <input type="password" name="password_confirmation" class="form-control"
                                placeholder="Ulangi password" required>
                            <i class="bi bi-shield-lock"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-register">
                        Buat Akun Sekarang
                    </button>

                    <div class="text-center mt-4 text-secondary small">
                        Sudah punya akun?
                        <a href="{{ route('login') }}" class="login-link">Masuk di sini</a>
                    </div>

                </form>
            </div>
        </div>

    </div>

</body>

</html>

