<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['state_id', 'name'])]
class Lga extends Model
{
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
