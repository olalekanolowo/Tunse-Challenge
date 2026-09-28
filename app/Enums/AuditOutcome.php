<?php

namespace App\Enums;

enum AuditOutcome: string
{
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Flagged = 'flagged';
    case Correction = 'correction';
}
