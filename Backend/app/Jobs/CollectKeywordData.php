<?php

namespace App\Jobs;

use App\Models\Keyword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sprint 4: Fetch permitted demand data for a keyword from all active sources.
 *
 * Queued on: ingestion
 * Dispatched by: KeywordController::store, Scheduler (daily)
 */
class CollectKeywordData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(public readonly int $keywordId)
    {
        $this->onQueue('ingestion');
    }

    public function handle(): void
    {
        $keyword = Keyword::with(['locations', 'project.organization'])->find($this->keywordId);

        if (! $keyword || $keyword->status !== 'active') {
            return;
        }

        // TODO Sprint 4: iterate over active DataSources for this organization,
        // resolve the KeywordDataProvider contract, fetch interest/volume/competition
        // and persist a KeywordMeasurement record.
        //
        // $provider = app(KeywordDataProviderFactory::class)->make($source->type);
        // $data     = $provider->getInterest($keyword->keyword, $location, '30d');
        // KeywordMeasurement::create([...]);

        Log::info("CollectKeywordData: keyword #{$this->keywordId} queued for ingestion.");

        // After collection, chain processing
        ProcessDemandSignal::dispatch($this->keywordId)->onQueue('processing');
    }
}
