<?php

namespace App\Services\Sources;

use App\Models\ScrapedItem;
use Carbon\Carbon;

/**
 * TenderScoringService - Specialized scoring for tender opportunities
 * 
 * Calculates opportunity and lead scores specific to government tenders,
 * considering factors like tender value, closing dates, organization match, etc.
 */
class TenderScoringService
{
    /**
     * Calculate tender opportunity score (0-100)
     */
    public function calculateOpportunityScore(ScrapedItem $tender): float
    {
        $score = 0;
        $metadata = $tender->metadata ?? [];

        // 1. Value Score (30 points) - Higher value = higher score
        $score += $this->calculateValueScore($metadata);

        // 2. Timing Score (25 points) - More time to prepare = higher score
        $score += $this->calculateTimingScore($metadata);

        // 3. Keyword Relevance (20 points) - How well it matches tracked keywords
        $score += min(20, $tender->relevance_score * 0.2);

        // 4. Organization Match (15 points) - Does it match target organizations?
        $score += $this->calculateOrganizationScore($tender, $metadata);

        // 5. Location Score (10 points) - Is it in target regions?
        $score += $this->calculateLocationScore($metadata);

        return min(100, round($score, 2));
    }

    /**
     * Calculate lead score for tender (0-100)
     */
    public function calculateLeadScore(ScrapedItem $tender): float
    {
        $score = 0;
        $metadata = $tender->metadata ?? [];

        // 1. Urgency (30 points) - Closing soon = higher urgency
        $score += $this->calculateUrgencyScore($metadata);

        // 2. Budget Fit (25 points) - Does value match company capacity?
        $score += $this->calculateBudgetFitScore($metadata);

        // 3. Eligibility Match (20 points) - Do we meet requirements?
        $score += $this->calculateEligibilityScore($metadata);

        // 4. Past Performance (15 points) - Have we won similar tenders?
        $score += $this->calculatePastPerformanceScore($tender);

        // 5. Competition Level (10 points) - Less competition = higher score
        $score += $this->calculateCompetitionScore($metadata);

        return min(100, round($score, 2));
    }

    /**
     * Calculate value-based score
     */
    protected function calculateValueScore(array $metadata): float
    {
        if (empty($metadata['value'])) {
            return 10; // Default score if no value
        }

        $value = $metadata['value'];
        $amount = $value['amount'] ?? 0;

        // Convert to KES if in USD (approximate rate)
        if (($value['currency'] ?? 'KES') === 'USD') {
            $amount *= 150; // ~150 KES per USD
        }

        // Score based on value ranges (in KES)
        if ($amount >= 100000000) { // 100M+
            return 30;
        } elseif ($amount >= 50000000) { // 50-100M
            return 27;
        } elseif ($amount >= 10000000) { // 10-50M
            return 24;
        } elseif ($amount >= 5000000) { // 5-10M
            return 20;
        } elseif ($amount >= 1000000) { // 1-5M
            return 15;
        } elseif ($amount >= 500000) { // 500K-1M
            return 10;
        } else { // < 500K
            return 5;
        }
    }

    /**
     * Calculate timing score based on closing date
     */
    protected function calculateTimingScore(array $metadata): float
    {
        if (empty($metadata['closing_date'])) {
            return 12; // Default score if no closing date
        }

        try {
            $closingDate = Carbon::parse($metadata['closing_date']);
            $daysUntilClose = now()->diffInDays($closingDate, false);

            if ($daysUntilClose < 0) {
                return 0; // Expired tender
            } elseif ($daysUntilClose >= 30) {
                return 25; // Plenty of time
            } elseif ($daysUntilClose >= 21) {
                return 22; // 3+ weeks
            } elseif ($daysUntilClose >= 14) {
                return 18; // 2-3 weeks
            } elseif ($daysUntilClose >= 7) {
                return 12; // 1-2 weeks
            } else {
                return 5; // Less than a week
            }
        } catch (\Throwable $e) {
            return 12;
        }
    }

    /**
     * Calculate organization score
     */
    protected function calculateOrganizationScore(ScrapedItem $tender, array $metadata): float
    {
        // Check if organization name contains keywords or matches tracked entities
        $organization = strtolower($metadata['organization'] ?? '');
        
        if (empty($organization)) {
            return 7; // Default score
        }

        // High-value organizations
        $highValueOrgs = ['treasury', 'ministry', 'national', 'parliament', 'judiciary'];
        foreach ($highValueOrgs as $keyword) {
            if (str_contains($organization, $keyword)) {
                return 15;
            }
        }

        // Medium-value organizations
        $mediumValueOrgs = ['county', 'authority', 'commission', 'corporation'];
        foreach ($mediumValueOrgs as $keyword) {
            if (str_contains($organization, $keyword)) {
                return 12;
            }
        }

        return 8; // Other organizations
    }

    /**
     * Calculate location score
     */
    protected function calculateLocationScore(array $metadata): float
    {
        $location = strtolower($metadata['location'] ?? '');
        
        if (empty($location)) {
            return 5; // Default score - assume national
        }

        // Major business hubs
        $majorHubs = ['nairobi', 'mombasa', 'kisumu', 'nakuru', 'eldoret'];
        foreach ($majorHubs as $hub) {
            if (str_contains($location, $hub)) {
                return 10;
            }
        }

        // National/multi-location
        if (str_contains($location, 'national') || str_contains($location, 'all counties')) {
            return 10;
        }

        return 6; // Other locations
    }

    /**
     * Calculate urgency score
     */
    protected function calculateUrgencyScore(array $metadata): float
    {
        if (empty($metadata['closing_date'])) {
            return 15;
        }

        try {
            $closingDate = Carbon::parse($metadata['closing_date']);
            $daysUntilClose = now()->diffInDays($closingDate, false);

            if ($daysUntilClose < 0) {
                return 0; // Expired
            } elseif ($daysUntilClose <= 3) {
                return 30; // Very urgent
            } elseif ($daysUntilClose <= 7) {
                return 25; // Urgent
            } elseif ($daysUntilClose <= 14) {
                return 18; // Moderate urgency
            } elseif ($daysUntilClose <= 21) {
                return 12; // Some time
            } else {
                return 5; // Plenty of time
            }
        } catch (\Throwable $e) {
            return 15;
        }
    }

    /**
     * Calculate budget fit score
     */
    protected function calculateBudgetFitScore(array $metadata): float
    {
        // This would ideally compare against company's capacity/past projects
        // For now, use value ranges that are typically manageable
        
        if (empty($metadata['value'])) {
            return 15; // Unknown budget = moderate score
        }

        $value = $metadata['value'];
        $amount = $value['amount'] ?? 0;

        // Convert to KES
        if (($value['currency'] ?? 'KES') === 'USD') {
            $amount *= 150;
        }

        // Score based on manageable ranges
        if ($amount > 0 && $amount <= 50000000) { // Up to 50M
            return 25; // Most manageable
        } elseif ($amount <= 100000000) { // 50-100M
            return 20;
        } elseif ($amount <= 500000000) { // 100-500M
            return 15;
        } else {
            return 10; // Very large projects
        }
    }

    /**
     * Calculate eligibility score
     */
    protected function calculateEligibilityScore(array $metadata): float
    {
        $eligibility = strtolower($metadata['eligibility'] ?? '');
        
        // If no eligibility specified, assume open
        if (empty($eligibility)) {
            return 15;
        }

        // Check for restrictive keywords
        $restrictive = ['prequalified', 'registered', 'certified', 'licensed'];
        foreach ($restrictive as $keyword) {
            if (str_contains($eligibility, $keyword)) {
                return 10; // May have barriers
            }
        }

        // Check for open keywords
        $open = ['all', 'any', 'open', 'invited'];
        foreach ($open as $keyword) {
            if (str_contains($eligibility, $keyword)) {
                return 20; // Open to all
            }
        }

        return 15; // Neutral
    }

    /**
     * Calculate past performance score
     */
    protected function calculatePastPerformanceScore(ScrapedItem $tender): float
    {
        // Would check against won/lost tender history
        // For now, return moderate score
        return 10;
    }

    /**
     * Calculate competition score
     */
    protected function calculateCompetitionScore(array $metadata): float
    {
        // Estimate competition based on tender characteristics
        $score = 5;

        // High-value tenders attract more competition
        if (!empty($metadata['value'])) {
            $amount = $metadata['value']['amount'] ?? 0;
            if ($amount < 5000000) { // Less than 5M
                $score += 5; // Less competition
            }
        }

        return $score;
    }

    /**
     * Determine tender quality level
     */
    public function getTenderQuality(float $opportunityScore, float $leadScore): string
    {
        $avgScore = ($opportunityScore + $leadScore) / 2;

        if ($avgScore >= 80) {
            return 'hot';
        } elseif ($avgScore >= 65) {
            return 'warm';
        } elseif ($avgScore >= 45) {
            return 'potential';
        } else {
            return 'low';
        }
    }

    /**
     * Get tender urgency level
     */
    public function getTenderUrgency(array $metadata): string
    {
        if (empty($metadata['closing_date'])) {
            return 'unknown';
        }

        try {
            $closingDate = Carbon::parse($metadata['closing_date']);
            $daysUntilClose = now()->diffInDays($closingDate, false);

            if ($daysUntilClose < 0) {
                return 'expired';
            } elseif ($daysUntilClose <= 3) {
                return 'critical';
            } elseif ($daysUntilClose <= 7) {
                return 'urgent';
            } elseif ($daysUntilClose <= 14) {
                return 'moderate';
            } else {
                return 'low';
            }
        } catch (\Throwable $e) {
            return 'unknown';
        }
    }

    /**
     * Process and score a tender item
     */
    public function scoreTender(ScrapedItem $tender): void
    {
        // Set intent to tender
        $tender->intent = 'tender';

        // Calculate scores
        $tender->opportunity_score = $this->calculateOpportunityScore($tender);
        $tender->lead_score = $this->calculateLeadScore($tender);

        // Add tender-specific metadata
        $metadata = $tender->metadata ?? [];
        $metadata['tender_quality'] = $this->getTenderQuality(
            $tender->opportunity_score,
            $tender->lead_score
        );
        $metadata['urgency'] = $this->getTenderUrgency($metadata);
        
        $tender->metadata = $metadata;
        $tender->save();
    }

    /**
     * Batch process tender scoring
     */
    public function batchScoreTenders(int $organizationId, int $limit = 50): int
    {
        $tenders = ScrapedItem::where('organization_id', $organizationId)
            ->where('intent', 'tender')
            ->where(function ($q) {
                $q->where('opportunity_score', 0)
                  ->orWhere('lead_score', 0);
            })
            ->limit($limit)
            ->get();

        foreach ($tenders as $tender) {
            $this->scoreTender($tender);
        }

        return $tenders->count();
    }

    /**
     * Get tender statistics
     */
    public function getTenderStatistics(int $organizationId, int $days = 30): array
    {
        $since = now()->subDays($days);

        $tenders = ScrapedItem::where('organization_id', $organizationId)
            ->where('intent', 'tender')
            ->where('created_at', '>=', $since)
            ->get();

        $stats = [
            'total_tenders' => $tenders->count(),
            'hot_tenders' => 0,
            'warm_tenders' => 0,
            'potential_tenders' => 0,
            'expired_tenders' => 0,
            'avg_opportunity_score' => 0,
            'avg_lead_score' => 0,
            'total_value' => 0,
            'by_organization' => [],
        ];

        foreach ($tenders as $tender) {
            $quality = $tender->metadata['tender_quality'] ?? 'low';
            $urgency = $tender->metadata['urgency'] ?? 'unknown';

            if ($quality === 'hot') $stats['hot_tenders']++;
            if ($quality === 'warm') $stats['warm_tenders']++;
            if ($quality === 'potential') $stats['potential_tenders']++;
            if ($urgency === 'expired') $stats['expired_tenders']++;

            $stats['avg_opportunity_score'] += $tender->opportunity_score;
            $stats['avg_lead_score'] += $tender->lead_score;

            // Sum tender values
            if (!empty($tender->metadata['value']['amount'])) {
                $stats['total_value'] += $tender->metadata['value']['amount'];
            }

            // Group by organization
            $org = $tender->metadata['organization'] ?? 'Unknown';
            $stats['by_organization'][$org] = ($stats['by_organization'][$org] ?? 0) + 1;
        }

        if ($tenders->count() > 0) {
            $stats['avg_opportunity_score'] = round($stats['avg_opportunity_score'] / $tenders->count(), 2);
            $stats['avg_lead_score'] = round($stats['avg_lead_score'] / $tenders->count(), 2);
        }

        // Sort organizations by count
        arsort($stats['by_organization']);
        $stats['by_organization'] = array_slice($stats['by_organization'], 0, 10, true);

        return $stats;
    }
}
