<?php

namespace App\Services;

use App\Models\Subscription;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class MidtransService
{
    public function createSnapTransaction(Subscription $subscription, array $customer): array
    {
        $serverKey = config('services.midtrans.server_key');

        throw_if(blank($serverKey), new \RuntimeException('MIDTRANS_SERVER_KEY belum diatur.'));

        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->post(config('services.midtrans.snap_url').'/transactions', [
                'transaction_details' => [
                    'order_id' => $subscription->midtrans_order_id,
                    'gross_amount' => $subscription->amount,
                ],
                'customer_details' => [
                    'first_name' => $customer['name'],
                    'email' => $customer['email'],
                ],
                'item_details' => [[
                    'id' => $subscription->plan_type,
                    'price' => $subscription->amount,
                    'quantity' => 1,
                    'name' => 'Langganan Premium 30 Hari',
                ]],
            ]);

        $response->throw();

        return $response->json();
    }

    public function verifySignature(array $payload): bool
    {
        $serverKey = config('services.midtrans.server_key');

        if (blank($serverKey)) {
            return false;
        }

        $expected = hash(
            'sha512',
            ($payload['order_id'] ?? '')
            .($payload['status_code'] ?? '')
            .($payload['gross_amount'] ?? '')
            .$serverKey
        );

        return hash_equals($expected, (string) ($payload['signature_key'] ?? ''));
    }
}
