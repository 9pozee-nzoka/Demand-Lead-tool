<?php

namespace App\Services\Sources;

use App\Contracts\DataSourceInterface;
use App\Models\ScrapeJob;
use App\Models\SourceEvent;
use App\Models\SourceScraper;
use Illuminate\Support\Facades\Log;

/**
 * Abstract base class for all data source providers
 * 
 * Provides common functionality like error handling, logging, and validation
 */
abstract class AbstractDataSource implements DataSourceInterface
{
    /**
     * Validate the source configuration
     */
    public function validateConfiguration(array $configuration): bool
    {
        $required = $this->getRequiredConfigKeys();
        
        foreach ($required as $key) {
            if (!isset($configuration[$key]) || empty($configuration[$key])) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Test the connection to the data source
     */
    public function testConnection(SourceScraper $source): array
    {
        try {
            // Attempt a small test fetch
            $testJob = new ScrapeJob([
                'source_scraper_id' => $source->id,
                'organization_id' => $source->organization_id,
                'status' => 'pending',
            ]);
            
            $items = $this->fetch($source, $testJob);
            
            return [
                'success' => true,
                'message' => 'Connection successful',
                'metadata' => [
                    'items_found' => count($items),
                    'tested_at' => now()->toIso8601String(),
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'metadata' => [
                    'error_type' => get_class($e),
                    'tested_at' => now()->toIso8601String(),
                ],
            ];
        }
    }

    /**
     * Normalize parsed data to ScrapedItem attributes
     */
    public function normalize(array $parsedData): array
    {
        return [
            'external_id' => $parsedData['external_id'] ?? null,
            'content_hash' => $this->generateContentHash($parsedData),
            'title' => $parsedData['title'] ?? 'Untitled',
            'description' => $parsedData['description'] ?? null,
            'content' => $parsedData['content'] ?? null,
            'url' => $parsedData['url'] ?? null,
            'source_name' => $parsedData['source_name'] ?? null,
            'published_at' => $parsedData['published_at'] ?? now(),
            'intent' => 'unknown',
            'relevance_score' => 0,
            'lead_score' => 0,
            'opportunity_score' => 0,
            'processing_status' => 'pending',
            'metadata' => $parsedData['metadata'] ?? [],
        ];
    }

    /**
     * Handle any cleanup or post-processing after a successful fetch
     */
    public function afterFetch(SourceScraper $source, ScrapeJob $job): void
    {
        // Default implementation - can be overridden by specific sources
        Log::info("Source fetch completed", [
            'source_id' => $source->id,
            'job_id' => $job->id,
            'items' => $job->items_new,
        ]);
    }

    /**
     * Handle errors that occur during fetch
     */
    public function handleError(SourceScraper $source, ScrapeJob $job, \Throwable $exception): void
    {
        Log::error("Source fetch failed", [
            'source_id' => $source->id,
            'job_id' => $job->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $source->recordError($exception->getMessage());
        $job->fail($exception->getMessage());

        SourceEvent::logJobFailed($job, $exception->getMessage());

        if ($source->error_count >= 5) {
            SourceEvent::logErrorThresholdExceeded($source);
        }
    }

    /**
     * Generate a content hash for deduplication
     */
    protected function generateContentHash(array $data): string
    {
        $content = ($data['title'] ?? '') . 
                   ($data['description'] ?? '') . 
                   ($data['url'] ?? '');
        
        return hash('sha256', $content);
    }

    /**
     * Get required configuration keys for this source type
     * 
     * @return array Array of required config key names
     */
    abstract protected function getRequiredConfigKeys(): array;

    /**
     * Clean and sanitize text content
     */
    protected function cleanText(?string $text): ?string
    {
        if (!$text) {
            return null;
        }

        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Trim
        $text = trim($text);
        
        return $text ?: null;
    }

    /**
     * Extract domain from URL
     */
    protected function extractDomain(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $parsed = parse_url($url);
        return $parsed['host'] ?? null;
    }

    /**
     * Check if URL is valid
     */
    protected function isValidUrl(?string $url): bool
    {
        if (!$url) {
            return false;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
