<?php

namespace App\Services\Sources\Providers;

use App\Models\ScrapeJob;
use App\Models\SourceScraper;
use App\Services\Sources\AbstractDataSource;
use App\Services\Sources\ParserService;
use App\Services\Sources\ScraperService;
use Illuminate\Support\Facades\Log;

/**
 * RssSource - RSS/Atom feed data source provider
 * 
 * Fetches and parses RSS/Atom feeds from Kenya news sources
 * and business publications.
 */
class RssSource extends AbstractDataSource
{
    protected ScraperService $scraper;
    protected ParserService $parser;

    /**
     * Default Kenya news feed URLs
     */
    protected array $kenyaFeeds = [
        'business_daily' => 'https://www.businessdailyafrica.com/bd/feed',
        'nation' => 'https://nation.africa/kenya/rss',
        'standard' => 'https://www.standardmedia.co.ke/rss',
        'capital_business' => 'https://www.capitalfm.co.ke/business/feed/',
        'the_star' => 'https://www.the-star.co.ke/feed',
    ];

    public function __construct(ScraperService $scraper, ParserService $parser)
    {
        $this->scraper = $scraper;
        $this->parser = $parser;
    }

    /**
     * Fetch data from RSS feed with filters applied
     */
    public function fetch(SourceScraper $source, ScrapeJob $job): array
    {
        $feedUrl = $source->base_url;

        if (!$feedUrl) {
            throw new \InvalidArgumentException('RSS feed URL is required');
        }

        Log::info("Fetching RSS feed", ['url' => $feedUrl]);

        try {
            // Fetch XML content
            $xml = $this->scraper->fetchXml($feedUrl);

            // Parse feed items
            $items = $this->parser->parseRssFeed($xml);

            Log::info("RSS feed parsed successfully", [
                'url' => $feedUrl,
                'items_count' => count($items),
            ]);

            // Apply filters
            $filteredItems = $this->applyFilters($items, $source);

            Log::info("RSS feed filtered", [
                'url' => $feedUrl,
                'original_count' => count($items),
                'filtered_count' => count($filteredItems),
            ]);

            return $filteredItems;
        } catch (\Throwable $e) {
            Log::error("Failed to fetch RSS feed", [
                'url' => $feedUrl,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Parse raw RSS item to standardized format
     */
    public function parse(array $rawItem, SourceScraper $source): array
    {
        // RSS items are already parsed by ParserService
        // Just add source-specific metadata
        return array_merge($rawItem, [
            'source_name' => $this->extractSourceName($source),
            'external_id' => $this->generateExternalId($rawItem),
        ]);
    }

    /**
     * Get the source type
     */
    public function getType(): string
    {
        return 'rss';
    }

    /**
     * Get the source category
     */
    public function getCategory(): string
    {
        return 'news';
    }

    /**
     * Get default configuration
     */
    public function getDefaultConfiguration(): array
    {
        return [
            'fetch_limit' => 50, // Maximum items to fetch per run
            'filter_keywords' => [], // Optional keywords to filter items
            'exclude_keywords' => [], // Keywords to exclude items
            'min_content_length' => 100, // Minimum content length in characters
            'extract_full_content' => false, // Whether to fetch and extract full article content
        ];
    }

    /**
     * Get required configuration keys
     */
    protected function getRequiredConfigKeys(): array
    {
        return []; // No required config for RSS, base_url is sufficient
    }

    /**
     * Extract source name from URL or configuration
     */
    protected function extractSourceName(SourceScraper $source): string
    {
        // Check if custom source name is configured
        if (isset($source->configuration['source_name'])) {
            return $source->configuration['source_name'];
        }

        // Try to extract from known Kenya feeds
        $url = $source->base_url;
        foreach ($this->kenyaFeeds as $name => $feedUrl) {
            if (str_contains($url, parse_url($feedUrl, PHP_URL_HOST))) {
                return ucwords(str_replace('_', ' ', $name));
            }
        }

        // Extract from domain
        $host = parse_url($url, PHP_URL_HOST);
        return ucwords(str_replace(['www.', '.com', '.co.ke', '-', '_'], [' ', '', '', ' ', ' '], $host));
    }

    /**
     * Generate external ID for RSS item
     */
    protected function generateExternalId(array $item): ?string
    {
        // Use URL as external ID if available
        if (!empty($item['url'])) {
            return md5($item['url']);
        }

        // Fallback to title + published date
        if (!empty($item['title']) && !empty($item['published_at'])) {
            return md5($item['title'] . $item['published_at']);
        }

        return null;
    }

    /**
     * Apply filters based on configuration
     */
    protected function applyFilters(array $items, SourceScraper $source): array
    {
        $config = $source->configuration ?? [];
        $filtered = [];

        foreach ($items as $item) {
            // Check minimum content length
            if (isset($config['min_content_length'])) {
                $contentLength = strlen($item['content'] ?? $item['description'] ?? '');
                if ($contentLength < $config['min_content_length']) {
                    continue;
                }
            }

            // Filter by keywords (if specified)
            if (!empty($config['filter_keywords'])) {
                $text = strtolower(($item['title'] ?? '') . ' ' . ($item['description'] ?? ''));
                $hasKeyword = false;
                foreach ($config['filter_keywords'] as $keyword) {
                    if (str_contains($text, strtolower($keyword))) {
                        $hasKeyword = true;
                        break;
                    }
                }
                if (!$hasKeyword) {
                    continue;
                }
            }

            // Exclude by keywords (if specified)
            if (!empty($config['exclude_keywords'])) {
                $text = strtolower(($item['title'] ?? '') . ' ' . ($item['description'] ?? ''));
                $hasExcluded = false;
                foreach ($config['exclude_keywords'] as $keyword) {
                    if (str_contains($text, strtolower($keyword))) {
                        $hasExcluded = true;
                        break;
                    }
                }
                if ($hasExcluded) {
                    continue;
                }
            }

            $filtered[] = $item;
        }

        // Apply fetch limit
        if (isset($config['fetch_limit'])) {
            $filtered = array_slice($filtered, 0, $config['fetch_limit']);
        }

        return $filtered;
    }

    /**
     * Extract full article content if configured
     */
    protected function extractFullContent(array $item): ?string
    {
        if (empty($item['url'])) {
            return null;
        }

        try {
            $html = $this->scraper->fetch($item['url']);
            $crawler = $this->parser->createCrawler($html);
            return $this->parser->extractArticleContent($crawler);
        } catch (\Throwable $e) {
            Log::warning("Failed to extract full content", [
                'url' => $item['url'],
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get available Kenya news feeds
     * 
     * Note: Some major publishers (Nation Media Group) have restricted public RSS access.
     * These feeds have been verified as working as of 2026.
     */
    public static function getKenyaNewsFeeds(): array
    {
        return [
            [
                'name' => 'Capital FM Business',
                'url' => 'https://www.capitalfm.co.ke/business/feed/',
                'category' => 'Business News',
                'description' => 'Kenya business news and analysis',
            ],
            [
                'name' => 'The Star Kenya',
                'url' => 'https://www.the-star.co.ke/feed',
                'category' => 'General News',
                'description' => 'Popular Kenyan daily newspaper',
            ],
            [
                'name' => 'The Star Business',
                'url' => 'https://www.the-star.co.ke/business/feed',
                'category' => 'Business News',
                'description' => 'Business section of The Star',
            ],
            [
                'name' => 'Kenya News Agency',
                'url' => 'https://www.kenyanews.go.ke/feed/',
                'category' => 'Government News',
                'description' => 'Official government news agency',
            ],
            [
                'name' => 'TechCabal',
                'url' => 'https://techcabal.com/feed/',
                'category' => 'Technology',
                'description' => 'African tech news and startup coverage',
            ],
            [
                'name' => 'Standard Media',
                'url' => 'https://www.standardmedia.co.ke/business/feed',
                'category' => 'Business News',
                'description' => 'The Standard business news',
            ],
            [
                'name' => 'Citizen Digital Business',
                'url' => 'https://www.citizen.digital/business/feed',
                'category' => 'Business News',
                'description' => 'Royal Media Services business news',
            ],
        ];
    }

    /**
     * Validate RSS feed URL
     */
    public function validateFeedUrl(string $url): array
    {
        try {
            $xml = $this->scraper->setTimeout(10)->fetchXml($url);
            $items = $this->parser->parseRssFeed($xml);

            return [
                'valid' => true,
                'items_found' => count($items),
                'feed_title' => (string) ($xml->channel->title ?? $xml->title ?? 'Unknown'),
            ];
        } catch (\Throwable $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
