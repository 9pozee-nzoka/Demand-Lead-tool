<?php

namespace App\Services\Billing;

use App\Models\Keyword;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\UsageRecord;

/**
 * Sprint 16 — Reads current-period usage and compares against plan limits.
 *
 * Plan limits (stored in plans.limits JSON) keys:
 *   keywords        — max active keywords across all projects
 *   leads_per_month — max leads that can be captured per calendar month
 *   projects        — max active projects
 *   users           — max team members
 *   ai_requests     — max AI calls per month
 *   alerts          — max alerts sent per month
 *
 * -1 means unlimited (typical for Enterprise tier).
 */
class UsageMeteringService
{
    /**
     * Return a full usage snapshot for the current calendar month.
     *
     * @return array{
     *   period: string,
     *   plan: array,
     *   usage: array,
     *   limits: array,
     *   overage: array,
     *   at_limit: bool
     * }
     */
    public function snapshot(Organization $organization): array
    {
        $organization->loadMissing('plan');
        $plan    = $organization->plan;
        $limits  = $plan?->limits ?? [];
        $period  = now()->format('Y-m');

        // Actual current values
        $usage = [
            'keywords'        => Keyword::whereHas('project', fn ($q) =>
                                    $q->where('organization_id', $organization->id))
                                    ->where('status', 'active')->count(),
            'projects'        => $organization->projects()->where('status', 'active')->count(),
            'users'           => $organization->users()->where('status', 'active')->count(),
            'leads_per_month' => $this->periodMetric($organization->id, 'leads',       $period),
            'ai_requests'     => $this->periodMetric($organization->id, 'ai_requests', $period),
            'alerts'          => $this->periodMetric($organization->id, 'alerts',      $period),
        ];

        // Build per-metric limit/usage/percent breakdown
        $breakdown = [];
        $atLimit   = false;

        foreach ($usage as $metric => $current) {
            $limit      = isset($limits[$metric]) ? (int) $limits[$metric] : -1;
            $unlimited  = $limit === -1;
            $percent    = ($unlimited || $limit === 0) ? 0 : round(($current / $limit) * 100);
            $exceeded   = ! $unlimited && $current >= $limit;

            if ($exceeded) $atLimit = true;

            $breakdown[$metric] = [
                'current'   => $current,
                'limit'     => $limit,
                'unlimited' => $unlimited,
                'percent'   => min(100, $percent),
                'exceeded'  => $exceeded,
                'remaining' => $unlimited ? null : max(0, $limit - $current),
            ];
        }

        return [
            'period'   => $period,
            'plan'     => [
                'name'          => $plan?->name ?? 'Free',
                'slug'          => $plan?->slug ?? 'free',
                'monthly_price' => (float) ($plan?->monthly_price ?? 0),
                'features'      => $plan?->features ?? [],
            ],
            'usage'    => $breakdown,
            'at_limit' => $atLimit,
        ];
    }

    /**
     * Check whether an org is within a specific limit.
     * Returns true if allowed, false if at/over limit.
     */
    public function isAllowed(Organization $organization, string $metric): bool
    {
        $organization->loadMissing('plan');
        $limit = (int) ($organization->plan?->limits[$metric] ?? -1);

        if ($limit === -1) return true; // unlimited

        $snapshot = $this->snapshot($organization);
        return ! ($snapshot['usage'][$metric]['exceeded'] ?? false);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function periodMetric(int $orgId, string $metric, string $period): int
    {
        return (int) UsageRecord::where('organization_id', $orgId)
            ->where('metric', $metric)
            ->where('period', $period)
            ->value('quantity') ?? 0;
    }
}
