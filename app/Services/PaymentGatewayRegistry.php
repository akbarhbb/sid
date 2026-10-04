<?php

namespace App\Services;

use App\Contracts\PaymentGatewayAdapter;
use App\Models\PaymentProvider;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Validation\ValidationException;

class PaymentGatewayRegistry
{
    /** @throws BindingResolutionException */
    public function adapterFor(PaymentProvider $provider): PaymentGatewayAdapter
    {
        $adapter = config("payment.drivers.{$provider->driver}");

        if (! is_string($adapter) || ! is_subclass_of($adapter, PaymentGatewayAdapter::class)) {
            throw ValidationException::withMessages([
                'payment_provider' => "Adapter '{$provider->driver}' belum terpasang. Tambahkan adapter yang memenuhi kontrak gateway.",
            ]);
        }

        return app($adapter);
    }
}
