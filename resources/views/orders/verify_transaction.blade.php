<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Transaksi - Sewana</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('sewana-favicon.svg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])
</head>

<body class="bg-light">
    @php
        $orderStatusLabels = [
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rented' => 'Sedang Disewa',
            'returned' => 'Dikembalikan',
            'cancelled' => 'Dibatalkan',
        ];

        $paymentStatusLabels = [
            'pending' => 'Belum Dibayar',
            'paid' => 'Sudah Dibayar',
            'failed' => 'Pembayaran Gagal',
            'expired' => 'Pembayaran Kedaluwarsa',
        ];
    @endphp

    <main class="container py-5">
        <div class="mx-auto" style="max-width: 720px;">
            <div class="mb-4">
                <a href="{{ url('/') }}" class="text-decoration-none fw-bold text-dark">SEWANA</a>
            </div>

            @if ($order)
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 p-md-5">
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill mb-3">
                            Transaksi Valid
                        </span>
                        <h1 class="h3 fw-bold text-dark mb-2">Data transaksi ditemukan</h1>
                        <p class="text-muted mb-4">Ringkasan ini hanya menampilkan informasi transaksi non-sensitif.</p>

                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <tbody>
                                    <tr>
                                        <th class="text-muted fw-semibold">Kode Order</th>
                                        <td class="text-end fw-bold">#{{ $order->id }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-semibold">Nama Penyewa</th>
                                        <td class="text-end">{{ $order->renter_name ?: ($order->customer_name ?: '-') }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-semibold">Nama Produk</th>
                                        <td class="text-end">{{ $order->product->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-semibold">Status Pembayaran</th>
                                        <td class="text-end">{{ $paymentStatusLabels[$order->payment_status] ?? 'Tidak Diketahui' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-semibold">Status Sewa</th>
                                        <td class="text-end">{{ $orderStatusLabels[$order->order_status] ?? 'Tidak Diketahui' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-semibold">Tanggal Sewa</th>
                                        <td class="text-end">{{ \Carbon\Carbon::parse($order->start_date)->format('d M Y') }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-semibold">Tanggal Kembali</th>
                                        <td class="text-end">{{ \Carbon\Carbon::parse($order->end_date)->format('d M Y') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 p-md-5 text-center">
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill mb-3">
                            Transaksi Tidak Valid
                        </span>
                        <h1 class="h3 fw-bold text-dark mb-2">Token transaksi tidak ditemukan</h1>
                        <p class="text-muted mb-0">Pastikan tautan verifikasi yang digunakan sudah benar.</p>
                    </div>
                </div>
            @endif
        </div>
    </main>
</body>

</html>
