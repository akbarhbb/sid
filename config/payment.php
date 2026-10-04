<?php

use App\Services\GenericHttpPaymentGatewayAdapter;

return [
    'drivers' => [
        'generic_http' => GenericHttpPaymentGatewayAdapter::class,
    ],
    'currency' => env('PAYMENT_CURRENCY', 'IDR'),
];
