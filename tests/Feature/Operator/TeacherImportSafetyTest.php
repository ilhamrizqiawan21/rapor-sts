<?php

namespace Tests\Feature\Operator;

use App\Enums\Role;
use App\Models\Classroom;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class TeacherImportSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_never_overwrites_non_teacher_accounts_or_existing_passwords(): void
    {
        $this->seed(DatabaseSeeder::class);
        $operator = User::where('username', 'admin')->first();
        $hash = $operator->password;
        $existing = User::factory()->guru()->create(['username' => '5000', 'password' => 'keepme']);
        $existingHash = $existing->fresh()->password;

        $sheet = ($ss = new Spreadsheet)->getActiveSheet();
        $sheet->setTitle('guru');
        $rows = [['admin', 'Penyusup'], ['5000', 'Guru Lama'], ['6001', 'A'], ['6002', 'B'], ['6003', 'C'], ['6004', 'D'], ['6005', 'E']];
        foreach ($rows as $i => [$nik, $name]) {
            $r = $i + 4;
            $sheet->setCellValueExplicit("A{$r}", $nik, DataType::TYPE_STRING);
            $sheet->setCellValue("C{$r}", $name);
            $sheet->setCellValue("D{$r}", 'L');
        }
        $path = tempnam(sys_get_temp_dir(), 'tch').'.xlsx';
        (new Xlsx($ss))->save($path);

        $this->actingAs($operator)->post(route('operator.teachers.import'), [
            'file' => new UploadedFile($path, 'guru.xlsx', null, null, true),
        ])->assertSessionHasNoErrors();

        $operator->refresh();
        $this->assertSame(Role::Operator, $operator->role);
        $this->assertSame($hash, $operator->password);
        $this->assertSame($existingHash, $existing->fresh()->password);
        $this->assertDatabaseHas('users', ['username' => '6005']);
        $this->assertNotSame('password', User::where('username', '6005')->firstOrFail()->password_plain);
        $this->assertSame(16, strlen(User::where('username', '6005')->firstOrFail()->password_plain));
        @unlink($path);
    }

    public function test_corrupt_student_file_does_not_crash(): void
    {
        $this->seed(DatabaseSeeder::class);
        $operator = User::where('username', 'admin')->first();
        $classroom = Classroom::factory()->create();
        $path = tempnam(sys_get_temp_dir(), 'bad').'.xlsx';
        file_put_contents($path, 'bukan xlsx');

        $this->actingAs($operator)->post(route('operator.students.import'), [
            'classroom_id' => $classroom->id,
            'file' => new UploadedFile($path, 'x.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ])->assertSessionHasErrors();
        @unlink($path);
    }
}
