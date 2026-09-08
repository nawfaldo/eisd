<?php

namespace App\Enums;

enum ReportType: string
{
    case Poisoning = 'poisoning';
    case Sppg = 'sppg';
    case Corruption = 'corruption';

    public function label(): string
    {
        return match ($this) {
            self::Poisoning => 'Poisoning',
            self::Sppg => 'SPPG province',
            self::Corruption => 'Corruption',
        };
    }

    /** What the report is asking to add, in the words the form uses. */
    public function describes(): string
    {
        return match ($this) {
            self::Poisoning => 'A mass food-poisoning case',
            self::Sppg => 'A count of operational kitchens',
            self::Corruption => 'A corruption case',
        };
    }
}
