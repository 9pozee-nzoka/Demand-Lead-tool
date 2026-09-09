<?php

namespace App\Services\Leads;

use App\Models\Lead;

/**
 * Lead Scoring Service
 *
 * Scores leads 0-100 based on:
 * - Intent (30%): commercial vs informational
 * - Engagement (20%): interactions, response time
 * - Location fit (15%): target market alignment
 * - Product fit (15%): service/product match
 * - Budget fit (10%): budget indicators
 * - Recency (10%): how recent the inquiry
 *
 * Scoring Bands:
 * - 90-100: HOT (immediate attention)
 * - 70-89:  WARM (priority follow-up)
 * - 40-69:  POTENTIAL (nurture sequence)
 * - 0-39:   LOW (deprioritize or auto-nurture)
 */
class LeadScoringService
{
    /**
     * Calculate comprehensive lead score (0-100)
     */
    public function calculateScore(Lead $lead): array
    {
        $intentScore = $this->calculateIntentScore($lead);
        $engagementScore = $this->calculateEngagementScore($lead);
        $locationScore = $this->calculateLocationFitScore($lead);
        $productScore = $this->calculateProductFitScore($lead);
        $budgetScore = $this->calculateBudgetFitScore($lead);
        $recencyScore = $this->calculateRecencyScore($lead);

        // Weighted formula
        $totalScore = round(
            ($intentScore * 0.30) +
            ($engagementScore * 0.20) +
            ($locationScore * 0.15) +
            ($productScore * 0.15) +
            ($budgetScore * 0.10) +
            ($recencyScore * 0.10)
        );

        $quality = $this->classifyQuality($totalScore);

        return [
            'total_score' => max(0, min(100, $totalScore)),
            'quality' => $quality,
            'breakdown' => [
                'intent' => $intentScore,
                'engagement' => $engagementScore,
                'location_fit' => $locationScore,
                'product_fit' => $productScore,
                'budget_fit' => $budgetScore,
                'recency' => $recencyScore,
            ],
            'explanation' => $this->generateExplanation($totalScore, $quality, [
                'intent' => $intentScore,
                'engagement' => $engagementScore,
                'location_fit' => $locationScore,
                'product_fit' => $productScore,
                'budget_fit' => $budgetScore,
                'recency' => $recencyScore,
            ]),
        ];
    }

    /**
     * Intent Score (30%): Commercial vs informational signals
     */
    protected function calculateIntentScore(Lead $lead): int
    {
        $score = 50; // baseline

        // Source channel weight
        $channelScores = [
            'whatsapp' => 85,     // Direct inquiry, high intent
            'landing_page' => 75, // Form submission, strong intent
            'phone' => 80,        // Direct call, very high intent
            'email' => 60,        // Email inquiry, moderate intent
            'chat' => 70,         // Live chat, good intent
            'referral' => 90,     // Referral, highest intent
            'organic' => 50,      // Organic search, varies
            'paid' => 65,         // Paid ad, moderate to good intent
            'social' => 45,       // Social media, exploratory
        ];

        if (isset($channelScores[$lead->source])) {
            $score = $channelScores[$lead->source];
        }

        // Message analysis (if message field exists)
        if ($lead->message) {
            $message = strtolower($lead->message);

            // High-intent keywords
            if (preg_match('/\b(buy|purchase|quote|price|cost|how much|interested|need|urgent|asap)\b/i', $message)) {
                $score += 15;
            }

            // Budget mentions
            if (preg_match('/\b(budget|afford|investment|spend)\b/i', $message)) {
                $score += 10;
            }

            // Timeline mentions
            if (preg_match('/\b(today|tomorrow|this week|immediately|soon)\b/i', $message)) {
                $score += 10;
            }

            // Low-intent keywords
            if (preg_match('/\b(just browsing|maybe later|just curious|information only)\b/i', $message)) {
                $score -= 20;
            }
        }

        return max(0, min(100, $score));
    }

    /**
     * Engagement Score (20%): Level of interaction
     */
    protected function calculateEngagementScore(Lead $lead): int
    {
        $score = 50; // baseline

        // Number of touches/interactions
        if ($lead->contact_count) {
            $score += min(30, $lead->contact_count * 10);
        }

        // Response time (if tracked)
        if ($lead->first_response_at && $lead->created_at) {
            $responseMinutes = $lead->created_at->diffInMinutes($lead->first_response_at);
            if ($responseMinutes < 15) {
                $score += 20; // Very fast response
            } elseif ($responseMinutes < 60) {
                $score += 10; // Fast response
            } elseif ($responseMinutes > 1440) { // 24 hours
                $score -= 10; // Slow response
            }
        }

        // Form completeness
        $completedFields = 0;
        $totalFields = 0;
        foreach (['name', 'email', 'phone', 'company', 'location', 'message'] as $field) {
            $totalFields++;
            if (!empty($lead->$field)) {
                $completedFields++;
            }
        }
        $completeness = ($completedFields / $totalFields) * 100;
        $score += ($completeness - 50) / 5; // Adjust based on completeness

        // Engagement status
        if ($lead->status === 'qualified') {
            $score += 20;
        } elseif ($lead->status === 'contacted') {
            $score += 10;
        } elseif ($lead->status === 'lost') {
            $score -= 30;
        }

        return max(0, min(100, $score));
    }

    /**
     * Location Fit Score (15%): Geographic alignment
     */
    protected function calculateLocationFitScore(Lead $lead): int
    {
        $score = 50; // baseline

        // If lead has location and opportunity has target locations
        if ($lead->location && $lead->opportunity) {
            // Check if lead location matches opportunity target
            $targetLocations = $lead->opportunity->keywords()
                ->with('locations')
                ->get()
                ->pluck('locations')
                ->flatten()
                ->pluck('location')
                ->unique()
                ->toArray();

            if (in_array($lead->location, $targetLocations)) {
                $score += 40; // Perfect location match
            } elseif ($this->isNearbyLocation($lead->location, $targetLocations)) {
                $score += 20; // Nearby location
            }
        }

        // Local business preference
        if ($lead->company && $lead->location) {
            $score += 10; // Local businesses score higher
        }

        return max(0, min(100, $score));
    }

    /**
     * Product Fit Score (15%): Service/product alignment
     */
    protected function calculateProductFitScore(Lead $lead): int
    {
        $score = 50; // baseline

        // Match lead keywords to opportunity keywords
        if ($lead->opportunity && $lead->message) {
            $opportunityKeywords = $lead->opportunity->keywords()
                ->pluck('keyword')
                ->map(fn($k) => strtolower($k))
                ->toArray();

            $message = strtolower($lead->message);
            $matchCount = 0;

            foreach ($opportunityKeywords as $keyword) {
                if (strpos($message, $keyword) !== false) {
                    $matchCount++;
                }
            }

            if ($matchCount > 0) {
                $score += min(40, $matchCount * 15); // More matches = better fit
            }
        }

        // Industry alignment (if tracked)
        if ($lead->company && $lead->opportunity) {
            $score += 10; // Business lead with context
        }

        return max(0, min(100, $score));
    }

    /**
     * Budget Fit Score (10%): Budget indicators
     */
    protected function calculateBudgetFitScore(Lead $lead): int
    {
        $score = 50; // baseline (unknown budget)

        if ($lead->budget_range) {
            // Parse budget range
            $budgetScores = [
                'low' => 40,
                'medium' => 70,
                'high' => 90,
                'enterprise' => 95,
            ];

            $score = $budgetScores[$lead->budget_range] ?? 50;
        }

        // Infer from message
        if ($lead->message) {
            $message = strtolower($lead->message);

            if (preg_match('/\b(premium|enterprise|best|top tier)\b/i', $message)) {
                $score += 20;
            } elseif (preg_match('/\b(cheap|affordable|budget|free)\b/i', $message)) {
                $score -= 15;
            }
        }

        // Company size indicator (if tracked)
        if ($lead->company_size) {
            $sizeScores = [
                '1-10' => 50,
                '11-50' => 65,
                '51-200' => 80,
                '201+' => 90,
            ];
            $score = max($score, $sizeScores[$lead->company_size] ?? 50);
        }

        return max(0, min(100, $score));
    }

    /**
     * Recency Score (10%): Time decay
     */
    protected function calculateRecencyScore(Lead $lead): int
    {
        $hoursOld = $lead->created_at->diffInHours(now());

        if ($hoursOld < 1) {
            return 100; // Very fresh
        } elseif ($hoursOld < 4) {
            return 90; // Fresh
        } elseif ($hoursOld < 24) {
            return 75; // Today
        } elseif ($hoursOld < 72) {
            return 60; // Within 3 days
        } elseif ($hoursOld < 168) {
            return 40; // Within a week
        } elseif ($hoursOld < 720) {
            return 20; // Within a month
        } else {
            return 10; // Old lead
        }
    }

    /**
     * Classify lead quality based on total score
     */
    protected function classifyQuality(int $score): string
    {
        if ($score >= 90) {
            return 'HOT';
        } elseif ($score >= 70) {
            return 'WARM';
        } elseif ($score >= 40) {
            return 'POTENTIAL';
        } else {
            return 'LOW';
        }
    }

    /**
     * Generate human-readable explanation
     */
    protected function generateExplanation(int $totalScore, string $quality, array $breakdown): string
    {
        $explanation = "This lead scores {$totalScore}/100 ({$quality} priority). ";

        // Highlight top factors
        arsort($breakdown);
        $topFactors = array_slice($breakdown, 0, 2, true);

        $factorNames = [
            'intent' => 'strong buying intent',
            'engagement' => 'high engagement level',
            'location_fit' => 'excellent location match',
            'product_fit' => 'strong product-service fit',
            'budget_fit' => 'favorable budget indicators',
            'recency' => 'very recent inquiry',
        ];

        $reasons = [];
        foreach ($topFactors as $factor => $score) {
            if ($score >= 70) {
                $reasons[] = $factorNames[$factor];
            }
        }

        if (!empty($reasons)) {
            $explanation .= "Key strengths: " . implode(', ', $reasons) . ". ";
        }

        // Action recommendation
        if ($quality === 'HOT') {
            $explanation .= "Recommended action: Contact immediately, within 15 minutes.";
        } elseif ($quality === 'WARM') {
            $explanation .= "Recommended action: Prioritize for follow-up within 1 hour.";
        } elseif ($quality === 'POTENTIAL') {
            $explanation .= "Recommended action: Add to nurture sequence, follow up within 24 hours.";
        } else {
            $explanation .= "Recommended action: Auto-nurture or deprioritize.";
        }

        return $explanation;
    }

    /**
     * Helper: Check if location is nearby target locations
     */
    protected function isNearbyLocation(string $location, array $targets): bool
    {
        // Simplified proximity check (in production, use geocoding)
        $location = strtolower($location);
        foreach ($targets as $target) {
            $target = strtolower($target);
            if (stripos($location, $target) !== false || stripos($target, $location) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Batch score multiple leads
     */
    public function scoreLeads(iterable $leads): array
    {
        $results = [];
        foreach ($leads as $lead) {
            $results[$lead->id] = $this->calculateScore($lead);
        }
        return $results;
    }

    /**
     * Update lead with calculated score
     */
    public function scoreAndUpdate(Lead $lead): Lead
    {
        $result = $this->calculateScore($lead);

        $lead->update([
            'lead_score' => $result['total_score'],
            'score_label' => strtolower($result['quality']),
            'score_breakdown' => $result['breakdown'],
            'score_explanation' => $result['explanation'],
            'scored_at' => now(),
        ]);

        return $lead->fresh();
    }
}
