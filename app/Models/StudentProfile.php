<?php

namespace App\Models;

use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'institution_id', 'challenge_id', 'phone', 'state',
    'student_id_number', 'student_id_file_path', 'department', 'graduation_year', 'status', 'approved_at',
])]
#[Hidden(['student_id_file_path'])]
class StudentProfile extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => StudentStatus::class,
            'approved_at' => 'datetime',
            'graduation_year' => 'integer',
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
}
