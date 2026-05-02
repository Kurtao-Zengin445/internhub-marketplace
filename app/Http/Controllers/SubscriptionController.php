<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\MidtransService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

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

        try {
            $midtransPayload = $midtransService->createSnapTransaction($subscription, [
                'name' => $request->user()->name,
                'email' => $request->user()->email,
            ]);
        } catch (Throwable $exception) {
            $subscription->delete();

            Log::error('Gagal membuat transaksi Midtrans.', [
                'target' => $validated['target'],
                'user_id' => $request->user()->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->with(
                'error',
                'Pembayaran premium belum bisa diproses. Pastikan konfigurasi MIDTRANS_SERVER_KEY, MIDTRANS_CLIENT_KEY, dan MIDTRANS_SNAP_URL sudah benar di file .env.'
            );
        }

        $subscription->update([
            'midtrans_snap_token' => $midtransPayload['token'] ?? null,
            'midtrans_redirect_url' => $midtransPayload['redirect_url'] ?? null,
            'payload' => $midtransPayload,
        ]);

        if (!$subscription->midtrans_redirect_url) {
            $subscription->delete();

            return back()->with('error', 'Midtrans tidak mengembalikan URL pembayaran. Periksa kembali konfigurasi Midtrans.');
        }

        return redirect()->away($subscription->midtrans_redirect_url);
    }
}
