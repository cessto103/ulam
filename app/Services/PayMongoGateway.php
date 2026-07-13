<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\CheckoutSession;
use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayMongoGateway implements PaymentGateway
{
    private function client(): PendingRequest
    {
        $key = (string) config('services.paymongo.secret_key');
        if ($key === '') {
            throw new RuntimeException('PAYMONGO_SECRET_KEY is not configured.');
        }

        return Http::acceptJson()->asJson()->withBasicAuth($key, '')->timeout(20)->retry(2, 250);
    }

    public function createCheckout(CheckoutSession $session): array
    {
        $session->loadMissing('price.plan', 'user');
        $price = $session->price;
        $plan = $price->plan;

        $response = $this->client()
            ->withHeader('Idempotency-Key', $session->idempotency_key)
            ->post(rtrim(config('services.paymongo.api_url'), '/').'/checkout_sessions', [
                'data' => ['attributes' => [
                    'billing' => [
                        'name' => $session->user->name,
                        'email' => $session->user->email,
                    ],
                    'cancel_url' => config('billing.cancel_url'),
                    'success_url' => config('billing.success_url'),
                    'description' => "uLam {$plan->name} subscription",
                    'line_items' => [[
                        'amount' => $session->amount,
                        'currency' => $session->currency,
                        'description' => "{$price->duration} seller subscription",
                        'name' => $plan->name,
                        'quantity' => 1,
                    ]],
                    'payment_method_types' => ['gcash'],
                    'reference_number' => $session->public_id,
                    'send_email_receipt' => true,
                    'show_description' => true,
                    'show_line_items' => true,
                    'metadata' => [
                        'checkout_public_id' => $session->public_id,
                        'user_id' => (string) $session->user_id,
                        'plan_price_id' => (string) $session->seller_plan_price_id,
                    ],
                ]],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('PayMongo checkout creation failed: '.$response->body());
        }

        $data = $response->json('data', []);
        $attributes = $data['attributes'] ?? [];
        $url = $attributes['checkout_url'] ?? null;
        if (! is_string($url) || $url === '') {
            throw new RuntimeException('PayMongo returned no checkout URL.');
        }

        return [
            'provider_session_id' => (string) ($data['id'] ?? ''),
            'checkout_url' => $url,
            'expires_at' => isset($attributes['expires_at']) ? date(DATE_ATOM, (int) $attributes['expires_at']) : null,
            'raw' => $data,
        ];
    }

    public function verifyWebhook(string $rawBody, string $signatureHeader): bool
    {
        $secret = (string) config('services.paymongo.webhook_secret');
        if ($secret === '' || $signatureHeader === '') return false;

        $parts = [];
        foreach (explode(',', $signatureHeader) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            $parts[$key] = $value;
        }

        $timestamp = $parts['t'] ?? '';
        if (! ctype_digit($timestamp) || abs(time() - (int) $timestamp) > 300) return false;

        $payload = json_decode($rawBody, true);
        $live = (bool) data_get($payload, 'data.attributes.livemode', false);
        $signature = $parts[$live ? 'li' : 'te'] ?? '';
        if ($signature === '') return false;

        return hash_equals(hash_hmac('sha256', "{$timestamp}.{$rawBody}", $secret), $signature);
    }

    public function refund(Payment $payment, int $amount, string $reason): array
    {
        $response = $this->client()->post(rtrim(config('services.paymongo.api_url'), '/').'/refunds', [
            'data' => ['attributes' => [
                'amount' => $amount,
                'payment_id' => $payment->provider_payment_id,
                'reason' => $reason,
                'notes' => "uLam payment #{$payment->id}",
            ]],
        ]);
        if ($response->failed()) throw new RuntimeException('PayMongo refund failed: '.$response->body());
        $data = $response->json('data', []);
        return ['provider_refund_id' => (string) ($data['id'] ?? ''), 'status' => (string) data_get($data, 'attributes.status', 'pending'), 'raw' => $data];
    }
}
