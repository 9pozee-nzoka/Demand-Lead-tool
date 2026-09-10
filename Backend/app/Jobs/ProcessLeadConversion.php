<?php

namespace App\Jobs;

use App\Models\ScrapedItem;
use App\Services\Sources\LeadConversionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessLeadConversion - Converts high-quality scraped items into CRM leads
 * 
 * Queue: lead_workflows
 * Triggered after: keyword matching and scoring
 */
class ProcessLeadConversion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * The scraped item to convert
     */
    protected ScrapedItem $item;

    /**
     * Conversion options
     */
    protected array $options;

    /**
     * Create a new job instance.
     */
    public function __construct(ScrapedItem $item, array $options = [])
    {
        $this->item = $item;
        $this->options = $options;
        $this->onQueue('lead_workflows');
    }

    /**
     * Execute the job.
     */
    public function handle(LeadConversionService $conversionService): void
    {
        Log::info("Processing lead conversion", [
            'item_id' => $this->item->id,
            'intent' => $this->item->intent,
            'lead_score' => $this->item->lead_score,
            'opportunity_score' => $this->item->opportunity_score,
        ]);

        try {
            $lead = $conversionService->convertToLead($this->item, $this->options);

            if ($lead) {
                Log::info("Lead conversion successful", [
                    'item_id' => $this->item->id,
                    'lead_id' => $lead->id,
                    'lead_quality' => $lead->quality,
                ]);

                // TODO: Dispatch follow-up jobs if needed
                // - QualifyLead for additional enrichment
                // - SendAlert if hot lead
                // - AssignLead for routing
            } else {
                Log::info("Item did not meet conversion criteria", [
                    'item_id' => $this->item->id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error("Lead conversion failed", [
                'item_id' => $this->item->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Lead conversion job failed after retries", [
            'item_id' => $this->item->id,
            'error' => $exception->getMessage(),
        ]);

        // Mark item as failed
        $this->item->update([
            'processing_status' => 'failed',
            'metadata' => array_merge($this->item->metadata ?? [], [
                'conversion_error' => $exception->getMessage(),
                'failed_at' => now()->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'lead-conversion',
            'item:' . $this->item->id,
            'org:' . $this->item->organization_id,
            'intent:' . $this->item->intent,
        ];
    }
}
