<?php

namespace App\Jobs;

use App\Models\ScrapedItem;
use App\Services\Sources\TenderScoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTenderScoring implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ScrapedItem $tender
    ) {
        $this->onQueue('scoring');
    }

    /**
     * Execute the job.
     */
    public function handle(TenderScoringService $scorer): void
    {
        Log::info("Starting tender scoring", [
            'tender_id' => $this->tender->id,
            'title' => $this->tender->title,
        ]);

        try {
            $scorer->scoreTender($this->tender);
            
            Log::info("Tender scoring completed", [
                'tender_id' => $this->tender->id,
                'opportunity_score' => $this->tender->opportunity_score,
                'lead_score' => $this->tender->lead_score,
            ]);
        } catch (\Throwable $e) {
            Log::error("Tender scoring failed", [
                'tender_id' => $this->tender->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Tender scoring job permanently failed", [
            'tender_id' => $this->tender->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
