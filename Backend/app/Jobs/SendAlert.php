<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Models\AlertRule;
use App\Models\Opportunity;
use App\Services\Alerts\AlertDispatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sprint 9-ready — Evaluate alert rules for an opportunity and create
 * Alert records. Channel delivery (SMS/email/WhatsApp) is delegated to
 * AlertDispatchService so this job stays clean.
 *
 * Implements: cooldown checks, deduplication, rule matching.
 *
 * Queue : notifications
 * Dispatched by: CalculateOpportunity when score ≥ threshold
 */
class SendAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    public function backoff(): array
    {
        return [30, 120];
    }

    public function __construct(public readonly int $opportunityId)
    {
        $this->onQueue('notifications');
    }

    // -------------------------------------------------------------------------

    public function handle(AlertDispatchService $dispatcher): void
    {
        $opportunity = Opportunity::with([
            'keyword',
            'location',
            'project.organization',
        ])->find($this->opportunityId);

        if (! $opportunity) {
            return;
        }

        $orgId = $opportunity->project->organization_id;

        // Load active rules scoped to this org and optionally this project
        $rules = AlertRule::where('organization_id', $orgId)
            ->where('status', 'active')
            ->where(fn ($q) =>
                $q->whereNull('project_id')
                  ->orWhere('project_id', $opportunity->project_id)
            )
            ->get();

        if ($rules->isEmpty()) {
            // No rules configured — create a dashboard notification by default
            $this->createDashboardAlert($opportunity, $orgId);
            return;
        }

        $fired = 0;

        foreach ($rules as $rule) {
            if (! $rule->matches($opportunity)) {
                continue;
            }

            // Cooldown check — don't re-alert for the same opportunity within cooldown window
            if ($this->inCooldown($rule, $opportunity)) {
                Log::info("SendAlert: rule #{$rule->id} in cooldown for opportunity #{$this->opportunityId}");
                continue;
            }

            // Create alert records and dispatch to channels
            foreach ($rule->channels ?? ['dashboard'] as $channel) {
                $message = $this->buildMessage($opportunity);

                $alert = Alert::create([
                    'organization_id' => $orgId,
                    'opportunity_id'  => $opportunity->id,
                    'alert_rule_id'   => $rule->id,
                    'type'            => 'opportunity',
                    'channel'         => $channel,
                    'recipient'       => $this->resolveRecipient($rule, $channel),
                    'message'         => $message,
                    'payload'         => [
                        'keyword'           => $opportunity->keyword?->keyword,
                        'location'          => $opportunity->location?->city ?? $opportunity->location?->country,
                        'opportunity_score' => $opportunity->opportunity_score,
                        'trend_state'       => $opportunity->trend_state,
                        'label'             => \App\Services\Opportunities\OpportunityScoringService::label($opportunity->opportunity_score),
                    ],
                    'status'  => 'pending',
                    'sent_at' => null,
                ]);

                // Dispatch to the appropriate channel driver
                $dispatcher->dispatch($alert);
                $fired++;
            }
        }

        Log::info("SendAlert: opportunity #{$this->opportunityId} fired {$fired} alerts.");
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function inCooldown(AlertRule $rule, Opportunity $opportunity): bool
    {
        $cooldownHours = $rule->cooldown ?? 24;

        return Alert::where('organization_id', $rule->organization_id)
            ->where('opportunity_id', $opportunity->id)
            ->where('alert_rule_id', $rule->id)
            ->where('created_at', '>=', now()->subHours($cooldownHours))
            ->exists();
    }

    private function buildMessage(Opportunity $opportunity): string
    {
        $keyword  = $opportunity->keyword?->keyword ?? 'Unknown keyword';
        $location = $opportunity->location?->city ?? $opportunity->location?->country ?? 'Unknown location';
        $score    = number_format($opportunity->opportunity_score, 0);
        $state    = ucfirst(str_replace('_', ' ', $opportunity->trend_state));

        return "🚀 Demand Alert: \"{$keyword}\" in {$location} — Score {$score}/100 ({$state}). " .
               \App\Services\Opportunities\OpportunityScoringService::label($opportunity->opportunity_score) .
               " opportunity detected.";
    }

    private function resolveRecipient(AlertRule $rule, string $channel): ?string
    {
        $recipients = $rule->recipients ?? [];
        if (empty($recipients)) return null;

        return match ($channel) {
            'sms', 'whatsapp' => collect($recipients)->first(fn ($r) => str_starts_with($r, '+')),
            'email'           => collect($recipients)->first(fn ($r) => str_contains($r, '@')),
            default           => null,
        };
    }

    private function createDashboardAlert(Opportunity $opportunity, int $orgId): void
    {
        Alert::create([
            'organization_id' => $orgId,
            'opportunity_id'  => $opportunity->id,
            'type'            => 'opportunity',
            'channel'         => 'dashboard',
            'message'         => $this->buildMessage($opportunity),
            'payload'         => ['opportunity_score' => $opportunity->opportunity_score],
            'status'          => 'pending',
        ]);
    }
}
