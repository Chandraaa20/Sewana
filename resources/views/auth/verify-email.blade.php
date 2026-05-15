<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Email - Sewana</title>
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
                <h1>Verifikasi<br>Email.</h1>
                <p>Verifikasi alamat email Anda agar akun Sewana dapat digunakan dengan lengkap.</p>

                <div class="mt-5">
                    <div class="auth-security-card d-flex align-items-center p-3 rounded-4">
                        <i class="bi bi-envelope-check text-success fs-3 me-3"></i>
                        <div>
                            <div class="fw-bold text-white small">Cek Email Anda</div>
                            <div class="auth-security-caption text-secondary">Tautan verifikasi dapat dikirim ulang
                                jika diperlukan.</div>
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
                        <i class="bi bi-envelope-check text-white fs-4"></i>
                    </div>
                    <h3 class="fw-bold text-white mb-1">Verifikasi Email</h3>
                    <p class="text-secondary small">Periksa email untuk menyelesaikan verifikasi</p>
                </div>

                <div class="d-none d-lg-block mb-4">
                    <h3 class="fw-bold text-white mb-1">Verifikasi Email</h3>
                    <p class="text-secondary small">Periksa email untuk menyelesaikan verifikasi akun</p>
                </div>

                <p class="text-secondary small mb-4">
                    Terima kasih telah mendaftar. Verifikasi alamat email Anda melalui tautan yang baru kami kirimkan.
                    Jika email belum diterima, kami dapat mengirimkannya kembali.
                </p>

                @if (session('status') == 'verification-link-sent')
                    <div
                        class="alert alert-success bg-success bg-opacity-10 border border-success border-opacity-25 text-success small p-2 mb-3 rounded-3">
                        Tautan verifikasi baru telah dikirim ke alamat email yang Anda gunakan saat pendaftaran.
                    </div>
                @endif

                <div class="mt-4">
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf

                        <button type="submit" class="btn-login">
                            Kirim Ulang Email Verifikasi
                        </button>
                    </form>

                    <form method="POST" action="{{ route('logout') }}" class="text-center mt-4">
                        @csrf

                        <button type="submit" class="btn btn-link auth-link small p-0">
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

</body>

</html>
