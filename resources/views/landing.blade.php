<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sewana - Sistem Penyewaan Pakaian</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('sewana-favicon.svg') }}">

    {{-- Fonts: Plus Jakarta Sans --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    @vite([
        'resources/css/app.css',
        'resources/css/landing.css',
        'resources/js/app.js',
    ])
</head>

<body>

    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand text-white fw-bold d-flex align-items-center" href="#">
                <div class="brand-dot-sm bg-primary rounded-circle me-2"></div>
                Sewana<span class="text-primary">.</span>
            </a>
            <div>
                <a href="{{ route('login') }}"
                    class="btn btn-link text-white text-decoration-none nav-btn me-1">Masuk</a>
                <a href="{{ route('register') }}" class="btn btn-primary-custom nav-btn nav-register-btn">Daftar</a>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container">
            <div class="badge-hero">
                <i class="bi bi-stars text-warning me-2"></i> Sistem Penyewaan Modern
            </div>
            <h1>Sewa Mudah,<br>Tampil Mewah.</h1>
            <p>Platform penyewaan busana premium dan perlengkapan acara. Temukan koleksi terbaik, cek ketersediaan
                secara langsung, dan sewa dalam hitungan detik.</p>
            <div class="hero-actions">
                <a href="{{ route('register') }}" class="btn btn-primary-custom">Mulai Menjelajah</a>
                <a href="#katalog" class="btn btn-outline-light nav-btn py-3 px-4">Lihat Koleksi</a>
            </div>
        </div>
    </section>

    <section class="py-5" id="fitur">
        <div class="container text-center pt-5">
            <h2 class="section-title">Mengapa Memilih Sewana?</h2>
            <p class="section-subtitle">Didesain untuk memberikan pengalaman sewa yang tak terlupakan.</p>

            <div class="row g-4 text-start">
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-box-seam"></i></div>
                        <h4 class="fw-bold mb-3 text-white">Katalog Premium</h4>
                        <p class="text-secondary mb-0">Ribuan produk eksklusif dengan kualitas terjamin. Dari kebaya
                            hingga jas formal, semua ada di sini.</p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="feature-card">
                        <div class="feature-icon feature-icon-success"><i
                                class="bi bi-lightning-charge"></i></div>
                        <h4 class="fw-bold mb-3 text-white">Pemesanan Instan</h4>
                        <p class="text-secondary mb-0">Cek ketersediaan ukuran dan warna secara langsung. Amankan
                            pesanan Anda tanpa harus menunggu balasan admin.</p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="feature-card">
                        <div class="feature-icon feature-icon-purple"><i
                                class="bi bi-shield-check"></i></div>
                        <h4 class="fw-bold mb-3 text-white">Transaksi Aman</h4>
                        <p class="text-secondary mb-0">Sistem pembayaran transparan dan terintegrasi. Uang jaminan Anda
                            aman bersama kami hingga barang dikembalikan.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 mt-5" id="katalog">
        <div class="container">
            <div class="catalog-heading d-flex justify-content-between align-items-end mb-5">
                <div>
                    <h2 class="section-title mb-0">Koleksi Terpopuler</h2>
                    <p class="text-secondary mt-2 mb-0">Baju incaran yang paling sering disewa minggu ini.</p>
                </div>
                <a href="{{ route('login') }}" class="btn btn-outline-light nav-btn d-none d-md-block">Lihat Semua <i
                        class="bi bi-arrow-right ms-2"></i></a>
            </div>

            <div class="row g-4">
                @forelse ($products as $product)
                    @php
                        $productImage = $product->firstAvailableImage();
                    @endphp
                    <div class="col-12 col-sm-6 col-lg-3">
                        <a href="{{ route('login') }}" class="text-decoration-none">
                            <div class="product-card">
                                <div class="product-img-wrapper">
                                    @if ($productImage)
                                        <img src="{{ $productImage->publicUrl() }}"
                                            alt="Foto produk {{ $product->name }}" width="320" height="320"
                                            loading="lazy" decoding="async">
                                    @else
                                        <div
                                            class="w-100 h-100 d-flex align-items-center justify-content-center bg-dark text-secondary">
                                            <i class="bi bi-image fs-1 opacity-50"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="product-info">
                                    <div class="product-sku mb-1">SKU: {{ $product->sku }}</div>
                                    <div class="product-title">{{ $product->name }}</div>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div class="text-white fw-bold">
                                            Rp{{ number_format($product->base_price ?? 50000, 0, ',', '.') }}<span
                                                class="text-secondary small fw-normal">/hari</span></div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                @empty
                    <div class="col-12 text-center py-5 border rounded-4 border-soft">
                        <p class="text-secondary mb-0">Belum ada produk yang ditampilkan.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="stats-band py-5 mt-5 border-top border-bottom">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-box border-end border-soft">
                        <div class="stat-number">{{ number_format($productCount) }}+</div>
                        <div class="stat-label">Koleksi Pakaian</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box border-end border-soft">
                        <div class="stat-number">{{ number_format($orderCount) }}</div>
                        <div class="stat-label">Transaksi Berhasil</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box">
                        <div class="stat-number">{{ number_format($userCount) }}</div>
                        <div class="stat-label">Pelanggan Aktif</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="py-5 mt-5">
        <div class="container text-center">
            <div class="d-flex align-items-center justify-content-center mb-4">
                <div class="brand-dot-md bg-primary rounded-circle me-2"></div>
                <h4 class="text-white fw-bold mb-0">Sewana<span class="text-primary">.</span></h4>
            </div>
            <p class="mb-4">Solusi modern untuk manajemen dan penyewaan busana Anda.</p>
            <div class="d-flex justify-content-center gap-3 mb-4">
                <a href="{{ route('login') }}" class="btn btn-link text-secondary text-decoration-none">Masuk</a>
                <a href="{{ route('register') }}" class="btn btn-link text-secondary text-decoration-none">Daftar</a>
            </div>
            <p class="small mb-0">&copy; {{ date('Y') }} Sewana. All rights reserved.</p>
        </div>
    </footer>
</body>

</html>

