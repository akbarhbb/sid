<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['region_id', 'organization_type', 'name', 'position', 'photo_path', 'term_starts_at', 'term_ends_at', 'display_order'])]
class OrganizationMember extends Model
{
    protected function casts(): array
    {
        return [
            'term_starts_at' => 'date',
            'term_ends_at' => 'date',
            'display_order' => 'integer',
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
