<?php

namespace App\Enums;

enum Role: string
{
    case Operator = 'operator';
    case Guru = 'guru';
    case Kepala = 'kepala';

    public function label(): string
    {
        return match ($this) {
            self::Operator => 'Operator',
            self::Guru => 'Guru',
            self::Kepala => 'Kepala Madrasah',
        };
    }
}

