<?php

return [
    'provider' => env('BILLING_PROVIDER', 'paymongo'),
    'currency' => 'PHP',
    'grace_days' => (int) env('BILLING_GRACE_DAYS', 3),
    'checkout_ttl_minutes' => (int) env('BILLING_CHECKOUT_TTL_MINUTES', 60),
    'success_url' => env('BILLING_SUCCESS_URL', env('APP_URL').'/billing/return?status=success'),
    'cancel_url' => env('BILLING_CANCEL_URL', env('APP_URL').'/billing/return?status=cancelled'),
    'mobile_return_url' => env('BILLING_MOBILE_RETURN_URL', 'ulam://subscription'),
];
