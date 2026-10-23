<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function edit(): View
    {
        return view('akun', ['user' => auth()->user()]);
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'photo.required' => 'Pilih foto terlebih dahulu.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
        ]);

        $user = $request->user();

        if ($user->photo_path) {
            Storage::disk('public')->delete($user->photo_path);
        }

        $user->update(['photo_path' => $request->file('photo')->store('avatars', 'public')]);

        ActivityLogger::log('update_photo', "{$user->name} mengganti foto profil", $user);

        return redirect()->route('akun.edit')->with('success', 'Foto profil berhasil diperbarui.');
    }

    public function removePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->photo_path) {
            Storage::disk('public')->delete($user->photo_path);
            $user->update(['photo_path' => null]);
        }

        return redirect()->route('akun.edit')->with('success', 'Foto profil dihapus.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'current_password.current_password' => 'Password saat ini salah.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'password.min' => 'Password baru minimal 6 karakter.',
        ]);

        $user = $request->user();

        // Password milik pengguna sendiri: salinan yang terlihat operator dihapus.
        $user->update([
            'password' => Hash::make($request->input('password')),
            'password_plain' => null,
        ]);

        ActivityLogger::log('change_password', "{$user->name} mengganti password sendiri", $user);

        return redirect()->route('akun.edit')->with('success', 'Password berhasil diganti.');
    }
}
