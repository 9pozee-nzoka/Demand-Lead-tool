<?php

namespace App\Jobs;

use App\Models\Opportunity;
use App\Services\Opportunities\OpportunityScoringService;
use App\Services\AI\AIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Calculate Opportunity Job
 * 
 * Scores opportunities using:
 * - Growth score (30%)
 * - Intent score (25%)
 * - Geo score (15%)
 * - Volume score (15%)
 * - Competition score (10%)
 * - Historical score (5%)
 * 
 * Generates AI explanation of score
 * 
 * Queue: scoring
 * Sprint 7
 */
class CalculateOpportunity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 2;

    protected Opportunity $opportunity;

    public function __construct(Opportunity $opportunity)
    {
        $this->opportunity = $opportunity;
        $this->onQueue('scoring');
    }

    public function handle(OpportunityScoringService $scoringService, AIService $aiService): void
    {
        Log::info('Calculating opportunity score', [
            'opportunity_id' => $this->opportunity->id,
            'title'          => $this->opportunity->title ?? 'N/A',
        ]);

        // Calculate score
        $result = $scoringService->calculateScore($this->opportunity);

        // Generate AI explanation if configured
        $explanation = '';
        if ($aiService->isConfigured()) {
            try {
                $explanation = $aiService->explainOpportunityScore($result);
            } catch (\Exception $e) {
                Log::warning('Failed to generate AI explanation', [
                    'opportunity_id' => $this->opportunity->id,
                    'error'          => $e->getMessage(),
                ]);
                $explanation = $this->generateFallbackExplanation($result);
            }
        } else {
            $explanation = $this->generateFallbackExplanation($result);
        }

        // Map scoring result to actual DB columns
        $this->opportunity->update([
            'opportunity_score' => $result['opportunity_score'],
            'growth_score'      => $result['growth_score'],
            'intent_score'      => $result['intent_score'],
            'geo_score'         => $result['geo_score'],
            'volume_score'      => $result['volume_score'],
            'competition_score' => $result['competition_score'],
            'historical_score'  => $result['historical_score'],
            'explanation'       => $explanation,
        ]);

        Log::info('Successfully calculated opportunity score', [
            'opportunity_id' => $this->opportunity->id,
            'score'          => $result['opportunity_score'],
        ]);

        // Advance status from detected → reviewed
        if ($this->opportunity->status === 'detected') {
            $this->opportunity->update(['status' => 'reviewed']);
        }
    }

    /**
     * Generate fallback explanation when AI is unavailable
     */
    protected function generateFallbackExplanation(array $result): string
    {
        $breakdown = $result['breakdown'];
        $score = $result['score'];
        $priority = $result['priority'];

        $parts = [];

        // Lead with priority
        $parts[] = "This is a {$priority} priority opportunity (score: {$score}/100).";

        // Find strongest factor
        $factors = [
            'growth_score' => 'demand growth',
            'intent_score' => 'buyer intent',
            'geo_score' => 'geographic targeting',
            'volume_score' => 'search volume',
        ];

        arsort($breakdown);
        $topFactor = array_key_first($breakdown);
        
        if (isset($factors[$topFactor])) {
            $parts[] = "Strong " . $factors[$topFactor] . " signals detected.";
        }

        // Add context based on priority
        if ($priority === 'very_high' || $priority === 'high') {
            $parts[] = "Immediate action recommended.";
        } elseif ($priority === 'moderate') {
            $parts[] = "Good timing to act within 1-2 weeks.";
        } else {
            $parts[] = "Monitor and consider action when conditions improve.";
        }

        return implode(' ', $parts);
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CalculateOpportunity job failed', [
            'opportunity_id' => $this->opportunity->id,
            'title' => $this->opportunity->title,
            'error' => $exception->getMessage(),
        ]);
    }
}
