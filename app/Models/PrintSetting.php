<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_id',
        'report_title',
        'print_place',
        'print_date',
        'score_label',
        'paper_size',
        'watermark_text',
        'sections',
    ];

    protected function casts(): array
    {
        return [
            'print_date' => 'date',
            'sections' => 'array',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }
}
