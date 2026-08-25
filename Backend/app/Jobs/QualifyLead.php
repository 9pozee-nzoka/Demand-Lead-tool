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
 * Sprint 11 — Lead scoring using the formula from architecture spec §Lead:
 *
 *   score = intent      × 0.30
 *         + engagement  × 0.20
 *         + location_fit× 0.15
 *         + product_fit × 0.15
 *         + budget_fit  × 0.10
 *         + recency     × 0.10
 *
 * Bands:  90–100 HOT  |  70–89 WARM  |  40–69 POTENTIAL  |  0–39 LOW
 *
 * Sprint 8+: OpenAI qualification questions will enrich the input signals
 * before scoring. For now, rule-based scoring uses available lead data.
 *
 * Queue: lead_workflows
 */
class QualifyLead implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function backoff(): array { return [30, 120]; }

    public function __construct(public readonly int $leadId)
    {
        $this->onQueue('lead_workflows');
    }

    // -------------------------------------------------------------------------

    public function handle(): void
    {
        $lead = Lead::with(['opportunity.keyword', 'project'])->find($this->leadId);

        if (! $lead || $lead->status === 'qualified') {
            return;
        }

        // ── Component scores (0–100 each) ─────────────────────────────────────

        $intentScore      = $this->scoreIntent($lead);
        $engagementScore  = $this->scoreEngagement($lead);
        $locationScore    = $this->scoreLocation($lead);
        $productScore     = $this->scoreProduct($lead);
        $budgetScore      = $this->scoreBudget($lead);
        $recencyScore     = $this->scoreRecency($lead);

        // ── Weighted formula ──────────────────────────────────────────────────

        $score = round(
            ($intentScore     * 0.30) +
            ($engagementScore * 0.20) +
            ($locationScore   * 0.15) +
            ($productScore    * 0.15) +
            ($budgetScore     * 0.10) +
            ($recencyScore    * 0.10),
            2
        );

        $summary = $this->buildSummary($lead, [
            'intent'      => $intentScore,
            'engagement'  => $engagementScore,
            'location'    => $locationScore,
            'product'     => $productScore,
            'budget'      => $budgetScore,
            'recency'     => $recencyScore,
        ]);

        $lead->fill([
            'lead_score'            => $score,
            'qualification_summary' => $summary,
            'qualification_data'    => compact(
                'intentScore','engagementScore','locationScore',
                'productScore','budgetScore','recencyScore'
            ),
        ]);
        $lead->recalculateLabel();
        $lead->status       = 'qualified';
        $lead->qualified_at = now();
        $lead->save();

        LeadEvent::create([
            'lead_id'     => $lead->id,
            'type'        => 'lead_qualified',
            'metadata'    => ['score' => $score, 'label' => $lead->score_label, 'auto' => true],
            'occurred_at' => now(),
        ]);

        UsageRecord::track($lead->organization_id, 'leads');

        Log::info("QualifyLead: lead #{$this->leadId} scored {$score} ({$lead->score_label})");
    }

    // ── Component scorers ─────────────────────────────────────────────────────

    private function scoreIntent(Lead $lead): float
    {
        // Use keyword intent from linked opportunity, or lead's own intent
        $intent = $lead->opportunity?->keyword?->intent ?? $lead->intent ?? 'unknown';
        return match ($intent) {
            'transactional' => 100,
            'local'         => 90,
            'commercial'    => 70,
            'informational' => 25,
            default         => 40,
        };
    }

    private function scoreEngagement(Lead $lead): float
    {
        // Proxy: how much contact info provided indicates engagement
        $score = 20; // baseline
        if ($lead->phone)   $score += 30; // phone = high intent
        if ($lead->email)   $score += 25;
        if ($lead->company) $score += 15;
        if ($lead->name)    $score += 10;
        return min(100, $score);
    }

    private function scoreLocation(Lead $lead): float
    {
        // If the lead's location matches the opportunity's location, boost
        $oppLocation = $lead->opportunity?->location;
        if (! $oppLocation || ! $lead->location) {
            return 40; // neutral — no location data
        }

        $leadLoc = strtolower($lead->location);
        $city    = strtolower($oppLocation->city ?? '');
        $country = strtolower($oppLocation->country ?? '');

        if ($city && str_contains($leadLoc, $city)) return 100;
        if ($country && str_contains($leadLoc, $country)) return 65;
        return 30;
    }

    private function scoreProduct(Lead $lead): float
    {
        // If the lead came from an opportunity with a high score, the product fit is higher
        $oppScore = $lead->opportunity?->opportunity_score ?? 0;
        return match (true) {
            $oppScore >= 80 => 90,
            $oppScore >= 60 => 70,
            $oppScore >= 40 => 50,
            $oppScore > 0   => 35,
            default         => 30,
        };
    }

    private function scoreBudget(Lead $lead): float
    {
        // Without explicit budget data, score based on company presence (proxy for B2B)
        return $lead->company ? 60 : 35;
    }

    private function scoreRecency(Lead $lead): float
    {
        // How recently was the lead captured?
        $hoursAgo = now()->diffInHours($lead->created_at);
        return match (true) {
            $hoursAgo <= 1   => 100,
            $hoursAgo <= 6   => 85,
            $hoursAgo <= 24  => 70,
            $hoursAgo <= 72  => 50,
            $hoursAgo <= 168 => 35,
            default          => 20,
        };
    }

    private function buildSummary(Lead $lead, array $scores): string
    {
        $name    = $lead->name ?? 'This lead';
        $keyword = $lead->opportunity?->keyword?->keyword ?? 'the keyword';
        $highest = array_key_first(arsort($scores) ? $scores : $scores);

        return sprintf(
            '%s was captured from "%s" and scored automatically. ' .
            'Strongest signal: %s (%.0f/100). ' .
            'Review and assign to a sales rep for follow-up.',
            $name,
            $keyword,
            $highest,
            $scores[$highest],
        );
    }
}
