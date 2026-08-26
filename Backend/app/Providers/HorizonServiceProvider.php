<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        // Tag queue jobs with their keyword ID for easy Horizon filtering
        Horizon::tag(function ($job) {
            if (property_exists($job, 'keywordId')) {
                return ['keyword:' . $job->keywordId];
            }
            return [];
        });
    }

    /**
     * Gate: restrict Horizon dashboard access to owner-role users.
     *
     * In production the Horizon route is also protected by HTTP Basic Auth
     * configured via HORIZON_BASIC_AUTH_USERNAME / HORIZON_BASIC_AUTH_PASSWORD
     * in the nginx / server config (not here — avoids app bootstrapping cost).
     *
     * Users in the HORIZON_ALLOWED_EMAILS env var (comma-separated) are always
     * allowed regardless of role, which is useful for ops/infra accounts that
     * are not in the tenant user table.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null): bool {
            // Not authenticated — deny
            if (! $user) {
                return false;
            }

            // Always allow emails explicitly listed in env
            $allowedEmails = array_filter(
                array_map('trim', explode(',', env('HORIZON_ALLOWED_EMAILS', '')))
            );

            if (in_array($user->email, $allowedEmails, true)) {
                return true;
            }

            // Only owner-role users within a valid organization may view Horizon
            return $user->role === 'owner' && $user->status === 'active';
        });
    }
}
