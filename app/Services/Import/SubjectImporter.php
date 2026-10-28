<?php

namespace App\Services\Import;

use App\Models\Subject;
use App\Services\ActivityLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SubjectImporter
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
            report($e);

            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['File tidak dapat dibaca. Pastikan file berasal dari template Excel mapel.'],
            ];
        }

        $sheet = $spreadsheet->getSheetByName('mapel') ?? $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();

        if ($highestRow < 4) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['File tidak memiliki baris data mapel yang dapat diimpor (minimal baris 4).'],
            ];
        }

        $rowsToImport = [];
        $errors = [];
        $seenKeys = [];
        $totalDataRows = 0;

        for ($row = 4; $row <= $highestRow; $row++) {
            $code = strtoupper($this->cellText($sheet, "A{$row}"));
            $name = $this->cellText($sheet, "B{$row}");
            $parentCode = strtoupper($this->cellText($sheet, "C{$row}"));
            $group = strtolower($this->cellText($sheet, "D{$row}"));
            $curriculum = strtolower($this->cellText($sheet, "E{$row}"));
            $sortOrder = $this->cellText($sheet, "F{$row}");
            $status = strtolower($this->cellText($sheet, "G{$row}"));

            if ($code === '' && $name === '' && $parentCode === '' && $group === '' && $curriculum === '' && $sortOrder === '' && $status === '') {
                continue;
            }

            $totalDataRows++;
            $rowKey = $this->key($curriculum, $code);

            if ($code === '') {
                $errors[] = "Baris {$row}: Kode mapel wajib diisi.";

                continue;
            }

            if (mb_strlen($code) > 20) {
                $errors[] = "Baris {$row}: Kode mapel maksimal 20 karakter.";

                continue;
            }

            if ($name === '') {
                $errors[] = "Baris {$row}: Nama mata pelajaran wajib diisi.";

                continue;
            }

            if (mb_strlen($name) > 150) {
                $errors[] = "Baris {$row}: Nama mata pelajaran maksimal 150 karakter.";

                continue;
            }

            if (! in_array($group, ['umum', 'mulok'], true)) {
                $errors[] = "Baris {$row}: Kelompok harus 'umum' atau 'mulok'.";

                continue;
            }

            if (! in_array($curriculum, ['merdeka', 'k13'], true)) {
                $errors[] = "Baris {$row}: Kurikulum harus 'merdeka' atau 'k13'.";

                continue;
            }

            if ($parentCode !== '' && mb_strlen($parentCode) > 20) {
                $errors[] = "Baris {$row}: Kode mapel induk maksimal 20 karakter.";

                continue;
            }

            if ($sortOrder === '') {
                $sortOrder = '0';
            }

            if (filter_var($sortOrder, FILTER_VALIDATE_INT) === false || (int) $sortOrder < 0) {
                $errors[] = "Baris {$row}: Urutan cetak harus berupa bilangan bulat minimal 0.";

                continue;
            }

            $isActive = $this->parseActiveStatus($status);
            if ($isActive === null) {
                $errors[] = "Baris {$row}: Status aktif harus 1/0 atau aktif/nonaktif.";

                continue;
            }

            if (isset($seenKeys[$rowKey])) {
                $errors[] = "Baris {$row}: Kode mapel '{$code}' dan kurikulum '{$curriculum}' duplikat dengan baris {$seenKeys[$rowKey]}.";

                continue;
            }

            $seenKeys[$rowKey] = $row;
            $rowsToImport[] = [
                'row' => $row,
                'key' => $rowKey,
                'code' => $code,
                'name' => $name,
                'parent_code' => $parentCode !== '' ? $parentCode : null,
                'group' => $group,
                'curriculum' => $curriculum,
                'sort_order' => (int) $sortOrder,
                'is_active' => $isActive,
            ];
        }

        if ($totalDataRows === 0) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['File tidak memiliki data mapel. Tambahkan data mulai baris 4.'],
            ];
        }

        $existingSubjects = Subject::query()->get(['id', 'curriculum', 'code']);
        $existingByKey = $existingSubjects->keyBy(fn (Subject $subject) => $this->key($subject->curriculum->value, $subject->code));
        $validRows = $rowsToImport;
        $parentErrors = [];
        do {
            $validRowKeys = array_fill_keys(array_column($validRows, 'key'), true);
            $nextValidRows = [];

            foreach ($validRows as $item) {
                if ($item['parent_code'] !== null) {
                    $parentKey = $this->key($item['curriculum'], $item['parent_code']);
                    $error = null;

                    if ($parentKey === $item['key']) {
                        $error = "Baris {$item['row']}: Mapel tidak boleh menjadi induk untuk dirinya sendiri.";
                    } elseif (! isset($validRowKeys[$parentKey]) && ! $existingByKey->has($parentKey)) {
                        $error = "Baris {$item['row']}: Mapel induk '{$item['parent_code']}' tidak ditemukan pada kurikulum '{$item['curriculum']}'.";
                    }

                    if ($error !== null) {
                        if (! isset($parentErrors[$item['key']])) {
                            $errors[] = $error;
                            $parentErrors[$item['key']] = true;
                        }

                        continue;
                    }
                }

                $nextValidRows[] = $item;
            }

            $hasRemovedRows = count($nextValidRows) !== count($validRows);
            $validRows = $nextValidRows;
        } while ($hasRemovedRows);

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

        if ($validRows === []) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => $errors ?: ['Tidak ada data mapel yang valid untuk diimpor.'],
            ];
        }

        $importedCount = 0;
        DB::transaction(function () use ($validRows, $existingByKey, &$importedCount): void {
            $persistedByKey = [];

            // Simpan dulu tanpa induk agar induk baru boleh berada di bawah anak pada file.
            foreach ($validRows as $item) {
                $subject = Subject::updateOrCreate(
                    ['curriculum' => $item['curriculum'], 'code' => $item['code']],
                    [
                        'name' => $item['name'],
                        'parent_id' => null,
                        'group' => $item['group'],
                        'sort_order' => $item['sort_order'],
                        'is_active' => $item['is_active'],
                    ]
                );

                $persistedByKey[$item['key']] = $subject;
                $importedCount++;
            }

            foreach ($validRows as $item) {
                if ($item['parent_code'] === null) {
                    continue;
                }

                $parentKey = $this->key($item['curriculum'], $item['parent_code']);
                $parent = $persistedByKey[$parentKey] ?? $existingByKey->get($parentKey);
                $persistedByKey[$item['key']]->update(['parent_id' => $parent->id]);
            }
        });

        ActivityLogger::log('import_subjects', "Berhasil mengimpor {$importedCount} data mata pelajaran dari Excel.");

        return [
            'success' => true,
            'imported' => $importedCount,
            'errors' => $errors,
        ];
    }

    private function cellText(mixed $sheet, string $coordinate): string
    {
        return trim((string) $sheet->getCell($coordinate)->getFormattedValue());
    }

    private function key(string $curriculum, string $code): string
    {
        return strtolower($curriculum).'|'.strtoupper($code);
    }

    private function parseActiveStatus(string $status): ?bool
    {
        if ($status === '') {
            return true;
        }

        return match ($status) {
            '1', 'aktif', 'active', 'ya', 'true' => true,
            '0', 'nonaktif', 'inactive', 'tidak', 'false' => false,
            default => null,
        };
    }
}
