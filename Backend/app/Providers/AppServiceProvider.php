<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    // -------------------------------------------------------------------------

    private function configureRateLimiting(): void
    {
        /**
         * Login — 5 attempts per minute per IP.
         * Uses IP because the user is not authenticated yet.
         */
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Too many login attempts. Please wait a moment before trying again.',
                ], 429));
        });

        /**
         * Register — 10 per minute per IP.
         */
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Too many registration attempts.',
                ], 429));
        });

        /**
         * Forgot password — 3 per 5 minutes per IP.
         * Deliberately tight to limit email address enumeration attempts.
         */
        RateLimiter::for('forgot-password', function (Request $request) {
            return Limit::perMinutes(5, 3)
                ->by($request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Too many password reset requests. Please wait before trying again.',
                ], 429));
        });

        /**
         * General API — 60 requests per minute per authenticated user.
         * Falls back to IP for unauthenticated requests.
         */
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(60)->by($request->user()->id)
                : Limit::perMinute(20)->by($request->ip());
        });
    }
}
