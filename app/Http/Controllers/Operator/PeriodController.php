<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Period;
use App\Services\PeriodService;
use Illuminate\Http\RedirectResponse;

class PeriodController extends Controller
{
    public function activate(Period $period, PeriodService $periodService): RedirectResponse
    {
        $periodService->setActivePeriod($period);

        return redirect()->back()->with('success', "Periode {$period->badge_text} berhasil diaktifkan.");
    }
}
