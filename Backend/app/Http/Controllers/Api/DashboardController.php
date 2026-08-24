<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Opportunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * GET /api/v1/dashboard
     * Overview stats for the authenticated organization.
     */
    public function index(Request $request): JsonResponse
    {
        $orgId = $request->user()->organization_id;
        $since = now()->startOfMonth();

        return response()->json([
            'opportunities' => [
                'total'      => Opportunity::whereHas('project', fn ($q) => $q->where('organization_id', $orgId))->count(),
                'high_score' => Opportunity::whereHas('project', fn ($q) => $q->where('organization_id', $orgId))
                    ->highScore(70)->active()->count(),
            ],
            'leads' => [
                'total'     => Lead::where('organization_id', $orgId)->count(),
                'this_month'=> Lead::where('organization_id', $orgId)->where('created_at', '>=', $since)->count(),
                'hot'       => Lead::where('organization_id', $orgId)->hot()->count(),
                'unassigned'=> Lead::where('organization_id', $orgId)->unassigned()->count(),
            ],
            'deals' => [
                'open'     => Deal::where('organization_id', $orgId)->open()->count(),
                'won'      => Deal::where('organization_id', $orgId)->won()->count(),
                'revenue'  => Deal::where('organization_id', $orgId)->won()
                    ->where('won_at', '>=', $since)
                    ->sum('value'),
            ],
        ]);
    }

    /**
     * GET /api/v1/dashboard/funnel
     * Search-to-revenue funnel numbers.
     */
    public function funnel(Request $request): JsonResponse
    {
        $orgId = $request->user()->organization_id;

        $leads     = Lead::where('organization_id', $orgId);
        $deals     = Deal::where('organization_id', $orgId);
        $opps      = Opportunity::whereHas('project', fn ($q) => $q->where('organization_id', $orgId));

        return response()->json([
            'opportunities' => $opps->count(),
            'leads'         => $leads->count(),
            'qualified'     => $leads->clone()->where('status', 'qualified')->count(),
            'deals'         => $deals->count(),
            'won_deals'     => $deals->clone()->won()->count(),
            'revenue'       => $deals->clone()->won()->sum('value'),
        ]);
    }

    /**
     * GET /api/v1/dashboard/roi
     * ROI summary for the current month.
     */
    public function roi(Request $request): JsonResponse
    {
        $orgId  = $request->user()->organization_id;
        $period = now()->format('Y-m');

        $revenue = Deal::where('organization_id', $orgId)
            ->won()
            ->whereYear('won_at', now()->year)
            ->whereMonth('won_at', now()->month)
            ->sum('value');

        $usageRecord = \App\Models\UsageRecord::where('organization_id', $orgId)
            ->where('period', $period)
            ->get()
            ->keyBy('metric');

        return response()->json([
            'period'               => $period,
            'revenue'              => $revenue,
            'leads_generated'      => $usageRecord->get('leads')?->quantity ?? 0,
            'ai_requests'          => $usageRecord->get('ai_requests')?->quantity ?? 0,
            'alerts_sent'          => $usageRecord->get('alerts')?->quantity ?? 0,
        ]);
    }
}
