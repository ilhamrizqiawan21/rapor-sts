<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MadrasahProfile extends Model
{
    use HasFactory;

    public static function classLevelsFor(?string $level): array
    {
        return match ($level) {
            'MI' => range(1, 6),
            'MTs' => range(7, 9),
            'MA' => range(10, 12),
            default => range(1, 12),
        };
    }

    public static function classLevelLabel(int $level): string
    {
        $names = [
            1 => 'Satu',
            2 => 'Dua',
            3 => 'Tiga',
            4 => 'Empat',
            5 => 'Lima',
            6 => 'Enam',
            7 => 'Tujuh',
            8 => 'Delapan',
            9 => 'Sembilan',
            10 => 'Sepuluh',
            11 => 'Sebelas',
            12 => 'Dua Belas',
        ];

        return $level.' ('.($names[$level] ?? $level).' / '.self::classLevelRoman($level).')';
    }

    public static function classLevelRoman(int $level): string
    {
        return [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        ][$level] ?? (string) $level;
    }

    public static function phaseForClassLevel(int $level): string
    {
        return match (true) {
            $level <= 2 => 'A',
            $level <= 4 => 'B',
            $level <= 6 => 'C',
            $level <= 9 => 'D',
            $level === 10 => 'E',
            default => 'F',
        };
    }

    public function classLevels(): array
    {
        return self::classLevelsFor($this->level);
    }

    public function maximumClassLevel(): int
    {
        return max($this->classLevels());
    }

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

    /**
     * Baris wilayah kop ("Kecamatan X, Kabupaten Y - Provinsi Z"); bagian kosong atau "-" dilewati.
     */
    public static function formatRegion(?string $district, ?string $regency, ?string $province): string
    {
        $clean = fn (?string $v) => in_array(trim((string) $v), ['', '-', '–'], true) ? null : trim((string) $v);

        $district = $clean($district);
        $regency = $clean($regency);
        $province = $clean($province);

        $line = collect([
            $district ? 'Kecamatan '.$district : null,
            $regency ? 'Kabupaten '.$regency : null,
        ])->filter()->implode(', ');

        return $province ? trim($line.' - '.$province, ' -') : $line;
    }

    public function getLogoLeftDataUrl(): ?string
    {
        if (! $this->logo_left_path) {
            return null;
        }

        if (Storage::disk('public')->exists($this->logo_left_path)) {
            $content = Storage::disk('public')->get($this->logo_left_path);
            $mime = Storage::disk('public')->mimeType($this->logo_left_path) ?? 'image/png';

            return 'data:'.$mime.';base64,'.base64_encode($content);
        }

        return null;
    }

    public function getLogoRightDataUrl(): ?string
    {
        if (! $this->logo_right_path) {
            return null;
        }

        if (Storage::disk('public')->exists($this->logo_right_path)) {
            $content = Storage::disk('public')->get($this->logo_right_path);
            $mime = Storage::disk('public')->mimeType($this->logo_right_path) ?? 'image/png';

            return 'data:'.$mime.';base64,'.base64_encode($content);
        }

        return null;
    }
}
