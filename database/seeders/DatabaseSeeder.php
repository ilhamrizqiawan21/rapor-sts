<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\AppSetting;
use App\Models\MadrasahProfile;
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
                'name' => 'MTSS AL-IHSAN',
                'level' => 'MTs',
                'nsm' => '121232170001',
                'npsn' => '20278901',
                'address' => 'JL. GALANGGANG NO. 69 RT. 01 RW. 18',
                'district' => 'Batujajar',
                'regency' => 'Bandung Barat',
                'province' => 'Jawa Barat',
                'contact' => '022-6866xxx',
                'headmaster_name' => 'Drs. H. Ahmad Sanusi, M.Pd.I',
                'headmaster_nip' => '197001011995031001',
                'logo_left_path' => null,
                'logo_right_path' => null,
            ]
        );

        // 2. App Setting default
        AppSetting::firstOrCreate(
            ['id' => 1],
            [
                'app_name' => 'Rapor STS',
                'app_subtitle' => 'MTsS Al-Ihsan',
                'app_logo_path' => null,
                'login_image_path' => null,
                'topbar_color' => '#628b0d',
                'default_kktp' => 70.00,
            ]
        );

        // 3. User Operator default
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin MTsS Al-Ihsan',
                'password' => Hash::make('admin123'),
                'role' => Role::Operator,
                'nik' => '3204000000000001',
                'nuptk' => null,
                'nip' => null,
                'gender' => 'L',
                'is_active' => true,
            ]
        );

        // 4. Mapel MTs bawaan
        $this->call(SubjectSeeder::class);
    }
}
