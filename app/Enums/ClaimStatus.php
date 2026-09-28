<?php

namespace App\Enums;

enum ClaimStatus: string
{
    case Submitted = 'submitted';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Flagged = 'flagged';
    case CorrectionRequested = 'correction_requested';
}
