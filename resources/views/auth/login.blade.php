<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Aplikasi — {{ $setting->app_name ?? 'Rapor STS' }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="background: #f4f6f9; margin: 0">
<div style="min-height: 100vh; display: flex; flex-wrap: wrap; font-family: 'Source Sans 3', Arial, sans-serif; color: #212529; background: #f4f6f9">
    
    {{-- Sisi Kiri: Branding & Ilustrasi Madrasah --}}
    <div style="flex: 3 1 520px; min-height: 480px; background: linear-gradient(135deg, #1b5e20 0%, #2f7d32 60%, #388e3c 100%); color: #fff; display: flex; flex-direction: column; justify-content: space-between; padding: 48px; box-sizing: border-box; gap: 28px; position: relative; overflow: hidden">
        
        {{-- Background pattern lembut --}}
        <div style="position: absolute; right: -80px; top: -80px; width: 320px; height: 320px; border-radius: 50%; background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0) 70%); pointer-events: none"></div>
        <div style="position: absolute; left: -100px; bottom: -100px; width: 360px; height: 360px; border-radius: 50%; background: radial-gradient(circle, rgba(255,255,255,0.06) 0%, rgba(255,255,255,0) 70%); pointer-events: none"></div>

        {{-- Header Identitas Madrasah --}}
        <div style="display: flex; align-items: center; gap: 18px; position: relative; z-index: 2">
            <div style="width: 76px; height: 76px; border-radius: 16px; background: #ffffff; color: #1b5e20; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(0,0,0,0.18); flex-shrink: 0; padding: 4px; box-sizing: border-box">
                @if(!empty($profile?->logo_left_path) && file_exists(storage_path('app/public/' . $profile->logo_left_path)))
                    <img src="{{ asset('storage/' . $profile->logo_left_path) }}" alt="Logo Madrasah" style="max-width: 68px; max-height: 68px; object-fit: contain">
                @else
                    {{-- Contoh Logo Vektor Madrasah --}}
                    <svg width="68" height="68" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="50" cy="50" r="48" fill="#ffffff" stroke="#c99700" stroke-width="2.5"/>
                        <circle cx="50" cy="50" r="42" fill="#1b5e20"/>
                        <circle cx="50" cy="50" r="39" stroke="#f1c40f" stroke-width="1" stroke-dasharray="2 2"/>
                        <!-- Bintang atas -->
                        <path d="M50 17 L52 22 L57 22 L53 25 L55 30 L50 27 L45 30 L47 25 L43 22 L48 22 Z" fill="#f1c40f"/>
                        <!-- Kitab / Buku Terbuka -->
                        <path d="M26 56 C36 50 46 53 50 58 C54 53 64 50 74 56 L74 40 C64 34 54 37 50 42 C46 37 36 34 26 40 Z" fill="#ffffff" stroke="#f1c40f" stroke-width="1.5"/>
                        <path d="M50 42 L50 58" stroke="#1b5e20" stroke-width="1.5"/>
                        <path d="M32 44 C38 41 44 42 47 46" stroke="#1b5e20" stroke-width="1.2"/>
                        <path d="M68 44 C62 41 56 42 53 46" stroke="#1b5e20" stroke-width="1.2"/>
                        <path d="M32 48 C38 45 44 46 47 50" stroke="#1b5e20" stroke-width="1.2"/>
                        <path d="M68 48 C62 45 56 46 53 50" stroke="#1b5e20" stroke-width="1.2"/>
                        <!-- Pita & Tulisan -->
                        <path d="M30 68 C42 64 58 64 70 68 L66 73 C56 70 44 70 34 73 Z" fill="#f1c40f"/>
                        <text x="50" y="82" font-family="'Source Sans 3', sans-serif" font-size="7.5" font-weight="bold" fill="#ffffff" text-anchor="middle" letter-spacing="1">MADRASAH</text>
                    </svg>
                @endif
            </div>
            <div>
                <div style="font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 1.5px; opacity: .88; color: #d4edda">
                    {{ $profile->parent_institution ?? '' }}
                </div>
                <div style="font-size: 28px; font-weight: 700; line-height: 1.15; margin: 2px 0 3px 0">
                    {{ $profile->name ?? 'Nama Madrasah' }}
                </div>
                <div style="font-size: 14.5px; opacity: .92; color: #e8f5e9">
                    @if(!empty($profile->district) || !empty($profile->regency))
                        {{ \App\Models\MadrasahProfile::formatRegion($profile->district, $profile->regency, null) }}
                    @else
                        Profil madrasah dapat diatur melalui akun Admin
                    @endif
                </div>
            </div>
        </div>

        {{-- Side Showcase Visual / Ilustrasi Gedung & Sistem Penilaian --}}
        <div style="flex: 1 1 auto; min-height: 250px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.2); border-radius: 16px; padding: 24px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: center; backdrop-filter: blur(6px); box-shadow: 0 10px 24px rgba(0,0,0,0.12); position: relative; z-index: 2">
            @if(!empty($setting?->login_image_path) && file_exists(storage_path('app/public/' . $setting->login_image_path)))
                <img src="{{ asset('storage/' . $setting->login_image_path) }}" alt="Gedung Madrasah" style="width: 100%; max-height: 260px; object-fit: cover; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.2)">
            @else
                {{-- Ilustrasi Visual Modern Gedung Madrasah & Rapor STS --}}
                <div style="display: flex; flex-direction: column; gap: 16px">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap">
                        <div style="display: flex; align-items: center; gap: 10px">
                            <span style="background: rgba(241, 196, 15, 0.2); color: #f1c40f; border: 1px solid rgba(241, 196, 15, 0.4); padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px">
                                Aplikasi Pendamping
                            </span>
                            <span style="font-size: 13px; opacity: .9; color: #e8f5e9">Tahun Ajaran Aktif {{ $activePeriod?->academicYear?->name ?? '-' }}</span>
                        </div>
                    </div>

                    <div style="font-size: 20px; font-weight: 700; line-height: 1.3">
                        Sistem Penilaian Sumatif Tengah Semester & Rekap Leger Kelas
                    </div>

                    {{-- 4 Fitur Unggulan Sistem --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 4px">
                        <div style="background: rgba(0,0,0,0.12); padding: 10px 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.12); font-size: 13px">
                            <div style="color: #f1c40f; font-weight: bold; margin-bottom: 2px">✓ Sumatif & STS</div>
                            <div style="opacity: .88; font-size: 12px">Penilaian Lingkup Materi & Tes STS berbobot otomatis</div>
                        </div>
                        <div style="background: rgba(0,0,0,0.12); padding: 10px 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.12); font-size: 13px">
                            <div style="color: #f1c40f; font-weight: bold; margin-bottom: 2px">✓ Rapor STS</div>
                            <div style="opacity: .88; font-size: 12px">Cetak PDF rapor siswa per kelas dengan kop & ttd</div>
                        </div>
                        <div style="background: rgba(0,0,0,0.12); padding: 10px 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.12); font-size: 13px">
                            <div style="color: #f1c40f; font-weight: bold; margin-bottom: 2px">✓ Leger Kurikulum Merdeka</div>
                            <div style="opacity: .88; font-size: 12px">Excel satu sheet, nilai akhir per mata pelajaran</div>
                        </div>
                        <div style="background: rgba(0,0,0,0.12); padding: 10px 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.12); font-size: 13px">
                            <div style="color: #f1c40f; font-weight: bold; margin-bottom: 2px">✓ Export Template Excel</div>
                            <div style="opacity: .88; font-size: 12px">Isi otomatis template Excel penilaian tanpa ketik ulang</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Footer Sisi Kiri --}}
        <div style="font-size: 13.5px; line-height: 1.5; max-width: 560px; opacity: .9; position: relative; z-index: 2">
            Platform pengolahan nilai dan rapor tengah semester terpadu. Data nilai siswa terjaga aman. Bukan aplikasi resmi Kementerian Agama.
        </div>
    </div>

    {{-- Sisi Kanan: Form Login --}}
    <div style="flex: 2 1 380px; display: flex; align-items: center; justify-content: center; padding: 40px 24px; box-sizing: border-box">
        <form method="POST" action="{{ route('login') }}" style="width: 100%; max-width: 400px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,.08); border: 1px solid #e9ecef; padding: 32px; box-sizing: border-box; display: flex; flex-direction: column; gap: 14px">
            @csrf

            {{-- Brand Logo Aplikasi --}}
            <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 4px">
                <div style="width: 48px; height: 48px; border-radius: 10px; background: linear-gradient(135deg, #1b5e20, #2f7d32); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; box-shadow: 0 4px 10px rgba(47, 125, 50, 0.25); flex-shrink: 0; overflow: hidden">
                    @if(!empty($setting?->app_logo_path) && file_exists(storage_path('app/public/' . $setting->app_logo_path)))
                        <img src="{{ asset('storage/' . $setting->app_logo_path) }}" alt="Logo Aplikasi" style="max-width: 40px; max-height: 40px; object-fit: contain">
                    @else
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                            <path d="M9 8h6"/>
                            <path d="M9 12h4"/>
                        </svg>
                    @endif
                </div>
                <div>
                    <div style="font-size: 21px; font-weight: 700; color: #1b5e20; line-height: 1.1">{{ $setting->app_name ?? 'Rapor STS' }}</div>
                    <div style="font-size: 13px; color: #6c757d; margin-top: 2px">{{ $setting->app_subtitle ?? 'Sistem Penilaian Sumatif Tengah Semester' }}</div>
                </div>
            </div>

            <div style="font-size: 17px; font-weight: 600; color: #343a40; margin-top: 4px">Silakan login</div>

            @if ($errors->any())
                <div class="alert a-dan" style="background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; padding: 10px 12px; border-radius: 6px; font-size: 13px">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="f">
                <label for="u" style="font-size: 13px; font-weight: 600; color: #495057">Username (NIK/NUPTK)</label>
                <input id="u" name="username" class="in" type="text" placeholder="Masukkan NIK atau NUPTK" value="{{ old('username') }}" required autofocus style="height: 42px; border-radius: 6px">
            </div>

            <div class="f">
                <label for="p" style="font-size: 13px; font-weight: 600; color: #495057">Password</label>
                <input id="p" name="password" class="in" type="password" placeholder="Masukkan password" required style="height: 42px; border-radius: 6px">
            </div>

            <div class="f">
                <label for="ta" style="font-size: 13px; font-weight: 600; color: #495057">Tahun Pelajaran</label>
                <select id="ta" name="academic_year_id" class="in" required style="height: 42px; border-radius: 6px">
                    @foreach($academicYears as $year)
                        <option value="{{ $year->id }}" @selected(old('academic_year_id', $activePeriod?->academic_year_id) == $year->id)>
                            {{ $year->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="f">
                <label for="sm" style="font-size: 13px; font-weight: 600; color: #495057">Semester</label>
                <select id="sm" name="semester" class="in" required style="height: 42px; border-radius: 6px">
                    <option value="1" @selected(old('semester', $activePeriod?->semester?->value ?? 1) == 1)>Semester 1 (Ganjil)</option>
                    <option value="2" @selected(old('semester', $activePeriod?->semester?->value ?? 1) == 2)>Semester 2 (Genap)</option>
                </select>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 6px">
                <button type="submit" class="btn b-pri" style="min-height: 44px; padding: 0 28px; font-size: 15px; font-weight: 600; border-radius: 6px; cursor: pointer">
                    Masuk
                </button>
                <a href="#" style="font-size: 13.5px; color: #1b5e20; text-decoration: none" onclick="alert('Silakan hubungi admin madrasah untuk mereset password.'); return false;">
                    Lupa Password?
                </a>
            </div>

            <div style="font-size: 12px; color: #6c757d; line-height: 1.5; border-top: 1px solid #e9ecef; padding-top: 14px; margin-top: 4px; text-align: center">
                Aplikasi Penilaian Sumatif Tengah Semester Madrasah<br>Versi 1.0
            </div>
        </form>
    </div>
</div>
</body>
</html>
