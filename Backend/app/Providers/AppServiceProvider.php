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
        $this->registerQueueEventListeners();
    }

    // -------------------------------------------------------------------------

    /**
     * Register queue event listeners
     */
    private function registerQueueEventListeners(): void
    {
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Queue\Events\JobFailed::class,
            \App\Listeners\NotifyOnJobFailure::class
        );
    }

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
         * Uses cache driver (file on shared hosting, Redis elsewhere).
         */
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(60)->by($request->user()->id)
                : Limit::perMinute(20)->by($request->ip());
        });

        /**
         * Webhook endpoints — 100 per minute per IP
         * Higher limit for legitimate webhook traffic
         */
        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(100)->by($request->ip());
        });

        /**
         * Landing page lead capture — 10 per minute per IP
         * Prevents spam submissions
         */
        RateLimiter::for('lead-capture', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        /**
         * AI content generation — 20 per hour per user
         * Prevents API quota abuse
         */
        RateLimiter::for('ai-generation', function (Request $request) {
            return $request->user()
                ? Limit::perHour(20)->by($request->user()->id)
                : Limit::perHour(5)->by($request->ip());
        });

        /**
         * SMS sending — 50 per day per organization
         * Prevents SMS cost abuse
         */
        RateLimiter::for('sms', function (Request $request) {
            return $request->user()
                ? Limit::perDay(50)->by('org:' . $request->user()->organization_id)
                : Limit::perDay(5)->by($request->ip());
        });
    }
}
