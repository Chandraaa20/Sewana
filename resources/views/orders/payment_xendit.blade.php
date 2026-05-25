@extends('layouts.admin')

@section('content')
    @php
        $paymentStatusLabels = [
            'pending' => 'Belum Dibayar',
            'paid' => 'Sudah Dibayar',
            'failed' => 'Pembayaran Gagal',
            'expired' => 'Pembayaran Kedaluwarsa',
        ];
        $closedPaymentOrderStatuses = ['cancelled', 'rejected', 'refunded'];
        $isClosedPaymentOrder = in_array($order->order_status, $closedPaymentOrderStatuses, true);
        $canContinuePayment =
            !$isClosedPaymentOrder && !in_array($order->payment_status, ['paid', 'failed', 'expired'], true);
    @endphp

    <div class="admin-page">
        <div class="admin-page-header">
            <div>
                <span class="admin-page-eyebrow">Xendit Sandbox</span>
                <h1 class="admin-page-title">Instruksi Pembayaran</h1>
                <p class="admin-page-subtitle">Selesaikan pembayaran melalui halaman Xendit Sandbox.</p>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <div class="row g-4 align-items-start">
                    <div class="col-lg-7">
                        <h2 class="h5 fw-bold text-dark mb-3">Ringkasan Pesanan</h2>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <tbody>
                                    <tr>
                                        <th class="text-muted fw-semibold">Kode Order</th>
                                        <td class="text-end fw-bold">#{{ $order->id }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-semibold">Produk</th>
                                        <td class="text-end">{{ $order->product->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-semibold">Total Pembayaran</th>
                                        <td class="text-end fw-bold text-primary">
                                            Rp{{ number_format($order->total_price, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-semibold">Status Pembayaran</th>
                                        <td class="text-end">
                                            {{ $paymentStatusLabels[$order->payment_status] ?? 'Tidak Diketahui' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-semibold">Reference</th>
                                        <td class="text-end">{{ $order->payment_reference ?? '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="bg-light rounded-4 p-4 h-100">
                            <h3 class="h6 fw-bold text-dark mb-2">Pembayaran Xendit Sandbox</h3>
                            <p class="text-muted small mb-4">
                                Klik tombol di bawah untuk membuka halaman pembayaran Xendit Sandbox. Status pembayaran
                                akan diperbarui otomatis melalui webhook Xendit.
                            </p>
                            @if ($canContinuePayment && $paymentUrl)
                                <a href="{{ $paymentUrl }}" class="btn btn-dark rounded-pill px-4 w-100 mb-3">
                                    Buka Pembayaran Xendit
                                </a>
                            @elseif (!$canContinuePayment)
                                <div class="alert alert-info rounded-4 small mb-3">
                                    Pembayaran tidak bisa dilanjutkan karena status pesanan atau pembayaran sudah final.
                                </div>
                            @else
                                <div class="alert alert-warning rounded-4 small mb-3">
                                    URL pembayaran Xendit belum tersedia. Silakan hubungi petugas.
                                </div>
                            @endif
                            @if ($canContinuePayment)
                                <p class="text-muted small mb-3" id="order-status-polling-note">
                                    Menunggu konfirmasi pembayaran...
                                </p>
                            @endif
                            @if (app()->environment(['local', 'development', 'testing']) && $canContinuePayment)
                                <div class="alert alert-secondary rounded-4 small mb-3">
                                    Fallback lokal untuk demo jika webhook Xendit Sandbox belum dapat diterima.
                                </div>
                                <form method="POST"
                                    action="{{ route('penyewa.orders.payment.simulate-success', $order->id) }}"
                                    class="mb-3">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-success rounded-pill px-4 w-100">
                                        Fallback Lokal: Tandai Pembayaran Berhasil
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('penyewa.orders.show', $order->id) }}" class="btn btn-dark rounded-pill px-4">
                                Kembali ke Detail Order
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const statusUrl = @json(route('penyewa.orders.status', $order->id));
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
