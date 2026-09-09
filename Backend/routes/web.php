<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| This is an API-only backend. The Angular frontend at app.soarcorp.co.ke
| consumes the REST API at /api/v1/*
|
| The only web route needed is the root health-check page and the
| Horizon dashboard (when Horizon is installed with Redis).
*/

// Root — shows API status page (no Vite, no auth required)
Route::get('/', function () {
    return view('welcome');
});

// Horizon queue dashboard (only active when Horizon package is installed)
if (class_exists(\Laravel\Horizon\Horizon::class)) {
    \Laravel\Horizon\Horizon::auth(function ($request) {
        return app()->environment('local') || $request->user()?->isSuperAdmin();
    });
}
