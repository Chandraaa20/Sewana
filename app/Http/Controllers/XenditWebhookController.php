<?php

namespace App\Http\Controllers;

use App\Services\PaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class XenditWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGatewayService $paymentGateway): JsonResponse
    {
        $context = $this->safeContext($request->all());
        Log::info('Xendit webhook received.', $context);

        try {
            $order = $paymentGateway->handleXenditWebhook(
                $request->all(),
                $request->header('x-callback-token')
            );
        } catch (RuntimeException $exception) {
            $statusCode = $this->exceptionStatusCode($exception);
            Log::warning('Xendit webhook rejected.', $context + [
                'order_found' => false,
                'result' => 'rejected',
                'http_status' => $statusCode,
                'reason' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => $exception->getMessage(),
            ], $statusCode);
        }

        if (! $order) {
            Log::warning('Xendit webhook ignored because order was not found.', $context + [
                'order_found' => false,
                'result' => 'ignored',
            ]);

            return response()->json([
                'message' => 'Webhook diterima, tetapi order pembayaran tidak ditemukan.',
                'order_found' => false,
            ]);
        }

        Log::info('Xendit webhook processed.', $context + [
            'order_found' => true,
            'order_id' => $order->id,
            'payment_status' => $order->payment_status,
            'result' => 'processed',
        ]);

        return response()->json([
            'message' => 'Webhook Xendit diproses.',
            'payment_status' => $order->payment_status,
        ]);
    }

    private function safeContext(array $payload): array
    {
        return [
            'external_id' => $this->firstFilled($payload, [
                'external_id',
                'data.external_id',
                'data.data.external_id',
                'data.reference_id',
                'data.data.reference_id',
                'reference_id',
            ]),
            'status' => $this->firstFilled($payload, [
                'status',
                'data.status',
                'data.data.status',
                'event',
            ]),
            'amount' => $this->firstFilled($payload, [
                'paid_amount',
                'amount',
                'data.paid_amount',
                'data.amount',
                'data.data.paid_amount',
                'data.data.amount',
                'data.request_amount',
                'data.data.request_amount',
            ]),
        ];
    }

    private function firstFilled(array $payload, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = data_get($payload, $key);

            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }

    private function exceptionStatusCode(RuntimeException $exception): int
    {
        return in_array($exception->getCode(), [400, 401, 422], true)
            ? $exception->getCode()
            : 400;
    }
}
