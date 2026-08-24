<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\UsageRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sprint 10–11: AI-assisted lead qualification.
 *
 * Uses the AI layer to ask qualification questions (location, service,
 * urgency, budget, preferred contact), summarize the enquiry and compute
 * the weighted lead score.
 *
 * Lead Score = intent*0.30 + engagement*0.20 + location_fit*0.15
 *            + product_fit*0.15 + budget_fit*0.10 + recency*0.10
 *
 * Queued on: lead_workflows
 * Dispatched by: WhatsApp webhook handler, lead form submission
 */
class QualifyLead implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(public readonly int $leadId)
    {
        $this->onQueue('lead_workflows');
    }

    public function handle(): void
    {
        $lead = Lead::with(['project', 'campaign', 'opportunity'])->find($this->leadId);

        if (! $lead) {
            return;
        }

        // TODO Sprint 11: Call AI service to qualify the lead
        //
        // $aiService = app(LeadQualificationService::class);
        // $result    = $aiService->qualify($lead);
        //
        // $lead->fill([
        //     'qualification_summary' => $result->summary,
        //     'qualification_data'    => $result->data,
        //     'lead_score'            => $result->score,
        // ]);
        // $lead->recalculateLabel();
        // $lead->status       = 'qualified';
        // $lead->qualified_at = now();
        // $lead->save();
        //
        // LeadEvent::create([...]);
        // UsageRecord::increment($lead->organization_id, 'leads');
        //
        // Dispatch routing
        // \App\Services\Leads\LeadRoutingService::route($lead);

        Log::info("QualifyLead: lead #{$this->leadId} qualification queued.");
    }
}
