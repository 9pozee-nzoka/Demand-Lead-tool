<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // CORS — must run before auth so preflight OPTIONS requests get headers
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);

        // Sanctum stateful domains for SPA support
        $middleware->statefulApi();

        // Custom middleware aliases
        $middleware->alias([
            'tenant'       => \App\Http\Middleware\EnsureSameTenant::class,
            'role'         => \App\Http\Middleware\CheckRole::class,
            'limit'        => \App\Http\Middleware\CheckPlanLimit::class,
            'super.admin'  => \App\Http\Middleware\EnsureSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
