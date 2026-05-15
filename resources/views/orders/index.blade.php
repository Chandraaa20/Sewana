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
            <div class="row g-4">
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
                                    class="col-md-3 text-center bg-light d-flex align-items-center justify-content-center p-3">
                                    @if ($order->product && $productImage)
                                        <img src="{{ $productImage->publicUrl() }}"
                                            alt="Foto produk {{ $order->product->name }}"
                                            class="img-fluid rounded-3 shadow-sm admin-list-image"
                                            width="160" height="160"
                                            @if ($orderIndex === 0) fetchpriority="high" @else loading="lazy" @endif
                                            decoding="async">
                                    @else
                                        <div class="rounded-3 bg-white d-flex flex-column align-items-center justify-content-center admin-list-image">
                                            <i class="bi bi-image text-muted fs-2"></i>
                                            <span class="small text-muted mt-2">Tidak Ada Gambar</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Product details --}}
                                <div class="col-md-6 p-3 d-flex flex-column justify-content-center">
                                    <h5 class="fw-bold text-dark mb-1">
                                        {{ $order->product->name ?? 'Produk Tidak Ditemukan' }}
                                    </h5>
                                    <p class="text-muted small mb-1">
                                        {{ Str::limit($order->product->description ?? '-', 100) }}
                                    </p>
                                    <p class="mb-0 small">
                                        <span class="fw-semibold">Varian:</span>
                                        {{ $order->variant->size ?? '-' }} / {{ $order->variant->color ?? '-' }}
                                    </p>
                                    <p class="mb-0 small">
                                        <span class="fw-semibold">Tanggal:</span>
                                        {{ \Carbon\Carbon::parse($order->start_date)->format('d M Y') }}
                                        s/d {{ \Carbon\Carbon::parse($order->end_date)->format('d M Y') }}
                                    </p>
                                    <p class="mb-0 small">
                                        <span class="fw-semibold">Durasi:</span> {{ $order->rent_days }} hari
                                    </p>
                                        <h6 class="fw-bold mt-2 text-dark">
                                        <i class="bi bi-cash-stack text-primary me-1"></i> Rp{{ number_format($order->total_price, 0, ',', '.') }}
                                    </h6>
                                </div>

                                {{-- Status and buttons --}}
                                {{-- Status and buttons --}}
                                <div
                                    class="col-md-3 d-flex flex-column align-items-center justify-content-center bg-white p-3 border-start order-card-right">
                                    {{-- Wrapper for all elements --}}
                                    <div class="d-flex flex-column align-items-center justify-content-center gap-2 w-100">

                                        {{-- Badge Status --}}
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

                                        {{-- Status --}}
                                        <div class="d-flex flex-column align-items-center gap-2 w-100">
                                            <span class="admin-status-badge admin-status-{{ $statusClass }}">
                                                {{ $statusLabel }}
                                            </span>

                                            <span class="admin-status-badge admin-status-{{ $paymentClass }}">
                                                {{ $paymentLabel }}
                                            </span>
                                        </div>

                                        {{-- Buttons --}}
                                        <div class="d-flex justify-content-center align-items-center gap-2 mt-2">
                                            <a href="{{ route('penyewa.orders.show', $order->id) }}"
                                                class="btn-action btn-dark" aria-label="Lihat detail pesanan {{ $order->id }}">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <form action="{{ route('penyewa.orders.destroy', $order->id) }}" method="POST"
                                                data-confirm data-confirm-title="Hapus pesanan?"
                                                data-confirm-message="Pesanan #{{ $order->id }} akan dihapus dari daftar Anda jika masih bisa dihapus."
                                                data-confirm-label="Hapus Pesanan">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-action btn-danger"
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
            <div class="admin-empty-state">
                <i class="bi bi-box-seam text-muted opacity-25 d-block mb-3"></i>
                <h6 class="text-dark fw-bold mb-1">Belum ada pesanan</h6>
                <p class="text-muted small">Belum ada pesanan yang dibuat.</p>
                <a href="{{ route('penyewa.orders.create') }}" class="btn btn-dark rounded-pill px-4">
                    <i class="bi bi-shop"></i> Mulai Sewa Sekarang
                </a>
            </div>
        @endif
    </div>

    {{-- CSS Tambahan --}}
@endsection

