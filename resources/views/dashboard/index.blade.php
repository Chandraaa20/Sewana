@extends('layouts.admin')

@section('title', 'Dashboard Pemilik - Sewana')
@section('meta_description', 'Ringkasan bisnis Sewana untuk memantau transaksi, inventaris, dan pengguna.')

@section('content')
    @php
        $summaryCards = [
            [
                'label' => 'Produk',
                'value' => $data['products'] ?? 0,
                'icon' => 'box-seam',
                'tone' => 'primary',
                'description' => 'Total produk terdaftar',
            ],
            [
                'label' => 'Pesanan',
                'value' => $data['orders'] ?? 0,
                'icon' => 'bag-check',
                'tone' => 'success',
                'description' => 'Semua transaksi sewa',
            ],
            [
                'label' => 'Pelanggan',
                'value' => $data['penyewa'] ?? 0,
                'icon' => 'people',
                'tone' => 'info',
                'description' => 'Akun penyewa aktif',
            ],
            [
                'label' => 'Staf',
                'value' => $data['pegawai'] ?? 0,
                'icon' => 'person-badge',
                'tone' => 'neutral',
                'description' => 'Pegawai operasional',
            ],
        ];

        $userStats = [
            ['label' => 'Pemilik', 'value' => $data['pemilik'] ?? 0, 'icon' => 'shield-check', 'tone' => 'primary'],
            ['label' => 'Pegawai', 'value' => $data['pegawai'] ?? 0, 'icon' => 'person-badge', 'tone' => 'success'],
            ['label' => 'Pelanggan', 'value' => $data['penyewa'] ?? 0, 'icon' => 'people', 'tone' => 'info'],
            ['label' => 'Total Pengguna', 'value' => $data['users'] ?? 0, 'icon' => 'person-lines-fill', 'tone' => 'neutral'],
        ];
    @endphp

    <div class="dashboard-container owner-dashboard">
        <section class="owner-hero">
            <div>
                <span class="owner-eyebrow">Pemilik Area</span>
                <h1 class="owner-title">Dashboard Pemilik</h1>
                <p class="owner-subtitle">Ringkasan bisnis Sewana untuk memantau transaksi, inventaris, dan pengguna.</p>
            </div>

            <div class="owner-hero-actions">
                <a href="{{ route('pemilik.reports.index') }}" class="owner-primary-action">
                    <i class="bi bi-graph-up-arrow"></i>
                    Lihat Laporan
                </a>
                <div class="owner-date-card">
                    <i class="bi bi-calendar-event"></i>
                    <div>
                        <span>Hari ini</span>
                        <strong>{{ now()->translatedFormat('l, d F Y') }}</strong>
                    </div>
                </div>
            </div>
        </section>

        <section class="row g-4 mb-4">
            @foreach ($summaryCards as $card)
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="owner-summary-card tone-{{ $card['tone'] }}">
                        <div class="owner-card-top">
                            <div class="owner-icon">
                                <i class="bi bi-{{ $card['icon'] }}"></i>
                            </div>
                            <span class="owner-card-label">{{ $card['label'] }}</span>
                        </div>
                        <div class="owner-card-value">{{ number_format($card['value']) }}</div>
                        <p class="owner-card-description">{{ $card['description'] }}</p>
                    </div>
                </div>
            @endforeach
        </section>

        <section class="owner-content-grid">
            <div class="owner-panel owner-orders-panel">
                <div class="owner-section-header">
                    <div>
                        <span class="owner-eyebrow">Aktivitas Terbaru</span>
                        <h2>Pesanan Terkini</h2>
                    </div>
                    <a href="{{ route('pemilik.orders.index') }}" class="owner-link-button">
                        Lihat Semua
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="owner-order-list">
                    @forelse($latestOrders as $order)
                        @php
                            $statusData = match ($order->order_status) {
                                'pending' => ['tone' => 'warning', 'label' => 'Menunggu', 'icon' => 'clock-history'],
                                'approved' => ['tone' => 'primary', 'label' => 'Disetujui', 'icon' => 'hand-thumbs-up'],
                                'rented' => ['tone' => 'info', 'label' => 'Sedang Disewa', 'icon' => 'arrow-repeat'],
                                'returned' => ['tone' => 'success', 'label' => 'Dikembalikan', 'icon' => 'check-circle'],
                                'cancelled' => ['tone' => 'danger', 'label' => 'Dibatalkan', 'icon' => 'x-circle'],
                                default => ['tone' => 'secondary', 'label' => $order->order_status ?? '-', 'icon' => 'dash-circle'],
                            };

                            $customerName = $order->renter_name ?? ($order->customer_name ?? ($order->user->name ?? 'Tamu'));
                            $productName = $order->product->name ?? '-';
                        @endphp

                        <a href="{{ route('pegawai.orders.show', $order->id) }}" class="owner-order-item">
                            <div class="owner-order-id">#{{ $order->id }}</div>

                            <div class="owner-order-avatar">
                                {{ strtoupper(substr($customerName, 0, 1)) }}
                            </div>

                            <div class="owner-order-main">
                                <div class="owner-order-title-row">
                                    <h3>{{ $customerName }}</h3>
                                    <span class="owner-status-pill status-{{ $statusData['tone'] }}">
                                        <i class="bi bi-{{ $statusData['icon'] }}"></i>
                                        {{ $statusData['label'] }}
                                    </span>
                                </div>

                                <div class="owner-order-meta">
                                    <span><i class="bi bi-box"></i> {{ \Illuminate\Support\Str::limit($productName, 34) }}</span>
                                    <span><i class="bi bi-clock"></i> {{ optional($order->created_at)->diffForHumans() }}</span>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="owner-empty-state">
                            <div class="owner-empty-icon">
                                <i class="bi bi-inbox"></i>
                            </div>
                            <h3>Belum ada pesanan masuk</h3>
                            <p>Transaksi terbaru akan tampil di area ini.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <aside class="owner-panel owner-side-panel">
                <div class="owner-section-header compact">
                    <div>
                        <span class="owner-eyebrow">Pengguna</span>
                        <h2>Komposisi Akun</h2>
                    </div>
                </div>

                <div class="owner-stat-list">
                    @foreach ($userStats as $stat)
                        <div class="owner-stat-item tone-{{ $stat['tone'] }}">
                            <div class="owner-stat-icon">
                                <i class="bi bi-{{ $stat['icon'] }}"></i>
                            </div>
                            <div>
                                <span>{{ $stat['label'] }}</span>
                                <strong>{{ number_format($stat['value']) }}</strong>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="owner-quick-actions">
                    <span class="owner-eyebrow">Aksi Cepat</span>
                    <a href="{{ route('pemilik.users.index') }}" class="owner-action-link">
                        <i class="bi bi-people"></i>
                        Manajemen Pengguna
                    </a>
                    <a href="{{ route('pemilik.reports.index') }}" class="owner-action-link">
                        <i class="bi bi-graph-up-arrow"></i>
                        Laporan Sewa
                    </a>
                    <a href="{{ route('pemilik.products.index') }}" class="owner-action-link">
                        <i class="bi bi-box-seam"></i>
                        Inventaris Produk
                    </a>
                </div>
            </aside>
        </section>
    </div>
@endsection
