<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\ProfileUpdateRequest;
use App\Models\AppSetting;
use App\Models\MadrasahProfile;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $profile = MadrasahProfile::firstOrCreate(['id' => 1], [
            'name' => 'Nama Madrasah',
            'level' => 'MTs',
            'nsm' => '-',
            'npsn' => '-',
            'address' => '-',
            'district' => '-',
            'regency' => '-',
            'province' => '-',
            'headmaster_name' => '-',
        ]);

        $setting = AppSetting::firstOrCreate(['id' => 1], [
            'app_name' => 'Rapor STS',
            'app_subtitle' => 'Nama Madrasah',
            'topbar_color' => '#628b0d',
            'default_kktp' => 70.00,
        ]);

        return view('operator.profil', compact('profile', 'setting'));
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $profile = MadrasahProfile::firstOrCreate(['id' => 1]);
        $setting = AppSetting::firstOrCreate(['id' => 1]);

        $validated = $request->validated();

        // Handle upload Logo Kop Kiri
        if ($request->hasFile('logo_left')) {
            if ($profile->logo_left_path) {
                Storage::disk('public')->delete($profile->logo_left_path);
            }
            $validated['logo_left_path'] = $request->file('logo_left')->store('branding', 'public');
        }

        // Handle upload Logo Kop Kanan
        if ($request->hasFile('logo_right')) {
            if ($profile->logo_right_path) {
                Storage::disk('public')->delete($profile->logo_right_path);
            }
            $validated['logo_right_path'] = $request->file('logo_right')->store('branding', 'public');
        }

        // Handle upload Logo Aplikasi
        if ($request->hasFile('logo_app')) {
            if ($setting->app_logo_path) {
                Storage::disk('public')->delete($setting->app_logo_path);
            }
            $validated['app_logo_path'] = $request->file('logo_app')->store('branding', 'public');
        }

        // Handle upload Gambar Login
        if ($request->hasFile('login_image')) {
            if ($setting->login_image_path) {
                Storage::disk('public')->delete($setting->login_image_path);
            }
            $validated['login_image_path'] = $request->file('login_image')->store('branding', 'public');
        }

        $profile->update([
            'parent_institution' => $validated['parent_institution'] ?? null,
            'foundation_name' => $validated['foundation_name'] ?? null,
            'name' => $validated['name'],
            'level' => $validated['level'],
            'nsm' => $validated['nsm'],
            'npsn' => $validated['npsn'],
            'address' => $validated['address'],
            'district' => $validated['district'],
            'regency' => $validated['regency'],
            'province' => $validated['province'],
            'contact' => $validated['contact'] ?? null,
            'headmaster_name' => $validated['headmaster_name'],
            'headmaster_nip' => $validated['headmaster_nip'] ?? null,
            'logo_left_path' => $validated['logo_left_path'] ?? $profile->logo_left_path,
            'logo_right_path' => $validated['logo_right_path'] ?? $profile->logo_right_path,
        ]);

        $setting->update([
            'app_name' => $validated['app_name'],
            'app_subtitle' => $validated['app_subtitle'] ?? null,
            'topbar_color' => $validated['topbar_color'],
            'app_logo_path' => $validated['app_logo_path'] ?? $setting->app_logo_path,
            'login_image_path' => $validated['login_image_path'] ?? $setting->login_image_path,
        ]);

        ActivityLogger::log('update_profile', 'Memperbarui profil madrasah dan branding aplikasi.');

        return redirect()->route('operator.profil.edit')->with('success', 'Profil madrasah dan branding berhasil diperbarui.');
    }
}
