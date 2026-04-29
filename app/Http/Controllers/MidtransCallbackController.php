<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\MidtransService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MidtransCallbackController extends Controller
{
    public function __invoke(
        Request $request,
        MidtransService $midtransService,
        SubscriptionService $subscriptionService
    ): JsonResponse {
        $payload = $request->all();

        abort_unless($midtransService->verifySignature($payload), 403, 'Signature Midtrans tidak valid.');

        $subscription = Subscription::where('midtrans_order_id', $payload['order_id'] ?? null)->firstOrFail();

        if (in_array($payload['transaction_status'] ?? null, ['capture', 'settlement'], true)) {
            $subscriptionService->activatePremium($subscription);
        } elseif (in_array($payload['transaction_status'] ?? null, ['deny', 'cancel', 'expire'], true)) {
            $subscription->update(['status' => 'failed', 'payload' => $payload]);
        }

        return response()->json(['ok' => true]);
    }
}
