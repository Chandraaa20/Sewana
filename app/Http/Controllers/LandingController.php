<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\View\View;

class LandingController extends Controller
{
    public function __invoke(): View
    {
        $products = Product::with('images')
            ->where('status', 'active')
            ->whereHas('variants', function ($query) {
                $query->where('stock', '>', 0)
                    ->where('status', 'tersedia');
            })
            ->take(8)
            ->get();

        return view('landing', [
            'products' => $products,
            'productCount' => Product::count(),
            'orderCount' => Order::count(),
            'userCount' => User::count(),
        ]);
    }
}
