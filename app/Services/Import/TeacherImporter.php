<?php

namespace App\Services\Import;

use App\Enums\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TeacherImporter
{
    /**
     * @return array{success: bool, imported: int, errors: array<string>}
     */
    public function import(UploadedFile|string $file): array
    {
        $filePath = is_string($file) ? $file : $file->getRealPath();
        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['File tidak dapat dibaca: '.$e->getMessage()],
            ];
        }
        $sheet = $spreadsheet->getSheetByName('guru') ?? $spreadsheet->getActiveSheet();

        $highestRow = $sheet->getHighestDataRow();

        if ($highestRow < 4) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['File tidak memiliki baris data guru yang dapat diimpor (minimal baris 4).'],
            ];
        }

        $rowsToImport = [];
        $errors = [];
        $totalDataRows = 0;

        for ($row = 4; $row <= $highestRow; $row++) {
            $nik = trim((string) $sheet->getCell("A{$row}")->getValue());
            $nip = trim((string) $sheet->getCell("B{$row}")->getValue());
            $name = trim((string) $sheet->getCell("C{$row}")->getValue());
            $gender = strtoupper(trim((string) $sheet->getCell("D{$row}")->getValue()));
            $password = trim((string) $sheet->getCell("E{$row}")->getValue());

            // Lewati baris kosong total
            if ($nik === '' && $name === '') {
                continue;
            }

            $totalDataRows++;

            if ($nik === '') {
                $errors[] = "Baris {$row}: NIK/NUPTK wajib diisi.";

                continue;
            }

            if ($name === '') {
                $errors[] = "Baris {$row}: Nama wajib diisi.";

                continue;
            }

            if (! in_array($gender, ['L', 'P'], true)) {
                $gender = 'L'; // fallback default
            }

            $existing = User::where('username', $nik)->first();
            if ($existing && $existing->role !== Role::Guru) {
                $errors[] = "Baris {$row}: NIK/NUPTK '{$nik}' bentrok dengan akun non-guru dan dilewati.";

                continue;
            }

            $plainPassword = $password !== '' ? $password : 'password';

            $item = [
                'username' => $nik,
                'name' => $name,
                'nik' => $nik,
                'nip' => $nip !== '' ? $nip : null,
                'gender' => $gender,
                'password' => Hash::make($plainPassword),
                'password_plain' => $plainPassword,
                'role' => Role::Guru,
                'is_active' => true,
            ];

            // Guru yang sudah ada: jangan reset password bila kolom password dikosongkan.
            if ($existing && $password === '') {
                unset($item['password'], $item['password_plain']);
            }

            $rowsToImport[] = $item;
        }

        if ($totalDataRows > 0 && (count($errors) / $totalDataRows) > 0.20) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => array_merge(
                    ['Gagal: Lebih dari 20% baris data memiliki kesalahan. Seluruh file dibatalkan.'],
                    $errors
                ),
            ];
        }

        $importedCount = 0;
        DB::transaction(function () use ($rowsToImport, &$importedCount) {
            foreach ($rowsToImport as $data) {
                User::updateOrCreate(
                    ['username' => $data['username']],
                    $data
                );
                $importedCount++;
            }
        });

        ActivityLogger::log('import_teachers', "Berhasil mengimpor {$importedCount} data guru dari Excel.");

        return [
            'success' => true,
            'imported' => $importedCount,
            'errors' => $errors,
        ];
    }
}
