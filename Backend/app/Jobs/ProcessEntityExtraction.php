<?php

namespace App\Jobs;

use App\Models\ScrapedItem;
use App\Services\Intelligence\EntityExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessEntityExtraction - Extract structured entities from content
 * 
 * Queue: processing
 * Triggered after: intent classification
 */
class ProcessEntityExtraction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 90;

    protected ScrapedItem $item;

    public function __construct(ScrapedItem $item)
    {
        $this->item = $item;
        $this->onQueue('processing');
    }

    public function handle(EntityExtractor $extractor): void
    {
        Log::info("Processing entity extraction", [
            'item_id' => $this->item->id,
        ]);

        try {
            $entities = $extractor->extract($this->item);
            $extractor->updateItemWithEntities($this->item, $entities);

            $entityCounts = array_map('count', $entities);

            Log::info("Entity extraction complete", [
                'item_id' => $this->item->id,
                'entity_counts' => $entityCounts,
            ]);

            // Trigger keyword matching after entity extraction
            ProcessKeywordMatching::dispatch($this->item);

        } catch (\Throwable $e) {
            Log::error("Entity extraction failed", [
                'item_id' => $this->item->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Entity extraction job failed", [
            'item_id' => $this->item->id,
            'error' => $exception->getMessage(),
        ]);
    }

    public function tags(): array
    {
        return [
            'entity-extraction',
            'item:' . $this->item->id,
            'org:' . $this->item->organization_id,
        ];
    }
}
