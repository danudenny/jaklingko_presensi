<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\UnitRenops;
use App\Observers\UnitRenopsObserver;

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
        // Register the UnitRenops observer
        UnitRenops::observe(UnitRenopsObserver::class);
    }
}
