<?php

namespace App\Jobs;

use App\Models\Keyword;
use App\Models\KeywordMeasurement;
use App\Services\Providers\GoogleTrendsProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Collect Keyword Data Job
 * 
 * Runs daily to fetch keyword trend data from Google Trends
 * and store measurements for baseline and trend analysis.
 * 
 * Queue: ingestion
 * Sprint 4
 */
class CollectKeywordData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;
    public $backoff = [60, 120, 300]; // Retry after 1min, 2min, 5min

    protected Keyword $keyword;

    public function __construct(Keyword $keyword)
    {
        $this->keyword = $keyword;
        $this->onQueue('ingestion');
    }

    public function handle(GoogleTrendsProvider $trendsProvider): void
    {
        Log::info('Collecting data for keyword', [
            'keyword_id' => $this->keyword->id,
            'keyword' => $this->keyword->keyword,
        ]);

        // Skip if keyword is inactive
        if ($this->keyword->status !== 'active') {
            Log::info('Skipping inactive keyword', ['keyword_id' => $this->keyword->id]);
            return;
        }

        // Check if provider is configured
        if (!$trendsProvider->isConfigured()) {
            Log::warning('Google Trends provider not configured, using fallback synthetic data');
        }

        // Fetch data for each location
        foreach ($this->keyword->locations as $location) {
            $this->collectForLocation($trendsProvider, $location->country);
        }

        // Update last_measured_at
        $this->keyword->update([
            'last_measured_at' => now(),
        ]);

        Log::info('Successfully collected data', [
            'keyword_id' => $this->keyword->id,
            'locations' => $this->keyword->locations->count(),
        ]);
    }

    /**
     * Collect data for a specific location
     */
    protected function collectForLocation(GoogleTrendsProvider $trendsProvider, string $geo): void
    {
        try {
            // Fetch 3-month data (provides daily granularity)
            $data = $trendsProvider->getInterestOverTime(
                $this->keyword->keyword,
                $geo,
                'today 3-m'
            );

            if (empty($data) || empty($data['timeline'])) {
                Log::warning('No data returned from Google Trends', [
                    'keyword' => $this->keyword->keyword,
                    'geo' => $geo,
                ]);
                return;
            }

            // Store each data point
            foreach ($data['timeline'] as $point) {
                $this->storeMeasurement($geo, $point, $data);
            }

            // Store current snapshot with metadata
            $this->storeCurrentSnapshot($geo, $data);

        } catch (\Exception $e) {
            Log::error('Error collecting data for location', [
                'keyword_id' => $this->keyword->id,
                'geo' => $geo,
                'error' => $e->getMessage(),
            ]);
            
            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Store a measurement point
     */
    protected function storeMeasurement(string $geo, array $point, array $fullData): void
    {
        // Parse date (format varies)
        $date = $this->parseDate($point['date']);

        // Avoid duplicate measurements
        $existing = KeywordMeasurement::where('keyword_id', $this->keyword->id)
            ->where('geo', $geo)
            ->whereDate('date', $date)
            ->first();

        if ($existing) {
            // Update if value changed
            if ($existing->interest !== $point['value']) {
                $existing->update([
                    'interest' => $point['value'],
                    'volume' => $point['value'], // Normalized 0-100
                ]);
            }
            return;
        }

        // Create new measurement
        KeywordMeasurement::create([
            'keyword_id' => $this->keyword->id,
            'source' => 'google_trends',
            'date' => $date,
            'interest' => $point['value'],
            'volume' => $point['value'], // Google Trends returns normalized 0-100
            'growth' => 0, // Will be calculated by ProcessDemandSignal
            'geo' => $geo,
            'raw_data' => [
                'average' => $fullData['average_interest'] ?? 0,
                'max' => $fullData['max_interest'] ?? 0,
                'min' => $fullData['min_interest'] ?? 0,
            ],
        ]);
    }

    /**
     * Store current snapshot with metadata
     */
    protected function storeCurrentSnapshot(string $geo, array $data): void
    {
        // Store as today's measurement with full metadata
        KeywordMeasurement::updateOrCreate(
            [
                'keyword_id' => $this->keyword->id,
                'source' => 'google_trends',
                'geo' => $geo,
                'date' => now()->startOfDay(),
            ],
            [
                'interest' => $data['current_interest'],
                'volume' => $data['current_interest'],
                'growth' => 0, // Calculated later
                'raw_data' => [
                    'average_interest' => $data['average_interest'],
                    'max_interest' => $data['max_interest'],
                    'min_interest' => $data['min_interest'],
                    'data_points' => count($data['timeline']),
                    'collected_at' => now()->toISOString(),
                ],
            ]
        );
    }

    /**
     * Parse date string to Carbon instance
     */
    protected function parseDate(string $dateStr): \Carbon\Carbon
    {
        // Google Trends returns various formats:
        // "Dec 1, 2023" or "2023-12-01" or "Dec 1 – 7, 2023"
        
        try {
            // Try ISO format first
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                return \Carbon\Carbon::parse($dateStr);
            }

            // Try "Dec 1, 2023" format
            if (preg_match('/^([A-Za-z]+)\s+(\d+),\s+(\d{4})$/', $dateStr, $matches)) {
                return \Carbon\Carbon::parse($dateStr);
            }

            // Try "Dec 1 – 7, 2023" format (take start date)
            if (preg_match('/^([A-Za-z]+)\s+(\d+)\s+[–-]\s+\d+,\s+(\d{4})$/', $dateStr, $matches)) {
                return \Carbon\Carbon::parse("{$matches[1]} {$matches[2]}, {$matches[3]}");
            }

            // Fallback: today
            return now();

        } catch (\Exception $e) {
            Log::warning('Could not parse date, using today', [
                'date_string' => $dateStr,
                'error' => $e->getMessage(),
            ]);
            return now();
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CollectKeywordData job failed', [
            'keyword_id' => $this->keyword->id,
            'keyword' => $this->keyword->keyword,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Mark keyword as having issues
        $this->keyword->update([
            'status' => 'error',
            'notes' => 'Data collection failed: ' . $exception->getMessage(),
        ]);
    }
}
