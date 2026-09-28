<?php

namespace App\Models;

use App\Enums\AuditOutcome;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['claim_id', 'auditor_id', 'outcome', 'backend_lookup_key', 'audit_notes', 'audited_at'])]
class Audit extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'outcome' => AuditOutcome::class,
            'audited_at' => 'datetime',
        ];
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }
}
