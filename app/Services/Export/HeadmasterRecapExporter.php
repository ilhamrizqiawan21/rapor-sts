<?php

namespace App\Services\Export;

use App\Models\Classroom;
use App\Models\Period;
use App\Models\TeachingAssignment;
use App\Services\Grading\AchievementResolver;
use App\Services\Grading\ClassGradeSheet;
use App\Services\Grading\CompetencyDescriber;
use App\Services\Grading\GradeCalculator;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class HeadmasterRecapExporter
{
    public function __construct(
        protected GradeCalculator $calculator = new GradeCalculator,
        protected AchievementResolver $resolver = new AchievementResolver,
        protected CompetencyDescriber $describer = new CompetencyDescriber
    ) {}

    public function export(Period $period): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0); // Hapus default sheet kosong

        $classrooms = Classroom::where('academic_year_id', $period->academic_year_id)
            ->with([
                'homeroomTeacher',
                'locks' => fn ($q) => $q->where('period_id', $period->id),
                'enrollments' => fn ($q) => $q->where('academic_year_id', $period->academic_year_id)->with('student'),
            ])
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $levels = $classrooms->pluck('level')->unique()->sort()->values();

        // Ambil semua penugasan mengajar
        $assignments = TeachingAssignment::where('period_id', $period->id)
            ->with([
                'subject.parent',
                'teacher',
                'classroom',
                'learningScopes.scores',
                'stsScores',
                'competencyOverrides',
            ])
            ->get();

        // Hitung grade sheet per penugasan
        $assignmentGradeSheets = [];
        foreach ($assignments as $a) {
            $sheet = ClassGradeSheet::forAssignment($a, $this->calculator, $this->resolver, $this->describer);
            $assignmentGradeSheets[$a->id] = $sheet;
        }

        foreach ($levels as $levelIndex => $level) {
            $sheet = $spreadsheet->createSheet($levelIndex);
            $sheetTitle = 'Tingkat_'.$level;
            $sheet->setTitle($sheetTitle);

            $levelClassrooms = $classrooms->where('level', $level)->values();
            $levelAssignments = $assignments->whereIn('classroom_id', $levelClassrooms->pluck('id'));

            // Ambil daftar mapel unik yang diajarkan pada tingkat ini
            $subjects = $levelAssignments->pluck('subject')->unique('id')->sortBy('sort_order')->values();

            $totalCols = 6 + $subjects->count(); // No, Kelas, Wali Kelas, Terkirim, Status, Rerata + subjects
            $lastColLetter = Coordinate::stringFromColumnIndex($totalCols);

            // Baris 1: Judul
            $sheet->setCellValue('A1', 'REKAPITULASI PROGRES & NILAI TINGKAT '.$level);
            $sheet->mergeCells('A1:'.$lastColLetter.'1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Baris 2: Sub-judul
            $sheet->setCellValue('A2', 'Semester: '.$period->semester->label().' | Tahun Pelajaran: '.$period->academicYear->name);
            $sheet->mergeCells('A2:'.$lastColLetter.'2');
            $sheet->getStyle('A2')->getFont()->setSize(11);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Baris 4: Header Tabel
            $headerRow = 4;
            $sheet->setCellValue('A'.$headerRow, 'No');
            $sheet->setCellValue('B'.$headerRow, 'Kelas');
            $sheet->setCellValue('C'.$headerRow, 'Wali Kelas');
            $sheet->setCellValue('D'.$headerRow, 'Mapel Terkirim');
            $sheet->setCellValue('E'.$headerRow, 'Status Kunci');
            $sheet->setCellValue('F'.$headerRow, 'Rerata Kelas');

            $colIdx = 7;
            foreach ($subjects as $subj) {
                $colLetter = Coordinate::stringFromColumnIndex($colIdx++);
                $sheet->setCellValueExplicit($colLetter.$headerRow, $subj->code, DataType::TYPE_STRING);
            }

            $sheet->getStyle('A'.$headerRow.':'.$lastColLetter.$headerRow)->getFont()->setBold(true);
            $sheet->getStyle('A'.$headerRow.':'.$lastColLetter.$headerRow)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFF1F3F5');
            $sheet->getStyle('A'.$headerRow.':'.$lastColLetter.$headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Baris Data mulai baris 5
            $currentRow = 5;
            $no = 1;

            foreach ($levelClassrooms as $cls) {
                $clsAssignments = $assignments->where('classroom_id', $cls->id);
                $totalInClass = $clsAssignments->count();
                $submittedInClass = $clsAssignments->filter(fn ($a) => $a->isSubmitted())->count();
                $isLocked = $cls->locks->isNotEmpty();

                $sheet->setCellValue('A'.$currentRow, $no++);
                $sheet->setCellValueExplicit('B'.$currentRow, $cls->name, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('C'.$currentRow, $cls->homeroomTeacher?->name ?? '–', DataType::TYPE_STRING);
                $sheet->setCellValue('D'.$currentRow, "{$submittedInClass}/{$totalInClass}");
                $sheet->setCellValue('E'.$currentRow, $isLocked ? 'Dikunci' : 'Belum');

                // Rerata per mapel di kelas ini
                $clsScores = [];
                $colIdx = 7;
                foreach ($subjects as $subj) {
                    $colLetter = Coordinate::stringFromColumnIndex($colIdx++);
                    $assignment = $clsAssignments->firstWhere('subject_id', $subj->id);

                    if ($assignment && isset($assignmentGradeSheets[$assignment->id])) {
                        $sheetData = $assignmentGradeSheets[$assignment->id];
                        $scores = $sheetData->rows->pluck('finalScore')->filter(fn ($s) => $s !== null);
                        if ($scores->isNotEmpty()) {
                            $avg = round($scores->avg(), 1);
                            $sheet->setCellValue($colLetter.$currentRow, $avg);
                            $clsScores = array_merge($clsScores, $scores->all());
                        } else {
                            $sheet->setCellValue($colLetter.$currentRow, '–');
                        }
                    } else {
                        $sheet->setCellValue($colLetter.$currentRow, '–');
                    }
                    $sheet->getStyle($colLetter.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $classAvg = count($clsScores) > 0 ? round(array_sum($clsScores) / count($clsScores), 1) : null;
                $sheet->setCellValue('F'.$currentRow, $classAvg !== null ? $classAvg : '–');

                $sheet->getStyle('A'.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('D'.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('E'.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('F'.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $currentRow++;
            }

            // Border seluruh tabel
            $sheet->getStyle('A'.$headerRow.':'.$lastColLetter.($currentRow - 1))
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            // Auto size columns
            for ($i = 1; $i <= $totalCols; $i++) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
            }
        }

        return $spreadsheet;
    }
}
