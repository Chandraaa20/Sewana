@extends('layouts.admin')

@section('title', 'Dashboard Pelanggan - Sewana')
@section('meta_description', 'Dashboard pelanggan Sewana untuk memantau aktivitas sewa dan produk populer.')

@section('content')
    @php
        $summaryCards = [
            [
                'label' => 'Total Pesanan',
                'value' => $customerData['total_orders'] ?? 0,
                'icon' => 'receipt',
                'description' => 'Semua pesanan yang pernah dibuat',
            ],
            [
                'label' => 'Pesanan Aktif',
                'value' => $customerData['active_orders'] ?? 0,
                'icon' => 'bag-check',
                'description' => 'Masih menunggu atau sedang disewa',
            ],
            [
                'label' => 'Pesanan Selesai',
                'value' => $customerData['closed_orders'] ?? 0,
                'icon' => 'check2-circle',
                'description' => 'Sudah dikembalikan atau dibatalkan',
            ],
        ];
    @endphp

    <div class="dashboard-container staff-dashboard customer-dashboard">
        <section class="customer-hero">
            <div>
                <span class="staff-eyebrow">Area Penyewa</span>
                <h1 class="staff-title">Dashboard Pelanggan</h1>
                <p class="staff-subtitle">Pantau aktivitas sewa Anda dan temukan produk yang paling sering diminati.</p>
            </div>

            <a href="{{ route('penyewa.products.index') }}" class="staff-link-button">
                Lihat Produk
                <i class="bi bi-arrow-right"></i>
            </a>
        </section>

        <section class="row g-4 mb-4">
            @foreach ($summaryCards as $card)
                <div class="col-12 col-md-4">
                    <div class="customer-summary-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div class="customer-icon">
                                <i class="bi bi-{{ $card['icon'] }}"></i>
                            </div>
                            <span class="staff-card-label">{{ $card['label'] }}</span>
                        </div>
                        <div class="customer-card-value">{{ number_format($card['value']) }}</div>
                        <p class="staff-card-description">{{ $card['description'] }}</p>
                    </div>
                </div>
            @endforeach
        </section>

        <section class="staff-panel">
            <div class="staff-section-header">
                <div>
                    <span class="staff-eyebrow">Rekomendasi</span>
                    <h2>Produk Populer</h2>
                </div>
                <a href="{{ route('penyewa.orders.index') }}" class="staff-link-button">
                    Pesanan Saya
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="row g-4">
                @forelse($customerData['popular_products'] as $product)
                    @php
                        $productIndex = $loop->index;
                        $availableImages = $product->availableImages();
                        $rentableVariants = $product->variants
                            ->where('stock', '>', 0)
                            ->where('status', 'tersedia')
                            ->values();
                    @endphp
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <div class="card shadow-sm h-100 product-card d-flex flex-column border-0 rounded-4 overflow-hidden">
                            <div class="position-relative img-hover-zoom">
                                @if ($availableImages->isNotEmpty())
                                    <div id="carouselPopular{{ $product->id }}" class="carousel slide h-100"
                                        data-bs-ride="carousel">
                                        <div class="carousel-inner h-100">
                                            @foreach ($availableImages as $key => $img)
                                                <div class="carousel-item {{ $key == 0 ? 'active' : '' }} h-100">
                                                    <img src="{{ $img->publicUrl() }}"
                                                        class="d-block w-100 h-100 product-img"
                                                        alt="Foto produk {{ $product->name }}"
                                                        width="320" height="176"
                                                        @if ($productIndex === 0 && $key === 0) fetchpriority="high" @else loading="lazy" @endif
                                                        decoding="async">
                                                </div>
                                            @endforeach
                                        </div>
                                        @if ($availableImages->count() > 1)
                                            <button class="carousel-control-prev" type="button"
                                                data-bs-target="#carouselPopular{{ $product->id }}" data-bs-slide="prev"
                                                aria-label="Gambar produk sebelumnya">
                                                <span class="carousel-control-prev-icon"></span>
                                            </button>
                                            <button class="carousel-control-next" type="button"
                                                data-bs-target="#carouselPopular{{ $product->id }}" data-bs-slide="next"
                                                aria-label="Gambar produk berikutnya">
                                                <span class="carousel-control-next-icon"></span>
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    <div class="no-image-box d-flex align-items-center justify-content-center h-100">
                                        <i class="bi bi-image fs-1 opacity-25"></i>
                                    </div>
                                @endif
                            </div>

                            <div class="card-body text-center flex-grow-1 product-card__meta">
                                <h6 class="product-card__title">{{ $product->name }}</h6>
                                <p class="text-muted small mb-2 text-truncate-2">{{ $product->description }}</p>
                                <span class="status-badge {{ $product->availabilityBadgeClass() }}">
                                    {{ $rentableVariants->isNotEmpty() ? 'Tersedia' : 'Stok belum tersedia' }}
                                </span>
                            </div>

                            <div class="card-footer d-flex flex-column gap-2 product-card__footer">
                                <div class="d-flex gap-2 product-card__actions">
                                    <button class="btn btn-outline-secondary btn-sm flex-fill" data-bs-toggle="modal"
                                        data-bs-target="#detailModalPopular{{ $product->id }}">
                                        <i class="bi bi-eye"></i> Detail
                                    </button>
                                    <button class="btn btn-outline-dark btn-sm flex-fill" data-bs-toggle="modal"
                                        data-bs-target="#imageModalPopular{{ $product->id }}">
                                        <i class="bi bi-image"></i> Lihat
                                    </button>
                                </div>
                                <a href="{{ route('penyewa.orders.create', ['product_id' => $product->id]) }}"
                                    class="btn btn-dark btn-sm w-100">
                                    <i class="bi bi-cart-plus"></i> Sewa
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="detailModalPopular{{ $product->id }}" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content product-modal">
                                <div class="modal-header bg-dark text-white">
                                    <h5 class="modal-title">Detail Produk - {{ $product->name }}</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                        aria-label="Tutup detail produk"></button>
                                </div>
                                <div class="modal-body">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th>Nama</th>
                                            <td>{{ $product->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>SKU</th>
                                            <td>{{ $product->sku }}</td>
                                        </tr>
                                        <tr>
                                            <th>Deskripsi</th>
                                            <td>{!! nl2br(e($product->description)) !!}</td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td>{{ $rentableVariants->isNotEmpty() ? 'Tersedia' : 'Stok belum tersedia' }}</td>
                                        </tr>
                                    </table>

                                    <h6 class="fw-bold mt-3">Varian:</h6>
                                    @if ($rentableVariants->isNotEmpty())
                                        <table class="table table-sm table-striped text-center">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Ukuran</th>
                                                    <th>Warna</th>
                                                    <th>Harga</th>
                                                    <th>Stok</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($rentableVariants as $variant)
                                                    <tr>
                                                        <td>{{ $variant->size }}</td>
                                                        <td>{{ $variant->color }}</td>
                                                        <td>Rp{{ number_format($variant->price, 0, ',', '.') }}</td>
                                                        <td>{{ $variant->stock }}</td>
                                                        <td>Tersedia</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <p class="text-muted fst-italic">Stok belum tersedia</p>
                                    @endif
                                </div>
                                <div class="modal-footer">
                                    <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="imageModalPopular{{ $product->id }}" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content bg-dark">
                                <div class="modal-body p-0">
                                    <div id="imageCarouselPopular{{ $product->id }}" class="carousel slide"
                                        data-bs-ride="carousel">
                                        <div class="carousel-inner">
                                            @foreach ($availableImages as $key => $img)
                                                <div class="carousel-item {{ $key == 0 ? 'active' : '' }}">
                                                    <img src="{{ $img->publicUrl() }}"
                                                        class="d-block w-100 product-modal__img"
                                                        alt="Foto produk {{ $product->name }}"
                                                        width="960" height="640" loading="lazy" decoding="async">
                                                </div>
                                            @endforeach
                                        </div>
                                        @if ($availableImages->count() > 1)
                                            <button class="carousel-control-prev" type="button"
                                                data-bs-target="#imageCarouselPopular{{ $product->id }}" data-bs-slide="prev"
                                                aria-label="Gambar produk sebelumnya">
                                                <span class="carousel-control-prev-icon"></span>
                                            </button>
                                            <button class="carousel-control-next" type="button"
                                                data-bs-target="#imageCarouselPopular{{ $product->id }}" data-bs-slide="next"
                                                aria-label="Gambar produk berikutnya">
                                                <span class="carousel-control-next-icon"></span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                                <div class="modal-footer border-0">
                                    <button class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="admin-empty-state">
                            <i class="bi bi-box-seam text-muted opacity-25 d-block mb-3"></i>
                            <h6 class="text-dark fw-bold mb-1">Belum ada produk populer</h6>
                            <p class="text-muted small mb-0">Produk yang sering disewa akan tampil di area ini.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
