<?php

namespace App\Jobs;

use App\Models\Keyword;
use App\Models\KeywordMeasurement;
use App\Services\Demand\BaselineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Process Demand Signal Job
 * 
 * Analyzes collected keyword measurements to:
 * - Calculate growth rates
 * - Determine trend states
 * - Update keyword trend metadata
 * - Trigger opportunity calculation if significant change detected
 * 
 * Queue: processing
 * Sprint 5
 */
class ProcessDemandSignal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 2;

    protected Keyword $keyword;

    public function __construct(Keyword $keyword)
    {
        $this->keyword = $keyword;
        $this->onQueue('processing');
    }

    public function handle(BaselineService $baselineService): void
    {
        Log::info('Processing demand signal', [
            'keyword_id' => $this->keyword->id,
            'term' => $this->keyword->term,
        ]);

        // Get measurements
        $measurements = KeywordMeasurement::where('keyword_id', $this->keyword->id)
            ->orderBy('measured_at', 'desc')
            ->limit(90)
            ->get();

        if ($measurements->isEmpty()) {
            Log::warning('No measurements found for keyword', [
                'keyword_id' => $this->keyword->id,
            ]);
            return;
        }

        // Calculate baselines
        $baselines = $baselineService->calculateBaselines($this->keyword);
        
        if (!$baselines['has_data']) {
            Log::info('Insufficient data for baseline calculation', [
                'keyword_id' => $this->keyword->id,
            ]);
            return;
        }

        // Determine trend state
        $trendState = $baselineService->determineTrendState($baselines);
        
        // Calculate growth rates
        $growth7d = $baselineService->calculateGrowthRate(
            $baselines['current_value'],
            $baselines['baseline_7d']
        );

        $growth30d = $baselineService->calculateGrowthRate(
            $baselines['current_value'],
            $baselines['baseline_30d']
        );

        $growth90d = $baselineService->calculateGrowthRate(
            $baselines['current_value'],
            $baselines['baseline_90d']
        );

        // Calculate volatility
        $volatility = $baselineService->calculateVolatility($measurements);

        // Update keyword with trend data
        $this->keyword->update([
            'trend_state' => $trendState,
            'baseline_7d' => $baselines['baseline_7d'],
            'baseline_30d' => $baselines['baseline_30d'],
            'baseline_90d' => $baselines['baseline_90d'],
            'current_interest' => $baselines['current_value'],
            'growth_rate_7d' => $growth7d,
            'growth_rate_30d' => $growth30d,
            'growth_rate_90d' => $growth90d,
            'volatility' => $volatility,
            'trend_updated_at' => now(),
        ]);

        // Update latest measurement with growth rate
        $latestMeasurement = $measurements->first();
        if ($latestMeasurement) {
            $latestMeasurement->update([
                'growth_rate' => $growth7d,
            ]);
        }

        Log::info('Successfully processed demand signal', [
            'keyword_id' => $this->keyword->id,
            'trend_state' => $trendState,
            'growth_7d' => $growth7d,
            'growth_30d' => $growth30d,
        ]);

        // Check if significant change detected
        if ($this->isSignificantChange($trendState, $growth7d, $growth30d)) {
            $this->triggerOpportunityCalculation();
        }
    }

    /**
     * Check if change is significant enough to trigger opportunity recalculation
     */
    protected function isSignificantChange(string $trendState, float $growth7d, float $growth30d): bool
    {
        // Trigger on spike or high growth
        if ($trendState === 'spike') {
            return true;
        }

        // Trigger on significant 7-day growth
        if (abs($growth7d) >= 50) {
            return true;
        }

        // Trigger on sustained 30-day growth
        if ($growth30d >= 30) {
            return true;
        }

        // Trigger on rising trend
        if ($trendState === 'rising') {
            return true;
        }

        return false;
    }

    /**
     * Trigger opportunity calculation for related opportunities
     */
    protected function triggerOpportunityCalculation(): void
    {
        // Find opportunities related to this keyword (through keyword_id)
        $opportunities = \App\Models\Opportunity::where('keyword_id', $this->keyword->id)
            ->where('status', '!=', 'dismissed')
            ->get();

        foreach ($opportunities as $opportunity) {
            // Dispatch CalculateOpportunity job
            \App\Jobs\CalculateOpportunity::dispatch($opportunity)
                ->onQueue('scoring');
            
            Log::info('Triggered opportunity calculation', [
                'opportunity_id' => $opportunity->id,
                'keyword_id' => $this->keyword->id,
            ]);
        }

        // If no opportunities exist, consider creating one
        if ($opportunities->isEmpty() && $this->keyword->trend_state === 'rising') {
            $this->considerCreatingOpportunity();
        }
    }

    /**
     * Consider creating a new opportunity
     */
    protected function considerCreatingOpportunity(): void
    {
        // Only create if keyword has sustained growth
        if ($this->keyword->growth_rate_30d < 30) {
            return;
        }

        // Check if opportunity already exists
        $exists = \App\Models\Opportunity::where('project_id', $this->keyword->project_id)
            ->where('keyword_id', $this->keyword->id)
            ->exists();

        if ($exists) {
            return;
        }

        // Create new opportunity
        $opportunity = \App\Models\Opportunity::create([
            'organization_id' => $this->keyword->project->organization_id,
            'project_id' => $this->keyword->project_id,
            'keyword_id' => $this->keyword->id,
            'title' => "Rising demand: {$this->keyword->term}",
            'description' => "Detected rising trend with {$this->keyword->growth_rate_30d}% growth over 30 days.",
            'status' => 'detected',
            'priority' => 'high',
            'opportunity_score' => 0, // Will be calculated by CalculateOpportunity
            'detected_at' => now(),
        ]);

        // Dispatch scoring job
        \App\Jobs\CalculateOpportunity::dispatch($opportunity)
            ->onQueue('scoring');

        Log::info('Created new opportunity for rising keyword', [
            'opportunity_id' => $opportunity->id,
            'keyword_id' => $this->keyword->id,
            'growth_30d' => $this->keyword->growth_rate_30d,
        ]);
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessDemandSignal job failed', [
            'keyword_id' => $this->keyword->id,
            'term' => $this->keyword->term,
            'error' => $exception->getMessage(),
        ]);
    }
}
