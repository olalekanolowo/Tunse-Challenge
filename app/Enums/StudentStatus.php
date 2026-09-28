<?php

namespace App\Enums;

enum StudentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Disqualified = 'disqualified';
}
