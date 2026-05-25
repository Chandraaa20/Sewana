@extends('layouts.admin')

@section('content')
    <div class="container py-4">
        {{-- Header Navigation --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h3 class="fw-bold text-dark mb-0 admin-page-title fs-3">Detail Pesanan</h3>
                    <span
                        class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-3 py-1">
                        #{{ $order->id }}
                    </span>
                </div>
                <p class="text-muted small mb-0">
                    <i class="bi bi-clock me-1"></i> Dibuat pada: {{ $order->created_at?->format('d M Y, H:i') }}
                </p>
            </div>
            @php
                $backTarget = request('back');

                $backUrl = match ($backTarget) {
                    'all' => route('pegawai.orders.all'),
                    'active' => route('pegawai.orders.index'),
                    'my' => route('penyewa.orders.index'),
                    default => auth()
                        ->user()
                        ->hasAnyRole(['pegawai', 'pemilik'])
                        ? route('pegawai.orders.index')
                        : route('penyewa.orders.index'),
                };
            @endphp

            <a href="{{ $backUrl }}" class="btn btn-outline-dark rounded-pill px-4 shadow-sm fw-medium">
                <i class="bi bi-arrow-left me-2"></i> Kembali
            </a>
        </div>

        {{-- PHP Variables Preparation --}}
        @php
            $status = $order->order_status;

            // Status Data modern
            $statusData = match ($status) {
                'pending' => ['color' => 'warning', 'label' => 'Menunggu', 'icon' => 'clock-history'],
                'approved' => ['color' => 'primary', 'label' => 'Disetujui', 'icon' => 'hand-thumbs-up'],
                'rented' => ['color' => 'info', 'label' => 'Sedang Disewa', 'icon' => 'arrow-repeat'],
                'returned' => ['color' => 'success', 'label' => 'Dikembalikan', 'icon' => 'check-circle'],
                'cancelled' => ['color' => 'danger', 'label' => 'Dibatalkan', 'icon' => 'x-circle'],
                default => ['color' => 'secondary', 'label' => 'Tidak Diketahui', 'icon' => 'question-circle'],
            };

            $paymentClass = match ($order->payment_status) {
                'paid' => 'success',
                'pending' => 'danger',
                'failed', 'expired' => 'danger',
                default => 'secondary',
            };
            $paymentLabel = match ($order->payment_status) {
                'paid' => 'Sudah Dibayar',
                'pending' => 'Belum Dibayar',
                'failed' => 'Pembayaran Gagal',
                'expired' => 'Pembayaran Kedaluwarsa',
                default => 'Tidak Diketahui',
            };
            $paymentIcon = $order->payment_status === 'paid' ? 'check-circle' : 'x-circle';
            $isOnlineOrder = $order->source === 'online';
            $isOfflineQrisOrder = $order->source === 'offline' && $order->payment_method === 'qris';
            $isOfflineCashOrder = $order->source === 'offline' && $order->payment_method === 'cash';

            $canApproveOrder =
                $status === 'pending' &&
                ((!$isOnlineOrder && !$isOfflineQrisOrder) || $order->payment_status === 'paid');
            $paymentApprovalInfo = match ($order->payment_status) {
                'pending' => $isOfflineQrisOrder ? 'Menunggu proses pembayaran' : 'Menunggu pembayaran penyewa',
                'failed' => 'Pembayaran penyewa gagal',
                'expired' => 'Pembayaran penyewa kedaluwarsa',
                default => 'Status pembayaran belum valid',
            };

            $customerLabel = $order->customer_name ?: $order->user->name ?? 'Tidak Diketahui';
            $productName = $order->product->name ?? 'Produk Tidak Ditemukan';
            $productDesc = $order->product->description ?? '-';

            $variantLabel = $order->variant
                ? ($order->variant->size ?? '-') . ' / ' . ($order->variant->color ?? '-')
                : 'Tidak ada varian';

            $productImage = $order->product?->firstAvailableImage()?->publicUrl();

            $identityPhoto = $order->identityPhotoUrl();
            $paymentProof = $order->paymentProofUrl();
        @endphp

        <div class="row g-4">
            {{-- KOLOM KIRI: Informasi Produk & Tagihan --}}
            <div class="col-lg-5">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden h-100">

                    <div class="bg-light p-4 admin-detail-hero">
                        @if ($productImage)
                            <div class="d-flex justify-content-center align-items-center h-100">
                                <img src="{{ $productImage }}" class="rounded-4 shadow-sm admin-detail-image"
                                    alt="Foto produk {{ $productName }}" width="520" height="260" fetchpriority="high"
                                    decoding="async">
                            </div>
                        @else
                            <div
                                class="text-center text-muted d-flex flex-column justify-content-center align-items-center h-100">
                                <i class="bi bi-image display-1 opacity-50"></i>
                                <p class="mt-2 small">Tidak ada foto produk</p>
                            </div>
                        @endif
                    </div>

                    {{-- Product details --}}
                    <div class="card-body p-4">
                        <div class="mb-4">
                            <h4 class="fw-bold text-dark mb-2">{{ $productName }}</h4>
                            <p class="text-muted small mb-3">{{ Str::limit($productDesc, 100) }}</p>

                            {{-- Badges Status & Pembayaran --}}
                            <div class="d-flex gap-2 flex-wrap">
                                <span class="admin-status-badge admin-status-{{ $statusData['color'] }}">
                                    <i class="bi bi-{{ $statusData['icon'] }} me-1"></i>
                                    {{ strtoupper($statusData['label']) }}
                                </span>
                                <span class="admin-status-badge admin-status-{{ $paymentClass }}">
                                    <i class="bi bi-{{ $paymentIcon }} me-1"></i> {{ strtoupper($paymentLabel) }}
                                </span>
                            </div>
                        </div>

                        {{-- Rincian Sewa --}}
                        <div class="bg-light rounded-4 p-3 mb-4">
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="admin-mini-label">Varian</div>
                                    <div class="fw-semibold text-dark">{{ $variantLabel }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="admin-mini-label">Durasi Sewa
                                    </div>
                                    <div class="fw-semibold text-dark">{{ $order->rent_days }} Hari</div>
                                </div>
                                <div class="col-12 mt-3 pt-3 border-top">
                                    <div class="admin-mini-label">Periode Sewa
                                    </div>
                                    <div class="fw-semibold text-dark d-flex align-items-center">
                                        {{ \Carbon\Carbon::parse($order->start_date)->format('d M Y') }}
                                        <i class="bi bi-arrow-right mx-2 text-muted"></i>
                                        {{ \Carbon\Carbon::parse($order->end_date)->format('d M Y') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tagihan --}}
                        <div class="border rounded-4 p-3 border-secondary border-opacity-25">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted small">Harga per Hari</span>
                                <span
                                    class="fw-semibold text-dark">Rp{{ number_format($order->price_per_day, 0, ',', '.') }}</span>
                            </div>
                            <hr class="text-muted opacity-25 my-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark">Total Tagihan</span>
                                <span
                                    class="fw-bold text-primary fs-5">Rp{{ number_format($order->total_price, 0, ',', '.') }}</span>
                            </div>
                        </div>
                        @if ($order->source === 'offline' && $order->payment_method === 'cash')
                            <div class="border rounded-4 p-3 border-success border-opacity-25 mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Metode Pembayaran</span>
                                    <span class="fw-semibold text-dark">Tunai</span>
                                </div>

                                <hr class="text-muted opacity-25 my-2">

                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Nominal Diterima</span>
                                    <span class="fw-semibold text-dark">
                                        Rp{{ number_format((int) $order->amount_received, 0, ',', '.') }}
                                    </span>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Kembalian</span>
                                    <span class="fw-semibold text-success">
                                        Rp{{ number_format((int) $order->change_amount, 0, ',', '.') }}
                                    </span>
                                </div>

                                @if ($order->paid_at)
                                    <hr class="text-muted opacity-25 my-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted small">Dibayar pada</span>
                                        <span class="fw-semibold text-dark">
                                            {{ $order->paid_at->format('d M Y, H:i') }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: Data Pelanggan, Identitas, & Aksi --}}
            <div class="col-lg-7 d-flex flex-column gap-4">

                {{-- Data Pelanggan --}}
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-dark border-bottom pb-3 mb-4">
                            <i class="bi bi-person-badge text-primary me-2"></i> Informasi Pelanggan
                        </h6>
                        <div class="row g-4">
                            <div class="col-md-6 d-flex align-items-start gap-3">
                                <div class="bg-light rounded-circle text-secondary admin-icon-box">
                                    <i class="bi bi-person-fill fs-5"></i>
                                </div>
                                <div>
                                    <div class="admin-mini-label">Nama Penyewa</div>
                                    <div class="fw-semibold text-dark">{{ $customerLabel }}</div>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex align-items-start gap-3">
                                <div class="bg-light rounded-circle text-secondary admin-icon-box">
                                    <i class="bi bi-envelope-at-fill fs-5"></i>
                                </div>
                                <div>
                                    <div class="admin-mini-label">Akun Sistem</div>
                                    <div class="fw-semibold text-dark">{{ $order->user->name ?? 'Tamu/Offline' }}</div>
                                    @if (isset($order->user->email))
                                        <div class="text-muted small">{{ $order->user->email }}</div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-12 d-flex align-items-start gap-3">
                                <div class="bg-light rounded-circle text-secondary admin-icon-box">
                                    <i class="bi bi-geo-alt-fill fs-5"></i>
                                </div>
                                <div>
                                    <div class="admin-mini-label">Alamat Lengkap</div>
                                    <div class="fw-semibold text-dark">{{ $order->address ?? 'Alamat tidak disertakan' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Foto Identitas --}}
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                            <h6 class="fw-bold text-dark mb-0">
                                <i class="bi bi-card-image text-primary me-2"></i> Foto Identitas (KTP/KTM)
                            </h6>
                            @if ($identityPhoto)
                                <a class="btn btn-sm btn-outline-primary rounded-pill px-3" target="_blank" rel="noopener"
                                    href="{{ $identityPhoto }}"
                                    aria-label="Perbesar foto identitas {{ $customerLabel }}">
                                    <i class="bi bi-arrows-fullscreen me-1"></i> Perbesar
                                </a>
                            @endif
                        </div>

                        @if ($identityPhoto)
                            <div class="text-center bg-light rounded-4 p-3 border border-dashed">
                                <img src="{{ $identityPhoto }}" class="img-fluid rounded-3 shadow-sm admin-proof-image"
                                    alt="Foto identitas {{ $customerLabel }}" width="640" height="420"
                                    loading="lazy" decoding="async">
                            </div>
                        @else
                            <div class="text-center py-4 text-muted bg-light rounded-4 border border-dashed">
                                <i class="bi bi-camera fs-1 opacity-50 mb-2 d-block"></i>
                                Belum ada foto identitas yang dilampirkan.
                            </div>
                        @endif
                    </div>
                </div>
                {{-- Bukti Pembayaran --}}
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                            <h6 class="fw-bold text-dark mb-0">
                                <i class="bi bi-receipt text-success me-2"></i> Bukti Pembayaran
                            </h6>

                            @if ($paymentProof)
                                <a class="btn btn-sm btn-outline-success rounded-pill px-3" target="_blank"
                                    rel="noopener" href="{{ $paymentProof }}"
                                    aria-label="Perbesar bukti pembayaran pesanan #{{ $order->id }}">
                                    Perbesar
                                </a>
                            @endif
                        </div>

                        @if ($paymentProof)
                            <div class="text-center bg-light rounded-4 p-3 border border-dashed">
                                <img src="{{ $paymentProof }}" class="img-fluid rounded-3 shadow-sm admin-proof-image"
                                    alt="Bukti pembayaran pesanan #{{ $order->id }}" width="640" height="420"
                                    loading="lazy" decoding="async">
                            </div>
                        @elseif (($isOnlineOrder || $isOfflineQrisOrder) && ($order->payment_reference || $order->paid_at))
                            <div class="bg-light rounded-4 p-3 border border-dashed">
                                <p class="text-muted small mb-3">
                                    Pembayaran diverifikasi melalui sistem QRIS/payment gateway.
                                </p>
                                <div class="d-flex justify-content-between gap-3 small mb-2">
                                    <span class="text-muted">Reference</span>
                                    <span
                                        class="fw-semibold text-dark text-end">{{ $order->payment_reference ?? '-' }}</span>
                                </div>
                                <div class="d-flex justify-content-between gap-3 small">
                                    <span class="text-muted">Dibayar pada</span>
                                    <span class="fw-semibold text-dark text-end">
                                        {{ $order->paid_at?->format('d M Y, H:i') ?? '-' }}
                                    </span>
                                </div>
                            </div>
                        @elseif ($isOfflineCashOrder)
                            <div class="bg-light rounded-4 p-3 border border-dashed">
                                <p class="text-muted small mb-0">
                                    Pembayaran tunai diproses langsung oleh pegawai.
                                </p>
                            </div>
                        @else
                            <div class="text-center py-4 text-muted bg-light rounded-4 border border-dashed">
                                <i class="bi bi-image fs-1 opacity-50 mb-2 d-block"></i>
                                Belum ada bukti pembayaran.
                            </div>
                        @endif
                    </div>
                </div>
                @if ($isOnlineOrder && $order->payment_status === 'pending')
                    <div class="card shadow-sm rounded-4 border-start border-4 border-warning">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-dark mb-2">
                                <i class="bi bi-wallet2 text-warning me-2"></i> Pembayaran Belum Selesai
                            </h6>

                            <p class="text-muted small mb-3">
                                Pesanan ini masih menunggu pembayaran. Silakan lanjutkan pembayaran agar pesanan dapat
                                diproses oleh petugas.
                            </p>

                            <a href="{{ route('penyewa.orders.payment.instructions', $order->id) }}"
                                class="btn btn-warning rounded-pill px-4 fw-semibold">
                                <i class="bi bi-credit-card me-1"></i> Lanjutkan Pembayaran
                            </a>
                        </div>
                    </div>
                @endif
                {{-- QR Code Validasi Transaksi --}}
                @if ($verificationUrl && $verificationQrCodeSvg)
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="bi bi-qr-code text-dark me-2"></i> Validasi Transaksi
                                </h6>
                            </div>

                            <div class="d-flex flex-column flex-md-row align-items-center gap-4">
                                <div class="bg-white border rounded-4 p-3 shadow-sm">
                                    {!! $verificationQrCodeSvg !!}
                                </div>
                                <div class="text-center text-md-start">
                                    <p class="fw-semibold text-dark mb-2">Pindai QR Code ini untuk memvalidasi transaksi.
                                    </p>
                                    <a href="{{ $verificationUrl }}" target="_blank" rel="noopener"
                                        class="small text-decoration-none">
                                        Buka halaman validasi
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                {{-- Panel Aksi Staff --}}
                @hasanyrole('pegawai|pemilik')
                    <div class="card shadow-sm  rounded-4 border-start border-4 border-dark">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-dark mb-3">
                                <i class="bi bi-sliders me-2"></i> Tindakan Petugas
                            </h6>

                            <div class="bg-light p-3 rounded-4 mb-3">
                                @if ($isOfflineQrisOrder)
                                    <a href="{{ route('pegawai.orders.offline-qris.show', $order) }}"
                                        class="btn btn-outline-dark w-100 rounded-pill fw-semibold mb-3">
                                        <i class="bi bi-qr-code me-1"></i> Buka Pembayaran QRIS
                                    </a>
                                @endif
                                {{-- Jika PENDING --}}
                                @if ($status === 'pending')
                                    <p class="text-muted small mb-3">Tinjau pesanan dan identitas pelanggan. Pilih
                                        <b>Setujui</b> untuk menyetujui, atau <b>Batalkan</b> jika tidak memenuhi syarat.
                                    </p>
                                    <div class="d-flex gap-2">
                                        @if ($canApproveOrder)
                                            <form action="{{ route('pegawai.orders.approve', $order->id) }}" method="POST"
                                                class="flex-grow-1">
                                                @csrf
                                                @method('PATCH')
                                                @if ($isOfflineQrisOrder)
                                                    <div class="alert alert-success rounded-4 small mb-3">
                                                        Pembayaran QRIS sudah terkonfirmasi oleh sistem.
                                                    </div>
                                                @elseif ($isOfflineCashOrder)
                                                    <div class="alert alert-info rounded-4 small mb-3">
                                                        Pembayaran tunai diproses langsung oleh pegawai.
                                                    </div>
                                                @endif
                                                <button class="btn btn-success w-100 rounded-pill fw-semibold shadow-sm">
                                                    <i class="bi bi-check-lg me-1"></i> Setujui Pesanan
                                                </button>
                                            </form>
                                        @else
                                            <div class="alert alert-warning rounded-4 small mb-0 flex-grow-1">
                                                <i class="bi bi-hourglass-split me-1"></i> {{ $paymentApprovalInfo }}
                                            </div>
                                        @endif

                                        <form action="{{ route('pegawai.orders.reject', $order->id) }}" method="POST"
                                            class="flex-grow-1" data-confirm data-confirm-title="Tolak pesanan?"
                                            data-confirm-message="Pesanan #{{ $order->id }} akan dibatalkan dan tidak bisa diproses sebagai sewa aktif."
                                            data-confirm-label="Tolak Pesanan">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-outline-danger w-100 rounded-pill fw-semibold bg-white">
                                                <i class="bi bi-x-lg me-1"></i> Batalkan Pesanan
                                            </button>
                                        </form>
                                    </div>

                                    {{-- Jika APPROVED --}}
                                @elseif ($status === 'approved')
                                    <p class="text-muted small mb-3">Pesanan telah disetujui. Atur status pembayaran dan klik
                                        <b>Serah Barang</b> saat barang diambil pelanggan.
                                    </p>
                                    <form action="{{ route('pegawai.orders.handover', $order->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <div class="input-group mb-3 shadow-sm rounded-pill overflow-hidden border">
                                            <label class="input-group-text bg-white border-0 text-muted"
                                                for="paymentSelect"><i class="bi bi-wallet2"></i></label>
                                            <select name="payment_status" id="paymentSelect"
                                                class="form-select border-0 focus-ring focus-ring-light">
                                                <option value="pending"
                                                    {{ $order->payment_status === 'pending' ? 'selected' : '' }}>Tagihan Belum
                                                    Dibayar</option>
                                                <option value="paid"
                                                    {{ $order->payment_status === 'paid' ? 'selected' : '' }}>Tagihan Sudah
                                                    Lunas</option>
                                            </select>
                                        </div>
                                        <button class="btn btn-primary w-100 rounded-pill fw-semibold shadow-sm py-2">
                                            <i class="bi bi-box-seam me-1"></i> Serahkan Barang (Ubah ke Sedang Disewa)
                                        </button>
                                    </form>

                                    {{-- Jika RENTED --}}
                                @elseif ($status === 'rented')
                                    <p class="text-muted small mb-3">Barang sedang disewa. Klik tombol di bawah ini jika barang
                                        sudah dikembalikan oleh pelanggan.</p>
                                    <form action="{{ route('pegawai.orders.returned', $order->id) }}" method="POST"
                                        data-confirm data-confirm-title="Selesaikan pesanan?"
                                        data-confirm-message="Pastikan barang telah diterima dan dicek kondisinya sebelum menandai pesanan #{{ $order->id }} sebagai dikembalikan."
                                        data-confirm-label="Tandai Dikembalikan" data-confirm-button="btn-info text-white">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-info w-100 rounded-pill fw-semibold text-white shadow-sm py-2">
                                            <i class="bi bi-arrow-return-left me-1"></i> Barang Telah Dikembalikan
                                        </button>
                                    </form>

                                    {{-- Jika RETURNED atau CANCELLED --}}
                                @else
                                    <div class="text-center py-3">
                                        <div
                                            class="bg-secondary bg-opacity-10 text-secondary rounded-circle admin-icon-box mb-2">
                                            <i class="bi bi-lock-fill fs-4"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-1">Pesanan Ditutup</h6>
                                        <p class="text-muted small mb-0">Pesanan ini berstatus
                                            <b>{{ strtoupper($statusData['label']) }}</b> dan tidak memerlukan aksi lebih
                                            lanjut.
                                        </p>
                                    </div>
                                @endif
                            </div>

                        </div>
                    </div>
                @endhasanyrole
            </div>
        </div>
    </div>

    {{-- Sedikit CSS untuk mempercantik border dashed foto --}}
@endsection
