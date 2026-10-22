<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
