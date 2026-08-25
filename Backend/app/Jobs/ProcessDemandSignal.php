<?php

namespace App\Jobs;

use App\Models\Keyword;
use App\Models\KeywordMeasurement;
use App\Services\Demand\BaselineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sprint 5 — Normalize measurements, compute 7/30/90-day baselines,
 * calculate growth % and determine trend state for each measurement.
 *
 * This job runs after CollectKeywordData has persisted raw interest
 * values. It writes growth % back to keyword_measurements so that
 * the TrendDetectionService and OpportunityScoringService have clean
 * input data.
 *
 * Queue : processing
 * Chains: CalculateOpportunity (scoring queue) on completion
 */
class ProcessDemandSignal implements ShouldQueue
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
        $this->onQueue('processing');
    }

    // -------------------------------------------------------------------------

    public function handle(BaselineService $baseline): void
    {
        $keyword = Keyword::with(['project:id,organization_id'])->find($this->keywordId);

        if (! $keyword) {
            return;
        }

        // Compute baselines for each source that has measurements
        $sources = KeywordMeasurement::where('keyword_id', $keyword->id)
            ->distinct()
            ->pluck('source');

        if ($sources->isEmpty()) {
            Log::info("ProcessDemandSignal: no measurements yet for keyword #{$this->keywordId}");
            return;
        }

        $results = [];

        foreach ($sources as $source) {
            $result   = $baseline->computeAndPersist($keyword, $source);
            $results[$source] = $result;

            Log::info(sprintf(
                "ProcessDemandSignal: keyword #%d / %s — b7=%.1f b30=%.1f b90=%.1f growth=%.1f%%",
                $this->keywordId,
                $source,
                $result['baseline_7'],
                $result['baseline_30'],
                $result['baseline_90'],
                $result['latest_growth'] ?? 0,
            ));
        }

        // Chain: score the opportunity
        CalculateOpportunity::dispatch($this->keywordId)->onQueue('scoring');
    }
}
