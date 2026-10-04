<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['region_id', 'name', 'driver', 'environment', 'credentials', 'supported_methods', 'is_active', 'is_default'])]
class PaymentProvider extends Model
{
    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'supported_methods' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }
}
