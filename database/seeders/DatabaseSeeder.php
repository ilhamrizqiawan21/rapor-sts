<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\Semester;
use App\Models\AcademicYear;
use App\Models\AppSetting;
use App\Models\MadrasahProfile;
use App\Models\Period;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Madrasah Profile default
        MadrasahProfile::firstOrCreate(
            ['id' => 1],
            [
                'parent_institution' => 'KEMENTERIAN AGAMA REPUBLIK INDONESIA',
                'foundation_name' => null,
                'name' => 'Nama Madrasah',
                'level' => 'MTs',
                'nsm' => '-',
                'npsn' => '-',
                'address' => '-',
                'district' => '-',
                'regency' => '-',
                'province' => '-',
                'contact' => null,
                'headmaster_name' => '-',
                'headmaster_nip' => null,
                'logo_left_path' => null,
                'logo_right_path' => null,
            ]
        );

        // 2. App Setting default
        AppSetting::firstOrCreate(
            ['id' => 1],
            [
                'app_name' => 'Rapor STS',
                'app_subtitle' => 'Nama Madrasah',
                'app_logo_path' => null,
                'login_image_path' => null,
                'topbar_color' => '#628b0d',
                'default_kktp' => 70.00,
            ]
        );

        // 3. Tahun pelajaran awal demo
        $academicYear = AcademicYear::firstOrCreate(['name' => '2026/2027']);

        Period::firstOrCreate(
            ['academic_year_id' => $academicYear->id, 'semester' => Semester::Ganjil],
            ['is_active' => true]
        );

        Period::firstOrCreate(
            ['academic_year_id' => $academicYear->id, 'semester' => Semester::Genap],
            ['is_active' => false]
        );

        // 4. User Operator default
        if (! User::where('username', 'admin')->exists()) {
            $initialPassword = config('app.initial_operator_password');
            if (app()->environment('production') && (! is_string($initialPassword) || strlen($initialPassword) < 12)) {
                throw new \RuntimeException('Set INITIAL_OPERATOR_PASSWORD (minimal 12 karakter) sebelum menjalankan seed di production.');
            }

            User::create([
                'username' => 'admin',
                'name' => 'Operator',
                'password' => Hash::make($initialPassword ?: 'admin123'),
                'role' => Role::Operator,
                'nik' => '3204000000000001',
                'nuptk' => null,
                'nip' => null,
                'gender' => 'L',
                'is_active' => true,
            ]);
        }

        // 4. Mapel MTs bawaan
        $this->call(SubjectSeeder::class);
    }
}
