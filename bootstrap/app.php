<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Environments;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\Authenticate;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Tambahkan middleware role
        $middleware->alias([
            'role' => CheckRole::class,
            'auth' => Authenticate::class,
        ]);
    })
    ->withExceptions(function ($exceptions) {
        //
    })->create();
