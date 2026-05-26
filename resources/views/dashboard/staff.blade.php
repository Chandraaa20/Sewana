@extends('layouts.admin')

@section('title', 'Dashboard Pegawai - Sewana')
@section('meta_description', 'Dashboard pegawai Sewana untuk memantau pesanan aktif, status operasional, dan inventaris.')

@section('content')
    @php
        $summaryCards = [
            [
                'label' => 'Pesanan Aktif',
                'value' => $data['active_orders'] ?? 0,
                'icon' => 'cart-check',
                'tone' => 'primary',
                'description' => 'Butuh diproses atau dipantau',
            ],
            [
                'label' => 'Pesanan Selesai',
                'value' => $data['closed_orders'] ?? 0,
                'icon' => 'check2-circle',
                'tone' => 'success',
                'description' => 'Dikembalikan dan dibatalkan',
            ],
            [
                'label' => 'Produk Aktif',
                'value' => $data['active_products'] ?? 0,
                'icon' => 'box-seam',
                'tone' => 'info',
                'description' => 'Tersedia di katalog',
            ],
        ];

        $operationStats = [
            ['label' => 'Total Produk', 'value' => $data['products'] ?? 0, 'icon' => 'boxes', 'tone' => 'primary'],
            ['label' => 'Total Pesanan', 'value' => $data['orders'] ?? 0, 'icon' => 'receipt', 'tone' => 'success'],
            ['label' => 'Pelanggan', 'value' => $data['penyewa'] ?? 0, 'icon' => 'people', 'tone' => 'info'],
            ['label' => 'Pegawai', 'value' => $data['pegawai'] ?? 0, 'icon' => 'person-badge', 'tone' => 'neutral'],
        ];
    @endphp

    <div class="dashboard-container staff-dashboard">
        <section class="staff-hero">
            <div>
                <span class="staff-eyebrow">Area Pegawai</span>
                <h1 class="staff-title">Dashboard Pegawai</h1>
                <p class="staff-subtitle">Pantau pesanan aktif, status operasional, dan inventaris yang perlu diproses.</p>
            </div>

            <div class="staff-date-card">
                <i class="bi bi-calendar-event"></i>
                <div>
                    <span>Hari ini</span>
                    <strong>{{ now()->translatedFormat('l, d F Y') }}</strong>
                </div>
            </div>
        </section>

        <section class="row g-4 mb-4">
            @foreach ($summaryCards as $card)
                <div class="col-12 col-md-4">
                    <div class="staff-summary-card tone-{{ $card['tone'] }}">
                        <div class="staff-card-top">
                            <div class="staff-icon">
                                <i class="bi bi-{{ $card['icon'] }}"></i>
                            </div>
                            <span class="staff-card-label">{{ $card['label'] }}</span>
                        </div>
                        <div class="staff-card-value">{{ number_format($card['value']) }}</div>
                        <p class="staff-card-description">{{ $card['description'] }}</p>
                    </div>
                </div>
            @endforeach
        </section>

        <section class="staff-content-grid">
            <div class="staff-panel staff-orders-panel">
                <div class="staff-section-header">
                    <div>
                        <span class="staff-eyebrow">Prioritas</span>
                        <h2>Pesanan Terkini</h2>
                    </div>
                    <a href="{{ route('pegawai.orders.all') }}" class="staff-link-button">
                        Lihat Semua
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="staff-order-list">
                    @forelse($orders as $order)
                        @php
                            $statusData = match ($order->order_status) {
                                'pending' => ['tone' => 'warning', 'label' => 'Menunggu', 'icon' => 'clock-history'],
                                'approved' => ['tone' => 'primary', 'label' => 'Disetujui', 'icon' => 'hand-thumbs-up'],
                                'rented' => ['tone' => 'info', 'label' => 'Sedang Disewa', 'icon' => 'arrow-repeat'],
                                'returned' => ['tone' => 'success', 'label' => 'Dikembalikan', 'icon' => 'check-circle'],
                                'cancelled' => ['tone' => 'danger', 'label' => 'Dibatalkan', 'icon' => 'x-circle'],
                                default => ['tone' => 'secondary', 'label' => '-', 'icon' => 'dash-circle'],
                            };

                            $customerName = $order->renter_name ?? ($order->customer_name ?? ($order->user->name ?? 'Tidak Diketahui'));
                            $productName = $order->product->name ?? '-';
                        @endphp

                        <a href="{{ route('pegawai.orders.show', $order->id) }}" class="staff-order-item">
                            <div class="staff-order-avatar">
                                {{ strtoupper(substr($customerName, 0, 1)) }}
                            </div>

                            <div class="staff-order-main">
                                <div class="staff-order-title-row">
                                    <h3>{{ $customerName }}</h3>
                                    <span class="staff-status-pill status-{{ $statusData['tone'] }}">
                                        <i class="bi bi-{{ $statusData['icon'] }}"></i>
                                        {{ $statusData['label'] }}
                                    </span>
                                </div>

                                <div class="staff-order-meta">
                                    <span><i class="bi bi-hash"></i> Pesanan {{ $order->id }}</span>
                                    <span><i class="bi bi-box"></i>
                                        {{ \Illuminate\Support\Str::limit($productName, 32) }}</span>
                                    <span><i class="bi bi-calendar3"></i>
                                        {{ optional($order->created_at)->format('d M Y') }}</span>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="staff-empty-state">
                            <div class="staff-empty-icon">
                                <i class="bi bi-inbox"></i>
                            </div>
                            <h3>Belum ada pesanan aktif</h3>
                            <p>Pesanan pending, disetujui, atau sedang disewa akan muncul di sini.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <aside class="staff-panel staff-side-panel">
                <div class="staff-section-header compact">
                    <div>
                        <span class="staff-eyebrow">Operasional</span>
                        <h2>Ringkasan</h2>
                    </div>
                </div>

                <div class="staff-stat-list">
                    @foreach ($operationStats as $stat)
                        <div class="staff-stat-item tone-{{ $stat['tone'] }}">
                            <div class="staff-stat-icon">
                                <i class="bi bi-{{ $stat['icon'] }}"></i>
                            </div>
                            <div>
                                <span>{{ $stat['label'] }}</span>
                                <strong>{{ number_format($stat['value']) }}</strong>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="staff-quick-actions">
                    <span class="staff-eyebrow">Aksi Cepat</span>
                    <a href="{{ route('pegawai.orders.index') }}" class="staff-action-link">
                        <i class="bi bi-cart-check"></i>
                        Kelola Pesanan
                    </a>
                    <a href="{{ route('pegawai.products.index') }}" class="staff-action-link">
                        <i class="bi bi-box-seam"></i>
                        Inventaris Produk
                    </a>
                </div>
            </aside>
        </section>
    </div>
@endsection
