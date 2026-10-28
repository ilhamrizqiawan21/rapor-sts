<?php

namespace App\Http\Middleware;

use App\Models\Period;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetActivePeriod
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $periodId = $request->session()->get('period_id');
        $activePeriod = null;

        if (Schema::hasTable('periods')) {
            if ($periodId) {
                $activePeriod = Period::with('academicYear')->find($periodId);
            }

            if (! $activePeriod) {
                $activePeriod = Period::with('academicYear')->where('is_active', true)->orderByDesc('id')->first();
                if ($activePeriod) {
                    $request->session()->put('period_id', $activePeriod->id);
                }
            }
        }

        app()->instance('activePeriod', $activePeriod);
        View::share('activePeriod', $activePeriod);

        return $next($request);
    }
}
