<?php

namespace App\Services;

use App\Contracts\PaymentGatewayAdapter;
use App\Models\PaymentProvider;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class GenericHttpPaymentGatewayAdapter implements PaymentGatewayAdapter
{
    public function createCheckout(PaymentProvider $provider, PaymentTransaction $transaction): array
    {
        $credentials = $provider->credentials ?? [];
        $endpoint = $credentials['checkout_endpoint'] ?? null;
        $this->assertSecureEndpoint($endpoint, $provider->environment);

        $user = $transaction->user()->first();
        $headers = ['Accept' => 'application/json'];

        if (filled($credentials['api_key'] ?? null)) {
            $headers['Authorization'] = 'Bearer '.$credentials['api_key'];
        }

        $response = Http::acceptJson()
            ->withHeaders($headers)
            ->timeout(15)
            ->post($endpoint, [
                'reference' => $transaction->reference,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'method' => $transaction->method,
                'description' => $transaction->purpose,
                'customer' => ['name' => $user?->name, 'email' => $user?->email],
                'return_url' => route('payments.show', $transaction),
                'webhook_url' => route('payments.webhook', $provider),
            ]);

        if (! $response->successful()) {
            throw ValidationException::withMessages(['payment_provider' => 'Provider menolak pembuatan checkout.']);
        }

        $body = $response->json();
        $checkoutUrl = data_get($body, $credentials['checkout_url_path'] ?? 'checkout_url');

        $this->assertSecureEndpoint($checkoutUrl, $provider->environment);

        return [
            'checkout_url' => $checkoutUrl,
            'provider_reference' => data_get($body, $credentials['provider_reference_path'] ?? 'id'),
        ];
    }

    public function verifyWebhook(PaymentProvider $provider, string $rawBody, array $headers): array
    {
        $credentials = $provider->credentials ?? [];
        $signatureHeader = $credentials['webhook_signature_header'] ?? 'X-Signature';
        $signature = $this->headerValue($headers, $signatureHeader);
        $secret = $credentials['webhook_secret'] ?? null;

        if (! is_string($secret) || $secret === '' || ! is_string($signature)) {
            throw ValidationException::withMessages(['webhook' => 'Secret atau tanda tangan webhook belum dikonfigurasi.']);
        }

        $providedSignature = preg_replace('/^sha256=/i', '', $signature) ?? '';
        $body = json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR);
        $signaturePayload = $rawBody;

        if (filled($credentials['webhook_signature_payload'] ?? null)) {
            $parts = [];
            foreach (explode(',', $credentials['webhook_signature_payload']) as $path) {
                $parts[] = (string) data_get($body, trim($path));
            }
            $signaturePayload = implode('', $parts);
        }

        $expectedSignature = hash_hmac((string) ($credentials['webhook_signature_algorithm'] ?? 'sha256'), $signaturePayload, $secret);

        if (! hash_equals(strtolower($expectedSignature), strtolower($providedSignature))) {
            throw ValidationException::withMessages(['webhook' => 'Tanda tangan webhook tidak valid.']);
        }
        $status = strtolower((string) data_get($body, $credentials['webhook_status_path'] ?? 'status'));
        $normalizedStatus = match ($status) {
            'paid', 'success', 'successful', 'settlement', 'capture', 'completed' => 'paid',
            'failed', 'failure' => 'failed',
            'cancelled', 'canceled' => 'cancelled',
            'expire', 'expired' => 'expired',
            default => 'pending',
        };

        $eventId = data_get($body, $credentials['webhook_event_id_path'] ?? 'id');
        $reference = data_get($body, $credentials['webhook_reference_path'] ?? 'reference');
        $amount = data_get($body, $credentials['webhook_amount_path'] ?? 'amount');
        $currency = data_get($body, $credentials['webhook_currency_path'] ?? 'currency');

        if (! is_scalar($eventId) || ! is_scalar($reference) || ! is_numeric($amount) || ! is_string($currency)) {
            throw ValidationException::withMessages(['webhook' => 'Payload webhook tidak memuat referensi, jumlah, mata uang, atau ID event yang valid.']);
        }

        return [
            'event_id' => (string) $eventId,
            'event_type' => (string) (($credentials['webhook_type_path'] ?? null) ? data_get($body, $credentials['webhook_type_path']) : $status),
            'reference' => (string) $reference,
            'status' => $normalizedStatus,
            'amount' => (int) $amount,
            'currency' => strtoupper($currency),
            'payload' => ['reference' => (string) $reference, 'status' => $normalizedStatus],
        ];
    }

    private function assertSecureEndpoint(mixed $url, string $environment): void
    {
        if (! is_string($url)) {
            throw ValidationException::withMessages(['payment_provider' => 'URL endpoint provider belum dikonfigurasi.']);
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        if ($scheme !== 'https' && ! ($environment === 'sandbox' && $scheme === 'http')) {
            throw ValidationException::withMessages(['payment_provider' => 'Gunakan endpoint HTTPS untuk provider produksi.']);
        }

        if ($environment === 'production' && is_string($host)) {
            foreach (gethostbynamel($host) ?: [] as $address) {
                if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    throw ValidationException::withMessages(['payment_provider' => 'Endpoint gateway produksi harus memakai host publik.']);
                }
            }
        }
    }

    private function headerValue(array $headers, string $name): ?string
    {
        foreach ($headers as $headerName => $values) {
            if (strcasecmp($headerName, $name) === 0) {
                return is_array($values) ? (string) ($values[0] ?? '') : (string) $values;
            }
        }

        return null;
    }
}
