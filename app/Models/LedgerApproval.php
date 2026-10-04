<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['ledger_transaction_id', 'approved_by', 'approved_at'])]
class LedgerApproval extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['approved_at' => 'datetime'];
    }
}
