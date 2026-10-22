<?php

namespace App\Enums;

enum Achievement: string
{
    case BelumTercapai = 'belum_tercapai';
    case Cukup = 'cukup';
    case Baik = 'baik';
    case SangatBaik = 'sangat_baik';

    public function label(): string
    {
        return match ($this) {
            self::BelumTercapai => 'Belum Tercapai',
            self::Cukup => 'Cukup',
            self::Baik => 'Baik',
            self::SangatBaik => 'Sangat Baik',
        };
    }

    public function descriptorWord(): string
    {
        return match ($this) {
            self::BelumTercapai => 'kurang',
            self::Cukup => 'cukup baik',
            self::Baik => 'baik',
            self::SangatBaik => 'sangat baik',
        };
    }
}
