@extends('layouts.admin')

@section('content')
    <div class="admin-page admin-page--wide">
        {{-- Header & Filter Toolbar --}}
        <div class="admin-page-header flex-column flex-md-row align-items-md-center">
            <div>
                <span class="admin-page-eyebrow">Riwayat</span>
                <h1 class="admin-page-title">Semua Sewa</h1>
                <p class="admin-page-subtitle">Riwayat dan daftar lengkap semua pesanan sewa.</p>
            </div>

            <form method="GET" action="{{ route('pegawai.orders.all') }}"
                class="admin-filter-toolbar filter-card search-form">
                <div class="input-group input-group-sm shadow-sm admin-filter-search search-input-wrap">
                    <span class="input-group-text bg-white border-end-0 text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="form-control border-start-0 search-input" placeholder="Cari pelanggan / produk..."
                        aria-label="Cari pelanggan atau produk">
                </div>

                <select name="status" class="form-select form-select-sm shadow-sm admin-filter-select filter-select"
                    aria-label="Filter status pesanan">
                    <option value="">Semua Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                    <option value="rented" {{ request('status') == 'rented' ? 'selected' : '' }}>Sedang Disewa</option>
                    <option value="returned" {{ request('status') == 'returned' ? 'selected' : '' }}>Dikembalikan</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                </select>

                <div class="admin-toolbar-actions">
                    <button type="submit" class="btn btn-sm btn-dark shadow-sm px-3 search-button">
                        Filter
                    </button>
                    @if (request('search') || request('status'))
                        <a href="{{ route('pegawai.orders.all') }}" class="btn btn-sm btn-outline-danger shadow-sm px-3"
                            title="Atur Ulang Filter" aria-label="Atur ulang filter pesanan">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Daftar Pesanan --}}
        @if ($orders->count() > 0)
            <div class="row g-4">
                @foreach ($orders as $order)
                    @php
                        $orderIndex = $loop->index;
                        $productImage = $order->product?->firstAvailableImage();
                    @endphp
                    <div class="col-12">
                        <div class="card shadow-sm border border-light rounded-4 overflow-hidden admin-order-card">
                            <div class="row g-0">

                                {{-- Gambar Produk --}}
                                <div
                                    class="col-md-3 col-lg-2 bg-light d-flex align-items-center justify-content-center p-3 border-end">
                                    @if ($order->product && $productImage)
                                        <img src="{{ $productImage->publicUrl() }}"
                                            alt="Foto produk {{ $order->product->name }}"
                                            class="img-fluid rounded-3 shadow-sm admin-square-media" width="160"
                                            height="160"
                                            @if ($orderIndex === 0) fetchpriority="high" @else loading="lazy" @endif
                                            decoding="async"
                                            onerror="this.onerror=null; this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none'); this.nextElementSibling.classList.add('d-flex');">
                                        <div
                                            class="d-none bg-white rounded-3 flex-column align-items-center justify-content-center shadow-sm admin-square-media">
                                            <i class="bi bi-image text-muted fs-1"></i>
                                            <span class="small text-muted mt-2">Gambar tidak tersedia</span>
                                        </div>
                                    @else
                                        <div
                                            class="bg-white rounded-3 d-flex flex-column align-items-center justify-content-center shadow-sm admin-square-media">
                                            <i class="bi bi-image text-muted fs-1"></i>
                                            <span class="small text-muted mt-2">Tidak Ada Gambar</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Order details --}}
                                <div class="col-md-6 col-lg-7 p-4 d-flex flex-column justify-content-center">
                                    <h5 class="fw-bold text-dark mb-3">
                                        {{ $order->product->name ?? 'Produk Tidak Ditemukan' }}
                                    </h5>

                                    <div class="row g-3 admin-order-meta-grid">
                                        <div class="col-sm-6">
                                            <div class="d-flex align-items-start">
                                                <i class="bi bi-person-badge text-muted fs-5 me-2"></i>
                                                <div>
                                                    <p class="text-muted small mb-0">Pelanggan</p>
                                                    <p
                                                        class="fw-semibold text-dark mb-0 text-truncate admin-text-truncate-sm">
                                                        {{ $order->customer_name ?? ($order->user->name ?? 'Tidak Diketahui') }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="d-flex align-items-start">
                                                <i class="bi bi-hash text-muted fs-5 me-2"></i>
                                                <div>
                                                    <p class="text-muted small mb-0">ID Pesanan</p>
                                                    <p class="fw-semibold text-dark mb-0">#{{ $order->id }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="d-flex align-items-start">
                                                <i class="bi bi-calendar-check text-muted fs-5 me-2"></i>
                                                <div>
                                                    <p class="text-muted small mb-0">Periode ({{ $order->rent_days }} hari)
                                                    </p>
                                                    <p class="fw-semibold text-dark mb-0">
                                                        {{ \Carbon\Carbon::parse($order->start_date)->format('d M') }} -
                                                        {{ \Carbon\Carbon::parse($order->end_date)->format('d M Y') }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="d-flex align-items-start">
                                                <i class="bi bi-tags text-muted fs-5 me-2"></i>
                                                <div>
                                                    <p class="text-muted small mb-0">Total Harga</p>
                                                    <p class="fw-bold text-dark mb-0">
                                                        Rp{{ number_format($order->total_price, 0, ',', '.') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Status & Aksi --}}
                                <div
                                    class="col-md-3 col-lg-3 d-flex flex-column align-items-center justify-content-center bg-light bg-opacity-50 p-4 border-start order-card-right">
                                    @php
                                        $statusClass = match ($order->order_status) {
                                            'pending' => 'warning',
                                            'approved' => 'primary',
                                            'rented' => 'info',
                                            'returned' => 'success',
                                            'cancelled' => 'danger',
                                            default => 'secondary',
                                        };
                                        $statusLabel = match ($order->order_status) {
                                            'pending' => 'Menunggu',
                                            'approved' => 'Disetujui',
                                            'rented' => 'Sedang Disewa',
                                            'returned' => 'Dikembalikan',
                                            'cancelled' => 'Dibatalkan',
                                            default => 'Tidak Diketahui',
                                        };
                                    @endphp

                                    <div class="text-center w-100">
                                        <p class="text-muted small mb-2">Status Pesanan</p>
                                        <span
                                            class="badge bg-{{ $statusClass }}-subtle text-{{ $statusClass }} border border-{{ $statusClass }} rounded-pill px-4 py-2 mb-3 fw-bold w-100">
                                            <i class="bi bi-circle-fill small me-1"></i>
                                            {{ strtoupper($statusLabel) }}
                                        </span>

                                        <a href="{{ route('pegawai.orders.show', $order->id) }}?back=all"
                                            class="btn btn-outline-dark rounded-3 w-100 fw-semibold">
                                            <i class="bi bi-eye me-1"></i> Lihat Detail
                                        </a>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="admin-pagination">
                {{ $orders->links('pagination::bootstrap-5') }}
            </div>
        @else
            {{-- Empty State --}}
            <div class="text-center py-5">
                <div class="admin-empty-icon-lg mb-3">
                    <i class="bi bi-search text-muted"></i>
                </div>
                <h4 class="fw-bold text-dark">Data tidak ditemukan</h4>
                <p class="text-muted">Coba ubah filter pencarian atau status pesanan.</p>
                <a href="{{ route('pegawai.orders.all') }}" class="btn btn-outline-dark mt-2">Tampilkan Semua</a>
            </div>
        @endif
    </div>

    {{-- Tambahan CSS --}}
@endsection
