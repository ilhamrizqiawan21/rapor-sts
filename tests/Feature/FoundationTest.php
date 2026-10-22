<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_configuration_locale_and_timezone(): void
    {
        $this->assertSame('id', Config::get('app.locale'));
        $this->assertSame('Asia/Jakarta', Config::get('app.timezone'));
    }

    public function test_operator_dashboard_page_renders_mockup_components(): void
    {
        $operator = User::factory()->operator()->create([
            'name' => 'Admin Madrasah',
        ]);

        $response = $this->actingAs($operator)->get('/operator/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard Operator');
        $response->assertSee('Admin Madrasah');
        $response->assertSee('Info Aplikasi');
        $response->assertSee('Aktivitas Terbaru');
    }

    public function test_blade_components_render_correctly(): void
    {
        $btnView = $this->blade('<x-btn variant="pri">Simpan</x-btn>');
        $btnView->assertSee('btn b-pri', false);
        $btnView->assertSee('Simpan');

        $alertView = $this->blade('<x-alert variant="warning">Peringatan</x-alert>');
        $alertView->assertSee('alert a-war', false);
        $alertView->assertSee('Peringatan');

        $statusPillView = $this->blade('<x-status-pill status="terkirim" />');
        $statusPillView->assertSee('pill', false);
        $statusPillView->assertSee('Terkirim');

        $progressView = $this->blade('<x-progress :value="80" :showText="true" />');
        $progressView->assertSee('width: 80%', false);
        $progressView->assertSee('80%');
    }
}
