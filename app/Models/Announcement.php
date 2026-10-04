<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['region_id', 'author_id', 'title', 'category', 'body', 'is_emergency', 'is_published', 'published_at'])]
class Announcement extends Model
{
    protected function casts(): array
    {
        return ['is_emergency' => 'boolean', 'is_published' => 'boolean', 'published_at' => 'datetime'];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
