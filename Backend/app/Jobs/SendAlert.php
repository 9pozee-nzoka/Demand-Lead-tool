<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Models\AlertRule;
use App\Models\Opportunity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sprint 9: Evaluate alert rules for an opportunity and dispatch
 * notifications via SMS, email, WhatsApp, push, dashboard, or webhook.
 *
 * Implements: cooldown checks, deduplication, quiet hours, digest mode.
 *
 * Queued on: notifications
 * Dispatched by: CalculateOpportunity
 */
class SendAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    public function __construct(public readonly int $opportunityId)
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $opportunity = Opportunity::with(['project.organization', 'keyword'])->find($this->opportunityId);

        if (! $opportunity) {
            return;
        }

        $rules = AlertRule::where('organization_id', $opportunity->project->organization_id)
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('project_id')->orWhere('project_id', $opportunity->project_id))
            ->get();

        foreach ($rules as $rule) {
            if (! $rule->matches($opportunity)) {
                continue;
            }

            // TODO Sprint 9: check cooldown, quiet hours, deduplication
            // TODO: resolve channel driver (SmsDriver, EmailDriver, WhatsAppDriver, etc.)
            // TODO: create Alert record and dispatch to channel

            Log::info("SendAlert: rule #{$rule->id} matched opportunity #{$this->opportunityId}.");
        }
    }
}
