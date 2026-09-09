<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\{Keyword, Opportunity, Lead, Deal, Alert, Project};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Main analytics dashboard
     */
    public function index(Request $request)
    {
        $organizationId = $request->user()->organization_id;
        $period = $request->get('period', '30'); // 7, 30, 90 days or custom
        
        // Date range
        if ($period === 'custom') {
            $startDate = Carbon::parse($request->get('start_date', now()->subDays(30)));
            $endDate = Carbon::parse($request->get('end_date', now()));
        } else {
            $startDate = now()->subDays((int)$period);
            $endDate = now();
        }

        // Previous period for comparison
        $periodDays = $startDate->diffInDays($endDate);
        $prevStartDate = $startDate->copy()->subDays($periodDays);
        $prevEndDate = $startDate->copy();

        // Get analytics data
        $analytics = $this->getAnalyticsData($organizationId, $startDate, $endDate);
        $prevAnalytics = $this->getAnalyticsData($organizationId, $prevStartDate, $prevEndDate);
        
        // Calculate changes
        $changes = $this->calculateChanges($analytics, $prevAnalytics);

        // Chart data
        $charts = [
            'trends' => $this->getTrendsChartData($organizationId, $startDate, $endDate),
            'opportunities' => $this->getOpportunitiesChartData($organizationId, $startDate, $endDate),
            'leads' => $this->getLeadsChartData($organizationId, $startDate, $endDate),
            'revenue' => $this->getRevenueChartData($organizationId, $startDate, $endDate),
            'leadSources' => $this->getLeadSourcesData($organizationId, $startDate, $endDate),
            'opportunityScores' => $this->getOpportunityScoresData($organizationId, $startDate, $endDate),
        ];

        return view('analytics.index', compact(
            'analytics',
            'changes',
            'charts',
            'period',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Get core analytics data
     */
    protected function getAnalyticsData($organizationId, $startDate, $endDate)
    {
        return [
            // Keywords & Trends
            'total_keywords' => Keyword::whereHas('project', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })->count(),
            
            'rising_keywords' => Keyword::whereHas('project', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })->where('trend_state', 'rising')->count(),
            
            // Opportunities
            'total_opportunities' => Opportunity::where('organization_id', $organizationId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
                
            'high_opportunities' => Opportunity::where('organization_id', $organizationId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where(function($q) {
                    $q->where('opportunity_score', '>=', 80) // VERY HIGH (80-100)
                      ->orWhereBetween('opportunity_score', [60, 79]); // HIGH (60-79)
                })
                ->count(),
                
            'avg_opportunity_score' => Opportunity::where('organization_id', $organizationId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->avg('opportunity_score') ?? 0,
            
            // Leads
            'total_leads' => Lead::where('organization_id', $organizationId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
                
            'hot_leads' => Lead::where('organization_id', $organizationId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('score_label', 'hot')
                ->count(),
                
            'converted_leads' => Lead::where('organization_id', $organizationId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'converted')
                ->count(),
                
            'avg_lead_score' => Lead::where('organization_id', $organizationId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->avg('lead_score') ?? 0,
            
            // Deals & Revenue
            'total_deals' => Deal::where('organization_id', $organizationId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
                
            'won_deals' => Deal::where('organization_id', $organizationId)
                ->whereBetween('won_at', [$startDate, $endDate])
                ->where('stage', 'won')
                ->count(),
                
            'pipeline_value' => Deal::where('organization_id', $organizationId)
                ->whereNotIn('stage', ['won', 'lost'])
                ->sum('value'),
                
            'revenue' => Deal::where('organization_id', $organizationId)
                ->whereBetween('won_at', [$startDate, $endDate])
                ->where('stage', 'won')
                ->sum('value'),
                
            'avg_deal_size' => Deal::where('organization_id', $organizationId)
                ->whereBetween('won_at', [$startDate, $endDate])
                ->where('stage', 'won')
                ->avg('value') ?? 0,
            
            // Alerts
            'alerts_sent' => Alert::where('organization_id', $organizationId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
        ];
    }

    /**
     * Calculate percentage changes
     */
    protected function calculateChanges($current, $previous)
    {
        $changes = [];
        
        foreach ($current as $key => $value) {
            $prevValue = $previous[$key] ?? 0;
            
            if ($prevValue > 0) {
                $changes[$key] = round((($value - $prevValue) / $prevValue) * 100, 1);
            } else {
                $changes[$key] = $value > 0 ? 100 : 0;
            }
        }
        
        return $changes;
    }

    /**
     * Get trends chart data (daily)
     */
    protected function getTrendsChartData($organizationId, $startDate, $endDate)
    {
        $data = Keyword::whereHas('project', function($q) use ($organizationId) {
            $q->where('organization_id', $organizationId);
        })
        ->whereBetween('created_at', [$startDate, $endDate])
        ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        return [
            'labels' => $data->pluck('date')->map(fn($d) => Carbon::parse($d)->format('M d')),
            'datasets' => [
                [
                    'label' => 'New Keywords',
                    'data' => $data->pluck('count'),
                    'borderColor' => 'rgb(99, 102, 241)',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'tension' => 0.4,
                ]
            ]
        ];
    }

    /**
     * Get opportunities chart data
     */
    protected function getOpportunitiesChartData($organizationId, $startDate, $endDate)
    {
        $data = Opportunity::where('organization_id', $organizationId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, AVG(opportunity_score) as avg_score')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->map(fn($d) => Carbon::parse($d)->format('M d')),
            'datasets' => [
                [
                    'label' => 'Opportunities Count',
                    'data' => $data->pluck('count'),
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Avg Score',
                    'data' => $data->pluck('avg_score')->map(fn($s) => round($s, 1)),
                    'borderColor' => 'rgb(245, 158, 11)',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'yAxisID' => 'y1',
                ]
            ]
        ];
    }

    /**
     * Get leads chart data
     */
    protected function getLeadsChartData($organizationId, $startDate, $endDate)
    {
        $data = Lead::where('organization_id', $organizationId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, 
                         COUNT(*) as total,
                         SUM(CASE WHEN score_label = "hot" THEN 1 ELSE 0 END) as hot,
                         SUM(CASE WHEN status = "converted" THEN 1 ELSE 0 END) as converted')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->map(fn($d) => Carbon::parse($d)->format('M d')),
            'datasets' => [
                [
                    'label' => 'Total Leads',
                    'data' => $data->pluck('total'),
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
                [
                    'label' => 'Hot Leads',
                    'data' => $data->pluck('hot'),
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                ],
                [
                    'label' => 'Converted',
                    'data' => $data->pluck('converted'),
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ]
            ]
        ];
    }

    /**
     * Get revenue chart data
     */
    protected function getRevenueChartData($organizationId, $startDate, $endDate)
    {
        $data = Deal::where('organization_id', $organizationId)
            ->where('stage', 'won')
            ->whereBetween('won_at', [$startDate, $endDate])
            ->selectRaw('DATE(won_at) as date, 
                         SUM(value) as revenue,
                         COUNT(*) as deals')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->map(fn($d) => Carbon::parse($d)->format('M d')),
            'datasets' => [
                [
                    'label' => 'Revenue ($)',
                    'data' => $data->pluck('revenue'),
                    'type' => 'bar',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.7)',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Deals Won',
                    'data' => $data->pluck('deals'),
                    'type' => 'line',
                    'borderColor' => 'rgb(99, 102, 241)',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'yAxisID' => 'y1',
                ]
            ]
        ];
    }

    /**
     * Get lead sources distribution
     */
    protected function getLeadSourcesData($organizationId, $startDate, $endDate)
    {
        $data = Lead::where('organization_id', $organizationId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->orderByDesc('count')
            ->get();

        return [
            'labels' => $data->pluck('source')->map(fn($s) => ucfirst($s ?? 'Direct')),
            'datasets' => [
                [
                    'data' => $data->pluck('count'),
                    'backgroundColor' => [
                        'rgba(99, 102, 241, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                    ],
                ]
            ]
        ];
    }

    /**
     * Get opportunity scores distribution
     */
    protected function getOpportunityScoresData($organizationId, $startDate, $endDate)
    {
        $data = Opportunity::where('organization_id', $organizationId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                CASE 
                    WHEN opportunity_score >= 80 THEN "very_high"
                    WHEN opportunity_score >= 60 THEN "high"
                    WHEN opportunity_score >= 40 THEN "moderate"
                    ELSE "low"
                END as score_label,
                COUNT(*) as count
            ')
            ->groupBy('score_label')
            ->get();

        $labels = ['very_high' => 'Very High (80-100)', 'high' => 'High (60-79)', 'moderate' => 'Moderate (40-59)', 'low' => 'Low (0-39)'];

        return [
            'labels' => $data->pluck('score_label')->map(fn($l) => $labels[$l] ?? ucfirst($l)),
            'datasets' => [
                [
                    'data' => $data->pluck('count'),
                    'backgroundColor' => [
                        'rgba(16, 185, 129, 0.8)',   // very_high - green
                        'rgba(59, 130, 246, 0.8)',   // high - blue
                        'rgba(245, 158, 11, 0.8)',   // moderate - orange
                        'rgba(239, 68, 68, 0.8)',    // low - red
                    ],
                ]
            ]
        ];
    }

    /**
     * Export analytics data to CSV
     */
    public function exportCsv(Request $request)
    {
        $organizationId = $request->user()->organization_id;
        $period = $request->get('period', '30');
        
        if ($period === 'custom') {
            $startDate = Carbon::parse($request->get('start_date'));
            $endDate = Carbon::parse($request->get('end_date'));
        } else {
            $startDate = now()->subDays((int)$period);
            $endDate = now();
        }

        $analytics = $this->getAnalyticsData($organizationId, $startDate, $endDate);

        $filename = 'analytics_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($analytics, $startDate, $endDate) {
            $file = fopen('php://output', 'w');
            
            // Header
            fputcsv($file, ['DemandLead Analytics Report']);
            fputcsv($file, ['Period', $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d')]);
            fputcsv($file, []);
            
            // Keywords & Trends
            fputcsv($file, ['KEYWORDS & TRENDS']);
            fputcsv($file, ['Metric', 'Value']);
            fputcsv($file, ['Total Keywords', $analytics['total_keywords']]);
            fputcsv($file, ['Rising Keywords', $analytics['rising_keywords']]);
            fputcsv($file, []);
            
            // Opportunities
            fputcsv($file, ['OPPORTUNITIES']);
            fputcsv($file, ['Metric', 'Value']);
            fputcsv($file, ['Total Opportunities', $analytics['total_opportunities']]);
            fputcsv($file, ['High Priority Opportunities', $analytics['high_opportunities']]);
            fputcsv($file, ['Average Score', number_format($analytics['avg_opportunity_score'], 2)]);
            fputcsv($file, []);
            
            // Leads
            fputcsv($file, ['LEADS']);
            fputcsv($file, ['Metric', 'Value']);
            fputcsv($file, ['Total Leads', $analytics['total_leads']]);
            fputcsv($file, ['Hot Leads', $analytics['hot_leads']]);
            fputcsv($file, ['Converted Leads', $analytics['converted_leads']]);
            fputcsv($file, ['Average Lead Score', number_format($analytics['avg_lead_score'], 2)]);
            fputcsv($file, ['Conversion Rate', $analytics['total_leads'] > 0 ? number_format(($analytics['converted_leads'] / $analytics['total_leads']) * 100, 2) . '%' : '0%']);
            fputcsv($file, []);
            
            // Deals & Revenue
            fputcsv($file, ['DEALS & REVENUE']);
            fputcsv($file, ['Metric', 'Value']);
            fputcsv($file, ['Total Deals', $analytics['total_deals']]);
            fputcsv($file, ['Won Deals', $analytics['won_deals']]);
            fputcsv($file, ['Pipeline Value', '$' . number_format($analytics['pipeline_value'], 2)]);
            fputcsv($file, ['Revenue', '$' . number_format($analytics['revenue'], 2)]);
            fputcsv($file, ['Average Deal Size', '$' . number_format($analytics['avg_deal_size'], 2)]);
            fputcsv($file, ['Win Rate', $analytics['total_deals'] > 0 ? number_format(($analytics['won_deals'] / $analytics['total_deals']) * 100, 2) . '%' : '0%']);
            fputcsv($file, []);
            
            // Alerts
            fputcsv($file, ['ALERTS']);
            fputcsv($file, ['Alerts Sent', $analytics['alerts_sent']]);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get detailed data for export
     */
    public function exportDetailedCsv(Request $request)
    {
        $organizationId = $request->user()->organization_id;
        $type = $request->get('type', 'leads'); // leads, opportunities, deals
        $period = $request->get('period', '30');
        
        if ($period === 'custom') {
            $startDate = Carbon::parse($request->get('start_date'));
            $endDate = Carbon::parse($request->get('end_date'));
        } else {
            $startDate = now()->subDays((int)$period);
            $endDate = now();
        }

        $filename = $type . '_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($type, $organizationId, $startDate, $endDate) {
            $file = fopen('php://output', 'w');
            
            switch ($type) {
                case 'leads':
                    $this->exportLeadsData($file, $organizationId, $startDate, $endDate);
                    break;
                case 'opportunities':
                    $this->exportOpportunitiesData($file, $organizationId, $startDate, $endDate);
                    break;
                case 'deals':
                    $this->exportDealsData($file, $organizationId, $startDate, $endDate);
                    break;
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function exportLeadsData($file, $organizationId, $startDate, $endDate)
    {
        fputcsv($file, ['ID', 'Name', 'Email', 'Phone', 'Source', 'Score', 'Label', 'Status', 'Created At']);
        
        Lead::where('organization_id', $organizationId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->chunk(1000, function($leads) use ($file) {
                foreach ($leads as $lead) {
                    fputcsv($file, [
                        $lead->id,
                        $lead->name,
                        $lead->email,
                        $lead->phone,
                        $lead->source ?? 'N/A',
                        $lead->lead_score ?? 0,
                        $lead->score_label ?? 'N/A',
                        $lead->status,
                        $lead->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });
    }

    protected function exportOpportunitiesData($file, $organizationId, $startDate, $endDate)
    {
        fputcsv($file, ['ID', 'Keywords', 'Score', 'Status', 'Growth Rate', 'Volume', 'Created At']);
        
        Opportunity::where('organization_id', $organizationId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('keywords')
            ->orderBy('opportunity_score', 'desc')
            ->chunk(1000, function($opportunities) use ($file) {
                foreach ($opportunities as $opp) {
                    fputcsv($file, [
                        $opp->id,
                        $opp->keywords->pluck('keyword')->implode(', '),
                        $opp->opportunity_score,
                        $opp->status,
                        ($opp->growth_score ?? 0) . '%',
                        $opp->volume_score ?? 0,
                        $opp->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });
    }

    protected function exportDealsData($file, $organizationId, $startDate, $endDate)
    {
        fputcsv($file, ['ID', 'Title', 'Value', 'Stage', 'Contact', 'Assigned To', 'Expected Close', 'Won/Lost At']);
        
        Deal::where('organization_id', $organizationId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['contact', 'assignedUser'])
            ->orderBy('value', 'desc')
            ->chunk(1000, function($deals) use ($file) {
                foreach ($deals as $deal) {
                    $closedAt = $deal->won_at ?? $deal->lost_at;
                    
                    fputcsv($file, [
                        $deal->id,
                        $deal->title,
                        $deal->value,
                        $deal->stage,
                        $deal->contact->name ?? 'N/A',
                        $deal->assignedUser->name ?? 'Unassigned',
                        $deal->expected_close_at ? $deal->expected_close_at->format('Y-m-d') : 'N/A',
                        $closedAt ? $closedAt->format('Y-m-d H:i:s') : 'N/A',
                    ]);
                }
            });
    }
}
