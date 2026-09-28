<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['phase_id', 'code', 'label', 'base_points', 'active', 'validation_rule_text'])]
class ClaimType extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'base_points' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }
}
