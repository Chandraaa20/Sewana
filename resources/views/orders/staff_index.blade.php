@extends('layouts.admin')

@section('content')
    <div class="admin-page admin-page--wide">
        <div class="admin-page-header">
            <div>
                <span class="admin-page-eyebrow">Operasional</span>
                <h1 class="admin-page-title">Kelola Pesanan</h1>
                <p class="admin-page-subtitle">Pantau dan kelola semua transaksi sewa pelanggan.</p>
            </div>
            @role('pegawai')
                <a href="{{ route('pegawai.orders.offline.create') }}" class="btn btn-dark rounded-pill px-4 shadow-sm">
                    <i class="bi bi-plus-lg me-2"></i> Pesanan Offline
                </a>
            @endrole
        </div>

        <form method="GET" action="{{ url()->current() }}" class="admin-toolbar search-card filter-card search-form">
            <div class="input-group admin-search-box search-input-wrap">
                <span class="input-group-text bg-white border-0 ps-4">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" name="search" class="form-control border-0 shadow-none py-2 search-input"
                    placeholder="Cari pelanggan atau produk..." value="{{ request('search') }}"
                    aria-label="Cari pelanggan atau produk">
            </div>
            <div class="admin-toolbar-actions">
                @if (request('search'))
                    <a href="{{ url()->current() }}" class="btn btn-outline-secondary rounded-pill px-3">
                        Reset
                    </a>
                @endif
                <button class="btn btn-dark rounded-pill px-4 fw-bold search-button" type="submit">Cari</button>
            </div>
        </form>

        @if ($orders->count() > 0)
            <div class="row g-4">
                @foreach ($orders as $order)
                    @php
                        $orderIndex = $loop->index;
                        $productImage = $order->product?->firstAvailableImage();
                        $status = $order->order_status;
                        $statusClass = match ($status) {
                            'pending' => 'warning',
                            'approved' => 'primary',
                            'rented' => 'info',
                            'returned' => 'success',
                            'cancelled' => 'danger',
                            default => 'secondary',
                        };
                        $statusLabel = match ($status) {
                            'pending' => 'Menunggu',
                            'approved' => 'Disetujui',
                            'rented' => 'Sedang Disewa',
                            'returned' => 'Dikembalikan',
                            'cancelled' => 'Dibatalkan',
                            default => 'Tidak Diketahui',
                        };
                        $customerLabel = $order->customer_name ?: $order->user->name ?? 'Tidak Diketahui';
                        $isOnlineOrder = $order->source === 'online';
                        $canApproveOrder = $status === 'pending' && (! $isOnlineOrder || $order->payment_status === 'paid');
                        $paymentApprovalInfo = match ($order->payment_status) {
                            'pending' => 'Menunggu pembayaran penyewa',
                            'failed' => 'Pembayaran penyewa gagal',
                            'expired' => 'Pembayaran penyewa kedaluwarsa',
                            default => 'Status pembayaran belum valid',
                        };
                    @endphp

                    <div class="col-12">
                        <div class="card shadow-sm border border-light rounded-4 overflow-hidden admin-order-card">
                            <div class="row g-0">

                                <div
                                    class="col-md-3 col-lg-2 bg-light d-flex align-items-center justify-content-center p-3 border-end">
                                    @if ($order->product && $productImage)
                                        <img src="{{ $productImage->publicUrl() }}"
                                            class="img-fluid rounded-3 shadow-sm admin-square-media"
                                            alt="Foto produk {{ $order->product->name }}"
                                            width="160" height="160"
                                            @if ($orderIndex === 0) fetchpriority="high" @else loading="lazy" @endif
                                            decoding="async"
                                            onerror="this.onerror=null; this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none'); this.nextElementSibling.classList.add('d-flex');">
                                        <div class="d-none bg-white rounded-3 flex-column align-items-center justify-content-center shadow-sm admin-square-media">
                                            <i class="bi bi-image text-muted fs-1"></i>
                                            <span class="small text-muted mt-2">Gambar tidak tersedia</span>
                                        </div>
                                    @else
                                        <div class="bg-white rounded-3 d-flex flex-column align-items-center justify-content-center shadow-sm admin-square-media">
                                            <i class="bi bi-image text-muted fs-1"></i>
                                            <span class="small text-muted mt-2">Tidak Ada Gambar</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="col-md-6 col-lg-7 p-4 d-flex flex-column justify-content-center">
                                    <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                                        <h5 class="fw-bold text-dark mb-0 me-2">
                                            {{ $order->product->name ?? 'Produk Tidak Ditemukan' }}
                                        </h5>
                                        <span class="admin-status-badge admin-status-{{ $statusClass }}">
                                            <i class="bi bi-circle-fill small me-1"></i> {{ strtoupper($statusLabel) }}
                                        </span>
                                        <span
                                            class="badge {{ $order->source == 'offline' ? 'bg-dark' : 'bg-success' }} rounded-pill px-3 py-2">
                                            {{ strtoupper($order->source) }}
                                        </span>
                                    </div>

                                    <div class="row mt-3 admin-order-meta-grid">
                                        <div class="col-sm-6 mb-3">
                                            <div class="d-flex align-items-start">
                                                <i class="bi bi-person-circle text-muted fs-5 me-2"></i>
                                                <div>
                                                    <p class="text-muted small mb-0">Pelanggan</p>
                                                    <p class="fw-semibold text-dark mb-0">{{ $customerLabel }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 mb-3">
                                            <div class="d-flex align-items-start">
                                                <i class="bi bi-calendar-range text-muted fs-5 me-2"></i>
                                                <div>
                                                    <p class="text-muted small mb-0">Periode Sewa ({{ $order->rent_days }}
                                                        Hari)</p>
                                                    <p class="fw-semibold text-dark mb-0">
                                                        {{ \Carbon\Carbon::parse($order->start_date)->format('d M') }} -
                                                        {{ \Carbon\Carbon::parse($order->end_date)->format('d M Y') }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 mb-3 mb-sm-0">
                                            <div class="d-flex align-items-start">
                                                <i class="bi bi-credit-card text-muted fs-5 me-2"></i>
                                                <div>
                                                    <p class="text-muted small mb-0">Status Pembayaran</p>
                                                    @if ($order->payment_status === 'paid')
                                                        <p class="fw-bold text-success mb-0"><i
                                                                class="bi bi-check2-circle"></i> Sudah Dibayar</p>
                                                    @elseif ($order->payment_status === 'pending')
                                                        <p class="fw-bold text-danger mb-0"><i class="bi bi-x-circle"></i>
                                                            Belum Dibayar</p>
                                                    @else
                                                        <p class="fw-bold text-danger mb-0"><i class="bi bi-x-circle"></i>
                                                            Status Pembayaran Bermasalah</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="d-flex align-items-start">
                                                <i class="bi bi-tags text-muted fs-5 me-2"></i>
                                                <div>
                                                    <p class="text-muted small mb-0">Total Harga</p>
                                                    <h5 class="fw-bold text-dark mb-0">
                                                        Rp{{ number_format($order->total_price, 0, ',', '.') }}</h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="col-md-3 col-lg-3 d-flex flex-column justify-content-center bg-light bg-opacity-50 p-4 border-start">
                                    <a href="{{ route('pegawai.orders.show', $order->id) }}"
                                        class="btn btn-outline-dark rounded-3 mb-2 w-100 fw-semibold">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>

                                    @if ($status === 'pending')
                                        @if ($canApproveOrder)
                                            <button class="btn btn-primary rounded-3 mb-2 w-100 fw-semibold shadow-sm"
                                                data-bs-toggle="modal" data-bs-target="#approveModal{{ $order->id }}">
                                                <i class="bi bi-check-lg"></i> Setujui
                                            </button>
                                        @else
                                            <div class="alert alert-warning rounded-3 small mb-2">
                                                <i class="bi bi-hourglass-split me-1"></i> {{ $paymentApprovalInfo }}
                                            </div>
                                        @endif

                                        <form action="{{ route('pegawai.orders.reject', $order->id) }}" method="POST"
                                            data-confirm data-confirm-title="Tolak pesanan?"
                                            data-confirm-message="Pesanan #{{ $order->id }} akan dibatalkan dan tidak bisa diproses sebagai sewa aktif."
                                            data-confirm-label="Tolak Pesanan">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-danger rounded-3 w-100 fw-semibold shadow-sm">
                                                <i class="bi bi-x-lg"></i> Batalkan
                                            </button>
                                        </form>

                                        @if ($canApproveOrder)
                                            <div class="modal fade" id="approveModal{{ $order->id }}" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content rounded-4 border-0 shadow">
                                                        <form action="{{ route('pegawai.orders.approve', $order->id) }}"
                                                            method="POST" enctype="multipart/form-data">
                                                            @csrf
                                                            @method('PATCH')
                                                            <div class="modal-header border-bottom-0 pb-0">
                                                                <h5 class="modal-title fw-bold">
                                                                    {{ $isOnlineOrder ? 'Setujui Pesanan Online' : 'Unggah Bukti Transaksi' }}
                                                                </h5>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal"
                                                                    aria-label="Tutup persetujuan pesanan"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                @if ($isOnlineOrder)
                                                                    <p class="text-muted small mb-3">
                                                                        Pembayaran sudah selesai. Bukti pembayaran online
                                                                        berasal dari payment reference, payload gateway,
                                                                        dan waktu pembayaran.
                                                                    </p>
                                                                    <div class="bg-light rounded-3 p-3 small">
                                                                        <div class="d-flex justify-content-between gap-3 mb-2">
                                                                            <span class="text-muted">Reference</span>
                                                                            <span class="fw-semibold text-end">{{ $order->payment_reference ?? '-' }}</span>
                                                                        </div>
                                                                        <div class="d-flex justify-content-between gap-3">
                                                                            <span class="text-muted">Dibayar pada</span>
                                                                            <span class="fw-semibold text-end">
                                                                                {{ $order->paid_at?->format('d M Y, H:i') ?? '-' }}
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                @else
                                                                    <p class="text-muted small mb-3">Silakan unggah bukti bahwa
                                                                        barang sudah disetujui untuk disewa atau diambil.</p>
                                                                    <input type="file" name="bukti" class="form-control mb-2"
                                                                        accept="image/*" capture="environment"
                                                                        required onchange="preview{{ $order->id }}(event)"
                                                                        aria-label="Unggah bukti transaksi pesanan {{ $order->id }}">
                                                                    <div class="admin-form-help mb-3">Format JPG, JPEG, PNG, atau WEBP. Maksimal 10 MB.</div>
                                                                    @error('bukti')
                                                                        <div class="admin-field-error mb-3">{{ $message }}</div>
                                                                    @enderror

                                                                    <div class="text-center bg-light rounded-3 p-2 admin-upload-preview">
                                                                        <img id="img{{ $order->id }}" class="rounded"
                                                                            alt="Pratinjau bukti transaksi" width="320" height="240"
                                                                            decoding="async">
                                                                        <span id="placeholder{{ $order->id }}"
                                                                            class="text-muted d-block mt-5"><i
                                                                                class="bi bi-image fs-3"></i><br>Pratinjau
                                                                            Bukti</span>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            <div class="modal-footer border-top-0 pt-0">
                                                                <button type="button" class="btn btn-light rounded-3"
                                                                    data-bs-dismiss="modal">Tutup</button>
                                                                <button type="submit"
                                                                    class="btn btn-primary rounded-3 px-4">Setujui</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            @unless ($isOnlineOrder)
                                                <script>
                                                    function preview{{ $order->id }}(e) {
                                                        let img = document.getElementById('img{{ $order->id }}');
                                                        let placeholder = document.getElementById('placeholder{{ $order->id }}');
                                                        img.src = URL.createObjectURL(e.target.files[0]);
                                                        img.style.display = 'block';
                                                        placeholder.style.display = 'none';
                                                    }
                                                </script>
                                            @endunless
                                        @endif
                                    @endif
                                </div>

                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="admin-pagination">
                {{ $orders->links('pagination::bootstrap-5') }}
            </div>
        @else
            <div class="admin-empty-state">
                <i class="bi bi-inbox text-muted opacity-25 d-block mb-3"></i>
                <h4 class="fw-bold mt-3 text-dark">Belum ada pesanan</h4>
                <p class="text-muted">Pesanan yang masuk akan ditampilkan di halaman ini.</p>
            </div>
        @endif
    </div>
@endsection
