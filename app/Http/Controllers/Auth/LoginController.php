<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AcademicYear;
use App\Models\AppSetting;
use App\Models\MadrasahProfile;
use App\Models\Period;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Tampilkan formulir login.
     */
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user()->role);
        }

        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activePeriod = Period::with('academicYear')->where('is_active', true)->first();
        $profile = MadrasahProfile::first();
        $setting = AppSetting::first();

        return view('auth.login', compact('academicYears', 'activePeriod', 'profile', 'setting'));
    }

    /**
     * Proses autentikasi login.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return $this->redirectBasedOnRole($request->user()->role);
    }

    /**
     * Proses logout user.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Arahkan user ke dashboard sesuai role.
     */
    public function home(): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        return $this->redirectBasedOnRole(Auth::user()->role);
    }

    private function redirectBasedOnRole(Role|string $role): RedirectResponse
    {
        $roleValue = is_string($role) ? $role : $role->value;

        return match ($roleValue) {
            Role::Operator->value => redirect()->intended(route('operator.dashboard')),
            Role::Guru->value => redirect()->intended(route('guru.dashboard')),
            Role::Kepala->value => redirect()->intended(route('kepala.dashboard')),
            default => redirect()->route('login'),
        };
    }
}
