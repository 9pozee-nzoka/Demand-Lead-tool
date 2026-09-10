<?php

namespace App\Jobs;

use App\Models\ScrapedItem;
use App\Services\Intelligence\IntentClassifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessIntentClassification - AI-powered intent classification
 * 
 * Queue: processing
 * Triggered after: content normalization
 */
class ProcessIntentClassification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    protected ScrapedItem $item;

    public function __construct(ScrapedItem $item)
    {
        $this->item = $item;
        $this->onQueue('processing');
    }

    public function handle(IntentClassifier $classifier): void
    {
        Log::info("Processing intent classification", [
            'item_id' => $this->item->id,
        ]);

        try {
            $classification = $classifier->classify($this->item);
            $classifier->updateItemWithIntent($this->item, $classification);

            Log::info("Intent classification complete", [
                'item_id' => $this->item->id,
                'intent' => $classification['intent'],
                'confidence' => $classification['confidence'],
            ]);

            // Trigger entity extraction after classification
            ProcessEntityExtraction::dispatch($this->item);

        } catch (\Throwable $e) {
            Log::error("Intent classification failed", [
                'item_id' => $this->item->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Intent classification job failed", [
            'item_id' => $this->item->id,
            'error' => $exception->getMessage(),
        ]);
    }

    public function tags(): array
    {
        return [
            'intent-classification',
            'item:' . $this->item->id,
            'org:' . $this->item->organization_id,
        ];
    }
}
