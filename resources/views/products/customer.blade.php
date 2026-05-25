@extends('layouts.admin')

@section('title', 'Produk Tersedia - Sewana')
@section('meta_description', 'Cari produk Sewana yang tersedia dan mulai proses sewa.')

@section('content')
    <div class="admin-page admin-page--wide">
        <div class="admin-page-header">
            <div>
                <span class="admin-page-eyebrow">Katalog</span>
                <h1 class="admin-page-title">Produk Tersedia</h1>
                <p class="admin-page-subtitle">Cari produk yang tersedia dan mulai proses sewa dari katalog Sewana.</p>
            </div>
        </div>

        {{-- Search --}}
        <form method="GET" action="{{ route('penyewa.products.index') }}" class="admin-toolbar search-card filter-card search-form">
            <div class="input-group admin-search-box search-input-wrap">
                <span class="input-group-text bg-white border-0 ps-4">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" name="search" class="form-control search-input" placeholder="Cari produk..."
                    value="{{ request('search') }}" aria-label="Cari produk">
            </div>
            <div class="admin-toolbar-actions">
                @if (request('search'))
                    <a href="{{ route('penyewa.products.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                        Reset
                    </a>
                @endif
                <button class="btn btn-dark rounded-pill px-4 fw-bold search-button" type="submit">Cari</button>
            </div>
        </form>


        <div class="row admin-product-grid">
            @forelse($products as $product)
                @php
                    $productIndex = $loop->index;
                    $availableImages = $product->availableImages();
                    $rentableVariants = $product->variants
                        ->where('stock', '>', 0)
                        ->where('status', 'tersedia')
                        ->values();
                @endphp
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="card shadow-sm h-100 product-card d-flex flex-column border-0 rounded-4 overflow-hidden">

                        {{-- Carousel Gambar --}}
                        <div class="product-card__image position-relative img-hover-zoom">
                            @if ($availableImages->isNotEmpty())
                                <div id="carouselCustomer{{ $product->id }}" class="carousel slide h-100"
                                    data-bs-ride="carousel">
                                    <div class="carousel-inner h-100">
                                        @foreach ($availableImages as $key => $img)
                                            <div class="carousel-item {{ $key == 0 ? 'active' : '' }} h-100">
                                                <img src="{{ $img->publicUrl() }}"
                                                    class="d-block w-100 h-100 product-card__img"
                                                    alt="Foto produk {{ $product->name }}"
                                                    width="320" height="176"
                                                    @if ($productIndex === 0 && $key === 0) fetchpriority="high" @else loading="lazy" @endif
                                                    decoding="async">
                                            </div>
                                        @endforeach
                                    </div>
                                    @if ($availableImages->count() > 1)
                                        <button class="carousel-control-prev " type="button"
                                            data-bs-target="#carouselCustomer{{ $product->id }}" data-bs-slide="prev"
                                            aria-label="Gambar produk sebelumnya">
                                            <span class="carousel-control-prev-icon"></span>
                                        </button>
                                        <button class="carousel-control-next " type="button"
                                            data-bs-target="#carouselCustomer{{ $product->id }}" data-bs-slide="next"
                                            aria-label="Gambar produk berikutnya">
                                            <span class="carousel-control-next-icon"></span>
                                        </button>
                                    @endif
                                </div>
                            @else
                                <div class="no-image-box d-flex align-items-center justify-content-center h-100">
                                    N/A
                                </div>
                            @endif
                        </div>

                        {{-- Body --}}
                        <div class="card-body text-center flex-grow-1 product-card__meta">
                            <h6 class="product-card__title">{{ $product->name }}</h6>
                            <p class="product-card__description text-muted small mb-1 text-truncate-2">
                                {{ $product->description }}
                            </p>
                            <span class="status-badge {{ $product->availabilityBadgeClass() }}">
                                {{ $rentableVariants->isNotEmpty() ? 'Tersedia' : 'Stok belum tersedia' }}
                            </span>
                        </div>

                        {{-- Footer Tombol --}}
                        <div class="card-footer d-flex flex-column gap-2 product-card__footer">
                            <div class="d-flex gap-2 product-card__actions">
                                <button class="btn btn-outline-secondary btn-sm flex-fill" data-bs-toggle="modal"
                                    data-bs-target="#detailModal{{ $product->id }}">
                                    <i class="bi bi-eye"></i> Detail
                                </button>
                                <button class="btn btn-outline-dark btn-sm flex-fill" data-bs-toggle="modal"
                                    data-bs-target="#imageModal{{ $product->id }}">
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

                {{-- Modal Detail --}}
                <div class="modal fade" id="detailModal{{ $product->id }}" tabindex="-1">
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
                                            @foreach ($rentableVariants as $v)
                                                <tr>
                                                    <td>{{ $v->size }}</td>
                                                    <td>{{ $v->color }}</td>
                                                    <td>Rp{{ number_format($v->price, 0, ',', '.') }}</td>
                                                    <td>{{ $v->stock }}</td>
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

                {{-- Modal Lihat Gambar --}}
                <div class="modal fade" id="imageModal{{ $product->id }}" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content bg-dark">
                            <div class="modal-body p-0">
                                <div id="imageCarousel{{ $product->id }}" class="carousel slide" data-bs-ride="carousel">
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
                                            data-bs-target="#imageCarousel{{ $product->id }}" data-bs-slide="prev"
                                            aria-label="Gambar produk sebelumnya">
                                            <span class="carousel-control-prev-icon"></span>
                                        </button>
                                        <button class="carousel-control-next" type="button"
                                            data-bs-target="#imageCarousel{{ $product->id }}" data-bs-slide="next"
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
                        <h6 class="text-dark fw-bold mb-1">Belum ada produk tersedia</h6>
                        <p class="text-muted small mb-0">Katalog akan tampil saat produk aktif tersedia.</p>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="admin-pagination">
            {{ $products->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
