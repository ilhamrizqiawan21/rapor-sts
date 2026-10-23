<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\Backup\DatabaseBackup;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function index(DatabaseBackup $backup): View
    {
        return view('operator.backup.index', [
            'files' => $backup->list(),
            'schedule' => config('backup.schedule'),
            'time' => config('backup.time'),
            'keep' => config('backup.keep'),
        ]);
    }

    public function store(DatabaseBackup $backup): RedirectResponse
    {
        try {
            $name = $backup->run();
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Backup gagal dibuat. Periksa log aplikasi.');
        }

        ActivityLogger::log('backup_database', "Membuat backup database {$name}");

        return back()->with('success', "Backup {$name} berhasil dibuat.");
    }

    public function download(string $file, DatabaseBackup $backup): BinaryFileResponse
    {
        $path = $backup->resolve($file);
        abort_unless($path, 404);

        ActivityLogger::log('download_backup', "Mengunduh backup database {$file}");

        return response()->download($path, $file);
    }
}
