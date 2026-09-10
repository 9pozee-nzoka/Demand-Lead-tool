<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Services\Sources\KeywordMatcherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessKeywordMatching implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Organization $organization,
        public int $batchSize = 100
    ) {
        $this->onQueue('processing');
    }

    /**
     * Execute the job.
     */
    public function handle(KeywordMatcherService $matcher): void
    {
        Log::info("Starting keyword matching", [
            'organization_id' => $this->organization->id,
            'batch_size' => $this->batchSize,
        ]);

        try {
            $processedCount = $matcher->batchMatchItems($this->organization, $this->batchSize);
            
            Log::info("Keyword matching completed", [
                'organization_id' => $this->organization->id,
                'processed_count' => $processedCount,
            ]);
        } catch (\Throwable $e) {
            Log::error("Keyword matching failed", [
                'organization_id' => $this->organization->id,
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
        Log::error("Keyword matching job permanently failed", [
            'organization_id' => $this->organization->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
