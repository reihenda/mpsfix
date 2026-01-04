<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Models\MonthlyCustomerBalance;
use App\Observers\MonthlyCustomerBalanceObserver;

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
        // Menggunakan Bootstrap untuk tampilan paginasi
        Paginator::useBootstrap();
        
        // REMOVED: Observer registration (Pure MVC approach)
        // MonthlyCustomerBalance::observe(MonthlyCustomerBalanceObserver::class);
    }
}
