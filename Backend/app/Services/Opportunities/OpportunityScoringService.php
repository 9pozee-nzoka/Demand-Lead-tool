<?php

namespace App\Services\Opportunities;

use App\Models\Opportunity;
use App\Models\Keyword;

/**
 * Opportunity Scoring Service
 * 
 * Implements the 0-100 scoring algorithm from the architecture:
 * - Growth score (30%)
 * - Intent score (25%)
 * - Geo score (15%)
 * - Volume score (15%)
 * - Competition score (10%)
 * - Historical score (5%)
 * 
 * Priority classification:
 * - 0-39: LOW
 * - 40-59: MODERATE
 * - 60-79: HIGH
 * - 80-100: VERY HIGH
 */
class OpportunityScoringService
{
    /**
     * Calculate the full opportunity score
     */
    public function calculateScore(Opportunity $opportunity): array
    {
        $keyword = $opportunity->keyword;
        $latestMeasurement = $keyword?->latestMeasurement;

        if (!$latestMeasurement) {
            return $this->defaultScores();
        }

        // Calculate individual component scores
        $growthScore = $this->calculateGrowthScore($latestMeasurement->growth_rate ?? 0);
        $intentScore = $this->calculateIntentScore($keyword->intent ?? 'informational');
        $geoScore = $this->calculateGeoScore($opportunity, $keyword);
        $volumeScore = $this->calculateVolumeScore($latestMeasurement->search_volume ?? 0);
        $competitionScore = $this->calculateCompetitionScore($latestMeasurement->competition ?? 0);
        $historicalScore = $this->calculateHistoricalScore($keyword);

        // Weighted total (must sum to 100%)
        $totalScore = (
            ($growthScore * 0.30) +
            ($intentScore * 0.25) +
            ($geoScore * 0.15) +
            ($volumeScore * 0.15) +
            ($competitionScore * 0.10) +
            ($historicalScore * 0.05)
        );

        $opportunityScore = round($totalScore);
        $priority = $this->calculatePriority($opportunityScore);

        return [
            'opportunity_score' => $opportunityScore,
            'priority' => $priority,
            'growth_score' => round($growthScore),
            'intent_score' => round($intentScore),
            'geo_score' => round($geoScore),
            'volume_score' => round($volumeScore),
            'competition_score' => round($competitionScore),
            'historical_score' => round($historicalScore),
        ];
    }

    /**
     * Calculate growth score (0-100) from growth rate percentage
     * 
     * Growth above 50% = 100 points
     * Growth 30-50% = 80-100 points
     * Growth 10-30% = 60-80 points
     * Growth 0-10% = 40-60 points
     * Negative growth = 0-40 points
     */
    protected function calculateGrowthScore(float $growthRate): float
    {
        if ($growthRate >= 50) {
            return 100;
        }

        if ($growthRate >= 30) {
            return 80 + (($growthRate - 30) / 20) * 20; // Scale 80-100
        }

        if ($growthRate >= 10) {
            return 60 + (($growthRate - 10) / 20) * 20; // Scale 60-80
        }

        if ($growthRate >= 0) {
            return 40 + ($growthRate / 10) * 20; // Scale 40-60
        }

        // Negative growth
        return max(0, 40 + ($growthRate / 50) * 40); // Scale 0-40
    }

    /**
     * Calculate intent score based on search intent classification
     * 
     * Transactional/Local = 100 (ready to buy)
     * Commercial = 80 (researching to buy)
     * Navigational = 60 (looking for specific brand)
     * Informational = 40 (just learning)
     */
    protected function calculateIntentScore(string $intent): float
    {
        return match (strtolower($intent)) {
            'transactional', 'local' => 100,
            'commercial' => 80,
            'navigational' => 60,
            'informational' => 40,
            default => 50,
        };
    }

    /**
     * Calculate geographic relevance score
     * 
     * Checks if keyword locations overlap with project target locations
     */
    protected function calculateGeoScore(Opportunity $opportunity, ?Keyword $keyword): float
    {
        if (!$keyword) {
            return 50;
        }

        $project = $opportunity->project;
        $keywordLocations = $keyword->locations->pluck('country_code')->toArray();
        
        // If project has default location, check if keyword targets it
        if ($project->default_location) {
            $projectCountry = strtoupper(substr($project->default_location, 0, 2));
            if (in_array($projectCountry, $keywordLocations)) {
                return 100; // Perfect match
            }
        }

        // If keyword has multiple locations, it's broader reach
        $locationCount = count($keywordLocations);
        
        if ($locationCount >= 5) {
            return 90; // Wide reach
        }
        
        if ($locationCount >= 3) {
            return 75; // Good reach
        }
        
        if ($locationCount >= 1) {
            return 60; // Some targeting
        }

        return 40; // No specific targeting
    }

    /**
     * Calculate volume score from search volume
     * 
     * Volume > 10,000 = 100 points
     * Volume 5,000-10,000 = 80-100 points
     * Volume 1,000-5,000 = 60-80 points
     * Volume 100-1,000 = 40-60 points
     * Volume < 100 = 0-40 points
     */
    protected function calculateVolumeScore(int $volume): float
    {
        if ($volume >= 10000) {
            return 100;
        }

        if ($volume >= 5000) {
            return 80 + (($volume - 5000) / 5000) * 20;
        }

        if ($volume >= 1000) {
            return 60 + (($volume - 1000) / 4000) * 20;
        }

        if ($volume >= 100) {
            return 40 + (($volume - 100) / 900) * 20;
        }

        return ($volume / 100) * 40;
    }

    /**
     * Calculate competition score (inverted - lower competition = higher score)
     * 
     * Competition is typically 0-1 scale from Google Ads
     * Low competition (< 0.3) = 100 points (great opportunity!)
     * Medium competition (0.3-0.7) = 60-80 points
     * High competition (> 0.7) = 20-60 points
     */
    protected function calculateCompetitionScore(float $competition): float
    {
        // Invert: less competition = better score
        if ($competition <= 0.3) {
            return 100 - ($competition / 0.3) * 20; // 80-100 points
        }

        if ($competition <= 0.7) {
            return 60 + ((0.7 - $competition) / 0.4) * 20; // 60-80 points
        }

        return max(20, 60 - (($competition - 0.7) / 0.3) * 40); // 20-60 points
    }

    /**
     * Calculate historical score based on keyword performance
     * 
     * Considers how long the keyword has been tracked and past conversion rates
     * New keywords get neutral score, proven performers get higher scores
     */
    protected function calculateHistoricalScore(?Keyword $keyword): float
    {
        if (!$keyword) {
            return 50;
        }

        // Check how many measurements we have
        $measurementCount = $keyword->measurements()->count();

        if ($measurementCount >= 90) {
            return 100; // Lots of historical data
        }

        if ($measurementCount >= 30) {
            return 80 + (($measurementCount - 30) / 60) * 20;
        }

        if ($measurementCount >= 7) {
            return 60 + (($measurementCount - 7) / 23) * 20;
        }

        // New keyword
        return 50 + ($measurementCount / 7) * 10; // 50-60 points
    }

    /**
     * Determine priority level from opportunity score
     */
    protected function calculatePriority(int $score): string
    {
        if ($score >= 80) {
            return 'very_high';
        }

        if ($score >= 60) {
            return 'high';
        }

        if ($score >= 40) {
            return 'moderate';
        }

        return 'low';
    }

    /**
     * Default scores for opportunities without data
     */
    protected function defaultScores(): array
    {
        return [
            'opportunity_score' => 50,
            'priority' => 'moderate',
            'growth_score' => 50,
            'intent_score' => 50,
            'geo_score' => 50,
            'volume_score' => 50,
            'competition_score' => 50,
            'historical_score' => 50,
        ];
    }

    /**
     * Update opportunity with calculated scores
     */
    public function updateOpportunityScores(Opportunity $opportunity): Opportunity
    {
        $scores = $this->calculateScore($opportunity);
        $opportunity->update($scores);
        return $opportunity->fresh();
    }

    /**
     * Batch recalculate scores for multiple opportunities
     */
    public function recalculateScores(string $status = 'open'): int
    {
        $count = 0;

        Opportunity::where('status', $status)
            ->with(['keyword.latestMeasurement', 'keyword.locations', 'project'])
            ->chunkById(100, function ($opportunities) use (&$count) {
                foreach ($opportunities as $opportunity) {
                    $this->updateOpportunityScores($opportunity);
                    $count++;
                }
            });

        return $count;
    }
}
