<?php

namespace App\Services\Demand;

use App\Models\Keyword;
use App\Models\KeywordMeasurement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Baseline Service
 * 
 * Calculates rolling averages and detects anomalies:
 * - 7-day baseline (short-term)
 * - 30-day baseline (medium-term)
 * - 90-day baseline (long-term)
 * 
 * Sprint 5
 */
class BaselineService
{
    /**
     * Calculate all baselines for a keyword
     */
    public function calculateBaselines(Keyword $keyword): array
    {
        $measurements = KeywordMeasurement::where('keyword_id', $keyword->id)
            ->orderBy('date', 'desc')
            ->limit(90)
            ->get();

        if ($measurements->isEmpty()) {
            return [
                'baseline_7d' => 0,
                'baseline_30d' => 0,
                'baseline_90d' => 0,
                'current_value' => 0,
                'has_data' => false,
            ];
        }

        $current = $measurements->first()->interest ?? 0;

        return [
            'baseline_7d' => $this->calculate7DayBaseline($measurements),
            'baseline_30d' => $this->calculate30DayBaseline($measurements),
            'baseline_90d' => $this->calculate90DayBaseline($measurements),
            'current_value' => $current,
            'has_data' => true,
            'data_points' => $measurements->count(),
        ];
    }

    /**
     * Calculate 7-day rolling average
     */
    public function calculate7DayBaseline(Collection $measurements): float
    {
        $recent = $measurements->take(7);
        
        if ($recent->count() < 3) {
            return 0; // Not enough data
        }

        $values = $recent->pluck('interest')->filter()->values();
        
        if ($values->isEmpty()) {
            return 0;
        }

        return round($values->average(), 2);
    }

    /**
     * Calculate 30-day rolling average
     */
    public function calculate30DayBaseline(Collection $measurements): float
    {
        $recent = $measurements->take(30);
        
        if ($recent->count() < 7) {
            return 0; // Not enough data
        }

        $values = $recent->pluck('interest')->filter()->values();
        
        if ($values->isEmpty()) {
            return 0;
        }

        return round($values->average(), 2);
    }

    /**
     * Calculate 90-day rolling average
     */
    public function calculate90DayBaseline(Collection $measurements): float
    {
        $recent = $measurements->take(90);
        
        if ($recent->count() < 14) {
            return 0; // Not enough data
        }

        $values = $recent->pluck('interest')->filter()->values();
        
        if ($values->isEmpty()) {
            return 0;
        }

        return round($values->average(), 2);
    }

    /**
     * Calculate growth rate
     */
    public function calculateGrowthRate(float $current, float $baseline): float
    {
        if ($baseline == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $baseline) / $baseline) * 100, 2);
    }

    /**
     * Detect if current value is an anomaly
     */
    public function detectAnomaly(float $current, float $baseline, float $threshold = 50): bool
    {
        if ($baseline == 0) {
            return false;
        }

        $growthRate = abs($this->calculateGrowthRate($current, $baseline));
        
        return $growthRate >= $threshold;
    }

    /**
     * Detect spike (sudden increase)
     */
    public function detectSpike(float $current, float $baseline7d, float $baseline30d, float $threshold = 100): bool
    {
        // Spike: Current is significantly above short-term and long-term baseline
        if ($baseline7d == 0 || $baseline30d == 0) {
            return false;
        }

        $growth7d = $this->calculateGrowthRate($current, $baseline7d);
        $growth30d = $this->calculateGrowthRate($current, $baseline30d);

        return $growth7d >= $threshold && $growth30d >= ($threshold / 2);
    }

    /**
     * Detect rising trend (consistent growth)
     */
    public function detectRisingTrend(float $baseline7d, float $baseline30d, float $baseline90d, float $threshold = 20): bool
    {
        // Rising: Short-term > Medium-term > Long-term
        if ($baseline30d == 0 || $baseline90d == 0) {
            return false;
        }

        $growth30d = $this->calculateGrowthRate($baseline7d, $baseline30d);
        $growth90d = $this->calculateGrowthRate($baseline30d, $baseline90d);

        return $growth30d >= $threshold && $growth90d >= ($threshold / 2);
    }

    /**
     * Detect declining trend
     */
    public function detectDecliningTrend(float $baseline7d, float $baseline30d, float $baseline90d, float $threshold = -20): bool
    {
        // Declining: Short-term < Medium-term < Long-term
        if ($baseline30d == 0 || $baseline90d == 0) {
            return false;
        }

        $growth30d = $this->calculateGrowthRate($baseline7d, $baseline30d);
        $growth90d = $this->calculateGrowthRate($baseline30d, $baseline90d);

        return $growth30d <= $threshold && $growth90d <= ($threshold / 2);
    }

    /**
     * Detect stable trend (minimal change)
     */
    public function detectStableTrend(float $baseline7d, float $baseline30d, float $threshold = 10): bool
    {
        if ($baseline30d == 0) {
            return false;
        }

        $growth = abs($this->calculateGrowthRate($baseline7d, $baseline30d));

        return $growth < $threshold;
    }

    /**
     * Determine trend state based on baselines
     */
    public function determineTrendState(array $baselines): string
    {
        if (!$baselines['has_data']) {
            return 'unknown';
        }

        $current = $baselines['current_value'];
        $baseline7d = $baselines['baseline_7d'];
        $baseline30d = $baselines['baseline_30d'];
        $baseline90d = $baselines['baseline_90d'];

        // Check for spike first (highest priority)
        if ($this->detectSpike($current, $baseline7d, $baseline30d)) {
            return 'spike';
        }

        // Check for rising trend
        if ($this->detectRisingTrend($baseline7d, $baseline30d, $baseline90d)) {
            return 'rising';
        }

        // Check for declining trend
        if ($this->detectDecliningTrend($baseline7d, $baseline30d, $baseline90d)) {
            return 'declining';
        }

        // Check for stable trend
        if ($this->detectStableTrend($baseline7d, $baseline30d)) {
            return 'stable';
        }

        // Check if emerging (new keyword with recent data)
        if ($baselines['data_points'] < 30 && $current > 0) {
            return 'emerging';
        }

        return 'unknown';
    }

    /**
     * Calculate volatility (standard deviation)
     */
    public function calculateVolatility(Collection $measurements): float
    {
        if ($measurements->count() < 2) {
            return 0;
        }

        $values = $measurements->pluck('interest')->filter()->values();
        
        if ($values->count() < 2) {
            return 0;
        }

        $mean = $values->average();
        $variance = $values->map(fn($value) => pow($value - $mean, 2))->average();
        $stdDev = sqrt($variance);

        return round($stdDev, 2);
    }

    /**
     * Get baseline summary for display
     */
    public function getBaselineSummary(Keyword $keyword): array
    {
        $baselines = $this->calculateBaselines($keyword);
        $measurements = KeywordMeasurement::where('keyword_id', $keyword->id)
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        $trendState = $this->determineTrendState($baselines);
        $volatility = $this->calculateVolatility($measurements);

        $growth7d = $this->calculateGrowthRate(
            $baselines['current_value'],
            $baselines['baseline_7d']
        );

        $growth30d = $this->calculateGrowthRate(
            $baselines['current_value'],
            $baselines['baseline_30d']
        );

        return [
            'baselines' => $baselines,
            'trend_state' => $trendState,
            'volatility' => $volatility,
            'growth_7d' => $growth7d,
            'growth_30d' => $growth30d,
            'is_anomaly' => $this->detectAnomaly($baselines['current_value'], $baselines['baseline_30d']),
            'recommendation' => $this->getRecommendation($trendState, $growth7d, $growth30d),
        ];
    }

    /**
     * Get recommendation based on trend state
     */
    protected function getRecommendation(string $trendState, float $growth7d, float $growth30d): string
    {
        return match($trendState) {
            'spike' => 'URGENT: Investigate spike immediately. High opportunity for quick action.',
            'rising' => 'Strong upward trend detected. Consider increasing budget and creating content.',
            'emerging' => 'New trend emerging. Monitor closely and prepare campaigns.',
            'stable' => 'Steady interest. Maintain current strategy.',
            'declining' => 'Declining interest. Review strategy or reallocate budget.',
            'unknown' => 'Insufficient data. Continue monitoring.',
            default => 'Monitor and analyze trend patterns.',
        };
    }

    /**
     * Batch calculate baselines for multiple keywords
     */
    public function batchCalculateBaselines(Collection $keywords): array
    {
        $results = [];

        foreach ($keywords as $keyword) {
            $results[$keyword->id] = $this->calculateBaselines($keyword);
        }

        return $results;
    }
}
