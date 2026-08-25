<?php

namespace App\Services\Demand;

use App\Models\Keyword;
use App\Models\KeywordMeasurement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sprint 5 — Computes rolling baselines and growth % for a keyword.
 *
 * Baselines:
 *   7-day  — short-term momentum (is it rising this week?)
 *   30-day — recent normal (what is "normal" for this keyword?)
 *   90-day — long-term context / seasonality correction
 *
 * Growth formula (architecture spec §38):
 *   growth = (current_interest - baseline_30) / baseline_30 × 100
 *
 * Where current_interest is the 7-day rolling average (smoothed to
 * avoid single-day noise spikes being misclassified as trends).
 */
class BaselineService
{
    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Compute and persist growth % for all un-scored measurements of a keyword.
     * Only measurements with growth = null are processed to avoid redundant work.
     *
     * @return array{baseline_7: float, baseline_30: float, baseline_90: float, latest_growth: float|null}
     */
    public function computeAndPersist(Keyword $keyword, string $source = 'google_trends'): array
    {
        $measurements = KeywordMeasurement::where('keyword_id', $keyword->id)
            ->where('source', $source)
            ->orderBy('date')
            ->get(['id', 'date', 'interest', 'growth', 'geo']);

        if ($measurements->count() < 2) {
            return ['baseline_7' => 0, 'baseline_30' => 0, 'baseline_90' => 0, 'latest_growth' => null];
        }

        $baselines = $this->calculateBaselines($measurements);

        // Update only measurements that don't have a growth value yet
        $needsUpdate = $measurements->where('growth', null);

        foreach ($needsUpdate as $measurement) {
            $growthBaseline = $baselines['baseline_30'] > 0 ? $baselines['baseline_30'] : 1;
            $growth = (($measurement->interest - $growthBaseline) / $growthBaseline) * 100;
            $growth = round($growth, 2);

            $measurement->update(['growth' => $growth]);
        }

        // Latest growth = growth of the most recent measurement
        $latest       = $measurements->last();
        $latestBaseline = $baselines['baseline_30'] > 0 ? $baselines['baseline_30'] : 1;
        $latestGrowth = round((($latest->interest - $latestBaseline) / $latestBaseline) * 100, 2);

        return array_merge($baselines, ['latest_growth' => $latestGrowth]);
    }

    /**
     * Compute baselines without writing to DB — used by TrendDetectionService.
     *
     * @return array{baseline_7: float, baseline_30: float, baseline_90: float}
     */
    public function calculateBaselines(Collection $measurements): array
    {
        $now   = now();
        $all   = $measurements->sortByDesc('date');

        $b7  = $all->filter(fn ($m) => $this->daysAgo($m->date, $now) <= 7)
                   ->avg('interest') ?? 0;
        $b30 = $all->filter(fn ($m) => $this->daysAgo($m->date, $now) <= 30)
                   ->avg('interest') ?? 0;
        $b90 = $all->filter(fn ($m) => $this->daysAgo($m->date, $now) <= 90)
                   ->avg('interest') ?? 0;

        return [
            'baseline_7'  => round((float) $b7,  2),
            'baseline_30' => round((float) $b30, 2),
            'baseline_90' => round((float) $b90, 2),
        ];
    }

    /**
     * Return a smoothed "current interest" — 7-day average — to reduce noise.
     */
    public function currentInterest(Collection $measurements): float
    {
        $recent = $measurements
            ->sortByDesc('date')
            ->take(7)
            ->avg('interest');

        return round((float) ($recent ?? 0), 2);
    }

    /**
     * Batch-recompute growth for all measurements of all active keywords
     * for a given organization. Used by the scheduler to backfill history.
     */
    public function batchRecompute(int $organizationId): int
    {
        $processed = 0;

        Keyword::whereHas('project', fn ($q) => $q->where('organization_id', $organizationId))
            ->where('status', 'active')
            ->chunkById(50, function ($keywords) use (&$processed) {
                foreach ($keywords as $keyword) {
                    $sources = KeywordMeasurement::where('keyword_id', $keyword->id)
                        ->distinct()
                        ->pluck('source');

                    foreach ($sources as $source) {
                        $this->computeAndPersist($keyword, $source);
                        $processed++;
                    }
                }
            });

        return $processed;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function daysAgo(string $date, \Carbon\Carbon $now): int
    {
        return (int) \Carbon\Carbon::parse($date)->diffInDays($now);
    }
}
