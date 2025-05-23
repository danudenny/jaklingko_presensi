<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\UnitRenops;
use App\Observers\UnitRenopsObserver;
use App\Services\ScheduleGeneratorService;
use App\Services\ScheduleGeneratorUtilityService;
use App\Services\DriverSelectionService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register schedule generator related services
        $this->app->singleton(ScheduleGeneratorUtilityService::class);
        $this->app->singleton(DriverSelectionService::class);
        
        // Register the main service with its dependencies
        $this->app->singleton(ScheduleGeneratorService::class, function ($app) {
            return new ScheduleGeneratorService(
                $app->make(ScheduleGeneratorUtilityService::class),
                $app->make(DriverSelectionService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register the UnitRenops observer
        UnitRenops::observe(UnitRenopsObserver::class);
    }
}
