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
        $paymentPayload = is_array($order->payment_payload) ? $order->payment_payload : [];
        $paymentUrl = data_get($paymentPayload, 'payment_url')
            ?: data_get($paymentPayload, 'invoice_url')
            ?: data_get($paymentPayload, 'response.invoice_url');

        return view('orders.payment_xendit', compact('order', 'paymentUrl'));
    }

    public function simulateSuccess(int $id, PaymentGatewayService $paymentGateway): RedirectResponse
    {
        abort_unless(app()->environment(['local', 'development', 'testing']), 404);

        $order = Order::where('user_id', Auth::id())
            ->where('source', 'online')
            ->findOrFail($id);

        if (! in_array($order->order_status, [Order::ORDER_STATUS_PENDING, Order::ORDER_STATUS_APPROVED], true)) {
            return redirect()
                ->route('penyewa.orders.show', $order->id)
                ->with('error', 'Pembayaran hanya bisa dikonfirmasi untuk pesanan yang masih pending atau disetujui.');
        }

        if ($order->payment_status !== Order::PAYMENT_STATUS_PAID) {
            $paymentGateway->markPaymentPaid($order, [
                'type' => 'local_payment_fallback',
                'provider' => 'local_fallback',
                'status' => 'success',
                'simulated_at' => now()->toISOString(),
                'environment' => app()->environment(),
                'reference' => $order->payment_reference,
            ]);
        }

        return redirect()
            ->route('penyewa.orders.show', $order->id)
            ->with('success', 'Fallback lokal pembayaran berhasil. Pesanan tetap menunggu persetujuan pegawai.');
    }
}
