<?php

namespace App\Jobs;

use App\Models\SourceScraper;
use App\Services\Sources\SourceManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunSourceScrape implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public SourceScraper $source
    ) {
        $this->onQueue('ingestion');
    }

    /**
     * Execute the job.
     */
    public function handle(SourceManager $manager): void
    {
        Log::info("Starting source scrape", [
            'source_id' => $this->source->id,
            'source_name' => $this->source->name,
        ]);

        try {
            $job = $manager->runSource($this->source);
            
            Log::info("Source scrape completed", [
                'source_id' => $this->source->id,
                'job_id' => $job->id,
                'items_new' => $job->items_new,
                'items_updated' => $job->items_updated,
            ]);
        } catch (\Throwable $e) {
            Log::error("Source scrape failed", [
                'source_id' => $this->source->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Source scrape job permanently failed", [
            'source_id' => $this->source->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
