<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Sewana')</title>
    <meta name="description" content="@yield('meta_description', 'Kelola dashboard, produk, dan pesanan Sewana sesuai hak akses akun.')">
    <link rel="icon" type="image/svg+xml" href="{{ asset('sewana-favicon.svg') }}">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    {{-- CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    @vite([
        'resources/css/app.css',
        'resources/css/admin.css',
        'resources/css/admin/page-components.css',
        'resources/css/admin/dashboard.css',
        'resources/css/admin/products.css',
        'resources/css/admin/orders.css',
        'resources/js/app.js',
    ])
</head>

<body class="admin-body">
    @php
        $roleName = auth()->check() ? auth()->user()->getRoleNames()->first() : null;
        $areaTitle = match ($roleName) {
            'pemilik' => 'Area Pemilik',
            'pegawai' => 'Area Pegawai',
            'penyewa' => 'Area Penyewa',
            default => 'Area Sewana',
        };
    @endphp

    <div class="admin-shell">
        <aside class="sidebar admin-sidebar offcanvas-lg offcanvas-start shadow" tabindex="-1" id="adminSidebar"
            aria-labelledby="adminSidebarLabel">
            <div class="offcanvas-header admin-sidebar-mobile-header d-lg-none">
                <a href="{{ route('dashboard') }}" class="sidebar-brand" id="adminSidebarLabel">
                    SEWANA<span class="text-primary">.</span>
                </a>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                    data-bs-target="#adminSidebar" aria-label="Tutup menu"></button>
            </div>

            <div class="offcanvas-body admin-sidebar-body">
                <a href="{{ route('dashboard') }}" class="sidebar-brand d-none d-lg-flex">
                    SEWANA<span class="text-primary">.</span>
                </a>

                <div class="sidebar-user-card">
                    @auth
                        <div class="sidebar-user-avatar">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <div class="sidebar-user-name">{{ Auth::user()->name }}</div>
                            <div class="sidebar-user-role">{{ strtoupper($roleName ?? '-') }}</div>
                        </div>
                    @endauth
                </div>

                <ul class="nav nav-pills flex-column sidebar-nav">
                    @auth
                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}"
                                class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i class="bi bi-grid-1x2-fill"></i>
                                <span>Ringkasan</span>
                            </a>
                        </li>

                        {{-- MENU UNTUK PEMILIK --}}
                        @if (auth()->user()->hasRole('pemilik'))
                            <li class="sidebar-section-title">Administrasi</li>
                            <li class="nav-item">
                                <a href="{{ route('pemilik.users.index') }}"
                                    class="nav-link {{ request()->routeIs('pemilik.users.*') ? 'active' : '' }}">
                                    <i class="bi bi-people"></i>
                                    <span>Manajemen Pengguna</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('pemilik.reports.index') }}"
                                    class="nav-link {{ request()->routeIs('pemilik.reports.*') ? 'active' : '' }}">
                                    <i class="bi bi-graph-up-arrow"></i>
                                    <span>Laporan Sewa</span>
                                </a>
                            </li>
                        @endif

                        {{-- MENU OPERASIONAL (PEGAWAI & PEMILIK) --}}
                        @if (auth()->user()->hasAnyRole(['pegawai', 'pemilik']))
                            <li class="sidebar-section-title">Operasional</li>
                            <li class="nav-item">
                                @php $productRoute = auth()->user()->hasRole('pemilik') ? 'pemilik.products.index' : 'pegawai.products.index'; @endphp
                                <a href="{{ route($productRoute) }}"
                                    class="nav-link {{ request()->routeIs('*.products.*') ? 'active' : '' }}">
                                    <i class="bi bi-box-seam"></i>
                                    <span>Inventaris Produk</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                @php $orderRoute = auth()->user()->hasRole('pemilik') ? 'pemilik.orders.index' : 'pegawai.orders.index'; @endphp
                                <a href="{{ route($orderRoute) }}"
                                    class="nav-link {{ request()->routeIs('*.orders.index') ? 'active' : '' }}">
                                    <i class="bi bi-cart-check"></i>
                                    <span>Kelola Pesanan</span>
                                </a>
                            </li>
                            @if (auth()->user()->hasRole('pegawai'))
                                <li class="nav-item">
                                    <a href="{{ route('pegawai.orders.all') }}"
                                        class="nav-link {{ request()->routeIs('pegawai.orders.all') ? 'active' : '' }}">
                                        <i class="bi bi-list-ul"></i>
                                        <span>Semua Riwayat</span>
                                    </a>
                                </li>
                            @endif
                        @endif

                        {{-- MENU PENYEWA --}}
                        @if (auth()->user()->hasRole('penyewa'))
                            <li class="sidebar-section-title">Menu Saya</li>
                            <li class="nav-item">
                                <a href="{{ route('penyewa.products.index') }}"
                                    class="nav-link {{ request()->routeIs('penyewa.products.*') ? 'active' : '' }}">
                                    <i class="bi bi-shop"></i>
                                    <span>Menu Produk</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('penyewa.orders.index') }}"
                                    class="nav-link {{ request()->routeIs('penyewa.orders.*') ? 'active' : '' }}">
                                    <i class="bi bi-receipt"></i>
                                    <span>Pesanan Saya</span>
                                </a>
                            </li>
                        @endif
                    @endauth
                </ul>

                <div class="sidebar-footer">
                    @auth
                        <form action="{{ route('logout') }}" method="POST" id="logout-form">
                            @csrf
                            <button type="submit" class="nav-link sidebar-logout" aria-label="Keluar dari akun">
                                <i class="bi bi-box-arrow-left"></i>
                                <span>Keluar</span>
                            </button>
                        </form>
                    @endauth
                </div>
            </div>
        </aside>

        <div class="admin-main">
            <nav class="navbar admin-topbar navbar-expand-lg sticky-top">
                <div class="container-fluid">
                    <div class="admin-topbar-left">
                        <button class="admin-menu-toggle d-lg-none" type="button" data-bs-toggle="offcanvas"
                            data-bs-target="#adminSidebar" aria-controls="adminSidebar" aria-label="Buka menu">
                            <i class="bi bi-list"></i>
                        </button>

                        <div>
                            <span class="admin-topbar-eyebrow">Dashboard</span>
                            <div class="admin-topbar-title" aria-label="{{ $areaTitle }}">{{ $areaTitle }}</div>
                        </div>
                    </div>

                    @auth
                        <div class="admin-topbar-user">
                            <div class="admin-user-meta d-none d-md-block">
                                <div class="admin-user-name">{{ Auth::user()->name }}</div>
                                <div class="admin-user-role">{{ strtoupper($roleName ?? '-') }}</div>
                            </div>
                            <div class="user-profile-img">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                        </div>
                    @endauth
                </div>
            </nav>

            <main class="admin-content">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm mb-4"
                        role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                    </div>
                @elseif (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>

</html>
