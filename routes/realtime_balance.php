<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RealtimeBalanceController;

/*
|--------------------------------------------------------------------------
| Real-time Balance API Routes
|--------------------------------------------------------------------------
|
| Routes untuk mengakses data balance yang disimpan real-time di database
|
*/

Route::prefix('api/realtime-balance')->middleware(['auth'])->group(function () {
    
    // System status dan health check
    Route::get('/status', [RealtimeBalanceController::class, 'getSystemStatus']);
    
    // Customer balance data
    Route::get('/customer/{customerId}/balance', [RealtimeBalanceController::class, 'getCustomerBalance']);
    Route::get('/customer/{customerId}/transactions', [RealtimeBalanceController::class, 'getCustomerTransactions']);
    
    // Dashboard dan analytics
    Route::get('/dashboard', [RealtimeBalanceController::class, 'getDashboardData']);
    Route::get('/comparison-report', [RealtimeBalanceController::class, 'getComparisonReport']);
    
    // Admin only routes
    Route::middleware(['admin'])->group(function () {
        Route::post('/customer/{customerId}/update', [RealtimeBalanceController::class, 'updateCustomerBalance']);
    });
});

// Web routes untuk testing (optional)
Route::prefix('realtime-balance')->middleware(['auth', 'admin'])->group(function () {
    
    Route::get('/test', function () {
        return view('realtime-balance.test');
    });
    
    Route::get('/dashboard', function () {
        return view('realtime-balance.dashboard');
    });
});
