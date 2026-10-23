<?php

namespace Tests\Feature\Operator;

use App\Enums\Role;
use App\Models\User;
use App\Services\Backup\DatabaseBackup;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupRestoreTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = storage_path('framework/testing/restore-'.uniqid());
        config(['backup.path' => $this->dir]);
        $this->artisan('migrate:fresh')->assertSuccessful();
        $this->seed(DatabaseSeeder::class);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    public function test_operator_restores_stored_backup_and_keeps_safety_copy(): void
    {
        $operator = User::where('username', 'admin')->firstOrFail();
        $original = $operator->name;
        $backup = app(DatabaseBackup::class);
        $name = $backup->run();
        $operator->update(['name' => 'Changed after backup']);

        $this->actingAs($operator)->post(route('operator.backup.restore'), [
            'source' => 'stored', 'file' => $name, 'confirmation' => 'RESTORE',
        ])->assertRedirect(route('operator.backup.index'))->assertSessionHas('success');

        $this->assertSame($original, $operator->fresh()->name);
        $this->assertCount(2, $backup->list());
    }

    public function test_upload_restores_and_invalid_requests_do_not_change_data(): void
    {
        $operator = User::where('username', 'admin')->firstOrFail();
        $backup = app(DatabaseBackup::class);
        $name = $backup->run();
        $contents = file_get_contents($this->dir.'/'.$name);
        $original = $operator->name;
        $operator->update(['name' => 'Changed after backup']);

        $this->actingAs($operator)->post(route('operator.backup.restore'), [
            'source' => 'upload', 'archive' => UploadedFile::fake()->createWithContent('backup.sql.gz', 'invalid'),
            'confirmation' => 'RESTORE',
        ])->assertSessionHas('error');
        $this->assertSame('Changed after backup', $operator->fresh()->name);

        $this->actingAs($operator)->post(route('operator.backup.restore'), [
            'source' => 'upload', 'archive' => UploadedFile::fake()->createWithContent('backup.sql.gz', $contents),
            'confirmation' => 'RESTORE',
        ])->assertSessionHas('success');
        $this->assertSame($original, $operator->fresh()->name);
    }

    public function test_restore_requires_operator_and_confirmation(): void
    {
        $name = app(DatabaseBackup::class)->run();
        $guru = User::factory()->create(['role' => Role::Guru]);
        $this->actingAs($guru)->post(route('operator.backup.restore'), [
            'source' => 'stored', 'file' => $name, 'confirmation' => 'RESTORE',
        ])->assertForbidden();

        $operator = User::where('username', 'admin')->firstOrFail();
        $this->actingAs($operator)->post(route('operator.backup.restore'), [
            'source' => 'stored', 'file' => $name, 'confirmation' => 'wrong',
        ])->assertSessionHasErrors('confirmation');
        $this->assertCount(1, app(DatabaseBackup::class)->list());
    }
}
