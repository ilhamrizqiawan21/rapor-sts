<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\Backup\DatabaseBackup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

            return back()->with('error', 'Backup gagal dibuat: '.$e->getMessage());
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

    public function restore(Request $request, DatabaseBackup $backup): RedirectResponse
    {
        $data = $request->validate([
            'source' => ['required', 'in:stored,upload'],
            'file' => ['required_if:source,stored', 'nullable', 'string'],
            'archive' => ['required_if:source,upload', 'nullable', 'file', 'max:51200'],
            'confirmation' => ['required', 'in:RESTORE'],
        ], [
            'confirmation.in' => 'Ketik RESTORE untuk mengonfirmasi penggantian data.',
        ]);

        if ($data['source'] === 'stored') {
            $path = $backup->resolve($data['file']);
            if (! $path) {
                return back()->with('error', 'Berkas backup tidak ditemukan.');
            }
            $source = $data['file'];
        } else {
            $upload = $request->file('archive');
            if (! str_ends_with(strtolower($upload->getClientOriginalName()), '.sql.gz')) {
                return back()->with('error', 'Unggah berkas dengan ekstensi .sql.gz.');
            }
            $path = $upload->getRealPath();
            $source = $upload->getClientOriginalName();
        }

        try {
            $safetyName = $backup->restore($path);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Restore gagal: '.$e->getMessage().' Jika data berubah, pulihkan dari backup pengaman di daftar backup.');
        }

        ActivityLogger::log('restore_database', "Restore database dari {$source}; backup pengaman: {$safetyName}");

        return redirect()->route('operator.backup.index')
            ->with('success', "Restore selesai. Backup data sebelumnya: {$safetyName}.");
    }
}
