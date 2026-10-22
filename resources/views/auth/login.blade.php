<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Aplikasi — {{ $setting->app_name ?? 'Rapor STS' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="background: #f4f6f9">
<div style="min-height: 100vh; display: flex; flex-wrap: wrap; font-family: 'Source Sans 3', Arial, sans-serif; color: #212529; background: #f4f6f9">
    <div style="flex: 3 1 520px; min-height: 420px; background: #2f7d32; color: #fff; display: flex; flex-direction: column; justify-content: space-between; padding: 48px; box-sizing: border-box; gap: 32px">
        <div style="display: flex; align-items: center; gap: 16px">
            <div style="width: 72px; height: 72px; border-radius: 12px; background: #fff; color: #2f7d32; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; text-align: center; line-height: 1.1">
                @if(!empty($profile?->logo_left_path))
                    <img src="{{ asset('storage/' . $profile->logo_left_path) }}" alt="Logo Madrasah" style="max-width: 60px; max-height: 60px; object-fit: contain">
                @else
                    LOGO<br>MADRASAH
                @endif
            </div>
            <div>
                <div style="font-size: 30px; font-weight: 700; line-height: 1.1">{{ $profile->name ?? 'Nama Madrasah' }}</div>
                <div style="font-size: 16px; opacity: .92">{{ $profile->district ?? '' }} · {{ $profile->regency ?? '' }}</div>
            </div>
        </div>

        <div style="flex: 1 1 auto; min-height: 260px; border: 2px dashed rgba(255,255,255,.55); border-radius: 12px; display: flex; align-items: center; justify-content: center; text-align: center; font-size: 15px; padding: 24px">
            @if(!empty($setting?->login_image_path))
                <img src="{{ asset('storage/' . $setting->login_image_path) }}" alt="Ilustrasi" style="max-width: 100%; max-height: 280px; object-fit: cover; border-radius: 8px">
            @else
                [FOTO GEDUNG / ILUSTRASI MADRASAH — dapat diganti di Profil Madrasah]
            @endif
        </div>

        <div style="font-size: 15px; line-height: 1.5; max-width: 560px">
            Pengolahan nilai Sumatif Tengah Semester. Nilai yang diinput di sini dapat diekspor ke format template Excel saat akhir semester.
        </div>
    </div>

    <div style="flex: 2 1 380px; display: flex; align-items: center; justify-content: center; padding: 40px 24px; box-sizing: border-box">
        <form method="POST" action="{{ route('login') }}" style="width: 100%; max-width: 380px; background: #fff; border-radius: 6px; box-shadow: 0 1px 4px rgba(0,0,0,.15); padding: 28px; box-sizing: border-box; display: flex; flex-direction: column; gap: 14px">
            @csrf

            <div style="display: flex; align-items: center; gap: 12px">
                <div style="width: 48px; height: 48px; border-radius: 8px; background: {{ $setting->topbar_color ?? '#628b0d' }}; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px">
                    RS
                </div>
                <div>
                    <div style="font-size: 20px; font-weight: 700; color: #1d5c2e; line-height: 1.1">{{ $setting->app_name ?? 'Rapor STS' }}</div>
                    <div style="font-size: 13px; color: #555">{{ $setting->app_subtitle ?? '' }}</div>
                </div>
            </div>

            <div style="font-size: 17px; font-weight: 600; margin-top: 6px">Silakan login</div>

            @if ($errors->any())
                <div class="alert a-dan" style="background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; padding: 10px 12px; border-radius: 4px; font-size: 13px">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="f">
                <label for="u">Username (NIK/NUPTK)</label>
                <input id="u" name="username" class="in" type="text" placeholder="Username" value="{{ old('username') }}" required autofocus>
            </div>

            <div class="f">
                <label for="p">Password</label>
                <input id="p" name="password" class="in" type="password" placeholder="Password" required>
            </div>

            <div class="f">
                <label for="ta">Tahun Pelajaran</label>
                <select id="ta" name="academic_year_id" class="in" required>
                    @foreach($academicYears as $year)
                        <option value="{{ $year->id }}" @selected(old('academic_year_id', $activePeriod?->academic_year_id) == $year->id)>
                            {{ $year->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="f">
                <label for="sm">Semester</label>
                <select id="sm" name="semester" class="in" required>
                    <option value="1" @selected(old('semester', $activePeriod?->semester?->value ?? 1) == 1)>Ganjil</option>
                    <option value="2" @selected(old('semester', $activePeriod?->semester?->value ?? 1) == 2)>Genap</option>
                </select>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 4px">
                <button type="submit" class="btn b-pri" style="min-height: 44px; padding: 0 24px; font-size: 15px">
                    Login
                </button>
                <a href="#" style="font-size: 14px" onclick="alert('Silakan hubungi operator madrasah untuk mereset password.'); return false;">
                    Lupa Password Admin?
                </a>
            </div>

            <div style="font-size: 12px; color: #6c757d; line-height: 1.5; border-top: 1px solid #e9ecef; padding-top: 12px">
                Aplikasi pendamping penilaian tengah semester. Bukan aplikasi resmi Kementerian Agama.<br>Versi 1.0
            </div>
        </form>
    </div>
</div>
</body>
</html>

