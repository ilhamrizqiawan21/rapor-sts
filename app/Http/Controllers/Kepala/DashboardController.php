<?php

namespace App\Http\Controllers\Kepala;

use App\Http\Controllers\Controller;
use App\Models\Period;
use App\Services\Headmaster\DashboardAnalytics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardAnalytics $analytics
    ) {}

    public function index(Request $request): View
    {
        /** @var Period $period */
        $period = app('activePeriod');

        $filterLevel = $request->query('tingkat');
        $filterLevelInt = is_numeric($filterLevel) ? (int) $filterLevel : null;

        $dashboardData = $this->analytics->getDashboardData($period, $filterLevelInt);

        return view('kepala.dashboard', $dashboardData);
    }
}
