<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['region_id', 'recorded_by', 'reverses_id', 'organization', 'type', 'category', 'description', 'amount', 'status', 'is_public', 'occurred_at', 'evidence_path'])]
class LedgerTransaction extends Model
{
    protected function casts(): array
    {
        return ['amount' => 'integer', 'is_public' => 'boolean', 'occurred_at' => 'datetime'];
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(LedgerApproval::class);
    }
}
