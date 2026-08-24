<?php

namespace App\Jobs;

use App\Models\Keyword;
use App\Models\KeywordMeasurement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sprint 5–6: Normalize measurements, compute 7/30/90-day baselines,
 * calculate growth % and determine trend state.
 *
 * Queued on: processing
 * Dispatched by: CollectKeywordData (after ingestion)
 */
class ProcessDemandSignal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(public readonly int $keywordId)
    {
        $this->onQueue('processing');
    }

    public function handle(): void
    {
        $keyword = Keyword::find($this->keywordId);

        if (! $keyword) {
            return;
        }

        // TODO Sprint 5: Compute rolling baselines
        // $baseline7  = $keyword->measurements()->inPeriod(7)->avg('interest');
        // $baseline30 = $keyword->measurements()->inPeriod(30)->avg('interest');
        // $baseline90 = $keyword->measurements()->inPeriod(90)->avg('interest');
        //
        // $latest     = $keyword->measurements()->latest('date')->first();
        // $growth     = ($latest->interest - $baseline30) / max($baseline30, 1) * 100;
        //
        // $latest->update(['growth' => $growth]);

        Log::info("ProcessDemandSignal: keyword #{$this->keywordId} processed.");

        // Chain opportunity scoring
        CalculateOpportunity::dispatch($this->keywordId)->onQueue('scoring');
    }
}
