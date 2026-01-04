<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\DataPencatatan;
use App\Models\User;
use App\Observers\DataPencatatanObserver;
use App\Observers\UserObserver;
use App\Services\RealtimeBalanceService;

class RealtimeBalanceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register RealtimeBalanceService sebagai singleton
        $this->app->singleton(RealtimeBalanceService::class, function ($app) {
            return new RealtimeBalanceService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register Observers
        DataPencatatan::observe(DataPencatatanObserver::class);
        User::observe(UserObserver::class);
    }
}
