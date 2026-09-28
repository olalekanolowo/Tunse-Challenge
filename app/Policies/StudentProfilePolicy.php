<?php

namespace App\Policies;

use App\Models\StudentProfile;
use App\Models\User;

class StudentProfilePolicy
{
    public function viewIdFile(User $user, StudentProfile $studentProfile): bool
    {
        return $user->isStaff();
    }
}
