<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Atur Ulang Password - Sewana</title>
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
                <h1>Buat Password<br>Baru.</h1>
                <p>Gunakan password baru untuk menjaga akses akun Sewana Anda tetap aman.</p>

                <div class="mt-5">
                    <div class="auth-security-card d-flex align-items-center p-3 rounded-4">
                        <i class="bi bi-shield-lock text-success fs-3 me-3"></i>
                        <div>
                            <div class="fw-bold text-white small">Keamanan Akun</div>
                            <div class="auth-security-caption text-secondary">Pastikan password baru hanya diketahui
                                oleh Anda.</div>
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
                        <i class="bi bi-shield-lock text-white fs-4"></i>
                    </div>
                    <h3 class="fw-bold text-white mb-1">Atur Ulang Password</h3>
                    <p class="text-secondary small">Masukkan password baru Anda</p>
                </div>

                <div class="d-none d-lg-block mb-4">
                    <h3 class="fw-bold text-white mb-1">Atur Ulang Password</h3>
                    <p class="text-secondary small">Lengkapi data berikut untuk memperbarui password</p>
                </div>

                <form method="POST" action="{{ route('password.store') }}">
                    @csrf

                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <div class="mb-3">
                        <label class="form-label" for="email">Alamat Email</label>
                        <div class="input-group-custom">
                            <input id="email" class="form-control" type="email" name="email"
                                placeholder="nama@email.com" value="{{ old('email', $request->email) }}" required
                                autofocus autocomplete="username">
                            <i class="bi bi-envelope"></i>
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-1 text-danger small" />
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-group-custom">
                            <input id="password" class="form-control" type="password" name="password"
                                placeholder="Masukkan password baru" required autocomplete="new-password">
                            <i class="bi bi-lock"></i>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-1 text-danger small" />
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="password_confirmation">Konfirmasi Password</label>
                        <div class="input-group-custom">
                            <input id="password_confirmation" class="form-control" type="password"
                                name="password_confirmation" placeholder="Ulangi password baru" required
                                autocomplete="new-password">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1 text-danger small" />
                    </div>

                    <button type="submit" class="btn-login">
                        Atur Ulang Password
                    </button>
                </form>
            </div>
        </div>

    </div>

</body>

</html>
