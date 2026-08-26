<?php

namespace Tests\Unit;

use App\Services\Demand\TrendDetectionService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class TrendDetectionServiceTest extends TestCase
{
    private TrendDetectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Boot the service with default config thresholds
        $this->service = new class extends TrendDetectionService {
            public function __construct()
            {
                // Bypass config() calls — inject known thresholds directly
                $this->risingThreshold    = 20.0;
                $this->rapidThreshold     = 50.0;
                $this->spikeThreshold     = 100.0;
                $this->decliningThreshold = -20.0;
            }
        };
    }

    // ── classify() ───────────────────────────────────────────────────────────

    #[Test]
    public function it_classifies_spike_when_growth_at_or_above_100(): void
    {
        $this->assertSame('spike', $this->service->classify(100.0));
        $this->assertSame('spike', $this->service->classify(150.0));
        $this->assertSame('spike', $this->service->classify(999.0));
    }

    #[Test]
    public function it_classifies_rapidly_rising_when_growth_between_50_and_99(): void
    {
        $this->assertSame('rapidly_rising', $this->service->classify(50.0));
        $this->assertSame('rapidly_rising', $this->service->classify(75.0));
        $this->assertSame('rapidly_rising', $this->service->classify(99.9));
    }

    #[Test]
    public function it_classifies_rising_when_growth_between_20_and_49(): void
    {
        $this->assertSame('rising', $this->service->classify(20.0));
        $this->assertSame('rising', $this->service->classify(35.0));
        $this->assertSame('rising', $this->service->classify(49.9));
    }

    #[Test]
    public function it_classifies_emerging_when_growth_between_5_and_19(): void
    {
        $this->assertSame('emerging', $this->service->classify(5.0));
        $this->assertSame('emerging', $this->service->classify(12.0));
        $this->assertSame('emerging', $this->service->classify(19.9));
    }

    #[Test]
    public function it_classifies_stable_when_growth_near_zero(): void
    {
        $this->assertSame('stable', $this->service->classify(0.0));
        $this->assertSame('stable', $this->service->classify(2.0));
        $this->assertSame('stable', $this->service->classify(-4.9));
    }

    #[Test]
    public function it_classifies_declining_when_growth_at_or_below_minus_20(): void
    {
        $this->assertSame('declining', $this->service->classify(-20.0));
        $this->assertSame('declining', $this->service->classify(-50.0));
        $this->assertSame('declining', $this->service->classify(-100.0));
    }

    #[Test]
    public function it_classifies_normal_when_growth_between_minus_5_and_5(): void
    {
        $this->assertSame('normal', $this->service->classify(-5.0));
        $this->assertSame('normal', $this->service->classify(-10.0));
    }

    #[Test]
    public function it_detects_peak_when_previously_rising_now_flat(): void
    {
        // Was rising last period (30%), now near-zero (2%) → peak
        $this->assertSame('peak', $this->service->classify(2.0, previousGrowth: 30.0));
    }

    #[Test]
    public function it_does_not_classify_peak_when_still_rising(): void
    {
        // Still rising — no peak
        $state = $this->service->classify(25.0, previousGrowth: 30.0);
        $this->assertSame('rising', $state);
    }

    // ── stateToScore() ────────────────────────────────────────────────────────

    #[Test]
    #[DataProvider('stateScoreProvider')]
    public function it_converts_state_to_score(string $state, float $growth, float $minExpected, float $maxExpected): void
    {
        $score = $this->service->stateToScore($state, $growth);

        $this->assertGreaterThanOrEqual($minExpected, $score,
            "Score for state '{$state}' should be >= {$minExpected}, got {$score}");
        $this->assertLessThanOrEqual($maxExpected, $score,
            "Score for state '{$state}' should be <= {$maxExpected}, got {$score}");
    }

    public static function stateScoreProvider(): array
    {
        return [
            'spike gives very high score'          => ['spike',          120.0, 90.0, 100.0],
            'rapidly_rising gives high score'      => ['rapidly_rising',  75.0, 80.0, 100.0],
            'rising gives medium-high score'       => ['rising',          25.0, 60.0, 85.0 ],
            'emerging gives medium score'          => ['emerging',        10.0, 40.0, 65.0 ],
            'stable gives low-medium score'        => ['stable',           2.0,  0.0, 45.0 ],
            'declining gives very low score'       => ['declining',       -30.0, 0.0, 20.0 ],
            'normal gives low score'               => ['normal',           -8.0, 0.0, 35.0 ],
        ];
    }

    #[Test]
    public function score_never_exceeds_100(): void
    {
        $this->assertLessThanOrEqual(100.0, $this->service->stateToScore('spike', 999.0));
    }

    #[Test]
    public function score_is_never_negative(): void
    {
        $this->assertGreaterThanOrEqual(0.0, $this->service->stateToScore('declining', -999.0));
    }
}
