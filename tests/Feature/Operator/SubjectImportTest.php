<?php

namespace Tests\Feature\Operator;

use App\Models\Subject;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class SubjectImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_operator_can_download_subject_template_for_selected_curriculum(): void
    {
        $operator = User::where('username', 'admin')->firstOrFail();

        $response = $this->actingAs($operator)->get(route('operator.subjects.template', ['curriculum' => 'merdeka']));

        $response->assertOk();
        $response->assertHeader('Content-Disposition');
        $this->assertStringContainsString('Template_Import_Mapel_merdeka.xlsx', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_operator_can_import_new_and_existing_subjects_from_excel(): void
    {
        $operator = User::where('username', 'admin')->firstOrFail();
        $filePath = $this->makeSubjectWorkbook([
            ['MTK', 'Matematika Revisi', '', 'umum', 'merdeka', 9, 1],
            ['KDG2', 'Kaidah Dasar Guru 2', '', 'mulok', 'merdeka', 20, 1],
            ['KDG2A', 'Kaidah Dasar Guru 2 Bagian A', 'KDG2', 'mulok', 'merdeka', 21, 1],
        ]);

        $response = $this->actingAs($operator)->post(route('operator.subjects.import'), [
            'file' => new UploadedFile($filePath, 'mapel.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ]);

        $response->assertRedirect(route('operator.subjects.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('subjects', ['code' => 'MTK', 'name' => 'Matematika Revisi']);
        $this->assertDatabaseHas('subjects', ['code' => 'KDG2', 'name' => 'Kaidah Dasar Guru 2']);
        $parent = Subject::where('code', 'KDG2')->where('curriculum', 'merdeka')->firstOrFail();
        $this->assertDatabaseHas('subjects', [
            'code' => 'KDG2A',
            'name' => 'Kaidah Dasar Guru 2 Bagian A',
            'parent_id' => $parent->id,
        ]);

        unlink($filePath);
    }

    public function test_subject_import_rolls_back_when_more_than_20_percent_rows_are_invalid(): void
    {
        $operator = User::where('username', 'admin')->firstOrFail();
        $filePath = $this->makeSubjectWorkbook([
            ['BATAL', 'Mapel Valid', '', 'umum', 'merdeka', 30, 1],
            ['SALAH', 'Mapel Tidak Valid', '', 'lainnya', 'merdeka', 31, 1],
        ]);

        $response = $this->actingAs($operator)->post(route('operator.subjects.import'), [
            'file' => new UploadedFile($filePath, 'mapel.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('subjects', ['code' => 'BATAL']);

        unlink($filePath);
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: string, 3: string, 4: string, 5: int, 6: int}>  $rows
     */
    private function makeSubjectWorkbook(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('mapel');

        foreach (['Kode Mapel', 'Nama Mata Pelajaran', 'Kode Mapel Induk', 'Kelompok', 'Kurikulum', 'Urutan', 'Aktif'] as $index => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1).'3', $header);
        }

        foreach ($rows as $index => $data) {
            foreach ($data as $column => $value) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($column + 1).($index + 4), $value);
            }
        }

        $filePath = tempnam(sys_get_temp_dir(), 'test_subject_').'.xlsx';
        (new Xlsx($spreadsheet))->save($filePath);
        $spreadsheet->disconnectWorksheets();

        return $filePath;
    }
}
