@extends('layouts.admin')

@section('content')
    <div class="admin-page">
        {{-- Header --}}
        <div class="admin-page-header">
            <div>
                <span class="admin-page-eyebrow">Pesanan</span>
                <h1 class="admin-page-title">Daftar Pesanan Saya</h1>
                <p class="admin-page-subtitle">Pantau status sewa, pembayaran, dan detail pesanan Anda.</p>
            </div>
        </div>

        {{-- Daftar Pesanan --}}
        @if ($orders->count() > 0)
            <div class="customer-orders-list">
                @foreach ($orders as $order)
                    @php
                        $orderIndex = $loop->index;
                        $productImage = $order->product?->firstAvailableImage();
                    @endphp
                    <div class="col-12">
                        <div class="card shadow-sm border-0 rounded-4 overflow-hidden admin-order-card">
                            <div class="row g-0">

                                {{-- Gambar Produk --}}
                                <div
                                    class="col-md-3 text-center d-flex align-items-center justify-content-center p-3 customer-order-media">
                                    @if ($order->product && $productImage)
                                        <img src="{{ $productImage->publicUrl() }}"
                                            alt="Foto produk {{ $order->product->name }}"
                                            class="img-fluid admin-list-image"
                                            width="160" height="160"
                                            @if ($orderIndex === 0) fetchpriority="high" @else loading="lazy" @endif
                                            decoding="async">
                                    @else
                                        <div class="customer-order-image-fallback d-flex flex-column align-items-center justify-content-center admin-list-image">
                                            <i class="bi bi-image text-muted fs-2"></i>
                                            <span class="small text-muted mt-2">Tidak Ada Gambar</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Product details --}}
                                <div class="col-md-6 customer-order-main">
                                    <div class="customer-order-copy">
                                        <div class="customer-order-kicker">Pesanan #{{ $order->id }}</div>
                                        <h5 class="customer-order-title">
                                            {{ $order->product->name ?? 'Produk Tidak Ditemukan' }}
                                        </h5>
                                        <p class="customer-order-description">
                                            {{ Str::limit($order->product->description ?? '-', 110) }}
                                        </p>
                                    </div>

                                    <div class="customer-order-meta">
                                        <div>
                                            <span>Varian</span>
                                            <strong>{{ $order->variant->size ?? '-' }} / {{ $order->variant->color ?? '-' }}</strong>
                                        </div>
                                        <div>
                                            <span>Periode</span>
                                            <strong>
                                                {{ \Carbon\Carbon::parse($order->start_date)->format('d M') }}
                                                - {{ \Carbon\Carbon::parse($order->end_date)->format('d M Y') }}
                                            </strong>
                                        </div>
                                        <div>
                                            <span>Durasi</span>
                                            <strong>{{ $order->rent_days }} hari</strong>
                                        </div>
                                    </div>
                                </div>

                                {{-- Status and buttons --}}
                                <div
                                    class="col-md-3 d-flex flex-column justify-content-center customer-order-side order-card-right">
                                    <div class="customer-order-side-inner">

                                        @php
                                            $statusClass = match ($order->order_status) {
                                                'pending' => 'warning',
                                                'approved' => 'primary',
                                                'rented' => 'info',
                                                'returned' => 'success',
                                                'cancelled', 'rejected' => 'danger',
                                                'finished' => 'secondary',
                                                default => 'secondary',
                                            };
                                            $paymentClass = $order->payment_status === 'paid' ? 'success' : 'danger';
                                            $statusLabel = match ($order->order_status) {
                                                'pending' => 'Menunggu',
                                                'approved' => 'Disetujui',
                                                'rejected' => 'Ditolak',
                                                'finished' => 'Selesai',
                                                'rented' => 'Sedang Disewa',
                                                'returned' => 'Dikembalikan',
                                                'cancelled' => 'Dibatalkan',
                                                default => 'Tidak Diketahui',
                                            };
                                            $paymentLabel = match ($order->payment_status) {
                                                'paid' => 'Sudah Dibayar',
                                                'unpaid' => 'Belum Dibayar',
                                                default => 'Tidak Diketahui',
                                            };
                                        @endphp

                                        <div class="customer-order-status">
                                            <span class="admin-status-badge admin-status-{{ $statusClass }}">
                                                {{ $statusLabel }}
                                            </span>
                                            <span class="admin-status-badge admin-status-{{ $paymentClass }}">
                                                {{ $paymentLabel }}
                                            </span>
                                        </div>

                                        <div class="customer-order-total">
                                            <span>Total</span>
                                            <strong>Rp{{ number_format($order->total_price, 0, ',', '.') }}</strong>
                                        </div>

                                        <div class="customer-order-actions">
                                            <a href="{{ route('penyewa.orders.show', $order->id) }}"
                                                class="btn btn-dark rounded-pill customer-order-detail"
                                                aria-label="Lihat detail pesanan {{ $order->id }}">
                                                <i class="bi bi-eye me-1"></i> Detail
                                            </a>

                                            <form action="{{ route('penyewa.orders.destroy', $order->id) }}" method="POST"
                                                data-confirm data-confirm-title="Hapus pesanan?"
                                                data-confirm-message="Pesanan #{{ $order->id }} akan dihapus dari daftar Anda jika masih bisa dihapus."
                                                data-confirm-label="Hapus Pesanan">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-light text-danger rounded-pill customer-order-delete"
                                                    aria-label="Hapus pesanan {{ $order->id }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="admin-pagination mt-4">
                {{ $orders->links('pagination::bootstrap-5') }}
            </div>
        @else
            {{-- Jika Tidak Ada Pesanan --}}
            <div class="customer-orders-empty">
                <div class="customer-orders-empty__icon">
                    <i class="bi bi-bag-check"></i>
                </div>
                <span class="admin-page-eyebrow">Belum Ada Aktivitas</span>
                <h2>Belum ada pesanan</h2>
                <p>Pilih produk dari katalog Sewana, tentukan varian dan tanggal sewa, lalu semua status pesanan akan tampil di sini.</p>
                <a href="{{ route('penyewa.products.index') }}" class="btn btn-dark rounded-pill px-4">
                    <i class="bi bi-shop me-2"></i> Jelajahi Produk
                </a>
            </div>
        @endif
    </div>

    {{-- CSS Tambahan --}}
@endsection

