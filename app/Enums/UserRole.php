<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Normal = 'normal';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Normal => 'Normal',
        };
    }
}
