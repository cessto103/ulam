<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpgradeController extends Controller
{
    private const PLANS = [
        'monthly' => ['amount' => 5900,  'label' => 'uLam Premium – Buwanin'],
        'yearly'  => ['amount' => 49900, 'label' => 'uLam Premium – Taon-taon'],
    ];

    // POST /upgrade/checkout  (auth required)
    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'plan' => ['required', 'in:monthly,yearly'],
        ]);

        $plan = self::PLANS[$request->plan];
        $user = $request->user();

        $response = Http::withBasicAuth(config('services.paymongo.secret_key'), '')
            ->post('https://api.paymongo.com/v1/links', [
                'data' => [
                    'attributes' => [
                        'amount'      => $plan['amount'],
                        'description' => $plan['label'],
                        'remarks'     => "uLam Premium for user #{$user->id}",
                        'metadata'    => [
                            'user_id'   => $user->id,
                            'plan_type' => $request->plan,
                        ],
                    ],
                ],
            ]);

        if ($response->failed()) {
            Log::error('PayMongo checkout error', ['body' => $response->body()]);
            return response()->json(['error' => 'Hindi ma-create ang payment link. Subukan ulit.'], 502);
        }

        $attrs = $response->json('data.attributes');

        return response()->json([
            'checkout_url'    => $attrs['checkout_url'],
            'payment_link_id' => $response->json('data.id'),
        ]);
    }

    // POST /upgrade/webhook  (public — called by PayMongo)
    public function webhook(Request $request): JsonResponse
    {
        // Verify signature
        $sigHeader = $request->header('paymongo-signature', '');
        if (! $this->verifySignature($sigHeader, $request->getContent())) {
            return response()->json(['error' => 'Invalid signature.'], 401);
        }

        $event = $request->json('data.attributes');
        $type  = $event['type'] ?? '';

        if ($type !== 'link.payment.paid') {
            return response()->json(['received' => true]); // Acknowledge other events
        }

        $meta   = $event['data']['attributes']['metadata'] ?? [];
        $userId = $meta['user_id'] ?? null;
        $plan   = $meta['plan_type'] ?? 'monthly';

        if (! $userId) {
            return response()->json(['error' => 'Missing user_id in metadata.'], 422);
        }

        $user = User::find($userId);
        if ($user) {
            $expiry = $plan === 'yearly'
                ? now()->addYear()
                : now()->addMonth();

            $user->update([
                'plan'                => 'premium',
                'premium_expires_at'  => $expiry,
                'premium_source'      => 'paid',
            ]);
        }

        // Record the payment in the ledger. Keyed on PayMongo's payment id so
        // webhook retries don't double-record.
        $payment = $event['data'] ?? [];
        $paymentId = $payment['id'] ?? null;
        $amount = $payment['attributes']['amount'] ?? self::PLANS[$plan]['amount'] ?? 0;

        $record = [
            'user_id' => $userId,
            'provider' => 'paymongo',
            'plan_type' => $plan,
            'amount' => $amount,
            'currency' => 'PHP',
            'status' => 'paid',
            'paid_at' => now(),
            'meta' => $meta ?: null,
        ];

        if ($paymentId) {
            Payment::firstOrCreate(['provider_payment_id' => $paymentId], $record);
        } else {
            // No payment id in the event — record anyway (a null key would wrongly
            // dedupe against any earlier id-less payment via firstOrCreate).
            Payment::create($record);
        }

        return response()->json(['received' => true]);
    }

    private function verifySignature(string $header, string $rawBody): bool
    {
        $secret = config('services.paymongo.webhook_secret', '');
        if (! $secret) {
            if (app()->environment(['local', 'testing'])) {
                Log::warning('PayMongo webhook signature verification bypassed in local/testing because PAYMONGO_WEBHOOK_SECRET is unset.');
                return true;
            }

            Log::error('PayMongo webhook rejected because PAYMONGO_WEBHOOK_SECRET is unset.');
            return false;
        }

        // Header format: t=<timestamp>,te=<test_sig>,li=<live_sig>
        $parts = [];
        foreach (explode(',', $header) as $part) {
            [$k, $v] = array_pad(explode('=', $part, 2), 2, '');
            $parts[$k] = $v;
        }

        $timestamp = $parts['t'] ?? '';
        $signature = $parts['li'] ?? $parts['te'] ?? '';

        if ($timestamp === '' || $signature === '') {
            return false;
        }

        if (! ctype_digit($timestamp) || abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $message   = "{$timestamp}.{$rawBody}";

        $computed = hash_hmac('sha256', $message, $secret);

        return hash_equals($computed, $signature);
    }
}
