<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\SubjectRequest;
use App\Models\Subject;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        $curriculum = $request->query('curriculum', 'merdeka');

        $subjects = Subject::with('parent')
            ->where('curriculum', $curriculum)
            ->orderBy('sort_order')
            ->get();

        $parentSubjects = Subject::where('curriculum', $curriculum)
            ->whereNull('parent_id')
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

    public function update(SubjectRequest $request, Subject $subject): RedirectResponse
    {
        $subject->update($request->validated());

        ActivityLogger::log('update_subject', "Memperbarui mata pelajaran {$subject->code} - {$subject->name}", $subject);

        return redirect()->route('operator.subjects.index', ['curriculum' => $subject->curriculum->value])
            ->with('success', "Mata pelajaran {$subject->name} berhasil diperbarui.");
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
