<?php

namespace App\Services\Analytics;

use App\Models\Deal;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\ScrapeJob;
use App\Models\ScrapedItem;
use App\Models\SourceScraper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SourceAnalytics - Performance tracking and ROI measurement for data sources
 * 
 * Tracks which sources generate the most revenue and provides insights
 * for optimization and investment decisions.
 */
class SourceAnalytics
{
    /**
     * Get comprehensive analytics for all sources
     */
    public function getSourcePerformance(Organization $organization, int $days = 30): array
    {
        $since = now()->subDays($days);

        $sources = SourceScraper::where('organization_id', $organization->id)
            ->with(['scrapeJobs', 'scrapedItems'])
            ->get();

        $analytics = [];

        foreach ($sources as $source) {
            $analytics[] = $this->getSourceMetrics($source, $since);
        }

        // Sort by ROI descending
        usort($analytics, fn($a, $b) => $b['roi']['value'] <=> $a['roi']['value']);

        return [
            'sources' => $analytics,
            'summary' => $this->calculateSummary($analytics),
            'period' => [
                'days' => $days,
                'from' => $since->toDateString(),
                'to' => now()->toDateString(),
            ],
        ];
    }

    /**
     * Get detailed metrics for a single source
     */
    public function getSourceMetrics(SourceScraper $source, ?\Carbon\Carbon $since = null): array
    {
        $since = $since ?? now()->subDays(30);

        // Scraping metrics
        $jobs = $source->scrapeJobs()->where('started_at', '>=', $since)->get();
        $items = $source->scrapedItems()->where('created_at', '>=', $since)->get();

        $scrapingMetrics = [
            'total_jobs' => $jobs->count(),
            'successful_jobs' => $jobs->where('status', 'completed')->count(),
            'failed_jobs' => $jobs->where('status', 'failed')->count(),
            'total_items' => $items->count(),
            'items_new' => $jobs->sum('items_new'),
            'items_updated' => $jobs->sum('items_updated'),
            'uptime_percentage' => $this->calculateUptime($jobs),
            'avg_items_per_job' => $jobs->count() > 0 ? round($items->count() / $jobs->count(), 2) : 0,
        ];

        // Quality metrics
        $qualityMetrics = [
            'avg_relevance_score' => round($items->avg('relevance_score') ?? 0, 2),
            'avg_opportunity_score' => round($items->avg('opportunity_score') ?? 0, 2),
            'avg_lead_score' => round($items->avg('lead_score') ?? 0, 2),
            'high_quality_items' => $items->where('opportunity_score', '>=', 70)->count(),
            'opportunities_found' => $items->where('opportunity_score', '>=', 50)->count(),
        ];

        // Conversion metrics
        $convertedItems = $items->whereNotNull('lead_id');
        $leadIds = $convertedItems->pluck('lead_id')->unique();
        $leads = Lead::whereIn('id', $leadIds)->get();

        $conversionMetrics = [
            'items_converted' => $convertedItems->count(),
            'conversion_rate' => $items->count() > 0 
                ? round(($convertedItems->count() / $items->count()) * 100, 2) 
                : 0,
            'leads_generated' => $leads->count(),
            'hot_leads' => $leads->where('score_label', 'hot')->count(),
            'warm_leads' => $leads->where('score_label', 'warm')->count(),
        ];

        // Revenue metrics
        $dealIds = $leads->flatMap(fn($lead) => $lead->deals()->pluck('id'));
        $deals = Deal::whereIn('id', $dealIds)->get();
        
        $wonDeals = $deals->where('status', 'won');
        $totalRevenue = $wonDeals->sum('value');

        $revenueMetrics = [
            'deals_created' => $deals->count(),
            'deals_won' => $wonDeals->count(),
            'total_revenue' => $totalRevenue,
            'avg_deal_value' => $wonDeals->count() > 0 
                ? round($totalRevenue / $wonDeals->count(), 2) 
                : 0,
        ];

        // ROI calculation
        $estimatedCost = $this->estimateSourceCost($source, $jobs->count());
        $roi = [
            'estimated_cost' => $estimatedCost,
            'revenue' => $totalRevenue,
            'profit' => $totalRevenue - $estimatedCost,
            'value' => $estimatedCost > 0 
                ? round((($totalRevenue - $estimatedCost) / $estimatedCost) * 100, 2) 
                : 0,
            'per_lead_cost' => $leads->count() > 0 
                ? round($estimatedCost / $leads->count(), 2) 
                : 0,
        ];

        // Intent breakdown
        $intentBreakdown = [];
        foreach ($items->groupBy('intent') as $intent => $intentItems) {
            $intentLeadIds = $intentItems->whereNotNull('lead_id')->pluck('lead_id')->unique();
            $intentLeads = Lead::whereIn('id', $intentLeadIds)->get();
            
            $intentBreakdown[$intent] = [
                'items' => $intentItems->count(),
                'leads' => $intentLeads->count(),
                'conversion_rate' => $intentItems->count() > 0 
                    ? round(($intentLeads->count() / $intentItems->count()) * 100, 2) 
                    : 0,
            ];
        }

        return [
            'source_id' => $source->id,
            'source_name' => $source->name,
            'source_type' => $source->type,
            'source_category' => $source->category,
            'status' => $source->status,
            'scraping' => $scrapingMetrics,
            'quality' => $qualityMetrics,
            'conversion' => $conversionMetrics,
            'revenue' => $revenueMetrics,
            'roi' => $roi,
            'intent_breakdown' => $intentBreakdown,
            'health_score' => $this->calculateHealthScore($scrapingMetrics, $qualityMetrics, $conversionMetrics),
        ];
    }

    /**
     * Calculate uptime percentage
     */
    protected function calculateUptime($jobs): float
    {
        $total = $jobs->count();
        
        if ($total === 0) {
            return 100.0;
        }

        $successful = $jobs->where('status', 'completed')->count();
        return round(($successful / $total) * 100, 2);
    }

    /**
     * Estimate source operational cost
     */
    protected function estimateSourceCost(SourceScraper $source, int $jobsRun): float
    {
        // Base cost per job execution
        $baseCostPerJob = match ($source->type) {
            'rss' => 0.10,        // RSS feeds are cheap
            'tender' => 0.50,     // Tender scraping more complex
            'webhook' => 0.05,    // Webhooks very cheap
            default => 0.25,
        };

        // Storage cost per item
        $items = $source->scrapedItems()->count();
        $storageCost = $items * 0.001; // $0.001 per item

        // AI processing cost (if using OpenAI)
        $aiProcessedItems = $source->scrapedItems()
            ->whereNotNull('metadata->intent_classification->method')
            ->count();
        $aiCost = $aiProcessedItems * 0.02; // Estimated $0.02 per AI analysis

        return round(($jobsRun * $baseCostPerJob) + $storageCost + $aiCost, 2);
    }

    /**
     * Calculate overall health score for a source
     */
    protected function calculateHealthScore(array $scraping, array $quality, array $conversion): float
    {
        $scores = [];

        // Uptime score (40%)
        $scores['uptime'] = $scraping['uptime_percentage'] / 100;

        // Quality score (30%)
        $avgScore = ($quality['avg_relevance_score'] + $quality['avg_opportunity_score']) / 2;
        $scores['quality'] = $avgScore / 100;

        // Conversion score (30%)
        $scores['conversion'] = min(1, $conversion['conversion_rate'] / 20); // 20% is excellent

        $weightedScore = ($scores['uptime'] * 0.4) + ($scores['quality'] * 0.3) + ($scores['conversion'] * 0.3);
        
        return round($weightedScore * 100, 2);
    }

    /**
     * Calculate summary across all sources
     */
    protected function calculateSummary(array $analytics): array
    {
        $summary = [
            'total_sources' => count($analytics),
            'active_sources' => count(array_filter($analytics, fn($s) => $s['status'] === 'active')),
            'total_items' => array_sum(array_column(array_column($analytics, 'scraping'), 'total_items')),
            'total_leads' => array_sum(array_column(array_column($analytics, 'conversion'), 'leads_generated')),
            'total_revenue' => array_sum(array_column(array_column($analytics, 'revenue'), 'total_revenue')),
            'total_cost' => array_sum(array_column(array_column($analytics, 'roi'), 'estimated_cost')),
            'avg_conversion_rate' => 0,
            'avg_health_score' => 0,
            'best_performer' => null,
            'worst_performer' => null,
        ];

        if (count($analytics) > 0) {
            $summary['avg_conversion_rate'] = round(
                array_sum(array_column(array_column($analytics, 'conversion'), 'conversion_rate')) / count($analytics),
                2
            );

            $summary['avg_health_score'] = round(
                array_sum(array_column($analytics, 'health_score')) / count($analytics),
                2
            );

            // Find best and worst performers by ROI
            $sorted = $analytics;
            usort($sorted, fn($a, $b) => $b['roi']['value'] <=> $a['roi']['value']);
            
            $summary['best_performer'] = [
                'source_name' => $sorted[0]['source_name'],
                'roi' => $sorted[0]['roi']['value'],
            ];

            $summary['worst_performer'] = [
                'source_name' => $sorted[count($sorted) - 1]['source_name'],
                'roi' => $sorted[count($sorted) - 1]['roi']['value'],
            ];
        }

        $summary['overall_roi'] = $summary['total_cost'] > 0
            ? round((($summary['total_revenue'] - $summary['total_cost']) / $summary['total_cost']) * 100, 2)
            : 0;

        return $summary;
    }

    /**
     * Get source comparison report
     */
    public function compareSourceTypes(Organization $organization, int $days = 30): array
    {
        $since = now()->subDays($days);

        $sources = SourceScraper::where('organization_id', $organization->id)->get();
        
        $comparison = [];

        foreach ($sources->groupBy('type') as $type => $typeSources) {
            $metrics = [];
            
            foreach ($typeSources as $source) {
                $metrics[] = $this->getSourceMetrics($source, $since);
            }

            $comparison[$type] = [
                'count' => count($typeSources),
                'total_items' => array_sum(array_column(array_column($metrics, 'scraping'), 'total_items')),
                'total_leads' => array_sum(array_column(array_column($metrics, 'conversion'), 'leads_generated')),
                'total_revenue' => array_sum(array_column(array_column($metrics, 'revenue'), 'total_revenue')),
                'avg_conversion_rate' => count($metrics) > 0 
                    ? round(array_sum(array_column(array_column($metrics, 'conversion'), 'conversion_rate')) / count($metrics), 2)
                    : 0,
                'avg_roi' => count($metrics) > 0
                    ? round(array_sum(array_column(array_column($metrics, 'roi'), 'value')) / count($metrics), 2)
                    : 0,
            ];
        }

        return $comparison;
    }

    /**
     * Get trending insights
     */
    public function getTrendingInsights(Organization $organization, int $days = 30): array
    {
        $since = now()->subDays($days);

        $items = ScrapedItem::where('organization_id', $organization->id)
            ->where('created_at', '>=', $since)
            ->get();

        // Top keywords by revenue
        $topKeywordsByRevenue = $this->getTopKeywordsByRevenue($items);

        // Top intents by conversion
        $topIntentsByConversion = $this->getTopIntentsByConversion($items);

        // Top entities (organizations, locations)
        $topEntities = $this->getTopEntities($items);

        // Time-based patterns
        $timePatterns = $this->getTimePatterns($items);

        return [
            'top_keywords_by_revenue' => $topKeywordsByRevenue,
            'top_intents_by_conversion' => $topIntentsByConversion,
            'top_entities' => $topEntities,
            'time_patterns' => $timePatterns,
        ];
    }

    /**
     * Get top keywords by revenue
     */
    protected function getTopKeywordsByRevenue($items): array
    {
        $keywordRevenue = [];

        foreach ($items as $item) {
            if (!$item->lead_id) {
                continue;
            }

            $lead = $item->lead;
            if (!$lead) {
                continue;
            }

            $revenue = $lead->deals()->where('status', 'won')->sum('value');

            foreach ($item->matched_keywords ?? [] as $kw) {
                $keyword = $kw['keyword'] ?? null;
                if (!$keyword) {
                    continue;
                }

                if (!isset($keywordRevenue[$keyword])) {
                    $keywordRevenue[$keyword] = ['revenue' => 0, 'leads' => 0];
                }

                $keywordRevenue[$keyword]['revenue'] += $revenue;
                $keywordRevenue[$keyword]['leads']++;
            }
        }

        arsort($keywordRevenue);
        return array_slice($keywordRevenue, 0, 10, true);
    }

    /**
     * Get top intents by conversion rate
     */
    protected function getTopIntentsByConversion($items): array
    {
        $intentStats = [];

        foreach ($items->groupBy('intent') as $intent => $intentItems) {
            $converted = $intentItems->whereNotNull('lead_id')->count();
            $total = $intentItems->count();

            $intentStats[$intent] = [
                'total' => $total,
                'converted' => $converted,
                'conversion_rate' => $total > 0 ? round(($converted / $total) * 100, 2) : 0,
            ];
        }

        uasort($intentStats, fn($a, $b) => $b['conversion_rate'] <=> $a['conversion_rate']);

        return $intentStats;
    }

    /**
     * Get top entities
     */
    protected function getTopEntities($items): array
    {
        $organizations = [];
        $locations = [];

        foreach ($items as $item) {
            $entities = $item->metadata['extracted_entities'] ?? [];

            foreach ($entities['organizations'] ?? [] as $org) {
                $name = $org['name'] ?? null;
                if ($name) {
                    $organizations[$name] = ($organizations[$name] ?? 0) + 1;
                }
            }

            foreach ($entities['locations'] ?? [] as $loc) {
                $name = $loc['name'] ?? null;
                if ($name) {
                    $locations[$name] = ($locations[$name] ?? 0) + 1;
                }
            }
        }

        arsort($organizations);
        arsort($locations);

        return [
            'organizations' => array_slice($organizations, 0, 10, true),
            'locations' => array_slice($locations, 0, 10, true),
        ];
    }

    /**
     * Get time-based patterns
     */
    protected function getTimePatterns($items): array
    {
        $byDay = [];
        $byHour = [];

        foreach ($items as $item) {
            $dayOfWeek = $item->created_at->dayOfWeek; // 0 = Sunday
            $hour = $item->created_at->hour;

            $byDay[$dayOfWeek] = ($byDay[$dayOfWeek] ?? 0) + 1;
            $byHour[$hour] = ($byHour[$hour] ?? 0) + 1;
        }

        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $formattedByDay = [];
        foreach ($byDay as $day => $count) {
            $formattedByDay[$dayNames[$day]] = $count;
        }

        return [
            'by_day_of_week' => $formattedByDay,
            'by_hour' => $byHour,
        ];
    }

    /**
     * Get recommendations for source optimization
     */
    public function getOptimizationRecommendations(Organization $organization): array
    {
        $analytics = $this->getSourcePerformance($organization, 30);
        $recommendations = [];

        foreach ($analytics['sources'] as $source) {
            $recs = [];

            // Low uptime
            if ($source['scraping']['uptime_percentage'] < 80) {
                $recs[] = [
                    'type' => 'reliability',
                    'severity' => 'high',
                    'message' => "Uptime is {$source['scraping']['uptime_percentage']}% - investigate errors and improve stability",
                ];
            }

            // Low conversion rate
            if ($source['conversion']['conversion_rate'] < 5 && $source['scraping']['total_items'] > 50) {
                $recs[] = [
                    'type' => 'conversion',
                    'severity' => 'medium',
                    'message' => "Conversion rate is {$source['conversion']['conversion_rate']}% - consider adjusting keyword filters or source configuration",
                ];
            }

            // Negative ROI
            if ($source['roi']['value'] < 0 && $source['revenue']['total_revenue'] == 0) {
                $recs[] = [
                    'type' => 'roi',
                    'severity' => 'high',
                    'message' => "No revenue generated yet - consider pausing and reallocating resources to better-performing sources",
                ];
            }

            // Great performance
            if ($source['roi']['value'] > 200 && $source['health_score'] > 80) {
                $recs[] = [
                    'type' => 'success',
                    'severity' => 'info',
                    'message' => "Excellent performance! ROI {$source['roi']['value']}% - consider adding similar sources",
                ];
            }

            if (!empty($recs)) {
                $recommendations[$source['source_name']] = $recs;
            }
        }

        return $recommendations;
    }
}
