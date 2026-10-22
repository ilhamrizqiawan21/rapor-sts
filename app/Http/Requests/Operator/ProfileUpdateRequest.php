<?php

namespace App\Http\Requests\Operator;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOperator() ?? false;
    }

    public function rules(): array
    {
        return [
            // Identitas Madrasah
            'parent_institution' => ['nullable', 'string', 'max:255'],
            'foundation_name' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'level' => ['required', 'string', 'in:MI,MTs,MA'],
            'nsm' => ['required', 'string', 'max:50'],
            'npsn' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string'],
            'district' => ['required', 'string', 'max:100'],
            'regency' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'contact' => ['nullable', 'string', 'max:255'],

            // Kepala Madrasah
            'headmaster_name' => ['required', 'string', 'max:255'],
            'headmaster_nip' => ['nullable', 'string', 'max:50'],

            // Logo Kop Rapor
            'logo_left' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
            'logo_right' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:1024'],

            // Branding Aplikasi
            'app_name' => ['required', 'string', 'max:100'],
            'app_subtitle' => ['nullable', 'string', 'max:150'],
            'logo_app' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
            'login_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'topbar_color' => ['required', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'],
        ];
    }
}
