<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user) {
            abort(403, 'Tidak berwenang');
        }

        // ================= PEMILIK =================
        if ($user->hasRole('pemilik')) {
            $data = [
                'products'  => Product::count(),
                'variants'  => ProductVariant::count(),
                'users'     => User::count(),
                'orders'    => Order::count(),
                'pemilik'   => User::role('pemilik')->count(),
                'pegawai'   => User::role('pegawai')->count(),
                'penyewa'   => User::role('penyewa')->count(),
            ];

            $latestOrders = Order::with(['user', 'product'])
                ->latest()
                ->take(5)
                ->get();

            return view('dashboard.index', compact('data', 'latestOrders'));
        }

        // ================= PEGAWAI =================
        if ($user->hasRole('pegawai')) {
            $data = [
                'orders' => Order::count(),

                'active_orders' => Order::whereIn('order_status', [
                    'pending',
                    'approved',
                    'rented'
                ])->count(),

                'finished_orders' => Order::whereIn('order_status', [
                    'returned',
                    'cancelled'
                ])->count(),

                'products' => Product::count(),
                'active_products' => Product::where('status', 'active')->count(),
                'penyewa' => User::role('penyewa')->count(),
                'pegawai' => User::role('pegawai')->count(),
            ];

            $orders = Order::with(['user', 'product'])
                ->whereIn('order_status', ['pending', 'approved', 'rented'])
                ->latest()
                ->take(10)
                ->get();

            return view('dashboard.staff', compact('data', 'orders'));
        }

        // ================= PENYEWA =================
        if ($user->hasRole('penyewa')) {
            $customerData = [
                'total_orders' => $user->orders()->count(),

                'active_orders' => $user->orders()
                    ->whereIn('order_status', ['pending', 'approved', 'rented'])
                    ->count(),

                'finished_orders' => $user->orders()
                    ->whereIn('order_status', ['returned', 'cancelled'])
                    ->count(),

                'popular_products' => Product::with(['images', 'variants'])
                    ->where('status', 'active')
                    ->whereHas('variants', function ($q) {
                        $q->where('stock', '>', 0)
                            ->where('status', 'tersedia');
                    })
                    ->withCount('orders')
                    ->orderByDesc('orders_count')
                    ->limit(8)
                    ->get(),
            ];

            return view('dashboard.customer', compact('customerData'));
        }

        abort(403, 'Peran tidak dikenali.');
    }
}
