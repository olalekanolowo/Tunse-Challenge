<?php

namespace App\Models;

use App\Enums\PhaseStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'number', 'starts_at', 'ends_at', 'status', 'prize_text', 'rules_version'])]
class Phase extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'status' => PhaseStatus::class,
        ];
    }

    public function claimTypes(): HasMany
    {
        return $this->hasMany(ClaimType::class);
    }
}
