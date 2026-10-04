<?php

namespace App\Contracts;

use App\Models\PaymentProvider;
use App\Models\PaymentTransaction;

interface PaymentGatewayAdapter
{
    /** @return array{checkout_url: string, provider_reference: ?string, metadata?: array<string, mixed>} */
    public function createCheckout(PaymentProvider $provider, PaymentTransaction $transaction): array;

    /** @return array{event_id: string, event_type: string, reference: string, status: string, amount: int, currency: string, payload: array<string, mixed>} */
    public function verifyWebhook(PaymentProvider $provider, string $rawBody, array $headers): array;
}
