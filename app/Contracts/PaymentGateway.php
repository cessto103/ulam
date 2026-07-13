<?php

namespace App\Contracts;

use App\Models\CheckoutSession;
use App\Models\Payment;

interface PaymentGateway
{
    /** @return array{provider_session_id:string,checkout_url:string,expires_at:?string,raw:array} */
    public function createCheckout(CheckoutSession $session): array;

    public function verifyWebhook(string $rawBody, string $signatureHeader): bool;

    /** @return array{provider_refund_id:string,status:string,raw:array} */
    public function refund(Payment $payment, int $amount, string $reason): array;
}
