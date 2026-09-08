<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Pending = 'pending';
    case Merged = 'merged';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Merged => 'Merged',
            self::Declined => 'Declined',
        };
    }
}
