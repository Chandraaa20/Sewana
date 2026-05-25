<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaymentGatewayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class PaymentInstructionController extends Controller
{
    public function show(int $id): View
    {
        $order = Order::with(['product'])
            ->where('user_id', Auth::id())
            ->where('source', 'online')
            ->findOrFail($id);

        return view('orders.payment_dummy', compact('order'));
    }

    public function simulateSuccess(int $id, PaymentGatewayService $paymentGateway): RedirectResponse
    {
        abort_unless(app()->environment(['local', 'development', 'testing']), 404);

        $order = Order::where('user_id', Auth::id())
            ->where('source', 'online')
            ->findOrFail($id);

        if ($order->payment_status !== Order::PAYMENT_STATUS_PAID) {
            $paymentGateway->markPaymentPaid($order, [
                'type' => 'dummy_payment_simulation',
                'status' => 'success',
                'simulated_at' => now()->toISOString(),
                'environment' => app()->environment(),
                'reference' => $order->payment_reference,
            ]);
        }

        return redirect()
            ->route('penyewa.orders.show', $order->id)
            ->with('success', 'Simulasi pembayaran dummy berhasil. Pesanan tetap menunggu persetujuan pegawai.');
    }
}
