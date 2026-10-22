<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\WeightRequest;
use App\Models\TeachingAssignment;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WeightController extends Controller
{
    public function edit(TeachingAssignment $teachingAssignment): View
    {
        $this->authorize('view', $teachingAssignment);

        return view('guru.bobot', compact('teachingAssignment'));
    }

    public function update(WeightRequest $request, TeachingAssignment $teachingAssignment): RedirectResponse
    {
        $this->authorize('update', $teachingAssignment);

        $validated = $request->validated();

        $teachingAssignment->update([
            'hours_per_week' => $validated['hours_per_week'],
            'weight_summative' => $validated['weight_summative'],
            'weight_sts' => $validated['weight_sts'],
        ]);

        ActivityLogger::log(
            'update_weight',
            "Mengubah bobot nilai mapel {$teachingAssignment->subject->name} kelas {$teachingAssignment->classroom->name} (Sumatif: {$validated['weight_summative']}, STS: {$validated['weight_sts']})",
            $teachingAssignment
        );

        return redirect()->back()->with('success', 'Bobot nilai berhasil diperbarui.');
    }
}
