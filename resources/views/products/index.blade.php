@extends('layouts.admin')

@section('title', 'Manajemen Produk - Sewana')
@section('meta_description', 'Kelola dan pantau stok inventaris produk Sewana.')

@section('content')
    <div class="admin-page admin-page--wide">

        {{-- HEADER SECTION --}}
        <div class="admin-page-header">
            <div>
                <span class="admin-page-eyebrow">Inventaris</span>
                <h1 class="admin-page-title">Manajemen Produk</h1>
                <p class="admin-page-subtitle">Kelola dan pantau stok inventaris Sewana Anda.</p>
            </div>

            @role('pegawai')
                <a href="{{ route('pegawai.products.create') }}" class="btn btn-dark px-4 rounded-pill shadow-sm">
                    <i class="bi bi-plus-lg me-2"></i> Tambah Produk
                </a>
            @endrole
        </div>

        {{-- SEARCH SECTION --}}
        <form method="GET"
            action="{{ auth()->user()->hasRole('pegawai') ? route('pegawai.products.index') : route('pemilik.products.index') }}"
            class="admin-toolbar">
            <div class="input-group admin-search-box">
                <span class="input-group-text bg-white border-0 ps-4">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" name="search" class="form-control border-0 shadow-none py-2"
                    placeholder="Cari nama atau deskripsi produk..." value="{{ request('search') }}"
                    aria-label="Cari nama atau deskripsi produk">
            </div>
            <div class="admin-toolbar-actions">
                @if (request('search'))
                    <a href="{{ auth()->user()->hasRole('pegawai') ? route('pegawai.products.index') : route('pemilik.products.index') }}"
                        class="btn btn-outline-secondary rounded-pill px-3">
                        Reset
                    </a>
                @endif
                <button class="btn btn-dark rounded-pill px-4 fw-bold" type="submit">Cari</button>
            </div>
        </form>

        {{-- Product grid --}}
        <div class="row admin-product-grid">

            @forelse($products as $product)
                @php
                    $productIndex = $loop->index;
                    $availableImages = $product->availableImages();
                    $productStatusLabel = match ($product->status) {
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
                        default => 'Tidak Diketahui',
                    };
                @endphp
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                    <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden product-card">

                        {{-- IMAGE --}}
                        <div class="product-img-container">
                            @if ($availableImages->isNotEmpty())
                                <div id="carousel{{ $product->id }}" class="carousel slide h-100" data-bs-ride="carousel">
                                    <div class="carousel-inner h-100">

                                        @foreach ($availableImages as $key => $img)
                                            <div class="carousel-item {{ $key == 0 ? 'active' : '' }} h-100">

                                                <div class="d-flex align-items-center justify-content-center h-100">
                                                    <img src="{{ $img->publicUrl() }}"
                                                        alt="Foto produk {{ $product->name }}"
                                                        width="320" height="176"
                                                        @if ($productIndex === 0 && $key === 0) fetchpriority="high" @else loading="lazy" @endif
                                                        decoding="async">
                                                </div>

                                            </div>
                                        @endforeach

                                    </div>
                                </div>
                            @else
                                <div class="h-100 d-flex align-items-center justify-content-center text-muted">
                                    <i class="bi bi-image fs-1 opacity-25"></i>
                                </div>
                            @endif
                        </div>

                        {{-- BODY --}}
                        <div class="card-body p-4 product-card__meta">
                            <h6 class="fw-bold text-dark mb-1 product-card__title">{{ $product->name }}</h6>
                            <p class="text-muted small mb-3">SKU: {{ $product->sku }}</p>

                            <div class="d-grid gap-2 product-card__actions">
                                <button class="btn btn-outline-dark btn-sm rounded-3 py-2 fw-bold" data-bs-toggle="modal"
                                    data-bs-target="#detailModal{{ $product->id }}">
                                    <i class="bi bi-eye me-1"></i> Detail & Varian
                                </button>

                                @role('pegawai')
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('pegawai.products.edit', $product) }}"
                                            class="btn btn-light btn-sm flex-fill border rounded-3"
                                            aria-label="Edit produk {{ $product->name }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('pegawai.products.destroy', $product) }}" method="POST"
                                            class="flex-fill" data-confirm data-confirm-title="Hapus produk?"
                                            data-confirm-message="Produk {{ $product->name }} akan dihapus jika tidak memiliki transaksi aktif."
                                            data-confirm-label="Hapus Produk">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-light text-danger btn-sm w-100 border rounded-3"
                                                type="submit" aria-label="Hapus produk {{ $product->name }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endrole
                            </div>
                        </div>
                    </div>
                </div>

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
                                        <td>{{ $productStatusLabel }}</td>
                                    </tr>
                                </table>

                                <h6 class="fw-bold mt-3">Varian:</h6>
                                @if ($product->variants->count())
                                    <table class="table table-sm table-striped text-center product-variant-table">
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
                                            @foreach ($product->variants as $v)
                                                <tr>
                                                    <td>{{ $v->size }}</td>
                                                    <td>{{ $v->color }}</td>
                                                    <td>Rp{{ number_format($v->price, 0, ',', '.') }}</td>
                                                    <td>{{ $v->stock }}</td>
                                                    <td>{{ $v->availabilityLabel() }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <p class="text-muted fst-italic">Tidak ada varian</p>
                                @endif
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                            </div>
                        </div>
                    </div>
                </div>

            @empty
                <div class="col-12">
                    <div class="admin-empty-state">
                        <i class="bi bi-box-seam text-muted opacity-25 d-block mb-3"></i>
                        <h6 class="text-dark fw-bold mb-1">Produk tidak ditemukan</h6>
                        <p class="text-muted small mb-0">Coba gunakan kata kunci pencarian yang lain.</p>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="admin-pagination">
            {{ $products->links('pagination::bootstrap-5') }}
        </div>
    </div>

@endsection
