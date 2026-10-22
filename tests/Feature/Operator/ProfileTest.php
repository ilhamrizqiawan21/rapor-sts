<?php

namespace Tests\Feature\Operator;

use App\Models\MadrasahProfile;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_operator_can_view_profile_page(): void
    {
        $operator = User::where('username', 'admin')->first();

        $response = $this->actingAs($operator)->get(route('operator.profil.edit'));

        $response->assertStatus(200);
        $response->assertSee('Profil Madrasah');
        $response->assertSee('Nama Madrasah');
        $response->assertSee('Branding Aplikasi');
    }

    public function test_operator_can_update_profile_and_branding(): void
    {
        Storage::fake('public');
        $operator = User::where('username', 'admin')->first();

        $logoLeft = UploadedFile::fake()->image('logo_left.png', 400, 400);

        $response = $this->actingAs($operator)->post(route('operator.profil.update'), [
            'parent_institution' => 'KEMENTERIAN AGAMA REPUBLIK INDONESIA',
            'name' => 'MTs Nurul Huda',
            'level' => 'MTs',
            'nsm' => '121232170001',
            'npsn' => '20278901',
            'address' => 'JL. PENDIDIKAN NO. 69',
            'district' => 'Sukasari',
            'regency' => 'Bandung',
            'province' => 'Jawa Barat',
            'headmaster_name' => 'Drs. H. Ahmad Sanusi, M.Pd.I',
            'app_name' => 'Rapor STS Madrasah',
            'topbar_color' => '#1e7e34',
            'logo_left' => $logoLeft,
        ]);

        $response->assertRedirect(route('operator.profil.edit'));

        $this->assertDatabaseHas('madrasah_profiles', [
            'name' => 'MTs Nurul Huda',
            'district' => 'Sukasari',
        ]);

        $this->assertDatabaseHas('app_settings', [
            'app_name' => 'Rapor STS Madrasah',
            'topbar_color' => '#1e7e34',
        ]);

        $profile = MadrasahProfile::first();
        $this->assertNotNull($profile->logo_left_path);
        Storage::disk('public')->assertExists($profile->logo_left_path);
    }
}
