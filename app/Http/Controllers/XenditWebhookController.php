<?php

namespace App\Http\Controllers;

use App\Services\PaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class XenditWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGatewayService $paymentGateway): JsonResponse
    {
        try {
            $order = $paymentGateway->handleXenditWebhook(
                $request->all(),
                $request->header('x-callback-token')
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 400);
        }

        if (! $order) {
            return response()->json([
                'message' => 'Order pembayaran tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Webhook Xendit diproses.',
            'payment_status' => $order->payment_status,
        ]);
    }
}
