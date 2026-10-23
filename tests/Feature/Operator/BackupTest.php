<?php

namespace Tests\Feature\Operator;

use App\Enums\Role;
use App\Models\User;
use App\Services\Backup\DatabaseBackup;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->dir = storage_path('framework/testing/backups-'.uniqid());
        config(['backup.path' => $this->dir, 'backup.keep' => 2]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    public function test_operator_can_create_and_download_backup(): void
    {
        $op = User::where('username', 'admin')->first();

        $this->actingAs($op)->post(route('operator.backup.store'))->assertSessionHas('success');

        $files = app(DatabaseBackup::class)->list();
        $this->assertCount(1, $files);
        $sql = gzdecode(file_get_contents($this->dir.'/'.$files[0]['name']));
        $this->assertStringContainsString('users', $sql);

        $this->actingAs($op)->get(route('operator.backup.index'))->assertOk()->assertSee($files[0]['name']);
        $this->actingAs($op)->get(route('operator.backup.download', $files[0]['name']))->assertOk();
    }

    public function test_old_backups_are_pruned_and_bad_names_rejected(): void
    {
        $svc = app(DatabaseBackup::class);
        File::ensureDirectoryExists($this->dir);
        foreach (['20250101-000001', '20250102-000001', '20250103-000001'] as $ts) {
            touch($this->dir."/backup-{$ts}.sql.gz");
        }
        $svc->prune();
        $this->assertCount(2, $svc->list());

        $this->assertNull($svc->resolve('../.env'));
        $this->actingAs(User::where('username', 'admin')->first())
            ->get(route('operator.backup.download', 'evil.txt'))->assertNotFound();
    }

    public function test_non_operator_forbidden(): void
    {
        $guru = User::factory()->create(['role' => Role::Guru]);
        $this->actingAs($guru)->get(route('operator.backup.index'))->assertForbidden();
        $this->actingAs($guru)->post(route('operator.backup.store'))->assertForbidden();
    }

    public function test_command_creates_backup(): void
    {
        $this->artisan('backup:run')->assertSuccessful();
        $this->assertCount(1, app(DatabaseBackup::class)->list());
    }

    public function test_dashboard_renders_system_activity_without_user(): void
    {
        $this->artisan('backup:run')->assertSuccessful();

        $this->actingAs(User::where('username', 'admin')->first())
            ->get(route('operator.dashboard'))->assertOk()->assertSee('Sistem');
    }
}
