<?php

namespace Tests\Feature;

use App\Enums\Semester;
use App\Models\AcademicYear;
use App\Models\Period;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $year = AcademicYear::factory()->create(['name' => '2026/2027']);
        Period::factory()->create([
            'academic_year_id' => $year->id,
            'semester' => Semester::Ganjil,
            'is_active' => true,
        ]);

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Silakan login');
        $response->assertSee('Username (NIK/NUPTK)');
        $response->assertSee('Tahun Pelajaran');
        $response->assertSee('Semester');
        $response->assertSee('2026/2027');
    }

    public function test_users_can_authenticate_and_period_is_stored_in_session(): void
    {
        $year = AcademicYear::factory()->create(['name' => '2026/2027']);
        $period = Period::factory()->create([
            'academic_year_id' => $year->id,
            'semester' => Semester::Ganjil,
            'is_active' => true,
        ]);

        $user = User::factory()->operator()->create([
            'username' => 'testoperator',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'testoperator',
            'password' => 'secret123',
            'academic_year_id' => $year->id,
            'semester' => 1,
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('operator.dashboard'));
        $this->assertSame($period->id, session('period_id'));
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $year = AcademicYear::factory()->create();
        Period::factory()->create(['academic_year_id' => $year->id, 'semester' => Semester::Ganjil]);

        User::factory()->create([
            'username' => 'activeuser',
            'password' => 'secret123',
        ]);

        $response = $this->post('/login', [
            'username' => 'activeuser',
            'password' => 'wrong-password',
            'academic_year_id' => $year->id,
            'semester' => 1,
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }

    public function test_login_cannot_create_a_missing_period(): void
    {
        $year = AcademicYear::factory()->create();
        User::factory()->operator()->create(['username' => 'operator', 'password' => 'secret123']);

        $this->post('/login', [
            'username' => 'operator',
            'password' => 'secret123',
            'academic_year_id' => $year->id,
            'semester' => 1,
        ])->assertSessionHasErrors('semester');

        $this->assertGuest();
        $this->assertDatabaseMissing('periods', ['academic_year_id' => $year->id, 'semester' => 1]);
    }

    public function test_inactive_users_cannot_authenticate(): void
    {
        $year = AcademicYear::factory()->create();
        Period::factory()->create(['academic_year_id' => $year->id, 'semester' => Semester::Ganjil]);

        User::factory()->inactive()->create([
            'username' => 'inactiveuser',
            'password' => 'secret123',
        ]);

        $response = $this->post('/login', [
            'username' => 'inactiveuser',
            'password' => 'secret123',
            'academic_year_id' => $year->id,
            'semester' => 1,
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }

    public function test_deactivated_user_loses_access_with_existing_session(): void
    {
        $teacher = User::factory()->guru()->create(['is_active' => true]);
        $this->actingAs($teacher);
        $teacher->update(['is_active' => false]);

        $this->get(route('guru.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_redirects_based_on_role(): void
    {
        $year = AcademicYear::factory()->create();
        Period::factory()->create(['academic_year_id' => $year->id, 'semester' => Semester::Ganjil]);

        $guru = User::factory()->guru()->create(['username' => 'guru1', 'password' => 'pass']);
        $kepala = User::factory()->kepala()->create(['username' => 'kepala1', 'password' => 'pass']);

        $resGuru = $this->post('/login', [
            'username' => 'guru1',
            'password' => 'pass',
            'academic_year_id' => $year->id,
            'semester' => 1,
        ]);
        $resGuru->assertRedirect(route('guru.dashboard'));

        $this->post('/logout');

        $resKepala = $this->post('/login', [
            'username' => 'kepala1',
            'password' => 'pass',
            'academic_year_id' => $year->id,
            'semester' => 1,
        ]);
        $resKepala->assertRedirect(route('kepala.dashboard'));
    }

    public function test_login_rate_limiting_after_five_attempts(): void
    {
        $year = AcademicYear::factory()->create();
        Period::factory()->create(['academic_year_id' => $year->id, 'semester' => Semester::Ganjil]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'username' => 'throttleuser',
                'password' => 'wrong',
                'academic_year_id' => $year->id,
                'semester' => 1,
            ]);
        }

        $response = $this->post('/login', [
            'username' => 'throttleuser',
            'password' => 'wrong',
            'academic_year_id' => $year->id,
            'semester' => 1,
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->operator()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_role_middleware_prevents_unauthorized_access(): void
    {
        $guru = User::factory()->guru()->create();
        $response = $this->actingAs($guru)->get('/operator/dashboard');

        $response->assertStatus(403);
    }

    public function test_read_only_for_headmaster_blocks_non_get_requests(): void
    {
        Route::post('/kepala/test-write', function () {
            return response()->json(['success' => true]);
        })->middleware(['auth', 'role:kepala', 'headmaster.readonly']);

        $kepala = User::factory()->kepala()->create();

        $response = $this->actingAs($kepala)->post('/kepala/test-write');

        $response->assertStatus(403);
    }
}
