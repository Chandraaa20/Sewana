<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;

class TransactionVerificationController extends Controller
{
    public function __invoke(string $token): View
    {
        $order = Order::with(['product'])
            ->where('validation_token', $token)
            ->first();

        return view('orders.verify_transaction', compact('order'));
    }
}
