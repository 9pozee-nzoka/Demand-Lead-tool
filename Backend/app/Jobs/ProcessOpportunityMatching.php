<?php

namespace App\Jobs;

use App\Models\ScrapedItem;
use App\Services\Intelligence\OpportunityMatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessOpportunityMatching - Analyze items for business opportunities
 * 
 * Queue: scoring
 * Triggered after: keyword matching (for non-tender items)
 */
class ProcessOpportunityMatching implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 90;

    protected ScrapedItem $item;

    public function __construct(ScrapedItem $item)
    {
        $this->item = $item;
        $this->onQueue('scoring');
    }

    public function handle(OpportunityMatcher $matcher): void
    {
        Log::info("Processing opportunity matching", [
            'item_id' => $this->item->id,
            'intent' => $this->item->intent,
        ]);

        try {
            $organization = $this->item->organization;
            
            if (!$organization) {
                Log::warning("Item has no organization", ['item_id' => $this->item->id]);
                return;
            }

            $analysis = $matcher->analyze($this->item, $organization);
            $matcher->updateItemWithAnalysis($this->item, $analysis);

            Log::info("Opportunity matching complete", [
                'item_id' => $this->item->id,
                'is_opportunity' => $analysis['is_opportunity'],
                'score' => $analysis['score'],
                'confidence' => $analysis['confidence'],
            ]);

            // If it's an opportunity, trigger lead conversion check
            if ($analysis['is_opportunity'] && $analysis['score'] >= 60) {
                // The KeywordMatcherService already has logic to trigger lead conversion
                // based on opportunity_score, so we just need to refresh the item
                $this->item->refresh();
            }

        } catch (\Throwable $e) {
            Log::error("Opportunity matching failed", [
                'item_id' => $this->item->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Opportunity matching job failed", [
            'item_id' => $this->item->id,
            'error' => $exception->getMessage(),
        ]);
    }

    public function tags(): array
    {
        return [
            'opportunity-matching',
            'item:' . $this->item->id,
            'org:' . $this->item->organization_id,
        ];
    }
}
