<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\MidtransService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function checkout(Request $request, MidtransService $midtransService): RedirectResponse
    {
        $validated = $request->validate([
            'target' => ['required', 'in:user,company'],
        ]);

        $subscribable = $validated['target'] === 'company'
            ? $request->user()->company
            : $request->user();

        abort_if(!$subscribable, 403, 'Akun langganan tidak ditemukan.');

        $subscription = Subscription::create([
            'subscribable_type' => $subscribable::class,
            'subscribable_id' => $subscribable->id,
            'plan_type' => 'premium',
            'status' => 'pending',
            'amount' => $validated['target'] === 'company' ? 149000 : 99000,
            'midtrans_order_id' => 'SUB-'.strtoupper($validated['target']).'-'.now()->format('YmdHis').'-'.mt_rand(1000, 9999),
        ]);

        $midtransPayload = $midtransService->createSnapTransaction($subscription, [
            'name' => $request->user()->name,
            'email' => $request->user()->email,
        ]);

        $subscription->update([
            'midtrans_snap_token' => $midtransPayload['token'] ?? null,
            'midtrans_redirect_url' => $midtransPayload['redirect_url'] ?? null,
            'payload' => $midtransPayload,
        ]);

        return redirect()->away($subscription->midtrans_redirect_url);
    }
}
