<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarDropdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_sidebar_renders_dropdown_accordion_structure(): void
    {
        $this->seed(DemoSeeder::class);
        $operator = User::where('username', 'admin')->first();

        $response = $this->actingAs($operator)->get(route('operator.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('nav-group', false);
        $response->assertSee('nav-toggle', false);
        $response->assertSee('nav-sub', false);
        $response->assertSee('chev', false);
        $response->assertSee('<span>Lembaga</span>', false);
        $response->assertSee('<span>Guru</span>', false);
        $response->assertSee('<span>Siswa</span>', false);
        $response->assertSee('<span>Rapor</span>', false);
    }

    public function test_active_route_automatically_opens_parent_dropdown_group(): void
    {
        $this->seed(DemoSeeder::class);
        $operator = User::where('username', 'admin')->first();

        // When visiting Mata Pelajaran (subjects)
        $response = $this->actingAs($operator)->get(route('operator.subjects.index'));

        $response->assertStatus(200);
        // Lembaga dropdown should have open class and aria-expanded=true
        $response->assertSee('<div class="nav-group open">', false);
        $response->assertSee('aria-expanded="true"', false);
        $response->assertSee('class="sub on">', false);
        $response->assertSee('<span>Mata Pelajaran</span>', false);
    }

    public function test_guru_sidebar_renders_collapsible_subject_and_wali_kelas_dropdowns(): void
    {
        $this->seed(DemoSeeder::class);
        $guru = User::where('username', 'gurufikih')->first();

        $response = $this->actingAs($guru)->get(route('guru.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('nav-group', false);
        $response->assertSee('nav-toggle', false);
        $response->assertSee('nav-sub', false);
    }

    public function test_wali_kelas_page_opens_wali_kelas_dropdown(): void
    {
        $this->seed(DemoSeeder::class);
        $guru = User::where('username', 'gurufikih')->first();

        $response = $this->actingAs($guru)->get(route('wali.kehadiran.index'));

        $response->assertStatus(200);
        $response->assertSee('<div class="nav-group open">', false);
        $response->assertSee('aria-expanded="true"', false);
        $response->assertSee('class="sub on">', false);
        $response->assertSee('Kehadiran &amp; Catatan', false);
    }

    public function test_layout_renders_hamburger_button_and_backdrop_with_toggle_handler(): void
    {
        $this->seed(DemoSeeder::class);
        $operator = User::where('username', 'admin')->first();

        $response = $this->actingAs($operator)->get(route('operator.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('id="hamburgerBtn"', false);
        $response->assertSee('id="sidebarBackdrop"', false);
        $response->assertSee('window.toggleSidebar', false);
    }
}
