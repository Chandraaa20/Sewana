<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentGatewayService
{
    public function createPayment(Order $order, array $options = []): array
    {
        $reference = $options['reference'] ?? $this->makeReference($order, $options['prefix'] ?? 'SEWANA');
        $amount = (int) round((float) $order->total_price);
        $paymentMethods = $options['payment_methods'] ?? null;

        $payload = [
            'external_id' => $reference,
            'amount' => $amount,
            'currency' => 'IDR',
            'description' => $options['description'] ?? $this->paymentDescription($order),
            'invoice_duration' => $options['invoice_duration'] ?? 86400,
            'success_redirect_url' => $options['success_redirect_url'] ?? route('penyewa.orders.show', $order->id),
            'failure_redirect_url' => $options['failure_redirect_url'] ?? route('penyewa.orders.show', $order->id),
        ];

        if ($order->user?->email) {
            $payload['payer_email'] = $order->user->email;
        }

        if ($paymentMethods) {
            $payload['payment_methods'] = $paymentMethods;
        }

        $response = $this->postInvoice($payload);
        $responsePayload = $response->json();
        $paymentUrl = $responsePayload['invoice_url']
            ?? $responsePayload['payment_url']
            ?? $responsePayload['checkout_url']
            ?? null;

        return [
            'payment_url' => $paymentUrl,
            'reference' => $reference,
            'gateway' => 'xendit',
            'payload' => [
                'request' => $payload,
                'response' => $responsePayload,
                'invoice_id' => $responsePayload['id'] ?? null,
                'invoice_url' => $responsePayload['invoice_url'] ?? null,
                'payment_url' => $paymentUrl,
                'qr_data' => $this->extractQrData($responsePayload),
                'created_at' => now()->toISOString(),
            ],
        ];
    }

    public function markPaymentPaid(Order $order, array $payload = []): Order
    {
        if ($order->payment_status !== Order::PAYMENT_STATUS_PAID && ! $this->canReceivePaidStatus($order)) {
            return $order->fresh() ?: $order;
        }

        $order->update([
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'paid_at' => $order->paid_at ?: now(),
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

    public function handleXenditWebhook(array $payload, ?string $callbackToken): ?Order
    {
        $expectedToken = (string) config('services.xendit.callback_token');

        if ($expectedToken !== '' && ! hash_equals($expectedToken, (string) $callbackToken)) {
            throw new RuntimeException('Token callback Xendit tidak valid.', 401);
        }

        $reference = $this->extractReference($payload);

        if (! $reference) {
            throw new RuntimeException('Payload webhook Xendit tidak memiliki external_id.', 400);
        }

        $order = Order::where('payment_reference', $reference)->first();

        if (! $order) {
            return null;
        }

        $paidAmount = $this->extractAmount($payload);

        if ($paidAmount !== null) {
            $expectedAmount = (int) round((float) $order->total_price);

            if ($expectedAmount !== $paidAmount) {
                throw new RuntimeException('Nominal pembayaran Xendit tidak sesuai dengan tagihan.', 422);
            }
        }

        $newStatus = $this->mapXenditStatus($payload);
        $paymentPayload = is_array($order->payment_payload) ? $order->payment_payload : [];
        $paymentPayload['last_webhook'] = $payload;
        $paymentPayload['last_webhook_at'] = now()->toISOString();

        $updates = [
            'payment_gateway' => 'xendit',
            'payment_payload' => $paymentPayload,
        ];

        if ($newStatus) {
            $resolvedPaymentStatus = $this->resolvePaymentStatus($order, $newStatus);

            if (
                $resolvedPaymentStatus === Order::PAYMENT_STATUS_PAID
                && $order->payment_status !== Order::PAYMENT_STATUS_PAID
                && ! $this->canReceivePaidStatus($order)
            ) {
                $resolvedPaymentStatus = $order->payment_status;
            }

            $updates['payment_status'] = $resolvedPaymentStatus;

            if ($updates['payment_status'] === Order::PAYMENT_STATUS_PAID && ! $order->paid_at) {
                $updates['paid_at'] = $this->extractPaidAt($payload) ?? now();
            }
        }

        $order->update($updates);

        return $order->fresh();
    }

    private function postInvoice(array $payload)
    {
        $response = Http::withBasicAuth($this->secretKey(), '')
            ->acceptJson()
            ->asJson()
            ->post($this->invoiceEndpoint(), $payload);

        if ($response->failed()) {
            $message = $response->json('message')
                ?: $response->json('error_code')
                ?: $response->body();

            throw new RuntimeException('Gagal membuat pembayaran Xendit Sandbox: ' . $message);
        }

        return $response;
    }

    private function makeReference(Order $order, string $prefix): string
    {
        return sprintf('%s-%s-%s', $prefix, $order->id, now()->timestamp);
    }

    private function paymentDescription(Order $order): string
    {
        $productName = $order->product->name ?? 'Sewa Pakaian';

        return Str::limit('Sewana - ' . $productName . ' #' . $order->id, 255, '');
    }

    private function extractQrData(array $payload): ?array
    {
        $qrData = [];

        foreach (['qr_string', 'qr_code', 'qr_code_string', 'qr_code_url', 'qris_url'] as $key) {
            if (filled(data_get($payload, $key))) {
                $qrData[$key] = data_get($payload, $key);
            }
        }

        return $qrData ?: null;
    }

    private function extractReference(array $payload): ?string
    {
        foreach ([
            'external_id',
            'data.external_id',
            'data.data.external_id',
            'data.reference_id',
            'data.data.reference_id',
            'reference_id',
        ] as $key) {
            $value = data_get($payload, $key);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    private function extractAmount(array $payload): ?int
    {
        foreach ([
            'paid_amount',
            'amount',
            'data.paid_amount',
            'data.amount',
            'data.data.paid_amount',
            'data.data.amount',
            'data.request_amount',
            'data.data.request_amount',
        ] as $key) {
            $value = data_get($payload, $key);

            if (is_numeric($value)) {
                return (int) round((float) $value);
            }
        }

        return null;
    }

    private function extractPaidAt(array $payload): mixed
    {
        foreach ([
            'paid_at',
            'data.paid_at',
            'data.data.paid_at',
            'updated',
            'data.updated',
            'data.data.updated',
        ] as $key) {
            $value = data_get($payload, $key);

            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }

    private function mapXenditStatus(array $payload): ?string
    {
        $status = strtoupper((string) (
            data_get($payload, 'status')
            ?? data_get($payload, 'data.status')
            ?? data_get($payload, 'data.data.status')
            ?? data_get($payload, 'event')
            ?? ''
        ));

        return match ($status) {
            'PAID', 'SETTLED', 'SUCCEEDED', 'SUCCESS', 'COMPLETED', 'INVOICE.PAID', 'INVOICE.SETTLED', 'PAYMENT.SUCCEEDED', 'PAYMENT.CAPTURE' => Order::PAYMENT_STATUS_PAID,
            'PENDING' => Order::PAYMENT_STATUS_PENDING,
            'FAILED', 'FAILURE', 'CANCELLED', 'CANCELED', 'DENIED', 'VOIDED' => Order::PAYMENT_STATUS_FAILED,
            'EXPIRED', 'INVOICE.EXPIRED' => Order::PAYMENT_STATUS_EXPIRED,
            default => null,
        };
    }

    private function resolvePaymentStatus(Order $order, string $newStatus): string
    {
        if ($newStatus === Order::PAYMENT_STATUS_PAID) {
            return Order::PAYMENT_STATUS_PAID;
        }

        if ($order->payment_status === Order::PAYMENT_STATUS_PAID) {
            return Order::PAYMENT_STATUS_PAID;
        }

        return $newStatus;
    }

    private function canReceivePaidStatus(Order $order): bool
    {
        return in_array($order->order_status, [
            Order::ORDER_STATUS_PENDING,
            Order::ORDER_STATUS_APPROVED,
        ], true);
    }

    private function invoiceEndpoint(): string
    {
        return 'https://api.xendit.co/v2/invoices';
    }

    private function secretKey(): string
    {
        $secretKey = (string) config('services.xendit.secret_key');

        if ($secretKey === '') {
            throw new RuntimeException('XENDIT_SECRET_KEY belum diatur.');
        }

        return $secretKey;
    }
}
