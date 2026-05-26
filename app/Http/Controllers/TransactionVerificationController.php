<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
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

    public function scanner(): View
    {
        return view('orders.scan_transaction');
    }

    public function resolveForStaff(string $token): RedirectResponse
    {
        $order = Order::where('validation_token', $token)->first();

        if (! $order) {
            return redirect()
                ->route('pegawai.transactions.scanner')
                ->with('error', 'Token validasi transaksi tidak ditemukan atau tidak valid.');
        }

        return redirect()->route('pegawai.orders.show', $order->id);
    }
}
