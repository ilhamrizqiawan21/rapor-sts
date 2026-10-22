<?php

namespace Database\Seeders;

use App\Enums\Curriculum;
use App\Enums\Role;
use App\Enums\Semester;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Period;
use App\Models\PrintSetting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);

        // 1. Tahun Pelajaran 2026/2027
        $academicYear = AcademicYear::firstOrCreate(['name' => '2026/2027']);

        // 2. Periode Ganjil & Genap
        $periodGanjil = Period::firstOrCreate(
            ['academic_year_id' => $academicYear->id, 'semester' => Semester::Ganjil],
            ['is_active' => true]
        );

        Period::firstOrCreate(
            ['academic_year_id' => $academicYear->id, 'semester' => Semester::Genap],
            ['is_active' => false]
        );

        // Pengaturan Cetak Default
        PrintSetting::firstOrCreate(
            ['period_id' => $periodGanjil->id],
            [
                'report_title' => 'LAPORAN HASIL SUMATIF TENGAH SEMESTER',
                'print_place' => 'Bandung Barat',
                'print_date' => now()->toDateString(),
                'score_label' => 'Nilai STS',
                'paper_size' => 'A4',
                'watermark_text' => null,
                'sections' => [
                    'show_total' => false,
                    'show_notes' => true,
                    'show_parent_response' => true,
                ],
            ]
        );

        // 3. User Kepala Madrasah
        User::firstOrCreate(
            ['username' => 'kepala'],
            [
                'name' => 'Drs. H. Ahmad Sanusi, M.Pd.I',
                'password' => Hash::make('password'),
                'role' => Role::Kepala,
                'nik' => '3204000000000002',
                'nip' => '197001011995031001',
                'gender' => 'L',
                'is_active' => true,
            ]
        );

        // 4. 10 Guru Fiktif
        $teachersData = [
            ['username' => 'gurufikih', 'name' => 'GURU FIKIH, S.Pd', 'gender' => 'L', 'code' => 'FIK'],
            ['username' => 'guruqurdis', 'name' => 'Guru Qurdis, S.Pd.I', 'gender' => 'P', 'code' => 'QH'],
            ['username' => 'guruakidah', 'name' => 'Guru Akidah, S.Ag', 'gender' => 'L', 'code' => 'AA'],
            ['username' => 'guruski', 'name' => 'Guru SKI, S.Pd.I', 'gender' => 'L', 'code' => 'SKI'],
            ['username' => 'gurubindo', 'name' => 'Guru B. Indonesia, S.Pd', 'gender' => 'P', 'code' => 'BIN'],
            ['username' => 'gurumtk', 'name' => 'Guru Matematika, S.Pd', 'gender' => 'L', 'code' => 'MTK'],
            ['username' => 'guruipa', 'name' => 'Guru IPA, S.Pd', 'gender' => 'P', 'code' => 'IPA'],
            ['username' => 'guruips', 'name' => 'Guru IPS, S.Pd', 'gender' => 'L', 'code' => 'IPS'],
            ['username' => 'gurubing', 'name' => 'Guru B. Inggris, S.Pd', 'gender' => 'P', 'code' => 'BIG'],
            ['username' => 'gurupjok', 'name' => 'Guru PJOK, S.Pd', 'gender' => 'L', 'code' => 'PJOK'],
        ];

        $teachers = [];
        foreach ($teachersData as $idx => $t) {
            $teachers[$t['code']] = User::firstOrCreate(
                ['username' => $t['username']],
                [
                    'name' => $t['name'],
                    'password' => Hash::make('password'),
                    'password_plain' => 'password',
                    'role' => Role::Guru,
                    'nik' => '320400000000100'.($idx + 1),
                    'nuptk' => '123456789012345'.($idx + 1),
                    'gender' => $t['gender'],
                    'is_active' => true,
                ]
            );
        }

        // 5. 3 Kelas
        $classA = Classroom::firstOrCreate(
            ['academic_year_id' => $academicYear->id, 'name' => 'VII.A'],
            [
                'level' => 7,
                'alias' => 'Kelas VII-A',
                'curriculum' => Curriculum::Merdeka,
                'phase' => 'D',
                'homeroom_teacher_id' => $teachers['FIK']->id,
            ]
        );

        $classB = Classroom::firstOrCreate(
            ['academic_year_id' => $academicYear->id, 'name' => 'VII.B'],
            [
                'level' => 7,
                'alias' => 'Kelas VII-B',
                'curriculum' => Curriculum::Merdeka,
                'phase' => 'D',
                'homeroom_teacher_id' => $teachers['QH']->id,
            ]
        );

        $class8A = Classroom::firstOrCreate(
            ['academic_year_id' => $academicYear->id, 'name' => 'VIII.A'],
            [
                'level' => 8,
                'alias' => 'Kelas VIII-A',
                'curriculum' => Curriculum::Merdeka,
                'phase' => 'D',
                'homeroom_teacher_id' => $teachers['AA']->id,
            ]
        );

        // 6. 30 Siswa Fiktif (10 per kelas)
        $studentNames = [
            // Kelas VII.A
            ['AHMAD FAUZAN', 'L'], ['AISYAH NURAINI', 'P'], ['DIMAS PRATAMA', 'L'], ['FITRI RAMADHANI', 'P'],
            ['MUHAMMAD RIZKI', 'L'], ['NURUL HIDAYAH', 'P'], ['RENDI SETIAWAN', 'L'], ['SITI AMINAH', 'P'],
            ['TAUFIK HIDAYAT', 'L'], ['ZAHRA AULIA', 'P'],
            // Kelas VII.B
            ['ANDI SAPUTRA', 'L'], ['BELLA OKTAVIA', 'P'], ['CHANDRA WIJAYA', 'L'], ['DEWI KURNIA', 'P'],
            ['EKO PRASETYO', 'L'], ['FANI ANGGRAENI', 'P'], ['GILANG RAMADHAN', 'L'], ['HANA SAFITRI', 'P'],
            ['IQBAL MAULANA', 'L'], ['JESSICA PUTRI', 'P'],
            // Kelas VIII.A
            ['KIKI FATMALA', 'P'], ['LUKMAN HAKIM', 'L'], ['MIRA SANTIKA', 'P'], ['NAUFAL AZHAR', 'L'],
            ['OKTAVIANI', 'P'], ['PANJI ANOM', 'L'], ['QORI NURJANAH', 'P'], ['RIDWAN KAMIL', 'L'],
            ['SALMA NABILA', 'P'], ['TEGUH PRASETYA', 'L'],
        ];

        $classes = [$classA, $classB, $class8A];
        foreach ($studentNames as $idx => $st) {
            $classIndex = (int) floor($idx / 10);
            $targetClass = $classes[$classIndex];

            $nisn = sprintf('01%08d', $idx + 1);
            $nis = sprintf('26%04d', $idx + 1);

            $student = Student::firstOrCreate(
                ['nisn' => $nisn],
                [
                    'nis' => $nis,
                    'name' => $st[0],
                    'gender' => $st[1],
                    'birth_place' => 'Bandung',
                    'birth_date' => '2013-05-15',
                    'address' => 'Batujajar, Bandung Barat',
                    'is_active' => true,
                ]
            );

            Enrollment::firstOrCreate([
                'academic_year_id' => $academicYear->id,
                'classroom_id' => $targetClass->id,
                'student_id' => $student->id,
            ]);
        }

        // 7. Teaching Assignments untuk kelas VII.A & VII.B
        $subjects = Subject::whereNotNull('parent_id')->orWhereDoesntHave('children')->get();
        foreach ([$classA, $classB] as $cls) {
            foreach ($subjects as $sbj) {
                // Tentukan guru berdasarkan kode mapel atau fallback
                $teacher = $teachers[$sbj->code] ?? $teachers['FIK'];

                TeachingAssignment::firstOrCreate(
                    [
                        'period_id' => $periodGanjil->id,
                        'classroom_id' => $cls->id,
                        'subject_id' => $sbj->id,
                    ],
                    [
                        'teacher_id' => $teacher->id,
                        'hours_per_week' => 2,
                        'weight_summative' => 50.00,
                        'weight_sts' => 50.00,
                        'kktp' => 70.00,
                    ]
                );
            }
        }

        $this->call(DemoScoresSeeder::class);
    }
}
