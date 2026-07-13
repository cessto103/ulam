# uLam billing architecture

## Ownership

Laravel is the only billing authority. The Expo application never stores provider secrets, calculates an amount, activates a plan, or trusts a browser redirect as payment proof. It requests a checkout URL and renders the entitlement snapshot returned by the API.

## Checkout flow

1. `GET /api/billing/plans` returns active catalog prices and the user's entitlement snapshot.
2. `POST /api/billing/checkout` accepts only `price_id`. Laravel reloads the price and amount from the database.
3. `BillingService` creates a local UUID checkout session before calling the configured `PaymentGateway`.
4. `PayMongoGateway` creates a hosted GCash checkout with a unique reference and idempotency key.
5. Expo opens the hosted URL with `expo-web-browser` and refreshes billing state when the app becomes active.
6. Redirects are informational. Only a verified webhook may activate access.

## Webhook flow

PayMongo calls `POST /api/billing/webhooks/paymongo`. The handler verifies the timestamped HMAC against the unmodified request body, rejects signatures older than five minutes, stores the unique provider event, and processes it transactionally. Duplicate deliveries return 200 without creating another payment or extending access twice.

Configure one PayMongo webhook for checkout/payment events. The public URL must use HTTPS. Do not create a webhook for each checkout.

## Subscription states

- `pending`: checkout created but not confirmed
- `active`: paid and within the current period
- `grace_period`: period ended and renewal is still allowed
- `suspended`: grace period ended; paid entitlements are removed
- `expired`: cancelled period ended
- `superseded`: replaced by a different paid plan

Cancellation is scheduled at period end. Same-plan payments extend the existing end date. A different-plan payment supersedes the old subscription and begins immediately.

## Lifecycle scheduler

Run Laravel's scheduler every minute and a queue worker continuously in production:

```cron
* * * * * php /path/to/uLam/artisan schedule:run
```

`billing:process-lifecycle` sends expiry/grace/suspension notifications and reconciles store visibility. It is idempotent and protected with `withoutOverlapping()`.

Refunds are initiated through the authenticated admin billing endpoint, stored in the `refunds` audit table, and submitted to PayMongo. GCash refunds are subject to PayMongo's eligibility window and available payout balance.

## Entitlements

`EntitlementService` is the central authorization vocabulary. It always includes `stores.max` and `store_items.max_per_store`, then overlays feature values from `seller_plan_features`. Backend policies/controllers must enforce limits; mobile visibility is UX only.

## Deployment checklist

1. Back up the database and storage.
2. Set `APP_ENV=production`, `APP_DEBUG=false`, an HTTPS `APP_URL`, PayMongo keys, webhook secret, and billing URLs.
3. Run `php artisan migrate --force`.
4. Run `php artisan config:cache && php artisan route:cache`.
5. Start queue workers and the scheduler.
6. Register the HTTPS webhook in PayMongo and perform a test-mode payment.
7. Confirm one `webhook_events`, `payments`, and active `subscriptions` row is created.
8. Confirm a duplicate webhook does not extend access twice.

GCash checkout is treated as customer-initiated renewal. Do not claim automatic charging unless the selected provider and payment method explicitly support reusable payment authorization.
