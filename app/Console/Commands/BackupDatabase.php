<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Services\Backup\DatabaseBackup;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'Buat backup database (.sql.gz) di storage/app/backups';

    public function handle(DatabaseBackup $backup): int
    {
        try {
            $name = $backup->run();
        } catch (\Throwable $e) {
            $this->error('Backup gagal: '.$e->getMessage());
            report($e);

            return self::FAILURE;
        }

        ActivityLog::create([
            'user_id' => null,
            'action' => 'backup_database',
            'description' => "Backup database otomatis dibuat: {$name}",
            'created_at' => now(),
        ]);
        $this->info("Backup dibuat: {$name}");

        return self::SUCCESS;
    }
}
