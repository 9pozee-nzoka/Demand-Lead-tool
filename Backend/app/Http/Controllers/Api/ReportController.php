<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\Keyword;
use App\Models\KeywordMeasurement;
use App\Models\Lead;
use App\Models\Opportunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // JSON summary endpoints (used by the frontend dashboard)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/v1/reports/revenue-summary?from=&to=
     */
    public function revenueSummary(Request $request): JsonResponse
    {
        $orgId = $request->user()->organization_id;
        [$from, $to] = $this->dateRange($request);

        $deals = Deal::where('organization_id', $orgId)->won()
            ->whereBetween('won_at', [$from, $to]);

        $monthly = Deal::where('organization_id', $orgId)->won()
            ->whereBetween('won_at', [$from, $to])
            ->selectRaw("DATE_FORMAT(won_at, '%Y-%m') as month, SUM(value) as revenue, COUNT(*) as deals")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json([
            'period'         => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'total_revenue'  => (float) $deals->sum('value'),
            'won_deals'      => $deals->count(),
            'avg_deal_value' => $deals->count() > 0
                ? round($deals->sum('value') / $deals->count(), 2)
                : 0,
            'monthly'        => $monthly,
        ]);
    }

    /**
     * GET /api/v1/reports/funnel?from=&to=
     */
    public function funnel(Request $request): JsonResponse
    {
        $orgId = $request->user()->organization_id;
        [$from, $to] = $this->dateRange($request);

        $leads = Lead::where('organization_id', $orgId)
            ->whereBetween('created_at', [$from, $to]);

        $deals = Deal::where('organization_id', $orgId)
            ->whereBetween('created_at', [$from, $to]);

        $opps = Opportunity::whereHas('project',
            fn ($q) => $q->where('organization_id', $orgId))
            ->whereBetween('detected_at', [$from, $to]);

        $totalLeads = $leads->count();
        $qualified  = $leads->clone()->where('status', 'qualified')->count();
        $converted  = $leads->clone()->where('status', 'won')->count();
        $wonDeals   = $deals->clone()->won()->count();

        return response()->json([
            'period'             => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'opportunities'      => $opps->count(),
            'leads'              => $totalLeads,
            'qualified'          => $qualified,
            'deals'              => $deals->count(),
            'won_deals'          => $wonDeals,
            'revenue'            => (float) $deals->clone()->won()->sum('value'),
            'conversion_rate'    => $totalLeads > 0 ? round(($converted / $totalLeads) * 100, 1) : 0,
            'qualification_rate' => $totalLeads > 0 ? round(($qualified  / $totalLeads) * 100, 1) : 0,
        ]);
    }

    /**
     * GET /api/v1/reports/keywords?from=&to=&project_id=
     */
    public function keywords(Request $request): JsonResponse
    {
        $orgId = $request->user()->organization_id;
        [$from, $to] = $this->dateRange($request);

        $query = Keyword::whereHas('project', fn ($q) => $q->where('organization_id', $orgId))
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->project_id))
            ->withCount(['measurements', 'opportunities'])
            ->with(['measurements' => fn ($q) => $q
                ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                ->whereNotNull('growth')
                ->orderByDesc('date')
                ->limit(1),
            ])
            ->paginate(50);

        return response()->json($query);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CSV export endpoints (streamed — no temp files)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/v1/reports/export/leads.csv?from=&to=
     */
    public function exportLeads(Request $request): Response
    {
        $orgId = $request->user()->organization_id;
        [$from, $to] = $this->dateRange($request);

        $leads = Lead::where('organization_id', $orgId)
            ->whereBetween('created_at', [$from, $to])
            ->with(['assignedUser:id,name', 'opportunity.keyword:id,keyword'])
            ->orderByDesc('lead_score')
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="leads-' . now()->format('Y-m-d') . '.csv"',
            'Cache-Control'       => 'no-cache',
        ];

        $rows = collect([['Name','Email','Phone','Company','Location','Score','Label',
                           'Status','Source','Keyword','Assigned To','Created At']]);

        foreach ($leads as $lead) {
            $rows->push([
                $lead->name        ?? '',
                $lead->email       ?? '',
                $lead->phone       ?? '',
                $lead->company     ?? '',
                $lead->location    ?? '',
                $lead->lead_score  ?? 0,
                strtoupper($lead->score_label ?? ''),
                $lead->status      ?? '',
                $lead->source      ?? '',
                $lead->opportunity?->keyword?->keyword ?? '',
                $lead->assignedUser?->name ?? '',
                $lead->created_at->toDateTimeString(),
            ]);
        }

        $csv = $rows->map(fn ($row) => implode(',', array_map(
            fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"',
            $row
        )))->implode("\n");

        return response($csv, 200, $headers);
    }

    /**
     * GET /api/v1/reports/export/deals.csv?from=&to=
     */
    public function exportDeals(Request $request): Response
    {
        $orgId = $request->user()->organization_id;
        [$from, $to] = $this->dateRange($request);

        $deals = Deal::where('organization_id', $orgId)
            ->whereBetween('created_at', [$from, $to])
            ->with(['lead:id,name,email', 'assignedUser:id,name'])
            ->orderByDesc('created_at')
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="deals-' . now()->format('Y-m-d') . '.csv"',
            'Cache-Control'       => 'no-cache',
        ];

        $rows = collect([['Title','Value','Stage','Status','Lead Name','Lead Email',
                           'Assigned To','Expected Close','Won At','Created At']]);

        foreach ($deals as $deal) {
            $rows->push([
                $deal->title,
                $deal->value     ?? 0,
                $deal->stage     ?? '',
                $deal->status    ?? '',
                $deal->lead?->name  ?? '',
                $deal->lead?->email ?? '',
                $deal->assignedUser?->name ?? '',
                $deal->expected_close_at?->toDateString() ?? '',
                $deal->won_at?->toDateString()            ?? '',
                $deal->created_at->toDateTimeString(),
            ]);
        }

        $csv = $rows->map(fn ($row) => implode(',', array_map(
            fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"',
            $row
        )))->implode("\n");

        return response($csv, 200, $headers);
    }

    /**
     * GET /api/v1/reports/export/keywords.csv?from=&to=
     */
    public function exportKeywords(Request $request): Response
    {
        $orgId = $request->user()->organization_id;
        [$from, $to] = $this->dateRange($request);

        $keywords = Keyword::whereHas('project', fn ($q) => $q->where('organization_id', $orgId))
            ->with(['project:id,name', 'measurements' => fn ($q) => $q
                ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                ->orderByDesc('growth')->limit(1)])
            ->withCount('opportunities')
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="keywords-' . now()->format('Y-m-d') . '.csv"',
            'Cache-Control'       => 'no-cache',
        ];

        $rows = collect([['Keyword','Project','Intent','Priority','Status',
                           'Latest Interest','Latest Growth %','Opportunities']]);

        foreach ($keywords as $kw) {
            $m = $kw->measurements->first();
            $rows->push([
                $kw->keyword,
                $kw->project?->name ?? '',
                $kw->intent,
                $kw->priority,
                $kw->status,
                $m?->interest ?? '',
                $m ? number_format((float) $m->growth, 1) : '',
                $kw->opportunities_count ?? 0,
            ]);
        }

        $csv = $rows->map(fn ($row) => implode(',', array_map(
            fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"',
            $row
        )))->implode("\n");

        return response($csv, 200, $headers);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /** @return array{Carbon, Carbon} */
    private function dateRange(Request $request): array
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->from)->startOfDay()
            : now()->subDays(30)->startOfDay();

        $to = $request->filled('to')
            ? Carbon::parse($request->to)->endOfDay()
            : now()->endOfDay();

        return [$from, $to];
    }
}
