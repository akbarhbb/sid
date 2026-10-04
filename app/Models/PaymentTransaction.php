<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['region_id', 'payment_provider_id', 'user_id', 'reference', 'purpose', 'provider_reference', 'amount', 'currency', 'method', 'status', 'metadata', 'paid_at'])]
class PaymentTransaction extends Model
{
    protected function casts(): array
    {
        return ['amount' => 'integer', 'metadata' => 'array', 'paid_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
