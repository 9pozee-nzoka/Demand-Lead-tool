<?php

namespace App\Services\Sources;

use App\Contracts\DataSourceInterface;
use App\Models\Organization;
use App\Models\ScrapeJob;
use App\Models\ScrapedItem;
use App\Models\SourceEvent;
use App\Models\SourceScraper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SourceManager - Orchestrates all data source operations
 * 
 * This service manages the lifecycle of data sources:
 * - Registration and configuration
 * - Scheduling and execution
 * - Error handling and recovery
 * - Performance monitoring
 */
class SourceManager
{
    protected array $providers = [];

    /**
     * Register a data source provider
     */
    public function registerProvider(string $type, string $providerClass): void
    {
        if (!class_exists($providerClass)) {
            throw new \InvalidArgumentException("Provider class {$providerClass} does not exist");
        }

        if (!in_array(DataSourceInterface::class, class_implements($providerClass))) {
            throw new \InvalidArgumentException("Provider must implement DataSourceInterface");
        }

        $this->providers[$type] = $providerClass;
    }

    /**
     * Get a provider instance for a source type
     */
    public function getProvider(string $type): DataSourceInterface
    {
        if (!isset($this->providers[$type])) {
            throw new \InvalidArgumentException("No provider registered for type: {$type}");
        }

        return app($this->providers[$type]);
    }

    /**
     * Create a new data source
     */
    public function createSource(Organization $organization, array $data): SourceScraper
    {
        $provider = $this->getProvider($data['type']);

        // Validate configuration
        if (!$provider->validateConfiguration($data['configuration'] ?? [])) {
            throw new \InvalidArgumentException('Invalid source configuration');
        }

        DB::beginTransaction();
        try {
            $source = SourceScraper::create([
                'organization_id' => $organization->id,
                'name' => $data['name'],
                'type' => $data['type'],
                'category' => $provider->getCategory(),
                'base_url' => $data['base_url'] ?? null,
                'configuration' => $data['configuration'] ?? [],
                'credentials' => $data['credentials'] ?? null,
                'schedule' => $data['schedule'] ?? '0 */6 * * *',
                'status' => 'active',
            ]);

            $source->calculateNextRun();

            SourceEvent::logSourceCreated($source, auth()->user());

            DB::commit();
            return $source;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Test connection to a data source
     */
    public function testSource(SourceScraper $source): array
    {
        $provider = $this->getProvider($source->type);
        return $provider->testConnection($source);
    }

    /**
     * Execute a single source scrape job
     */
    public function runSource(SourceScraper $source): ScrapeJob
    {
        if (!$source->isActive()) {
            throw new \RuntimeException("Source is not active");
        }

        $provider = $this->getProvider($source->type);

        // Create job record
        $job = ScrapeJob::create([
            'source_scraper_id' => $source->id,
            'organization_id' => $source->organization_id,
            'status' => 'pending',
        ]);

        SourceEvent::logJobStarted($job);
        $job->start();

        try {
            // Fetch raw data
            $rawItems = $provider->fetch($source, $job);
            
            $found = count($rawItems);
            $new = 0;
            $updated = 0;
            $failed = 0;

            // Process each item
            foreach ($rawItems as $rawItem) {
                try {
                    // Parse and normalize
                    $parsed = $provider->parse($rawItem, $source);
                    $normalized = $provider->normalize($parsed);

                    // Check for duplicates
                    $existing = ScrapedItem::findByContentHash($normalized['content_hash']);

                    if ($existing) {
                        // Update existing item
                        $existing->update($normalized);
                        $updated++;
                    } else {
                        // Create new item
                        ScrapedItem::create(array_merge($normalized, [
                            'source_scraper_id' => $source->id,
                            'scrape_job_id' => $job->id,
                            'organization_id' => $source->organization_id,
                        ]));
                        $new++;
                    }
                } catch (\Throwable $e) {
                    Log::error("Failed to process scraped item", [
                        'source_id' => $source->id,
                        'error' => $e->getMessage(),
                    ]);
                    $failed++;
                }
            }

            // Mark job as complete
            $job->complete($found, $new, $updated, $failed);
            $source->recordSuccess();
            $source->calculateNextRun();

            SourceEvent::logJobCompleted($job);
            $provider->afterFetch($source, $job);

            // Dispatch intent classification for new items (starts AI intelligence pipeline)
            if ($new > 0) {
                // Get newly created items for this job
                $newItems = ScrapedItem::where('scrape_job_id', $job->id)
                    ->where('processing_status', 'pending')
                    ->get();

                foreach ($newItems as $item) {
                    \App\Jobs\ProcessIntentClassification::dispatch($item);
                }
            }

            return $job;
        } catch (\Throwable $e) {
            $provider->handleError($source, $job, $e);
            throw $e;
        }
    }

    /**
     * Run all sources that are due for execution
     */
    public function runScheduledSources(): array
    {
        $sources = SourceScraper::dueForRun()->get();
        $results = [];

        foreach ($sources as $source) {
            try {
                $job = $this->runSource($source);
                $results[] = [
                    'source_id' => $source->id,
                    'success' => true,
                    'job_id' => $job->id,
                    'items_new' => $job->items_new,
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'source_id' => $source->id,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Update source configuration
     */
    public function updateSource(SourceScraper $source, array $data): SourceScraper
    {
        $provider = $this->getProvider($source->type);

        // If configuration is being updated, validate it
        if (isset($data['configuration'])) {
            if (!$provider->validateConfiguration($data['configuration'])) {
                throw new \InvalidArgumentException('Invalid source configuration');
            }
        }

        DB::beginTransaction();
        try {
            $source->update($data);

            if (isset($data['configuration']) || isset($data['credentials'])) {
                SourceEvent::create([
                    'source_scraper_id' => $source->id,
                    'organization_id' => $source->organization_id,
                    'event_type' => 'configuration_changed',
                    'severity' => 'info',
                    'message' => "Source configuration updated",
                    'user_id' => auth()->id(),
                ]);
            }

            DB::commit();
            return $source->fresh();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Pause a source
     */
    public function pauseSource(SourceScraper $source): void
    {
        $source->pause();
        
        SourceEvent::create([
            'source_scraper_id' => $source->id,
            'organization_id' => $source->organization_id,
            'event_type' => 'source_paused',
            'severity' => 'info',
            'message' => "Source paused",
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Activate a source
     */
    public function activateSource(SourceScraper $source): void
    {
        $source->activate();
        $source->calculateNextRun();
        
        SourceEvent::create([
            'source_scraper_id' => $source->id,
            'organization_id' => $source->organization_id,
            'event_type' => 'source_activated',
            'severity' => 'info',
            'message' => "Source activated",
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Disable a source
     */
    public function disableSource(SourceScraper $source): void
    {
        $source->disable();
        
        SourceEvent::create([
            'source_scraper_id' => $source->id,
            'organization_id' => $source->organization_id,
            'event_type' => 'source_disabled',
            'severity' => 'warning',
            'message' => "Source disabled",
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Delete a source and all its data
     */
    public function deleteSource(SourceScraper $source): void
    {
        DB::beginTransaction();
        try {
            // Soft delete will cascade due to relationships
            $source->delete();
            
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get source statistics
     */
    public function getSourceStats(SourceScraper $source): array
    {
        $jobs = $source->scrapeJobs();
        
        return [
            'total_jobs' => $jobs->count(),
            'successful_jobs' => $jobs->completed()->count(),
            'failed_jobs' => $jobs->failed()->count(),
            'total_items' => $source->scrapedItems()->count(),
            'pending_items' => $source->scrapedItems()->pending()->count(),
            'converted_items' => $source->scrapedItems()->converted()->count(),
            'last_run' => $source->last_run_at,
            'next_run' => $source->next_run_at,
            'error_count' => $source->error_count,
            'success_count' => $source->success_count,
            'uptime_percentage' => $this->calculateUptime($source),
        ];
    }

    /**
     * Calculate source uptime percentage
     */
    protected function calculateUptime(SourceScraper $source): float
    {
        $totalJobs = $source->scrapeJobs()->count();
        
        if ($totalJobs === 0) {
            return 100.0;
        }

        $successfulJobs = $source->scrapeJobs()->completed()->count();
        return round(($successfulJobs / $totalJobs) * 100, 2);
    }

    /**
     * Get all registered provider types
     */
    public function getAvailableProviders(): array
    {
        return array_keys($this->providers);
    }

    /**
     * Get provider metadata
     */
    public function getProviderMetadata(string $type): array
    {
        $provider = $this->getProvider($type);
        
        return [
            'type' => $provider->getType(),
            'category' => $provider->getCategory(),
            'default_configuration' => $provider->getDefaultConfiguration(),
        ];
    }
}
