<?php

namespace App\Jobs;

use App\Models\Keyword;
use App\Models\KeywordMeasurement;
use App\Services\Demand\BaselineService;
use App\Services\Demand\TrendDetectionService;
use App\Services\Opportunities\OpportunityScoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sprint 7 — Score an opportunity based on:
 *
 *   growth_score      × 0.30  (trend state + growth magnitude)
 *   intent_score      × 0.25  (transactional > local > commercial > informational)
 *   geo_score         × 0.15  (city > county > region > country)
 *   volume_score      × 0.15  (relative interest 0-100)
 *   competition_score × 0.10  (lower competition = higher score)
 *   historical_score  × 0.05  (past conversion rate for this keyword)
 *
 * Persists an Opportunity record and chains SendAlert if score ≥ threshold.
 *
 * Queue : scoring
 * Chains: SendAlert (notifications queue) when score ≥ demand.alert_min_score
 */
class CalculateOpportunity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function backoff(): array
    {
        return [60, 300];
    }

    public function __construct(public readonly int $keywordId)
    {
        $this->onQueue('scoring');
    }

    // -------------------------------------------------------------------------

    public function handle(
        OpportunityScoringService $scorer,
        BaselineService $baseline,
        TrendDetectionService $trend,
    ): void {
        $keyword = Keyword::with([
            'locations',
            'project:id,organization_id,country',
        ])->find($this->keywordId);

        if (! $keyword || $keyword->status !== 'active') {
            return;
        }

        // Score each source that has measurements for this keyword
        $sources = KeywordMeasurement::where('keyword_id', $keyword->id)
            ->whereNotNull('growth')
            ->distinct()
            ->pluck('source');

        if ($sources->isEmpty()) {
            Log::info("CalculateOpportunity: no growth data yet for keyword #{$this->keywordId}");
            return;
        }

        $bestOpportunity = null;
        $bestScore       = 0;

        foreach ($sources as $source) {
            $opportunity = $scorer->scoreKeyword($keyword, $source);

            if ($opportunity && $opportunity->opportunity_score > $bestScore) {
                $bestScore       = $opportunity->opportunity_score;
                $bestOpportunity = $opportunity;
            }
        }

        if (! $bestOpportunity) {
            Log::info("CalculateOpportunity: keyword #{$this->keywordId} scored below threshold.");
            return;
        }

        Log::info(sprintf(
            "CalculateOpportunity: keyword #%d scored %.1f (%s) — state: %s",
            $this->keywordId,
            $bestOpportunity->opportunity_score,
            OpportunityScoringService::label($bestOpportunity->opportunity_score),
            $bestOpportunity->trend_state,
        ));

        // Chain: fire alerts if score meets the minimum threshold
        $alertMinScore = (float) config('demand.alert_min_score', 60);

        if ($bestOpportunity->opportunity_score >= $alertMinScore) {
            SendAlert::dispatch($bestOpportunity->id)->onQueue('notifications');
        }

        // Chain: generate AI explanation (Sprint 7 stub — Sprint 8+ fills in)
        GenerateOpportunityExplanation::dispatch($bestOpportunity->id)->onQueue('processing');
    }
}
