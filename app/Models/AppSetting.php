<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_name',
        'app_subtitle',
        'app_logo_path',
        'login_image_path',
        'topbar_color',
        'default_kktp',
    ];

    protected function casts(): array
    {
        return [
            'default_kktp' => 'decimal:2',
        ];
    }
}
