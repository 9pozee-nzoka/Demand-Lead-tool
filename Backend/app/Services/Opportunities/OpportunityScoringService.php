<?php

namespace App\Services\Opportunities;

use App\Models\Keyword;
use App\Models\KeywordLocation;
use App\Models\Opportunity;
use App\Services\Demand\BaselineService;
use App\Services\Demand\TrendDetectionService;

/**
 * Sprint 7 — Implements the weighted opportunity scoring formula
 * from the architecture spec (§39):
 *
 *   score = growth_score     × 0.30
 *         + intent_score     × 0.25
 *         + geo_score        × 0.15
 *         + volume_score     × 0.15
 *         + competition_score× 0.10
 *         + historical_score × 0.05
 *
 * Score bands:
 *   0–39   LOW
 *   40–59  MODERATE
 *   60–79  HIGH
 *   80–100 VERY HIGH
 */
class OpportunityScoringService
{
    public function __construct(
        private readonly BaselineService      $baseline,
        private readonly TrendDetectionService $trend,
    ) {}

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Score a keyword and upsert/update the Opportunity record.
     * Returns the Opportunity model.
     */
    public function scoreKeyword(Keyword $keyword, string $source = 'google_trends'): ?Opportunity
    {
        $analysis = $this->trend->analyse($keyword, $this->baseline, $source);

        // Component scores (0-100 each)
        $growthScore      = $this->trend->stateToScore($analysis['state'], $analysis['growth']);
        $intentScore      = $this->scoreIntent($keyword->intent);
        $geoScore         = $this->scoreGeo($keyword);
        $volumeScore      = $this->scoreVolume($analysis['current_interest']);
        $competitionScore = $this->scoreCompetition($keyword);
        $historicalScore  = $this->scoreHistorical($keyword);

        // Weighted formula
        $opportunityScore = round(
            ($growthScore      * 0.30) +
            ($intentScore      * 0.25) +
            ($geoScore         * 0.15) +
            ($volumeScore      * 0.15) +
            ($competitionScore * 0.10) +
            ($historicalScore  * 0.05),
            2
        );

        // Only create/update opportunities scoring above 20 to reduce noise
        if ($opportunityScore < 20) {
            return null;
        }

        $location = $keyword->locations->first();

        $opportunity = Opportunity::updateOrCreate(
            [
                'project_id' => $keyword->project_id,
                'keyword_id' => $keyword->id,
                'status'     => 'detected',
            ],
            [
                'location_id'       => $location?->id,
                'growth_score'      => $growthScore,
                'intent_score'      => $intentScore,
                'geo_score'         => $geoScore,
                'volume_score'      => $volumeScore,
                'competition_score' => $competitionScore,
                'historical_score'  => $historicalScore,
                'opportunity_score' => $opportunityScore,
                'trend_state'       => $analysis['state'],
                'detected_at'       => now(),
                'expires_at'        => now()->addDays(30),
            ]
        );

        return $opportunity;
    }

    // -------------------------------------------------------------------------
    // Component scorers
    // -------------------------------------------------------------------------

    /**
     * Intent score — prioritises commercial and transactional searches
     * as per spec §8 (Intent Detection).
     */
    public function scoreIntent(string $intent): float
    {
        return match ($intent) {
            'transactional' => 100,   // "solar installation Nairobi price" — buy now
            'local'         => 90,    // "solar installer near me" — local high-intent
            'commercial'    => 75,    // "best solar company Nairobi"
            'informational' => 20,    // "what is solar energy"
            default         => 30,    // unknown
        };
    }

    /**
     * Geo score — how specific and populated the tracked location is.
     * City-level is highest value; no location defaults to a country-level estimate.
     */
    public function scoreGeo(object $keyword): float
    {
        $locations = $keyword->locations;

        if ($locations->isEmpty()) {
            return 20; // no geo context — low confidence
        }

        $maxScore = 0;

        foreach ($locations as $location) {
            $score = match ($location->type) {
                'city'    => 80,
                'county'  => 70,
                'region'  => 55,
                'country' => 35,
                default   => 20,
            };

            // Bonus for having specific city data
            if ($location->city) {
                $score = min(100, $score + 15);
            }

            $maxScore = max($maxScore, $score);
        }

        return (float) $maxScore;
    }

    /**
     * Volume score — relative to the Google Trends interest value (0-100).
     * Maps the normalised interest to an opportunity score contribution.
     */
    public function scoreVolume(float $currentInterest): float
    {
        // Google Trends interest of 100 = peak popularity for that term.
        // We treat 60+ interest as high-volume territory.
        return match (true) {
            $currentInterest >= 80 => 100,
            $currentInterest >= 60 => 80,
            $currentInterest >= 40 => 60,
            $currentInterest >= 20 => 40,
            $currentInterest >= 10 => 25,
            default                => 10,
        };
    }

    /**
     * Competition score — based on available competition data.
     * Lower competition = higher score (inverse relationship).
     * Uses keyword_measurements.competition (0-1 from Google Ads CPC data).
     * Falls back to a neutral score when no competition data exists.
     */
    public function scoreCompetition(object $keyword): float
    {
        $latestMeasurement = $keyword->latestMeasurement();

        if (! $latestMeasurement || $latestMeasurement->competition === null) {
            return 50; // neutral — no competition data
        }

        $competition = (float) $latestMeasurement->competition; // 0.0 to 1.0

        // Invert: low competition = high score
        return round((1 - $competition) * 100, 2);
    }

    /**
     * Historical conversion score — how often has this keyword/project
     * produced leads or deals in the past?
     * Ranges from 0 (never converted) to 100 (strong history).
     */
    public function scoreHistorical(object $keyword): float
    {
        // Count leads linked to opportunities for this keyword
        $leadCount = \App\Models\Lead::whereHas('opportunity', fn ($q) => $q->where('keyword_id', $keyword->id))
            ->count();

        // Count won deals from those leads
        $wonCount = \App\Models\Deal::whereHas('lead.opportunity', fn ($q) => $q->where('keyword_id', $keyword->id))
            ->where('status', 'won')
            ->count();

        if ($leadCount === 0) {
            return 20; // no history — small positive to not penalise new keywords
        }

        $conversionRate = $wonCount / $leadCount;

        return match (true) {
            $conversionRate >= 0.3  => 100,
            $conversionRate >= 0.15 => 80,
            $conversionRate >= 0.05 => 60,
            $conversionRate > 0     => 40,
            default                 => 25,
        };
    }

    // -------------------------------------------------------------------------
    // Score label helper
    // -------------------------------------------------------------------------

    public static function label(float $score): string
    {
        return match (true) {
            $score >= 80 => 'VERY HIGH',
            $score >= 60 => 'HIGH',
            $score >= 40 => 'MODERATE',
            default      => 'LOW',
        };
    }
}
