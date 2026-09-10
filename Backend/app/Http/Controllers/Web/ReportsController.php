<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\Keyword;
use App\Models\Lead;
use App\Models\Opportunity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = auth()->user()->organization_id;
        $period = $request->input('period', 30);
        $startDate = Carbon::now()->subDays($period);
        $endDate = Carbon::now();

        // Revenue Metrics
        $revenue = [
            'total' => Deal::where('organization_id', $organizationId)
                ->where('stage', 'won')
                ->where('won_at', '>=', $startDate)
                ->sum('value'),
            
            'pipeline' => Deal::where('organization_id', $organizationId)
                ->whereNotIn('stage', ['won', 'lost'])
                ->sum('value'),
            
            'avgDealSize' => Deal::where('organization_id', $organizationId)
                ->where('stage', 'won')
                ->where('won_at', '>=', $startDate)
                ->avg('value') ?? 0,
            
            'deals_won' => Deal::where('organization_id', $organizationId)
                ->where('stage', 'won')
                ->where('won_at', '>=', $startDate)
                ->count(),
        ];

        // Funnel Metrics
        $funnel = [
            'keywords' => Keyword::whereHas('project', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })
                ->where('created_at', '>=', $startDate)
                ->count(),
            
            'opportunities' => Opportunity::where('organization_id', $organizationId)
                ->where('created_at', '>=', $startDate)
                ->count(),
            
            'leads' => Lead::where('organization_id', $organizationId)
                ->where('created_at', '>=', $startDate)
                ->count(),
            
            'deals' => Deal::where('organization_id', $organizationId)
                ->where('created_at', '>=', $startDate)
                ->count(),
            
            'conversions' => Deal::where('organization_id', $organizationId)
                ->where('stage', 'won')
                ->where('won_at', '>=', $startDate)
                ->count(),
        ];

        // Conversion Rates
        $conversions = [
            'opportunity_to_lead' => $funnel['opportunities'] > 0 
                ? round(($funnel['leads'] / $funnel['opportunities']) * 100, 1) 
                : 0,
            
            'lead_to_deal' => $funnel['leads'] > 0 
                ? round(($funnel['deals'] / $funnel['leads']) * 100, 1) 
                : 0,
            
            'deal_to_won' => $funnel['deals'] > 0 
                ? round(($funnel['conversions'] / $funnel['deals']) * 100, 1) 
                : 0,
            
            'end_to_end' => $funnel['opportunities'] > 0 
                ? round(($funnel['conversions'] / $funnel['opportunities']) * 100, 1) 
                : 0,
        ];

        // ROI Calculation (simplified)
        $totalInvestment = 1000; // Placeholder - should come from actual campaign spend
        $roi = [
            'investment' => $totalInvestment,
            'revenue' => $revenue['total'],
            'profit' => $revenue['total'] - $totalInvestment,
            'percentage' => $totalInvestment > 0 
                ? round((($revenue['total'] - $totalInvestment) / $totalInvestment) * 100, 1) 
                : 0,
        ];

        // Revenue Trend Chart Data
        $revenueTrend = Deal::where('organization_id', $organizationId)
            ->where('stage', 'won')
            ->where('won_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(won_at) as date'),
                DB::raw('SUM(value) as revenue'),
                DB::raw('COUNT(*) as deals')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $revenueChartData = [
            'labels' => $revenueTrend->pluck('date')->map(fn($d) => Carbon::parse($d)->format('M d'))->toArray(),
            'datasets' => [
                [
                    'label' => 'Revenue ($)',
                    'data' => $revenueTrend->pluck('revenue')->toArray(),
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
        ];

        // Funnel Chart Data
        $funnelChartData = [
            'labels' => ['Opportunities', 'Leads', 'Deals', 'Won'],
            'datasets' => [
                [
                    'label' => 'Conversion Funnel',
                    'data' => [
                        $funnel['opportunities'],
                        $funnel['leads'],
                        $funnel['deals'],
                        $funnel['conversions'],
                    ],
                    'backgroundColor' => [
                        'rgba(234, 179, 8, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(147, 51, 234, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                    ],
                ],
            ],
        ];

        // Lead Source Performance
        $leadSources = Lead::where('organization_id', $organizationId)
            ->where('created_at', '>=', $startDate)
            ->select('source', DB::raw('COUNT(*) as count'))
            ->groupBy('source')
            ->get();

        $sourceChartData = [
            'labels' => $leadSources->pluck('source')->toArray(),
            'datasets' => [
                [
                    'label' => 'Lead Count',
                    'data' => $leadSources->pluck('count')->toArray(),
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(147, 51, 234, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(249, 115, 22, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                    ],
                ],
            ],
        ];

        // Top Performing Keywords
        $topKeywords = Keyword::whereHas('project', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })
            ->withCount('opportunities')
            ->orderBy('opportunities_count', 'desc')
            ->limit(10)
            ->get();

        return view('reports.index', compact(
            'revenue',
            'funnel',
            'conversions',
            'roi',
            'period',
            'startDate',
            'endDate',
            'revenueChartData',
            'funnelChartData',
            'sourceChartData',
            'topKeywords'
        ));
    }
}
