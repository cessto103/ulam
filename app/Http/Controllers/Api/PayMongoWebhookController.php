<?php

namespace App\Http\Controllers\Api;

use App\Contracts\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Models\WebhookEvent;
use App\Services\SubscriptionLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayMongoWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGateway $gateway, SubscriptionLifecycleService $lifecycle)
    {
        $raw = $request->getContent();
        if (! $gateway->verifyWebhook($raw, (string) $request->header('Paymongo-Signature'))) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $payload = json_decode($raw, true);
        if (! is_array($payload)) return response()->json(['message' => 'Invalid JSON.'], 400);

        $providerId = (string) data_get($payload, 'data.id', '');
        $type = (string) data_get($payload, 'data.attributes.type', 'unknown');
        if ($providerId === '') return response()->json(['message' => 'Missing event id.'], 422);

        $event = WebhookEvent::firstOrCreate(['provider_event_id' => $providerId], [
            'provider' => 'paymongo', 'event_type' => $type,
            'livemode' => (bool) data_get($payload, 'data.attributes.livemode', false),
            'payload' => $payload,
        ]);
        if (! $event->wasRecentlyCreated || $event->status === 'processed') return response()->json(['received' => true]);

        try {
            if (in_array($type, ['checkout_session.payment.paid', 'payment.paid'], true)) {
                $lifecycle->processPaidCheckout($event);
            } elseif (in_array($type, ['checkout_session.payment.failed', 'payment.failed'], true)) {
                $lifecycle->processFailedCheckout($event);
            } else {
                $event->update(['status' => 'ignored', 'processed_at' => now()]);
            }
        } catch (\Throwable $e) {
            $event->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 2000)]);
            Log::error('PayMongo webhook processing failed', ['event_id' => $providerId, 'exception' => $e]);
            // Always 200 once the event is verified and received — a 4xx/5xx
            // here tells PayMongo *delivery* failed, and enough of those get
            // the webhook auto-disabled. Processing failures are ours to
            // find and fix (via the log line above / WebhookEvent.status),
            // not something PayMongo should be told to retry indefinitely.
        }

        return response()->json(['received' => true]);
    }
}
