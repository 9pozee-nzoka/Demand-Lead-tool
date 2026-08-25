<?php

namespace App\Jobs;

use App\Models\Opportunity;
use App\Services\AI\OpportunityExplainerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sprint 7 (AI stub) — Generate a human-readable explanation and
 * recommended action plan for an opportunity using the AI layer.
 *
 * In Sprint 8+ this will call OpenAI (or equivalent) to produce:
 *   - WHY this opportunity matters (natural language summary)
 *   - WHAT TO DO next (prioritised action list)
 *
 * For now it generates rule-based explanations without an AI call,
 * which provides immediate value and avoids OpenAI costs during dev.
 *
 * Queue: processing
 */
class GenerateOpportunityExplanation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 45;

    public function __construct(public readonly int $opportunityId)
    {
        $this->onQueue('processing');
    }

    public function handle(OpportunityExplainerService $explainer): void
    {
        $opportunity = Opportunity::with(['keyword', 'location', 'project'])->find($this->opportunityId);

        if (! $opportunity) {
            return;
        }

        // Skip if explanation already exists (idempotent)
        if ($opportunity->explanation) {
            return;
        }

        $explanation = $explainer->explain($opportunity);
        $actions     = $explainer->recommendedActions($opportunity);

        $opportunity->update([
            'explanation'         => $explanation,
            'recommended_actions' => $actions,
        ]);

        Log::info("GenerateOpportunityExplanation: opportunity #{$this->opportunityId} explained.");
    }
}
