<?php

namespace App\Services\Demand;

use App\Models\Keyword;
use App\Models\KeywordMeasurement;
use Illuminate\Support\Collection;

/**
 * Sprint 6 — Classifies trend state for a keyword based on its
 * computed growth % and the architecture's defined state machine:
 *
 *   NORMAL → EMERGING → RISING → RAPIDLY_RISING → SPIKE
 *                                                ↓
 *                                             PEAK → STABLE → DECLINING
 *
 * All thresholds are read from config/demand.php so they can be
 * tuned per deployment without touching code.
 */
class TrendDetectionService
{
    protected float $risingThreshold;
    protected float $rapidThreshold;
    protected float $spikeThreshold;
    protected float $decliningThreshold;

    public function __construct()
    {
        $this->risingThreshold   = (float) config('demand.rising_threshold',   20);
        $this->rapidThreshold    = (float) config('demand.rapid_threshold',    50);
        $this->spikeThreshold    = (float) config('demand.spike_threshold',   100);
        $this->decliningThreshold = (float) config('demand.declining_threshold', -20);
    }

    // -------------------------------------------------------------------------
    // Core classification
    // -------------------------------------------------------------------------

    /**
     * Classify a growth % into a named trend state.
     *
     * @param  float      $growth        Current growth % vs 30-day baseline
     * @param  float|null $previousGrowth Previous period growth (for peak detection)
     */
    public function classify(float $growth, ?float $previousGrowth = null): string
    {
        // Spike: extremely rapid single-period jump
        if ($growth >= $this->spikeThreshold) {
            return 'spike';
        }

        // Rapidly rising
        if ($growth >= $this->rapidThreshold) {
            return 'rapidly_rising';
        }

        // Rising
        if ($growth >= $this->risingThreshold) {
            return 'rising';
        }

        // Emerging (subtle but positive momentum)
        if ($growth >= 5) {
            return 'emerging';
        }

        // Declining
        if ($growth <= $this->decliningThreshold) {
            return 'declining';
        }

        // Peak detection: was rising last period, now near zero growth
        if ($previousGrowth !== null && $previousGrowth >= $this->risingThreshold && abs($growth) < 5) {
            return 'peak';
        }

        // Stable: near zero growth
        if (abs($growth) < 5) {
            return 'stable';
        }

        return 'normal';
    }

    /**
     * Compute and return the trend state for a keyword based on its
     * most recent measurement growth values.
     *
     * Does NOT persist — call persistTrendState() to write back.
     */
    public function detectForKeyword(Keyword $keyword, string $source = 'google_trends'): string
    {
        $measurements = KeywordMeasurement::where('keyword_id', $keyword->id)
            ->where('source', $source)
            ->whereNotNull('growth')
            ->orderBy('date', 'desc')
            ->limit(2)
            ->get();

        if ($measurements->isEmpty()) {
            return 'normal';
        }

        $latest   = $measurements->first();
        $previous = $measurements->count() > 1 ? $measurements->last() : null;

        return $this->classify(
            growth:         (float) $latest->growth,
            previousGrowth: $previous ? (float) $previous->growth : null,
        );
    }

    /**
     * Detect trend state and return a full signal summary.
     *
     * @return array{
     *   state: string,
     *   growth: float,
     *   baseline_30: float,
     *   current_interest: float,
     *   momentum: string
     * }
     */
    public function analyse(Keyword $keyword, BaselineService $baseline, string $source = 'google_trends'): array
    {
        $measurements = KeywordMeasurement::where('keyword_id', $keyword->id)
            ->where('source', $source)
            ->orderBy('date')
            ->get();

        if ($measurements->isEmpty()) {
            return [
                'state'            => 'normal',
                'growth'           => 0.0,
                'baseline_30'      => 0.0,
                'current_interest' => 0.0,
                'momentum'         => 'flat',
            ];
        }

        $baselines       = $baseline->calculateBaselines($measurements);
        $currentInterest = $baseline->currentInterest($measurements);

        $b30    = $baselines['baseline_30'] > 0 ? $baselines['baseline_30'] : 1;
        $growth = round((($currentInterest - $b30) / $b30) * 100, 2);

        // Previous week's growth for peak detection
        $prevMeasurements = $measurements->take(-14)->take(7);
        $prevInterest     = $prevMeasurements->avg('interest') ?? 0;
        $prevGrowth       = round((((float)$prevInterest - $b30) / $b30) * 100, 2);

        $state    = $this->classify($growth, $prevGrowth);
        $momentum = $this->momentum($growth, $prevGrowth);

        return [
            'state'            => $state,
            'growth'           => $growth,
            'baseline_30'      => $baselines['baseline_30'],
            'current_interest' => $currentInterest,
            'momentum'         => $momentum,
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Human-readable momentum direction */
    private function momentum(float $current, float $previous): string
    {
        $delta = $current - $previous;
        if ($delta > 5)  return 'accelerating';
        if ($delta < -5) return 'decelerating';
        return 'flat';
    }

    /**
     * Map trend state to a 0-100 score contribution.
     * Used by OpportunityScoringService for the growth_score component.
     */
    public function stateToScore(string $state, float $growth): float
    {
        // Base score from state
        $stateScore = match ($state) {
            'spike'          => 95,
            'rapidly_rising' => 85,
            'rising'         => 70,
            'emerging'       => 50,
            'peak'           => 55,
            'stable'         => 30,
            'declining'      => 5,
            default          => 20,  // normal
        };

        // Fine-tune with growth magnitude (cap bonus at 10 pts)
        $growthBonus = min(10, max(0, ($growth - $this->risingThreshold) * 0.1));

        return min(100, $stateScore + $growthBonus);
    }
}
