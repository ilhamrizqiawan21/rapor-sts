<?php

namespace Tests\Feature\Operator;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_dashboard_renders_stats_and_activities(): void
    {
        $this->seed(DemoSeeder::class);

        $operator = User::where('username', 'admin')->first();

        $response = $this->actingAs($operator)->get(route('operator.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Dashboard Operator');
        $response->assertSee('Guru');
        $response->assertSee('Siswa');
        $response->assertSee('Kelas');
        $response->assertSee('Aktivitas Terbaru');
    }
}
