<?php

namespace App\Services;

use App\Models\Order;

class PaymentGatewayService
{
    public function createPayment(Order $order): array
    {
        $reference = sprintf('DUMMY-%s-%s', $order->id, now()->timestamp);

        return [
            'payment_url' => route('penyewa.orders.payment.instructions', $order->id),
            'reference' => $reference,
            'gateway' => 'dummy',
        ];
    }

    public function markPaymentPaid(Order $order, array $payload = []): Order
    {
        $order->update([
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'paid_at' => now(),
            'payment_payload' => $payload ?: $order->payment_payload,
        ]);

        return $order;
    }

    public function markPaymentFailed(Order $order, array $payload = []): Order
    {
        $order->update([
            'payment_status' => Order::PAYMENT_STATUS_FAILED,
            'payment_payload' => $payload ?: $order->payment_payload,
        ]);

        return $order;
    }

    public function markPaymentExpired(Order $order, array $payload = []): Order
    {
        $order->update([
            'payment_status' => Order::PAYMENT_STATUS_EXPIRED,
            'payment_payload' => $payload ?: $order->payment_payload,
        ]);

        return $order;
    }

    public function markPaymentCancelled(Order $order, array $payload = []): Order
    {
        return $this->markPaymentFailed($order, $payload);
    }
}
