<?php

namespace App\Services\Sources;

use App\Models\SourceScraper;
use App\Models\ScrapeJob;
use App\Models\ScrapedItem;
use App\Models\SourceEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ScraperService - Handles the actual data collection from sources
 */
class ScraperService
{
    public function __construct(
        private ParserService $parser,
        private NormalizerService $normalizer
    ) {}

    /**
     * Execute a scrape job for a source
     */
    public function scrape(SourceScraper $source): ScrapeJob
    {
        // Create a new scrape job
        $job = ScrapeJob::create([
            'source_scraper_id' => $source->id,
            'organization_id' => $source->organization_id,
            'status' => 'pending',
        ]);

        $job->start();
        SourceEvent::logJobStarted($source, $job);

        try {
            // Collect raw data based on source type
            $rawData = match ($source->type) {
                'rss' => $this->scrapeRss($source),
                'api' => $this->scrapeApi($source),
                'tender' => $this->scrapeTender($source),
                'scraper' => $this->scrapeWebsite($source),
                'webhook' => [], // Webhooks don't actively scrape
                default => throw new \Exception("Unknown source type: {$source->type}"),
            };

            $job->incrementFound(count($rawData));

            // Process each item
            foreach ($rawData as $rawItem) {
                try {
                    $this->processItem($source, $job, $rawItem);
                } catch (\Throwable $e) {
                    $job->incrementFailed();
                    Log::warning("Failed to process item", [
                        'source_id' => $source->id,
                        'job_id' => $job->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $job->complete();
            SourceEvent::logJobCompleted($source, $job);

            return $job;

        } catch (\Throwable $e) {
            $job->fail($e->getMessage());
            SourceEvent::logJobFailed($source, $job, $e->getMessage());
            
            // Check if error threshold exceeded
            if ($source->error_count >= 5) {
                SourceEvent::logErrorThresholdExceeded($source);
            }

            throw $e;
        }
    }

    /**
     * Scrape RSS feed
     */
    private function scrapeRss(SourceScraper $source): array
    {
        if (!$source->base_url) {
            return [];
        }

        $response = Http::timeout(30)
            ->withHeaders(['User-Agent' => 'DemandLead/1.0'])
            ->get($source->base_url);

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch RSS feed: HTTP {$response->status()}");
        }

        $xml = simplexml_load_string($response->body());
        if (!$xml) {
            throw new \Exception("Invalid RSS feed format");
        }

        $items = [];
        foreach ($xml->channel->item ?? [] as $item) {
            $items[] = [
                'title' => (string) $item->title,
                'description' => (string) ($item->description ?? ''),
                'content' => (string) ($item->description ?? $item->content ?? ''),
                'url' => (string) $item->link,
                'published_at' => $item->pubDate ? date('Y-m-d H:i:s', strtotime((string) $item->pubDate)) : null,
                'external_id' => (string) ($item->guid ?? $item->link),
                'source_name' => (string) ($xml->channel->title ?? $source->name),
            ];
        }

        return $items;
    }

    /**
     * Scrape API endpoint
     */
    private function scrapeApi(SourceScraper $source): array
    {
        if (!$source->base_url) {
            return [];
        }

        $headers = ['User-Agent' => 'DemandLead/1.0'];
        
        // Add authentication if configured
        if ($source->credentials) {
            $creds = json_decode($source->credentials, true);
            if (isset($creds['api_key'])) {
                $headers['Authorization'] = 'Bearer ' . $creds['api_key'];
            }
        }

        $response = Http::timeout(30)
            ->withHeaders($headers)
            ->get($source->base_url);

        if (!$response->successful()) {
            throw new \Exception("API request failed: HTTP {$response->status()}");
        }

        $data = $response->json();
        
        // Extract items based on configuration
        $itemsPath = $source->configuration['items_path'] ?? 'data';
        $items = data_get($data, $itemsPath, []);

        return $this->normalizeApiItems($items, $source);
    }

    /**
     * Scrape tender portal
     */
    private function scrapeTender(SourceScraper $source): array
    {
        if (!$source->base_url) {
            return [];
        }

        $response = Http::timeout(30)
            ->withHeaders(['User-Agent' => 'DemandLead/1.0'])
            ->get($source->base_url);

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch tender portal: HTTP {$response->status()}");
        }

        // Parse HTML using configuration
        return $this->parser->parseTenders($response->body(), $source);
    }

    /**
     * Scrape website
     */
    private function scrapeWebsite(SourceScraper $source): array
    {
        if (!$source->base_url) {
            return [];
        }

        $response = Http::timeout(30)
            ->withHeaders(['User-Agent' => 'DemandLead/1.0'])
            ->get($source->base_url);

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch website: HTTP {$response->status()}");
        }

        // Parse HTML using configuration
        return $this->parser->parseWebsite($response->body(), $source);
    }

    /**
     * Process a single scraped item
     */
    private function processItem(SourceScraper $source, ScrapeJob $job, array $rawItem): void
    {
        // Generate content hash for deduplication
        $contentHash = ScrapedItem::generateContentHash(
            ($rawItem['title'] ?? '') . ($rawItem['content'] ?? '')
        );

        // Check if item already exists
        if (ScrapedItem::exists($contentHash)) {
            $job->incrementUpdated();
            return;
        }

        // Normalize the item
        $normalized = $this->normalizer->normalize($rawItem, $source);

        // Create the scraped item
        ScrapedItem::create([
            'source_scraper_id' => $source->id,
            'scrape_job_id' => $job->id,
            'organization_id' => $source->organization_id,
            'external_id' => $rawItem['external_id'] ?? null,
            'content_hash' => $contentHash,
            'title' => $normalized['title'],
            'description' => $normalized['description'],
            'content' => $normalized['content'],
            'url' => $normalized['url'],
            'source_name' => $normalized['source_name'],
            'published_at' => $normalized['published_at'],
            'intent' => $normalized['intent'],
            'relevance_score' => $normalized['relevance_score'],
            'lead_score' => $normalized['lead_score'],
            'opportunity_score' => $normalized['opportunity_score'],
            'matched_keywords' => $normalized['matched_keywords'],
            'extracted_entities' => $normalized['extracted_entities'],
            'metadata' => $normalized['metadata'],
            'processing_status' => 'pending',
        ]);

        $job->incrementNew();
    }

    /**
     * Normalize API items to standard format
     */
    private function normalizeApiItems(array $items, SourceScraper $source): array
    {
        $mapping = $source->configuration['field_mapping'] ?? [];
        
        return array_map(function ($item) use ($mapping, $source) {
            return [
                'title' => data_get($item, $mapping['title'] ?? 'title'),
                'description' => data_get($item, $mapping['description'] ?? 'description'),
                'content' => data_get($item, $mapping['content'] ?? 'content'),
                'url' => data_get($item, $mapping['url'] ?? 'url'),
                'published_at' => data_get($item, $mapping['published_at'] ?? 'published_at'),
                'external_id' => data_get($item, $mapping['id'] ?? 'id'),
                'source_name' => $source->name,
            ];
        }, $items);
    }
}
