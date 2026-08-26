<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Billing\UsageMeteringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(
        private readonly UsageMeteringService $metering,
    ) {}

    /**
     * GET /api/v1/billing
     * Current subscription, plan details, and this month's usage snapshot.
     */
    public function show(Request $request): JsonResponse
    {
        $org = $request->user()->organization->loadMissing(['plan', 'activeSubscription.plan']);

        $snapshot     = $this->metering->snapshot($org);
        $subscription = $org->activeSubscription;

        return response()->json([
            'subscription' => $subscription ? [
                'id'         => $subscription->id,
                'status'     => $subscription->status,
                'renewal_at' => $subscription->renewal_at,
                'plan'       => $subscription->plan,
            ] : null,
            'plan'         => $snapshot['plan'],
            'usage'        => $snapshot['usage'],
            'period'       => $snapshot['period'],
            'at_limit'     => $snapshot['at_limit'],
        ]);
    }

    /**
     * GET /api/v1/billing/plans
     * All active plans for the upgrade CTA.
     */
    public function plans(): JsonResponse
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('monthly_price')
            ->get(['id', 'name', 'slug', 'monthly_price', 'limits', 'features']);

        return response()->json($plans);
    }

    /**
     * GET /api/v1/billing/usage
     * Usage summary for the current month — lighter than /billing (no sub details).
     */
    public function usage(Request $request): JsonResponse
    {
        $org      = $request->user()->organization->loadMissing('plan');
        $snapshot = $this->metering->snapshot($org);

        return response()->json([
            'period'   => $snapshot['period'],
            'usage'    => $snapshot['usage'],
            'at_limit' => $snapshot['at_limit'],
        ]);
    }
}
