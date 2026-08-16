<?php

namespace App\Providers;

use App\Models\Donasi;
use App\Observers\DonasiObserver;
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
        Donasi::observe(DonasiObserver::class);
    }
}