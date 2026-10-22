<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\PrintSettingRequest;
use App\Models\Period;
use App\Models\PrintSetting;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PrintSettingController extends Controller
{
    public function edit(): View
    {
        /** @var Period $period */
        $period = app('activePeriod');

        $setting = PrintSetting::firstOrCreate(
            ['period_id' => $period->id],
            [
                'report_title' => 'LAPORAN HASIL SUMATIF TENGAH SEMESTER',
                'print_place' => 'Bandung Barat',
                'print_date' => now()->toDateString(),
                'score_label' => 'Nilai STS',
                'paper_size' => 'A4',
                'watermark_text' => null,
                'sections' => [
                    'show_competency' => true,
                    'show_attendance' => true,
                    'show_note' => true,
                    'show_parent_response' => true,
                    'show_extracurricular' => false,
                    'show_achievement' => false,
                    'show_total_score' => false,
                    'show_qr' => false,
                ],
            ]
        );

        return view('operator.rapor.print-settings', compact('setting', 'period'));
    }

    public function update(PrintSettingRequest $request): RedirectResponse
    {
        /** @var Period $period */
        $period = app('activePeriod');

        $validated = $request->validated();

        $sectionsInput = $request->input('sections', []);
        $sections = [
            'show_competency' => ! empty($sectionsInput['show_competency']),
            'show_attendance' => ! empty($sectionsInput['show_attendance']),
            'show_note' => ! empty($sectionsInput['show_note']),
            'show_parent_response' => ! empty($sectionsInput['show_parent_response']),
            'show_extracurricular' => ! empty($sectionsInput['show_extracurricular']),
            'show_achievement' => ! empty($sectionsInput['show_achievement']),
            'show_total_score' => ! empty($sectionsInput['show_total_score']),
            'show_qr' => ! empty($sectionsInput['show_qr']),
        ];

        $setting = PrintSetting::updateOrCreate(
            ['period_id' => $period->id],
            [
                'report_title' => $validated['report_title'],
                'print_place' => $validated['print_place'],
                'print_date' => $validated['print_date'],
                'score_label' => $validated['score_label'],
                'paper_size' => $validated['paper_size'],
                'watermark_text' => $validated['watermark_text'] ?? null,
                'sections' => $sections,
            ]
        );

        ActivityLogger::log('update_print_setting', 'Memperbarui pengaturan cetak rapor STS', $setting);

        return back()->with('success', 'Pengaturan cetak rapor STS berhasil disimpan.');
    }
}
