<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\Keyword;
use App\Models\KeywordMeasurement;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\UsageRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * GET /api/v1/dashboard
     * Overview stats + top opportunities + trending keywords.
     */
    public function index(Request $request): JsonResponse
    {
        $orgId = $request->user()->organization_id;
        $since = now()->startOfMonth();

        // Core stats
        $opportunities = Opportunity::whereHas('project', fn ($q) => $q->where('organization_id', $orgId));
        $leads         = Lead::where('organization_id', $orgId);
        $deals         = Deal::where('organization_id', $orgId);

        // Top 5 opportunities (for dashboard widget)
        $topOpportunities = Opportunity::whereHas('project', fn ($q) => $q->where('organization_id', $orgId))
            ->with(['keyword:id,keyword,intent', 'location:id,city,country', 'project:id,name'])
            ->active()
            ->orderByDesc('opportunity_score')
            ->limit(5)
            ->get()
            ->map(fn ($o) => [
                'id'                => $o->id,
                'keyword'           => $o->keyword?->keyword,
                'intent'            => $o->keyword?->intent,
                'location'          => $o->location?->city ?? $o->location?->country,
                'project'           => $o->project?->name,
                'opportunity_score' => (float) $o->opportunity_score,
                'trend_state'       => $o->trend_state,
                'score_label'       => $o->scoreLabel(),
                'status'            => $o->status,
            ]);

        // Top 6 trending keywords (growth > 0, last 7 days)
        $trendingKeywords = KeywordMeasurement::whereHas('keyword.project', fn ($q) => $q->where('organization_id', $orgId))
            ->with('keyword:id,keyword,intent')
            ->whereNotNull('growth')
            ->where('growth', '>', 0)
            ->where('date', '>=', now()->subDays(7)->format('Y-m-d'))
            ->orderByDesc('growth')
            ->limit(6)
            ->get()
            ->map(fn ($m) => [
                'keyword_id' => $m->keyword_id,
                'keyword'    => $m->keyword?->keyword,
                'intent'     => $m->keyword?->intent,
                'growth'     => (float) $m->growth,
                'interest'   => $m->interest,
                'geo'        => $m->geo,
                'date'       => $m->date,
            ]);

        return response()->json([
            'opportunities' => [
                'total'      => $opportunities->count(),
                'high_score' => $opportunities->clone()->highScore(70)->active()->count(),
            ],
            'leads' => [
                'total'      => $leads->count(),
                'this_month' => $leads->clone()->where('created_at', '>=', $since)->count(),
                'hot'        => $leads->clone()->hot()->count(),
                'unassigned' => $leads->clone()->unassigned()->count(),
            ],
            'deals' => [
                'open'    => $deals->clone()->open()->count(),
                'won'     => $deals->clone()->won()->count(),
                'revenue' => (float) $deals->clone()->won()->where('won_at', '>=', $since)->sum('value'),
            ],
            'top_opportunities' => $topOpportunities,
            'trending_keywords' => $trendingKeywords,
        ]);
    }

    /**
     * GET /api/v1/dashboard/funnel
     */
    public function funnel(Request $request): JsonResponse
    {
        $orgId = $request->user()->organization_id;

        $leads = Lead::where('organization_id', $orgId);
        $deals = Deal::where('organization_id', $orgId);
        $opps  = Opportunity::whereHas('project', fn ($q) => $q->where('organization_id', $orgId));

        return response()->json([
            'opportunities' => $opps->count(),
            'leads'         => $leads->count(),
            'qualified'     => $leads->clone()->where('status', 'qualified')->count(),
            'deals'         => $deals->count(),
            'won_deals'     => $deals->clone()->won()->count(),
            'revenue'       => (float) $deals->clone()->won()->sum('value'),
        ]);
    }

    /**
     * GET /api/v1/dashboard/roi
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

        $usageRecord = UsageRecord::where('organization_id', $orgId)
            ->where('period', $period)
            ->get()
            ->keyBy('metric');

        return response()->json([
            'period'          => $period,
            'revenue'         => (float) $revenue,
            'leads_generated' => $usageRecord->get('leads')?->quantity ?? 0,
            'ai_requests'     => $usageRecord->get('ai_requests')?->quantity ?? 0,
            'alerts_sent'     => $usageRecord->get('alerts')?->quantity ?? 0,
        ]);
    }
}
