<?php

namespace App\Enums;

enum PhaseStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Frozen = 'frozen';
    case Closed = 'closed';

    /**
     * Legal forward transitions, keyed by current status.
     *
     * @return array<int, self>
     */
    public function allowedNextStatuses(): array
    {
        return match ($this) {
            self::Draft => [self::Open],
            self::Open => [self::Frozen],
            self::Frozen => [self::Closed, self::Open],
            self::Closed => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedNextStatuses(), true);
    }
}
