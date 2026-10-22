<?php

namespace App\Providers;

use App\Models\TeachingAssignment;
use App\Policies\TeachingAssignmentPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        Gate::policy(TeachingAssignment::class, TeachingAssignmentPolicy::class);
    }
}
