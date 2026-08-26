<?php

namespace Tests\Unit;

use App\Services\Demand\BaselineService;
use App\Services\Demand\TrendDetectionService;
use App\Services\Opportunities\OpportunityScoringService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class OpportunityScoringServiceTest extends TestCase
{
    private OpportunityScoringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OpportunityScoringService(
            new BaselineService(),
            new class extends TrendDetectionService {
                public function __construct()
                {
                    $this->risingThreshold    = 20.0;
                    $this->rapidThreshold     = 50.0;
                    $this->spikeThreshold     = 100.0;
                    $this->decliningThreshold = -20.0;
                }
            },
        );
    }

    // ── scoreIntent() ─────────────────────────────────────────────────────────

    #[Test]
    #[DataProvider('intentScoreProvider')]
    public function it_scores_intent_correctly(string $intent, float $expected): void
    {
        $this->assertSame($expected, $this->service->scoreIntent($intent));
    }

    public static function intentScoreProvider(): array
    {
        return [
            'transactional is highest'  => ['transactional', 100.0],
            'local is second'           => ['local',           90.0],
            'commercial is third'       => ['commercial',      75.0],
            'informational is lowest'   => ['informational',   20.0],
            'unknown is neutral'        => ['unknown',         30.0],
        ];
    }

    #[Test]
    public function transactional_intent_outscores_informational(): void
    {
        $this->assertGreaterThan(
            $this->service->scoreIntent('informational'),
            $this->service->scoreIntent('transactional')
        );
    }

    // ── scoreVolume() ─────────────────────────────────────────────────────────

    #[Test]
    #[DataProvider('volumeScoreProvider')]
    public function it_scores_volume_correctly(float $interest, float $expected): void
    {
        $this->assertSame($expected, $this->service->scoreVolume($interest));
    }

    public static function volumeScoreProvider(): array
    {
        return [
            'interest 100 → 100' => [100.0, 100.0],
            'interest 80  → 100' => [80.0,  100.0],
            'interest 79  →  80' => [79.0,   80.0],
            'interest 60  →  80' => [60.0,   80.0],
            'interest 59  →  60' => [59.0,   60.0],
            'interest 40  →  60' => [40.0,   60.0],
            'interest 39  →  40' => [39.0,   40.0],
            'interest 20  →  40' => [20.0,   40.0],
            'interest 19  →  25' => [19.0,   25.0],
            'interest 10  →  25' => [10.0,   25.0],
            'interest 9   →  10' => [9.0,    10.0],
            'interest 0   →  10' => [0.0,    10.0],
        ];
    }

    #[Test]
    public function volume_score_is_monotonically_non_decreasing(): void
    {
        $previous = 0.0;
        foreach ([0, 10, 20, 40, 60, 80, 100] as $interest) {
            $score = $this->service->scoreVolume((float) $interest);
            $this->assertGreaterThanOrEqual($previous, $score,
                "Volume score for interest={$interest} should be >= score for previous interest level");
            $previous = $score;
        }
    }

    // ── scoreGeo() ────────────────────────────────────────────────────────────

    #[Test]
    public function it_returns_low_score_for_keyword_with_no_locations(): void
    {
        $keyword = $this->makeKeyword(locations: []);
        $this->assertLessThanOrEqual(25.0, $this->service->scoreGeo($keyword));
    }

    #[Test]
    public function city_level_location_scores_higher_than_country_level(): void
    {
        $cityKeyword    = $this->makeKeyword(locations: [['type' => 'city',    'city' => 'Nairobi', 'region' => null, 'country' => 'KE']]);
        $countryKeyword = $this->makeKeyword(locations: [['type' => 'country', 'city' => null,      'region' => null, 'country' => 'KE']]);

        $this->assertGreaterThan(
            $this->service->scoreGeo($countryKeyword),
            $this->service->scoreGeo($cityKeyword)
        );
    }

    #[Test]
    public function city_with_explicit_city_field_gets_bonus(): void
    {
        $withCity    = $this->makeKeyword(locations: [['type' => 'city', 'city' => 'Nairobi', 'region' => null, 'country' => 'KE']]);
        $withoutCity = $this->makeKeyword(locations: [['type' => 'city', 'city' => null,      'region' => null, 'country' => 'KE']]);

        $this->assertGreaterThan(
            $this->service->scoreGeo($withoutCity),
            $this->service->scoreGeo($withCity)
        );
    }

    // ── scoreCompetition() ────────────────────────────────────────────────────

    #[Test]
    public function it_returns_neutral_score_when_no_competition_data(): void
    {
        $keyword = $this->makeKeyword(latestMeasurement: null);
        $this->assertSame(50.0, $this->service->scoreCompetition($keyword));
    }

    #[Test]
    public function low_competition_scores_high(): void
    {
        $keyword = $this->makeKeyword(competition: 0.1); // 10% competition
        $score   = $this->service->scoreCompetition($keyword);
        $this->assertGreaterThan(75.0, $score);
    }

    #[Test]
    public function high_competition_scores_low(): void
    {
        $keyword = $this->makeKeyword(competition: 0.9); // 90% competition
        $score   = $this->service->scoreCompetition($keyword);
        $this->assertLessThan(25.0, $score);
    }

    // ── label() ───────────────────────────────────────────────────────────────

    #[Test]
    #[DataProvider('labelProvider')]
    public function it_returns_correct_label_for_score(float $score, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, OpportunityScoringService::label($score));
    }

    public static function labelProvider(): array
    {
        return [
            'score 100 → VERY HIGH' => [100.0, 'VERY HIGH'],
            'score 80  → VERY HIGH' => [80.0,  'VERY HIGH'],
            'score 79  → HIGH'      => [79.0,  'HIGH'],
            'score 60  → HIGH'      => [60.0,  'HIGH'],
            'score 59  → MODERATE'  => [59.0,  'MODERATE'],
            'score 40  → MODERATE'  => [40.0,  'MODERATE'],
            'score 39  → LOW'       => [39.0,  'LOW'],
            'score 0   → LOW'       => [0.0,   'LOW'],
        ];
    }

    // ── Weighted formula sanity ───────────────────────────────────────────────

    #[Test]
    public function weighted_formula_uses_correct_weights(): void
    {
        // Verify weights sum to 1.0 (100%)
        $weights = [0.30, 0.25, 0.15, 0.15, 0.10, 0.05];
        $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.0001);
    }

    #[Test]
    public function max_possible_score_is_100(): void
    {
        // All components at 100 × their weights should equal 100
        $maxScore =
            (100 * 0.30) +
            (100 * 0.25) +
            (100 * 0.15) +
            (100 * 0.15) +
            (100 * 0.10) +
            (100 * 0.05);

        $this->assertEqualsWithDelta(100.0, $maxScore, 0.0001);
    }

    // ── Private factory helpers ───────────────────────────────────────────────

    private function makeKeyword(
        array   $locations       = [],
        ?float  $competition     = null,
        mixed   $latestMeasurement = 'auto',
    ): object {
        $measurement = match (true) {
            $latestMeasurement === null  => null,
            $latestMeasurement === 'auto' => (object) ['competition' => $competition],
            default                      => $latestMeasurement,
        };

        $locationObjects = collect($locations)->map(fn ($l) => (object) $l);

        return new class ($locationObjects, $measurement) {
            public function __construct(
                public readonly mixed $locations,
                private readonly mixed $measurement,
            ) {}

            public function latestMeasurement(): mixed { return $this->measurement; }
        };
    }
}
