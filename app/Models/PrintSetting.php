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

    public function isSectionVisible(string $key, bool $default = true): bool
    {
        $sections = $this->sections ?? [];

        // Support aliases
        if (! isset($sections[$key])) {
            if ($key === 'show_note' && isset($sections['show_notes'])) {
                return (bool) $sections['show_notes'];
            }
            if ($key === 'show_total_score' && isset($sections['show_total'])) {
                return (bool) $sections['show_total'];
            }

            return $default;
        }

        return (bool) $sections[$key];
    }
}
