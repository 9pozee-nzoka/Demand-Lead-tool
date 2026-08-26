<?php

namespace Tests\Unit;

use App\Services\Demand\BaselineService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BaselineServiceTest extends TestCase
{
    private BaselineService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BaselineService();
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    /**
     * Build a collection of fake measurements going back $days days.
     * $interestFn receives daysAgo (0=today) and returns the interest value.
     */
    private function makeMeasurements(int $days, callable $interestFn): Collection
    {
        return collect(range(0, $days - 1))->map(fn (int $daysAgo) => (object) [
            'date'     => now()->subDays($days - 1 - $daysAgo)->format('Y-m-d'),
            'interest' => $interestFn($daysAgo),
            'growth'   => null,
        ]);
    }

    // ── calculateBaselines() ──────────────────────────────────────────────────

    #[Test]
    public function it_returns_zero_baselines_for_empty_collection(): void
    {
        $baselines = $this->service->calculateBaselines(collect());

        $this->assertSame(0.0, $baselines['baseline_7']);
        $this->assertSame(0.0, $baselines['baseline_30']);
        $this->assertSame(0.0, $baselines['baseline_90']);
    }

    #[Test]
    public function it_calculates_7_day_baseline_as_average_of_last_7_days(): void
    {
        // 90 measurements: first 83 days interest=10, last 7 days interest=70
        $measurements = $this->makeMeasurements(90, fn (int $i) => $i < 83 ? 10 : 70);
        $baselines    = $this->service->calculateBaselines($measurements);

        // 7-day baseline should approximate 70 — allow wider delta because
        // the rolling window boundary lands on dates relative to now()
        $this->assertEqualsWithDelta(70.0, $baselines['baseline_7'], 10.0);
    }

    #[Test]
    public function it_calculates_30_day_baseline_as_average_of_last_30_days(): void
    {
        // 90 days: first 60 days=10, last 30 days=50
        $measurements = $this->makeMeasurements(90, fn (int $i) => $i < 60 ? 10 : 50);
        $baselines    = $this->service->calculateBaselines($measurements);

        $this->assertEqualsWithDelta(50.0, $baselines['baseline_30'], 5.0);
    }

    #[Test]
    public function it_calculates_90_day_baseline_covering_all_data(): void
    {
        // 90 days all at interest=40
        $measurements = $this->makeMeasurements(90, fn () => 40);
        $baselines    = $this->service->calculateBaselines($measurements);

        $this->assertEqualsWithDelta(40.0, $baselines['baseline_90'], 0.1);
    }

    #[Test]
    public function baseline_7_is_higher_than_30_when_recent_trend_is_rising(): void
    {
        // Flat for first 83 days, spike in last 7
        $measurements = $this->makeMeasurements(90, fn (int $i) => $i < 83 ? 20 : 80);
        $baselines    = $this->service->calculateBaselines($measurements);

        $this->assertGreaterThan($baselines['baseline_30'], $baselines['baseline_7']);
    }

    #[Test]
    public function all_baselines_are_rounded_to_two_decimal_places(): void
    {
        $measurements = $this->makeMeasurements(90, fn () => 33);
        $baselines    = $this->service->calculateBaselines($measurements);

        foreach (['baseline_7', 'baseline_30', 'baseline_90'] as $key) {
            $this->assertSame(
                round($baselines[$key], 2),
                $baselines[$key],
                "Expected {$key} to be rounded to 2 dp"
            );
        }
    }

    // ── currentInterest() ─────────────────────────────────────────────────────

    #[Test]
    public function it_returns_7_day_average_as_current_interest(): void
    {
        // Last 7 days all at 60, older days at 10
        $measurements = $this->makeMeasurements(30, fn (int $i) => $i < 23 ? 10 : 60);
        $current      = $this->service->currentInterest($measurements);

        $this->assertEqualsWithDelta(60.0, $current, 1.0);
    }

    #[Test]
    public function current_interest_returns_zero_for_empty_collection(): void
    {
        $this->assertSame(0.0, $this->service->currentInterest(collect()));
    }

    #[Test]
    public function current_interest_smooths_out_single_day_noise(): void
    {
        // 30 days at 40, last day is 100 (noise spike)
        $measurements = $this->makeMeasurements(30, fn (int $i) => $i === 29 ? 100 : 40);
        $current      = $this->service->currentInterest($measurements);

        // With 7-day average, the spike is diluted
        $this->assertLessThan(70.0, $current);
        $this->assertGreaterThan(40.0, $current);
    }

    // ── Growth formula ────────────────────────────────────────────────────────

    #[Test]
    public function growth_is_positive_when_current_exceeds_30_day_baseline(): void
    {
        // 90 days: first 83 days interest=30, last 7 days interest=60
        // baseline_30 will mix some of the 60s → ~51; current_7 ~60 → growth positive
        $measurements = $this->makeMeasurements(90, fn (int $i) => $i < 83 ? 30 : 60);
        $baselines    = $this->service->calculateBaselines($measurements);
        $current      = $this->service->currentInterest($measurements);

        $growth = (($current - $baselines['baseline_30']) / $baselines['baseline_30']) * 100;
        $this->assertGreaterThan(0.0, $growth);
    }

    #[Test]
    public function growth_is_negative_when_current_is_below_30_day_baseline(): void
    {
        // 90 days: first 83 days interest=60, last 7 days interest=20 (declining)
        $measurements = $this->makeMeasurements(90, fn (int $i) => $i < 83 ? 60 : 20);
        $baselines    = $this->service->calculateBaselines($measurements);
        $current      = $this->service->currentInterest($measurements);

        $growth = (($current - $baselines['baseline_30']) / $baselines['baseline_30']) * 100;
        $this->assertLessThan(0.0, $growth);
    }
}
