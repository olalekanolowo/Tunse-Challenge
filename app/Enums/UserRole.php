<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case Admin = 'admin';
    case Auditor = 'auditor';
    case SuperAdmin = 'super_admin';

    /**
     * @return array<int, string>
     */
    public static function staffValues(): array
    {
        return [self::Admin->value, self::Auditor->value, self::SuperAdmin->value];
    }
}
