<?php

namespace App\Services\Sources;

use App\Models\Organization;
use App\Models\SourceScraper;
use App\Models\ScrapeJob;
use App\Models\SourceEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SourceManager - Orchestrates data source lifecycle
 * 
 * Responsibilities:
 * - Create and configure data sources
 * - Test connections
 * - Trigger scraper jobs
 * - Manage source status
 * - Collect statistics
 */
class SourceManager
{
    public function __construct(
        private ScraperService $scraper
    ) {}
    /**
     * Create a new data source
     */
    public function createSource(Organization $organization, array $data): SourceScraper
    {
        $source = SourceScraper::create([
            'organization_id' => $organization->id,
            'name' => $data['name'],
            'type' => $data['type'],
            'category' => $data['category'] ?? $this->inferCategory($data['type']),
            'base_url' => $data['base_url'] ?? null,
            'configuration' => $data['configuration'] ?? [],
            'credentials' => $data['credentials'] ?? null,
            'schedule' => $data['schedule'] ?? '0 */6 * * *', // Every 6 hours by default
            'status' => 'active',
        ]);

        // Calculate next run time
        $source->calculateNextRun();

        // Log event
        SourceEvent::logSourceCreated($source, auth()->user());

        Log::info("Data source created", [
            'source_id' => $source->id,
            'organization_id' => $organization->id,
            'type' => $source->type,
        ]);

        return $source;
    }

    /**
     * Update an existing data source
     */
    public function updateSource(SourceScraper $source, array $data): SourceScraper
    {
        $source->update(array_filter([
            'name' => $data['name'] ?? null,
            'base_url' => $data['base_url'] ?? null,
            'configuration' => $data['configuration'] ?? null,
            'credentials' => $data['credentials'] ?? null,
            'schedule' => $data['schedule'] ?? null,
        ], fn($v) => $v !== null));

        if (isset($data['schedule'])) {
            $source->calculateNextRun();
        }

        SourceEvent::create([
            'source_scraper_id' => $source->id,
            'organization_id' => $source->organization_id,
            'event_type' => 'configuration_changed',
            'severity' => 'info',
            'message' => "Source '{$source->name}' configuration was updated.",
            'user_id' => auth()->id(),
        ]);

        return $source->fresh();
    }

    /**
     * Test source connection
     */
    public function testSource(SourceScraper $source): array
    {
        try {
            $result = match ($source->type) {
                'rss' => $this->testRss($source),
                'api' => $this->testApi($source),
                'webhook' => $this->testWebhook($source),
                'tender' => $this->testTender($source),
                'scraper' => $this->testScraper($source),
                default => ['success' => false, 'message' => 'Unknown source type'],
            };

            if ($result['success']) {
                $source->update([
                    'status' => 'active',
                    'error_count' => 0,
                    'last_error' => null,
                ]);
            }

            return $result;

        } catch (\Throwable $e) {
            Log::error("Source test failed", [
                'source_id' => $source->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Run a source scrape job manually
     */
    public function runSource(SourceScraper $source): ScrapeJob
    {
        if (!$source->isActive()) {
            throw new \Exception("Cannot run inactive source");
        }

        // Execute the scrape
        $job = $this->scraper->scrape($source);

        Log::info("Source scrape completed", [
            'source_id' => $source->id,
            'job_id' => $job->id,
            'items_found' => $job->items_found,
            'items_new' => $job->items_new,
        ]);

        return $job;
    }

    /**
     * Activate a source
     */
    public function activateSource(SourceScraper $source): void
    {
        $source->activate();
        $source->calculateNextRun();
        
        SourceEvent::logSourceActivated($source, auth()->user());
    }

    /**
     * Pause a source
     */
    public function pauseSource(SourceScraper $source): void
    {
        $source->pause();
        
        SourceEvent::logSourcePaused($source, auth()->user());
    }

    /**
     * Delete a source
     */
    public function deleteSource(SourceScraper $source): void
    {
        SourceEvent::create([
            'source_scraper_id' => $source->id,
            'organization_id' => $source->organization_id,
            'event_type' => 'source_disabled',
            'severity' => 'warning',
            'message' => "Source '{$source->name}' was deleted.",
            'user_id' => auth()->id(),
        ]);

        $source->delete();

        Log::info("Source deleted", [
            'source_id' => $source->id,
        ]);
    }

    /**
     * Get source statistics
     */
    public function getSourceStats(SourceScraper $source): array
    {
        return [
            'total_jobs' => $source->scrapeJobs()->count(),
            'successful_jobs' => $source->scrapeJobs()->completed()->count(),
            'failed_jobs' => $source->scrapeJobs()->failed()->count(),
            'total_items' => $source->scrapedItems()->count(),
            'pending_items' => $source->scrapedItems()->pending()->count(),
            'converted_leads' => $source->scrapedItems()->whereNotNull('lead_id')->count(),
            'converted_opportunities' => $source->scrapedItems()->whereNotNull('opportunity_id')->count(),
            'last_run' => $source->last_run_at,
            'next_run' => $source->next_run_at,
            'success_rate' => $source->success_count > 0 
                ? round(($source->success_count / ($source->success_count + $source->error_count)) * 100, 1)
                : 0,
        ];
    }

    /**
     * Get available provider types
     */
    public function getAvailableProviders(): array
    {
        return [
            [
                'type' => 'rss',
                'label' => 'RSS Feed',
                'category' => 'news',
                'description' => 'Monitor news feeds, blogs, and industry publications',
                'icon' => '📰',
            ],
            [
                'type' => 'tender',
                'label' => 'Tender/RFP Portal',
                'category' => 'tender',
                'description' => 'Track public procurement and tender opportunities',
                'icon' => '📋',
            ],
            [
                'type' => 'api',
                'label' => 'API Integration',
                'category' => 'market_data',
                'description' => 'Connect to third-party APIs for market data',
                'icon' => '🔌',
            ],
            [
                'type' => 'scraper',
                'label' => 'Web Scraper',
                'category' => 'lead_capture',
                'description' => 'Extract data from websites and directories',
                'icon' => '🕷️',
            ],
            [
                'type' => 'webhook',
                'label' => 'Webhook Receiver',
                'category' => 'lead_capture',
                'description' => 'Receive data from external systems via webhooks',
                'icon' => '📥',
            ],
        ];
    }

    // ── Private Helper Methods ────────────────────────────────────────────────

    private function inferCategory(string $type): string
    {
        return match ($type) {
            'rss' => 'news',
            'tender' => 'tender',
            'api' => 'market_data',
            'scraper', 'webhook' => 'lead_capture',
            default => 'market_data',
        };
    }

    private function testRss(SourceScraper $source): array
    {
        if (!$source->base_url) {
            return ['success' => false, 'message' => 'No RSS feed URL configured'];
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'DemandLead/1.0'])
                ->get($source->base_url);

            if (!$response->successful()) {
                return ['success' => false, 'message' => "RSS feed returned HTTP {$response->status()}"];
            }

            $xml = simplexml_load_string($response->body());
            if (!$xml) {
                return ['success' => false, 'message' => 'Invalid RSS feed format'];
            }

            $itemCount = count($xml->channel->item ?? []);
            return [
                'success' => true,
                'message' => "RSS feed is valid. Found {$itemCount} items.",
            ];

        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function testApi(SourceScraper $source): array
    {
        if (!$source->base_url) {
            return ['success' => false, 'message' => 'No API URL configured'];
        }

        try {
            $headers = [];
            if ($source->credentials) {
                $creds = json_decode($source->credentials, true);
                if (isset($creds['api_key'])) {
                    $headers['Authorization'] = 'Bearer ' . $creds['api_key'];
                }
            }

            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->get($source->base_url);

            return [
                'success' => $response->successful(),
                'message' => $response->successful() 
                    ? 'API connection successful' 
                    : "API returned HTTP {$response->status()}",
            ];

        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function testWebhook(SourceScraper $source): array
    {
        // Webhook sources don't have a testable connection
        // They just receive data
        return [
            'success' => true,
            'message' => 'Webhook receiver is ready. Send POST requests to your webhook URL.',
        ];
    }

    private function testTender(SourceScraper $source): array
    {
        if (!$source->base_url) {
            return ['success' => false, 'message' => 'No tender portal URL configured'];
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'DemandLead/1.0'])
                ->get($source->base_url);

            return [
                'success' => $response->successful(),
                'message' => $response->successful()
                    ? 'Tender portal is accessible'
                    : "Tender portal returned HTTP {$response->status()}",
            ];

        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function testScraper(SourceScraper $source): array
    {
        if (!$source->base_url) {
            return ['success' => false, 'message' => 'No target URL configured'];
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'DemandLead/1.0'])
                ->get($source->base_url);

            return [
                'success' => $response->successful(),
                'message' => $response->successful()
                    ? 'Target website is accessible'
                    : "Website returned HTTP {$response->status()}",
            ];

        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
