@extends('layouts.admin')

@section('content')
    @php
        $closedPaymentOrderStatuses = ['cancelled', 'rejected', 'refunded'];
        $isClosedPaymentOrder = in_array($order->order_status, $closedPaymentOrderStatuses, true);
        $canContinuePayment =
            !$isClosedPaymentOrder && !in_array($order->payment_status, ['paid', 'failed', 'expired'], true);
        $paymentStatusData = match ($order->payment_status) {
            'paid' => ['class' => 'success', 'label' => 'Sudah Dibayar'],
            'failed' => ['class' => 'danger', 'label' => 'Pembayaran Gagal'],
            'expired' => ['class' => 'danger', 'label' => 'Pembayaran Kedaluwarsa'],
            default => ['class' => 'warning text-dark', 'label' => 'Menunggu Pembayaran'],
        };
    @endphp

    <div class="admin-page">
        <div class="admin-page-header">
            <div>
                <span class="admin-page-eyebrow">Pembayaran Offline</span>
                <h1 class="admin-page-title">QRIS</h1>
                <p class="admin-page-subtitle">
                    Pembayaran QRIS pesanan offline melalui Xendit Sandbox.
                </p>
            </div>

            <a href="{{ route('pegawai.orders.index') }}" class="btn btn-outline-dark rounded-pill px-4">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>


        <div class="row g-4">
            <div class="col-lg-5">
                <div class="admin-card h-100">
                    <div class="admin-card-header">
                        <span class="admin-page-eyebrow">Xendit Sandbox</span>
                        <h5 class="fw-bold text-dark mb-0 mt-1">QRIS</h5>
                    </div>

                    <div class="admin-card-body text-center">
                        @if ($canContinuePayment && !empty($qrImageUrl))
                            <div class="bg-white border rounded-4 p-4 d-inline-block shadow-sm">
                                <img src="{{ $qrImageUrl }}" alt="QRIS Xendit pesanan #{{ $order->id }}"
                                    class="img-fluid" width="220" height="220">
                            </div>
                        @elseif ($canContinuePayment && $paymentQrCodeSvg)
                            <div class="bg-white border rounded-4 p-4 d-inline-block shadow-sm">
                                {!! $paymentQrCodeSvg !!}
                            </div>
                        @elseif (!$canContinuePayment)
                            <div class="alert alert-info rounded-4 small mb-0">
                                Pembayaran QRIS tidak bisa dilanjutkan karena status pesanan atau pembayaran sudah final.
                            </div>
                        @else
                            <div class="alert alert-warning rounded-4 small mb-0">
                                URL pembayaran Xendit belum tersedia untuk pesanan ini.
                            </div>
                        @endif

                        @if ($canContinuePayment && ($paymentQrCodeSvg || !empty($qrImageUrl)))
                            <p class="text-muted small mt-3 mb-0">
                                Pindai QR ini untuk membuka pembayaran Xendit Sandbox.
                            </p>
                        @endif

                        @if ($canContinuePayment && $paymentUrl)
                            <a href="{{ $paymentUrl }}" target="_blank" rel="noopener" class="small text-decoration-none">
                                Buka URL pembayaran Xendit
                            </a>
                        @endif

                        @if ($canContinuePayment)
                            <p class="text-muted small mt-3 mb-0" id="order-status-polling-note">
                                Menunggu konfirmasi pembayaran...
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <span class="admin-page-eyebrow">Detail Pesanan</span>
                        <h5 class="fw-bold text-dark mb-0 mt-1">Pesanan #{{ $order->id }}</h5>
                    </div>

                    <div class="admin-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="admin-mini-label">Nama Pelanggan</div>
                                <div class="fw-semibold text-dark">{{ $order->customer_name ?? '-' }}</div>
                            </div>

                            <div class="col-md-6">
                                <div class="admin-mini-label">Produk</div>
                                <div class="fw-semibold text-dark">{{ $order->product->name ?? '-' }}</div>
                            </div>

                            <div class="col-md-6">
                                <div class="admin-mini-label">Varian</div>
                                <div class="fw-semibold text-dark">
                                    {{ $order->variant ? ($order->variant->size ?? '-') . ' / ' . ($order->variant->color ?? '-') : '-' }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="admin-mini-label">Total Pembayaran</div>
                                <div class="fw-bold text-primary">
                                    Rp{{ number_format($order->total_price, 0, ',', '.') }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="admin-mini-label">Status Pembayaran</div>
                                <span class="badge bg-{{ $paymentStatusData['class'] }} rounded-pill px-3 py-2">
                                    {{ $paymentStatusData['label'] }}
                                </span>
                            </div>

                            <div class="col-md-6">
                                <div class="admin-mini-label">Status Pesanan</div>
                                <span class="badge bg-secondary rounded-pill px-3 py-2">
                                    {{ strtoupper($order->order_status) }}
                                </span>
                            </div>

                            <div class="col-12">
                                <div class="admin-mini-label">Reference</div>
                                <div class="fw-semibold text-dark">
                                    {{ $order->payment_reference ?? '-' }}
                                </div>
                            </div>

                            @if ($order->paid_at)
                                <div class="col-12">
                                    <div class="admin-mini-label">Dibayar pada</div>
                                    <div class="fw-semibold text-dark">
                                        {{ $order->paid_at->format('d M Y, H:i') }}
                                    </div>
                                </div>
                            @endif
                        </div>

                        <hr class="my-4">

                        @if ($canContinuePayment)
                            @env(['local', 'development', 'testing'])
                                <div class="alert alert-secondary rounded-4 small mb-3">
                                    Fallback lokal untuk demo jika webhook Xendit Sandbox belum dapat diterima.
                                </div>
                                <form action="{{ route('pegawai.orders.offline-qris.simulate-success', $order) }}"
                                    method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-success rounded-pill px-4">
                                        <i class="bi bi-check-circle me-1"></i>
                                        Fallback Lokal: Tandai Pembayaran Diterima
                                    </button>
                                </form>
                            @endenv
                        @elseif ($order->payment_status === 'paid')
                            <div class="alert alert-success rounded-4 mb-3">
                                Pembayaran sudah diterima. Pesanan masih menunggu persetujuan/penyerahan barang.
                            </div>

                            <a href="{{ route('pegawai.orders.show', $order->id) }}"
                                class="btn btn-dark rounded-pill px-4">
                                Buka Detail Pesanan
                            </a>
                        @else
                            <div class="alert alert-info rounded-4 mb-3">
                                Pembayaran tidak bisa dilanjutkan karena status pesanan atau pembayaran sudah final.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const statusUrl = @json(route('pegawai.orders.status', $order->id));
            let currentOrderStatus = @json($order->order_status);
            let currentPaymentStatus = @json($order->payment_status);
            const finalOrderStatuses = ['returned', 'cancelled', 'rejected', 'refunded'];
            const finalPaymentStatuses = ['failed', 'expired'];

            if (finalOrderStatuses.includes(currentOrderStatus) || finalPaymentStatuses.includes(currentPaymentStatus)) {
                return;
            }

            const timer = window.setInterval(async () => {
                try {
                    const response = await fetch(statusUrl, {
                        headers: {
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    });

                    if (!response.ok) return;

                    const data = await response.json();
                    if (data.order_status !== currentOrderStatus || data.payment_status !== currentPaymentStatus) {
                        window.clearInterval(timer);
                        window.location.reload();
                    }
                } catch (error) {
                    // Ignore transient network errors; the next interval will retry.
                }
            }, 5000);
        })();
    </script>
@endsection
