<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpgradeController extends Controller
{
    private const LABELS = [
        'monthly' => 'uLam Premium – Buwanin',
        'yearly'  => 'uLam Premium – Taon-taon',
    ];

    // Resolves the amount actually charged for a plan, in centavos — the base
    // admin-set price, or the promo price when a promo is active and set for
    // this plan (Content > Monetization > Premium Features > Pricing).
    private function resolveAmountCentavos(string $plan): int
    {
        $base = (float) AppSetting::get("premium_price_{$plan}", $plan === 'yearly' ? '499' : '59');

        if (AppSetting::get('premium_promo_enabled', '0') === '1') {
            $promo = AppSetting::get("premium_promo_price_{$plan}");
            if ($promo !== null && $promo !== '' && (float) $promo > 0) {
                $base = (float) $promo;
            }
        }

        return (int) round($base * 100);
    }

    // POST /upgrade/checkout  (auth required)
    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'plan' => ['required', 'in:monthly,yearly'],
        ]);

        $amount = $this->resolveAmountCentavos($request->plan);
        $label = self::LABELS[$request->plan];
        $user = $request->user();

        $response = Http::withBasicAuth(config('services.paymongo.secret_key'), '')
            ->post('https://api.paymongo.com/v1/links', [
                'data' => [
                    'attributes' => [
                        'amount'      => $amount,
                        'description' => $label,
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

        // The `metadata` sent above does NOT survive onto the Payment PayMongo
        // creates once this Link is paid (confirmed against a real live
        // transaction — the resulting Payment's metadata only ever contains
        // PayMongo's own pm_reference_number, never what we set here). What
        // DOES survive is this Link's reference_number, as the Payment's
        // external_reference_number — so that's what the webhook looks up by.
        PaymentLink::create([
            'user_id' => $user->id,
            'plan_type' => $request->plan,
            'provider_link_id' => $response->json('data.id'),
            'reference_number' => $attrs['reference_number'],
            'amount' => $amount,
            'status' => 'pending',
        ]);

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

        // For link.payment.paid, event.data is the LINK itself (id "link_...",
        // type "link") — NOT the payment, despite the event name. Confirmed
        // against a real webhook delivery payload. The link's own
        // reference_number is what checkout() stored in payment_links; the
        // actual Payment resource is nested one level deeper, inside this
        // link's own `payments` array, each entry wrapped in its own `data` key.
        $linkAttrs        = $event['data']['attributes'] ?? [];
        $referenceNumber  = $linkAttrs['reference_number'] ?? null;
        $nestedPayment    = $linkAttrs['payments'][0]['data'] ?? [];
        $paymentId        = $nestedPayment['id'] ?? null;
        $paymentAttrs     = $nestedPayment['attributes'] ?? $linkAttrs;

        // Once the signature is verified, everything below is our own
        // processing — any bug in it must still resolve to 200 (PayMongo
        // reads a 4xx/5xx as "delivery failed" and disables the webhook
        // after enough of those; processing failures are ours to find via
        // the log line, not something PayMongo should be told to retry).
        try {
            $paymentLink = $referenceNumber
                ? PaymentLink::where('reference_number', $referenceNumber)->first()
                : null;

            if (! $paymentLink) {
                Log::warning('PayMongo upgrade webhook: no matching payment_links row', [
                    'reference_number' => $referenceNumber,
                    'payment_id' => $paymentId,
                ]);
                return response()->json(['received' => true]);
            }

            $user = User::find($paymentLink->user_id);
            if ($user) {
                $expiry = $paymentLink->plan_type === 'yearly'
                    ? now()->addYear()
                    : now()->addMonth();

                $user->update([
                    'plan'                => 'premium',
                    'premium_expires_at'  => $expiry,
                    'premium_source'      => 'paid',
                ]);
            }

            $paymentLink->update(['status' => 'paid']);

            // Record the payment in the ledger. Keyed on PayMongo's payment id so
            // webhook retries don't double-record.
            $amount = $paymentAttrs['amount'] ?? $paymentLink->amount;

            $record = [
                'user_id' => $paymentLink->user_id,
                'provider' => 'paymongo',
                'plan_type' => $paymentLink->plan_type,
                'amount' => $amount,
                'currency' => 'PHP',
                'status' => 'paid',
                'paid_at' => now(),
                'meta' => $paymentAttrs['metadata'] ?? null,
            ];

            if ($paymentId) {
                Payment::firstOrCreate(['provider_payment_id' => $paymentId], $record);
            } else {
                // No payment id in the event — record anyway (a null key would wrongly
                // dedupe against any earlier id-less payment via firstOrCreate).
                Payment::create($record);
            }
        } catch (\Throwable $e) {
            Log::error('PayMongo upgrade webhook processing failed', ['reference_number' => $referenceNumber, 'exception' => $e]);
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
        $live = (bool) data_get(json_decode($rawBody, true), 'data.attributes.livemode', false);
        $signature = $parts[$live ? 'li' : 'te'] ?? '';

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
