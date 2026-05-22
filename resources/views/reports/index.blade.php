@extends('layouts.admin')

@section('content')
    <div class="admin-page admin-page--wide">

        {{-- Header and print button --}}
        <div class="admin-page-header flex-column flex-md-row align-items-md-center">
            <div>
                <span class="admin-page-eyebrow">Laporan</span>
                <h1 class="admin-page-title"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Laporan Bisnis Sewana</h1>
                <p class="admin-page-subtitle">Pantau performa penyewaan dan pendapatan Anda.</p>
            </div>
            <button onclick="window.print()" class="btn btn-outline-dark rounded-pill d-print-none shadow-sm px-4">
                <i class="bi bi-printer me-2"></i> Cetak Laporan
            </button>
        </div>

        {{-- Date filter --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4 d-print-none filter-card">
            <div class="card-body p-4">
                <form method="GET" class="row g-3 search-form">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold small text-muted text-uppercase">Tanggal Mulai</label>
                        <input type="date" name="start_date" value="{{ $start->format('Y-m-d') }}"
                            class="form-control border-light-subtle shadow-none">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold small text-muted text-uppercase">Tanggal Akhir</label>
                        <input type="date" name="end_date" value="{{ $end->format('Y-m-d') }}"
                            class="form-control border-light-subtle shadow-none">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm search-button">
                            <i class="bi bi-filter me-1"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>


        {{-- Summary metrics --}}
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="admin-stat-card h-100">
                    <div class="text-muted small fw-bold text-uppercase mb-2">Total Transaksi</div>
                    <h3 class="fw-bold mb-0">{{ number_format($totalOrders) }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="admin-stat-card h-100 bg-primary text-white">
                    <div class="small fw-bold text-uppercase mb-2 opacity-75">Total Pendapatan</div>
                    <h3 class="fw-bold mb-0">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="admin-stat-card h-100">
                    <div class="text-muted small fw-bold text-uppercase mb-2">Sedang Disewa</div>
                    <h3 class="fw-bold mb-0">{{ number_format($activeRentals) }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="admin-stat-card h-100">
                    <div class="text-muted small fw-bold text-uppercase mb-2">Selesai/Kembali</div>
                    <h3 class="fw-bold mb-0">{{ number_format($returnedOrders) }}</h3>
                </div>
            </div>
        </div>

        <div class="row g-4">
            {{-- Transaction chart --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-bar-chart-line text-primary me-2"></i>Grafik Transaksi Bulanan</h5>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <canvas id="ordersChart" class="report-chart"></canvas>
                    </div>
                </div>
            </div>

            {{-- Top products --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-trophy text-primary me-2"></i>Produk Terlaris</h5>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="small text-muted text-uppercase">
                                    <tr>
                                        <th>Nama Produk</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($topProducts as $p)
                                        <tr>
                                            <td class="fw-medium">{{ $p->product->name ?? 'Produk Dihapus' }}</td>
                                            <td class="text-end">
                                                <span
                                                    class="badge bg-light text-primary rounded-pill px-3">{{ $p->total }}x</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center py-5 text-muted small">Belum ada data
                                                transaksi</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Print-specific CSS --}}
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Indonesian month names.
        const monthNames = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September",
            "Oktober", "November", "Desember"
        ];

        const ctx = document.getElementById('ordersChart').getContext('2d');
        const ordersChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [
                    @foreach ($monthlyOrders as $m)
                        monthNames[{{ $m->month }}],
                    @endforeach
                ],
                datasets: [{
                    label: 'Jumlah Transaksi',
                    data: [
                        @foreach ($monthlyOrders as $m)
                            {{ $m->total }},
                        @endforeach
                    ],
                    backgroundColor: 'rgba(13, 110, 253, 0.7)',
                    borderColor: 'rgb(13, 110, 253)',
                    borderWidth: 1,
                    borderRadius: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: false
                        },
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
@endsection

