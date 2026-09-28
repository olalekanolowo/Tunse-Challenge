<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'institution_id', 'phase_id', 'claim_type_id',
    'recruit_name', 'recruit_phone', 'state', 'lga', 'category',
    'date_recruited', 'photo_path', 'notes', 'declaration_at', 'status',
    'provisional_points', 'audited_points', 'tworker_phone',
    'transaction_reference', 'approx_value', 'rating',
])]
class Claim extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date_recruited' => 'date',
            'declaration_at' => 'datetime',
            'status' => ClaimStatus::class,
            'provisional_points' => 'integer',
            'audited_points' => 'integer',
            'approx_value' => 'decimal:2',
            'rating' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    public function claimType(): BelongsTo
    {
        return $this->belongsTo(ClaimType::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class);
    }
}
