<?php

namespace App\Services\Demand;

use App\Models\Keyword;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Trend Detection Engine
 * 
 * Advanced trend detection with state machine and lifecycle tracking.
 * States: unknown → emerging → rising → peak → stable → declining → dormant
 * 
 * Sprint 6
 */
class TrendEngine
{
    protected BaselineService $baselineService;

    public function __construct(BaselineService $baselineService)
    {
        $this->baselineService = $baselineService;
    }

    /**
     * Detect and classify all trends for an organization
     */
    public function detectAllTrends(int $organizationId): array
    {
        $keywords = Keyword::whereHas('project', function($q) use ($organizationId) {
            $q->where('organization_id', $organizationId);
        })
        ->where('status', 'active')
        ->with(['measurements' => function($q) {
            $q->orderBy('date', 'desc')->limit(90);
        }])
        ->get();

        $trends = [
            'spike' => [],
            'rising' => [],
            'emerging' => [],
            'stable' => [],
            'declining' => [],
            'unknown' => [],
        ];

        foreach ($keywords as $keyword) {
            $state = $keyword->trend_state ?? 'unknown';
            $trends[$state][] = $this->formatTrendData($keyword);
        }

        return [
            'trends' => $trends,
            'summary' => $this->calculateTrendSummary($trends),
            'insights' => $this->generateInsights($trends),
        ];
    }

    /**
     * Get trending keywords (rising + spike)
     */
    public function getTrendingKeywords(int $organizationId, int $limit = 10): Collection
    {
        return Keyword::whereHas('project', function($q) use ($organizationId) {
            $q->where('organization_id', $organizationId);
        })
        ->whereIn('trend_state', ['spike', 'rising'])
        ->where('status', 'active')
        ->orderBy('growth_rate_7d', 'desc')
        ->limit($limit)
        ->get();
    }

    /**
     * Get declining keywords
     */
    public function getDecliningKeywords(int $organizationId, int $limit = 10): Collection
    {
        return Keyword::whereHas('project', function($q) use ($organizationId) {
            $q->where('organization_id', $organizationId);
        })
        ->where('trend_state', 'declining')
        ->where('status', 'active')
        ->orderBy('growth_rate_7d', 'asc')
        ->limit($limit)
        ->get();
    }

    /**
     * Get emerging keywords (new trends)
     */
    public function getEmergingKeywords(int $organizationId, int $limit = 10): Collection
    {
        return Keyword::whereHas('project', function($q) use ($organizationId) {
            $q->where('organization_id', $organizationId);
        })
        ->where('trend_state', 'emerging')
        ->where('status', 'active')
        ->where('current_interest', '>', 0)
        ->orderBy('created_at', 'desc')
        ->limit($limit)
        ->get();
    }

    /**
     * Analyze trend lifecycle and recommend next actions
     */
    public function analyzeTrendLifecycle(Keyword $keyword): array
    {
        $state = $keyword->trend_state;
        $growth7d = $keyword->growth_rate_7d;
        $growth30d = $keyword->growth_rate_30d;
        $volatility = $keyword->volatility;

        $lifecycle = $this->determineLifecycleStage($state, $growth7d, $growth30d);
        $actions = $this->recommendActions($lifecycle, $keyword);
        $timing = $this->estimateTiming($lifecycle, $growth7d, $growth30d);

        return [
            'current_state' => $state,
            'lifecycle_stage' => $lifecycle,
            'confidence' => $this->calculateConfidence($keyword),
            'actions' => $actions,
            'timing' => $timing,
            'risk_level' => $this->assessRisk($volatility, $state),
        ];
    }

    /**
     * Determine lifecycle stage
     */
    protected function determineLifecycleStage(string $state, float $growth7d, float $growth30d): string
    {
        return match($state) {
            'spike' => 'peak_alert',
            'rising' => $growth7d > $growth30d ? 'acceleration' : 'steady_growth',
            'emerging' => 'early_discovery',
            'stable' => 'maturity',
            'declining' => $growth7d < -50 ? 'rapid_decline' : 'slow_decline',
            default => 'observation',
        };
    }

    /**
     * Recommend actions based on lifecycle
     */
    protected function recommendActions(string $lifecycle, Keyword $keyword): array
    {
        $actions = match($lifecycle) {
            'peak_alert' => [
                'priority' => 'urgent',
                'actions' => [
                    'Create landing page immediately',
                    'Launch PPC campaign today',
                    'Publish SEO content within 24h',
                    'Set up conversion tracking',
                    'Monitor hourly for next 48h',
                ],
            ],
            'acceleration' => [
                'priority' => 'high',
                'actions' => [
                    'Increase budget allocation',
                    'Create supporting content',
                    'Launch email campaign',
                    'Prepare social media posts',
                    'Monitor daily',
                ],
            ],
            'steady_growth' => [
                'priority' => 'medium',
                'actions' => [
                    'Maintain current strategy',
                    'Optimize existing content',
                    'Build backlinks',
                    'Monitor weekly',
                ],
            ],
            'early_discovery' => [
                'priority' => 'medium',
                'actions' => [
                    'Research opportunity size',
                    'Analyze competition',
                    'Create initial content',
                    'Test small PPC budget',
                    'Monitor closely for validation',
                ],
            ],
            'maturity' => [
                'priority' => 'low',
                'actions' => [
                    'Maintain presence',
                    'Refresh content quarterly',
                    'Monitor monthly',
                ],
            ],
            'rapid_decline' => [
                'priority' => 'high',
                'actions' => [
                    'Pause or reduce spend',
                    'Investigate cause of decline',
                    'Redirect budget to growing keywords',
                    'Archive or update content',
                ],
            ],
            'slow_decline' => [
                'priority' => 'medium',
                'actions' => [
                    'Reduce investment gradually',
                    'Look for related growing keywords',
                    'Consider content refresh',
                ],
            ],
            default => [
                'priority' => 'low',
                'actions' => [
                    'Continue monitoring',
                    'Collect more data',
                ],
            ],
        };

        return $actions;
    }

    /**
     * Estimate timing/urgency
     */
    protected function estimateTiming(string $lifecycle, float $growth7d, float $growth30d): string
    {
        return match($lifecycle) {
            'peak_alert' => 'Act within 24 hours',
            'acceleration' => 'Act within 3-5 days',
            'steady_growth' => 'Act within 1-2 weeks',
            'early_discovery' => 'Research within 1 week, act within 1 month',
            'rapid_decline' => 'Review within 48 hours',
            'slow_decline' => 'Review within 1 week',
            default => 'Review within 1 month',
        };
    }

    /**
     * Calculate confidence score
     */
    protected function calculateConfidence(Keyword $keyword): int
    {
        $confidence = 50; // baseline

        // More data = more confidence
        $measurementCount = $keyword->measurements()->count();
        if ($measurementCount >= 90) {
            $confidence += 30;
        } elseif ($measurementCount >= 30) {
            $confidence += 20;
        } elseif ($measurementCount >= 7) {
            $confidence += 10;
        }

        // Low volatility = more confidence
        if ($keyword->volatility < 10) {
            $confidence += 15;
        } elseif ($keyword->volatility < 20) {
            $confidence += 10;
        } elseif ($keyword->volatility > 50) {
            $confidence -= 20;
        }

        // Clear trend = more confidence
        if (abs($keyword->growth_rate_30d) > 50) {
            $confidence += 10;
        }

        return max(0, min(100, $confidence));
    }

    /**
     * Assess risk level
     */
    protected function assessRisk(float $volatility, string $state): string
    {
        if ($state === 'spike' || $volatility > 50) {
            return 'high';
        }

        if ($state === 'declining' || $volatility > 30) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Format trend data for display
     */
    protected function formatTrendData(Keyword $keyword): array
    {
        return [
            'id' => $keyword->id,
            'term' => $keyword->keyword,
            'project' => $keyword->project->name ?? 'Unknown',
            'trend_state' => $keyword->trend_state,
            'current_interest' => $keyword->current_interest,
            'growth_7d' => $keyword->growth_rate_7d,
            'growth_30d' => $keyword->growth_rate_30d,
            'growth_90d' => $keyword->growth_rate_90d,
            'volatility' => $keyword->volatility,
            'baseline_7d' => $keyword->baseline_7d,
            'baseline_30d' => $keyword->baseline_30d,
            'last_measured' => $keyword->last_measured_at?->diffForHumans(),
            'priority' => $keyword->priority,
        ];
    }

    /**
     * Calculate trend summary statistics
     */
    protected function calculateTrendSummary(array $trends): array
    {
        $total = 0;
        foreach ($trends as $keywords) {
            $total += count($keywords);
        }

        return [
            'total_keywords' => $total,
            'spike_count' => count($trends['spike']),
            'rising_count' => count($trends['rising']),
            'emerging_count' => count($trends['emerging']),
            'stable_count' => count($trends['stable']),
            'declining_count' => count($trends['declining']),
            'unknown_count' => count($trends['unknown']),
            'action_required' => count($trends['spike']) + count($trends['rising']),
        ];
    }

    /**
     * Generate insights
     */
    protected function generateInsights(array $trends): array
    {
        $insights = [];

        // Spike alerts
        if (count($trends['spike']) > 0) {
            $insights[] = [
                'type' => 'urgent',
                'title' => 'Spike Detected',
                'message' => count($trends['spike']) . ' keyword(s) experiencing rapid growth. Immediate action recommended.',
                'count' => count($trends['spike']),
            ];
        }

        // Rising trends
        if (count($trends['rising']) > 0) {
            $insights[] = [
                'type' => 'opportunity',
                'title' => 'Rising Trends',
                'message' => count($trends['rising']) . ' keyword(s) showing consistent growth. Good time to invest.',
                'count' => count($trends['rising']),
            ];
        }

        // Emerging trends
        if (count($trends['emerging']) > 0) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Emerging Opportunities',
                'message' => count($trends['emerging']) . ' new keyword(s) detected. Monitor for validation.',
                'count' => count($trends['emerging']),
            ];
        }

        // Declining trends
        if (count($trends['declining']) > 0) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Declining Interest',
                'message' => count($trends['declining']) . ' keyword(s) losing momentum. Review and adjust.',
                'count' => count($trends['declining']),
            ];
        }

        return $insights;
    }

    /**
     * Get trend chart data
     */
    public function getChartData(Keyword $keyword, int $days = 30): array
    {
        $measurements = $keyword->measurements()
            ->orderBy('date', 'desc')
            ->limit($days)
            ->get()
            ->reverse()
            ->values();

        return [
            'labels' => $measurements->pluck('date')->map(fn($date) => $date->format('M d'))->toArray(),
            'values' => $measurements->pluck('interest')->toArray(),
            'baseline_7d' => array_fill(0, $measurements->count(), $keyword->baseline_7d),
            'baseline_30d' => array_fill(0, $measurements->count(), $keyword->baseline_30d),
        ];
    }
}
