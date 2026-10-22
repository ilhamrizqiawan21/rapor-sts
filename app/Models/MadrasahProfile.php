<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MadrasahProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_institution',
        'foundation_name',
        'name',
        'level',
        'nsm',
        'npsn',
        'address',
        'district',
        'regency',
        'province',
        'contact',
        'headmaster_name',
        'headmaster_nip',
        'logo_left_path',
        'logo_right_path',
    ];
}
