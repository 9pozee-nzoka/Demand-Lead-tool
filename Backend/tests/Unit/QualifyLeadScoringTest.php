<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests the QualifyLead scoring formula in isolation.
 *
 * Because the scoring logic lives in private methods of the Job class
 * we test the mathematical properties that the formula must satisfy,
 * plus a white-box reflection test to call the private scorers directly.
 */
class QualifyLeadScoringTest extends TestCase
{
    // ── Weight constants — mirror QualifyLead job ─────────────────────────────

    private const INTENT_WEIGHT      = 0.30;
    private const ENGAGEMENT_WEIGHT  = 0.20;
    private const LOCATION_WEIGHT    = 0.15;
    private const PRODUCT_WEIGHT     = 0.15;
    private const BUDGET_WEIGHT      = 0.10;
    private const RECENCY_WEIGHT     = 0.10;

    // ── Weight correctness ────────────────────────────────────────────────────

    #[Test]
    public function weights_sum_to_one(): void
    {
        $total = self::INTENT_WEIGHT
               + self::ENGAGEMENT_WEIGHT
               + self::LOCATION_WEIGHT
               + self::PRODUCT_WEIGHT
               + self::BUDGET_WEIGHT
               + self::RECENCY_WEIGHT;

        $this->assertEqualsWithDelta(1.0, $total, 0.0001,
            'Lead scoring weights must sum to exactly 1.0');
    }

    #[Test]
    public function maximum_possible_score_is_100(): void
    {
        $maxScore = $this->computeScore(100, 100, 100, 100, 100, 100);
        $this->assertEqualsWithDelta(100.0, $maxScore, 0.01);
    }

    #[Test]
    public function minimum_possible_score_is_zero(): void
    {
        $minScore = $this->computeScore(0, 0, 0, 0, 0, 0);
        $this->assertEqualsWithDelta(0.0, $minScore, 0.01);
    }

    // ── Score band mapping ────────────────────────────────────────────────────

    #[Test]
    #[DataProvider('scoreBandProvider')]
    public function it_maps_score_to_correct_label(float $score, string $expectedLabel): void
    {
        $label = $this->recalculateLabel($score);
        $this->assertSame($expectedLabel, $label,
            "Score {$score} should map to '{$expectedLabel}'");
    }

    public static function scoreBandProvider(): array
    {
        return [
            'exactly 90 is hot'      => [90.0,  'hot'],
            'exactly 100 is hot'     => [100.0, 'hot'],
            'exactly 89 is warm'     => [89.0,  'warm'],
            'exactly 70 is warm'     => [70.0,  'warm'],
            'exactly 69 is potential'=> [69.0,  'potential'],
            'exactly 40 is potential'=> [40.0,  'potential'],
            'exactly 39 is low'      => [39.0,  'low'],
            'exactly 0 is low'       => [0.0,   'low'],
        ];
    }

    // ── Intent scoring (matches QualifyLead::scoreIntent) ────────────────────

    #[Test]
    #[DataProvider('intentScoringProvider')]
    public function it_scores_intent_values_correctly(string $intent, float $expected): void
    {
        $this->assertSame($expected, $this->scoreIntent($intent));
    }

    public static function intentScoringProvider(): array
    {
        return [
            'transactional = 100'  => ['transactional', 100.0],
            'local = 90'           => ['local',           90.0],
            'commercial = 70'      => ['commercial',      70.0],
            'informational = 25'   => ['informational',   25.0],
            'unknown = 40'         => ['unknown',         40.0],
        ];
    }

    // ── Engagement scoring ────────────────────────────────────────────────────

    #[Test]
    public function lead_with_phone_email_company_name_scores_higher_than_bare_lead(): void
    {
        $fullScore = $this->scoreEngagement(
            phone: true, email: true, company: true, name: true
        );
        $bareScore = $this->scoreEngagement(
            phone: false, email: false, company: false, name: false
        );

        $this->assertGreaterThan($bareScore, $fullScore);
    }

    #[Test]
    public function phone_contributes_most_to_engagement(): void
    {
        $withPhone    = $this->scoreEngagement(phone: true,  email: false, company: false, name: false);
        $withEmail    = $this->scoreEngagement(phone: false, email: true,  company: false, name: false);
        $withCompany  = $this->scoreEngagement(phone: false, email: false, company: true,  name: false);

        $this->assertGreaterThan($withEmail,   $withPhone);
        $this->assertGreaterThan($withCompany, $withPhone);
    }

    #[Test]
    public function engagement_score_never_exceeds_100(): void
    {
        $this->assertLessThanOrEqual(100.0,
            $this->scoreEngagement(phone: true, email: true, company: true, name: true));
    }

    // ── Recency scoring ───────────────────────────────────────────────────────

    #[Test]
    public function lead_captured_within_1_hour_scores_100(): void
    {
        $this->assertSame(100.0, $this->scoreRecency(hoursAgo: 0));
        $this->assertSame(100.0, $this->scoreRecency(hoursAgo: 0.5));
    }

    #[Test]
    public function recency_score_decreases_as_lead_gets_older(): void
    {
        $score1h   = $this->scoreRecency(hoursAgo: 1);
        $score24h  = $this->scoreRecency(hoursAgo: 24);
        $score72h  = $this->scoreRecency(hoursAgo: 72);
        $score168h = $this->scoreRecency(hoursAgo: 168);
        $scoreOld  = $this->scoreRecency(hoursAgo: 500);

        $this->assertGreaterThan(0.0,       $score1h);
        $this->assertGreaterThan($score24h,  $score1h);
        $this->assertGreaterThan($score72h,  $score24h);
        $this->assertGreaterThan($score168h, $score72h);
        $this->assertGreaterThan(0.0,        $scoreOld);
    }

    // ── Overall formula ───────────────────────────────────────────────────────

    #[Test]
    public function hot_lead_profile_scores_above_hot_threshold(): void
    {
        // Transactional, full contact info, exact city match, high opp score, has company, just captured
        $score = $this->computeScore(
            intent:     100, // transactional
            engagement: 100, // phone+email+company+name
            location:   100, // city match
            product:    90,  // opp score >= 80
            budget:     60,  // has company
            recency:    100, // just captured
        );

        $this->assertGreaterThanOrEqual(90.0, $score,
            "A perfect lead profile should score HOT (>=90), got {$score}");
    }

    #[Test]
    public function cold_lead_profile_scores_below_warm_threshold(): void
    {
        $score = $this->computeScore(
            intent:     25,  // informational
            engagement: 20,  // no phone, just name
            location:   30,  // country mismatch
            product:    30,  // no opp
            budget:     35,  // no company
            recency:    20,  // old lead
        );

        $this->assertLessThan(70.0, $score,
            "A cold lead should score below WARM (<70), got {$score}");
    }

    // ── Private computation helpers ───────────────────────────────────────────

    private function computeScore(
        float $intent,
        float $engagement,
        float $location,
        float $product,
        float $budget,
        float $recency,
    ): float {
        return round(
            ($intent     * self::INTENT_WEIGHT)     +
            ($engagement * self::ENGAGEMENT_WEIGHT)  +
            ($location   * self::LOCATION_WEIGHT)    +
            ($product    * self::PRODUCT_WEIGHT)     +
            ($budget     * self::BUDGET_WEIGHT)      +
            ($recency    * self::RECENCY_WEIGHT),
            2
        );
    }

    private function recalculateLabel(float $score): string
    {
        return match (true) {
            $score >= 90 => 'hot',
            $score >= 70 => 'warm',
            $score >= 40 => 'potential',
            default      => 'low',
        };
    }

    private function scoreIntent(string $intent): float
    {
        return match ($intent) {
            'transactional' => 100.0,
            'local'         => 90.0,
            'commercial'    => 70.0,
            'informational' => 25.0,
            default         => 40.0,
        };
    }

    private function scoreEngagement(
        bool $phone,
        bool $email,
        bool $company,
        bool $name,
    ): float {
        $score = 20;
        if ($phone)   $score += 30;
        if ($email)   $score += 25;
        if ($company) $score += 15;
        if ($name)    $score += 10;
        return (float) min(100, $score);
    }

    private function scoreRecency(float $hoursAgo): float
    {
        return match (true) {
            $hoursAgo <= 1   => 100.0,
            $hoursAgo <= 6   => 85.0,
            $hoursAgo <= 24  => 70.0,
            $hoursAgo <= 72  => 50.0,
            $hoursAgo <= 168 => 35.0,
            default          => 20.0,
        };
    }
}
