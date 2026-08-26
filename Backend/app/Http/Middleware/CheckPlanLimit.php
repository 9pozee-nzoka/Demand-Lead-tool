<?php

namespace App\Http\Middleware;

use App\Services\Billing\UsageMeteringService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: block a request when the org has reached a plan limit.
 *
 * Usage on route:
 *   ->middleware('limit:keywords')
 *   ->middleware('limit:leads_per_month')
 *
 * Returns 402 Payment Required with a clear message pointing to /billing.
 */
class CheckPlanLimit
{
    public function __construct(
        private readonly UsageMeteringService $metering,
    ) {}

    public function handle(Request $request, Closure $next, string $metric): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $organization = $user->organization;

        if (! $this->metering->isAllowed($organization, $metric)) {
            return response()->json([
                'message'    => "You have reached your plan's {$metric} limit. Upgrade to continue.",
                'metric'     => $metric,
                'upgrade_url'=> '/billing',
            ], 402);
        }

        return $next($request);
    }
}
