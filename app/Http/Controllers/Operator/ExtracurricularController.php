<?php

namespace App\Http\Controllers\Operator;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\ExtracurricularActivityDefinitionRequest;
use App\Models\ExtracurricularActivityDefinition;
use App\Models\Period;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExtracurricularController extends Controller
{
    public function index(): View
    {
        /** @var Period|null $period */
        $period = app()->bound('activePeriod') ? app('activePeriod') : null;
        abort_unless($period, 404, 'Periode aktif tidak ditemukan.');

        return view('operator.extracurricular.index', [
            'period' => $period,
            'activities' => ExtracurricularActivityDefinition::query()
                ->where('period_id', $period->id)
                ->with('supervisor')
                ->withCount('records')
                ->orderBy('name')
                ->get(),
            'teachers' => User::query()
                ->where('role', Role::Guru->value)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function update(ExtracurricularActivityDefinitionRequest $request): RedirectResponse
    {
        /** @var Period $period */
        $period = app('activePeriod');
        $activities = $request->validated('activities', []);

        DB::transaction(function () use ($activities, $period): void {
            foreach ($activities as $activity) {
                $name = trim($activity['name']);
                $definition = ! empty($activity['id'])
                    ? ExtracurricularActivityDefinition::query()
                        ->where('period_id', $period->id)
                        ->findOrFail((int) $activity['id'])
                    : new ExtracurricularActivityDefinition([
                        'period_id' => $period->id,
                    ]);

                $definition->fill([
                    'name' => $name,
                    'supervisor_id' => (int) $activity['supervisor_id'],
                ])->save();

                $definition->records()->update([
                    'name' => $definition->name,
                    'supervisor_id' => $definition->supervisor_id,
                ]);
            }

            ActivityLogger::log(
                'save_extracurricular_definitions',
                "Mengatur kegiatan ekstrakurikuler periode {$period->id}"
            );
        });

        return back()->with('success', 'Kegiatan ekstrakurikuler dan guru pembina berhasil disimpan.');
    }

    public function destroy(int $activity): RedirectResponse
    {
        /** @var Period $period */
        $period = app('activePeriod');
        $definition = ExtracurricularActivityDefinition::query()
            ->where('period_id', $period->id)
            ->findOrFail($activity);

        if ($definition->records()->exists()) {
            return back()->with('error', 'Kegiatan tidak dapat dihapus karena sudah memiliki peserta atau nilai.');
        }

        $name = $definition->name;
        $definition->delete();
        ActivityLogger::log('delete_extracurricular_definition', "Menghapus kegiatan ekstrakurikuler {$name}");

        return back()->with('success', 'Kegiatan ekstrakurikuler berhasil dihapus.');
    }
}
