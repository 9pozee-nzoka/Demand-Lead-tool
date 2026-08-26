<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;

/**
 * Horizon dashboard guard.
 * This provider is safe to keep even when Horizon is not installed —
 * it only activates when the Laravel\Horizon package is present.
 */
class HorizonServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot(): void
    {
        // Only register Horizon if the package is installed (not on shared hosting)
        if (! class_exists(\Laravel\Horizon\Horizon::class)) {
            return;
        }

        \Laravel\Horizon\Horizon::tag(function ($job) {
            if (property_exists($job, 'keywordId')) {
                return ['keyword:' . $job->keywordId];
            }
            return [];
        });

        Gate::define('viewHorizon', function ($user = null): bool {
            if (! $user) return false;

            $allowedEmails = array_filter(
                array_map('trim', explode(',', env('HORIZON_ALLOWED_EMAILS', '')))
            );

            if (in_array($user->email, $allowedEmails, true)) {
                return true;
            }

            return $user->role === 'owner' && $user->status === 'active';
        });
    }
}
