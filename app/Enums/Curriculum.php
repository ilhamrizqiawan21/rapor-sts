<?php

namespace App\Enums;

enum Curriculum: string
{
    case Merdeka = 'merdeka';
    case K13 = 'k13';

    public function label(): string
    {
        return match ($this) {
            self::Merdeka => 'Kurikulum Merdeka',
            self::K13 => 'Kurikulum 2013',
        };
    }
}

