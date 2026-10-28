<?php

namespace App\Http\Controllers\Operator;

use App\Enums\Curriculum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\SubjectImportRequest;
use App\Http\Requests\Operator\SubjectRequest;
use App\Models\Subject;
use App\Services\ActivityLogger;
use App\Services\Import\SubjectImporter;
use App\Services\Import\TemplateBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        $curriculum = $request->query('curriculum', 'merdeka');

        $subjects = Subject::with('parent')
            ->where('curriculum', $curriculum)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $parentSubjects = Subject::where('curriculum', $curriculum)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('operator.subjects.index', compact('subjects', 'parentSubjects', 'curriculum'));
    }

    public function store(SubjectRequest $request): RedirectResponse
    {
        $subject = Subject::create($request->validated());

        ActivityLogger::log('create_subject', "Menambahkan mata pelajaran {$subject->code} - {$subject->name}", $subject);

        return redirect()->route('operator.subjects.index', ['curriculum' => $subject->curriculum->value])
            ->with('success', "Mata pelajaran {$subject->name} berhasil ditambahkan.");
    }

    public function template(Request $request, TemplateBuilder $builder): StreamedResponse
    {
        $curriculum = Curriculum::tryFrom($request->string('curriculum')->toString()) ?? Curriculum::Merdeka;
        $spreadsheet = $builder->buildSubjectTemplate($curriculum);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'Template_Import_Mapel_'.$curriculum->value.'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(SubjectImportRequest $request, SubjectImporter $importer): RedirectResponse
    {
        $result = $importer->import($request->file('file'));

        if (! $result['success']) {
            return redirect()->back()->withErrors(['file' => implode('<br>', $result['errors'])]);
        }

        $message = "Berhasil mengimpor {$result['imported']} data mata pelajaran.";
        if (! empty($result['errors'])) {
            $message .= ' Peringatan: '.implode('; ', $result['errors']);
        }

        return redirect()->route('operator.subjects.index')->with('success', $message);
    }

    public function update(SubjectRequest $request, Subject $subject): RedirectResponse
    {
        $subject->update($request->validated());

        ActivityLogger::log('update_subject', "Memperbarui mata pelajaran {$subject->code} - {$subject->name}", $subject);

        return redirect()->route('operator.subjects.index', ['curriculum' => $subject->curriculum->value])
            ->with('success', "Mata pelajaran {$subject->name} berhasil diperbarui.");
    }

    public function move(Request $request, Subject $subject): RedirectResponse
    {
        $direction = $request->validate([
            'direction' => ['required', 'in:up,down'],
        ])['direction'];

        DB::transaction(function () use ($subject, $direction): void {
            $subjects = Subject::query()
                ->where('curriculum', $subject->curriculum->value)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $currentIndex = $subjects->search(fn (Subject $item): bool => $item->id === $subject->id);
            if ($currentIndex === false) {
                return;
            }

            $neighborIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;
            if ($neighborIndex < 0 || $neighborIndex >= $subjects->count()) {
                return;
            }

            $orderedSubjects = $subjects->values()->all();
            [$orderedSubjects[$currentIndex], $orderedSubjects[$neighborIndex]] = [
                $orderedSubjects[$neighborIndex],
                $orderedSubjects[$currentIndex],
            ];

            foreach ($orderedSubjects as $index => $item) {
                $item->update(['sort_order' => $index + 1]);
            }
        });

        return redirect()
            ->route('operator.subjects.index', ['curriculum' => $subject->curriculum->value])
            ->with('success', "Urutan mata pelajaran {$subject->name} berhasil diperbarui.");
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        if ($subject->teachingAssignments()->exists()) {
            return redirect()->back()->with('error', 'Mata pelajaran tidak dapat dihapus karena sudah dipakai dalam data mengajar guru.');
        }

        if ($subject->children()->exists()) {
            return redirect()->back()->with('error', 'Mata pelajaran tidak dapat dihapus karena memiliki sub-mapel.');
        }

        $name = $subject->name;
        $curriculum = $subject->curriculum->value;
        $subject->delete();

        ActivityLogger::log('delete_subject', "Menghapus mata pelajaran {$name}");

        return redirect()->route('operator.subjects.index', ['curriculum' => $curriculum])
            ->with('success', "Mata pelajaran {$name} berhasil dihapus.");
    }
}
