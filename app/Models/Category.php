<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'label', 'bg_color', 'active'])]
class Category extends Model
{
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }
}
