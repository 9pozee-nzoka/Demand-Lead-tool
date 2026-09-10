<?php

namespace App\Services\Sources\Providers;

use App\Models\ScrapeJob;
use App\Models\SourceScraper;
use App\Services\Sources\AbstractDataSource;
use App\Services\Sources\ParserService;
use App\Services\Sources\ScraperService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

/**
 * TenderSource - Kenya Government Tenders data source provider
 * 
 * Scrapes tender opportunities from Kenya's government procurement portal
 * and other tender listing sites.
 */
class TenderSource extends AbstractDataSource
{
    protected ScraperService $scraper;
    protected ParserService $parser;

    /**
     * Kenya tender portals
     */
    protected array $tenderPortals = [
        'tenders_ke' => 'https://tenders.go.ke',
        'ifmis' => 'https://www.supplier.treasury.go.ke',
    ];

    public function __construct(ScraperService $scraper, ParserService $parser)
    {
        $this->scraper = $scraper;
        $this->parser = $parser;
    }

    /**
     * Fetch tender listings
     */
    public function fetch(SourceScraper $source, ScrapeJob $job): array
    {
        $baseUrl = $source->base_url;

        if (!$baseUrl) {
            throw new \InvalidArgumentException('Tender portal URL is required');
        }

        Log::info("Fetching tenders", ['url' => $baseUrl]);

        try {
            // Fetch HTML content
            $html = $this->scraper->setTimeout(60)->fetch($baseUrl);
            $crawler = $this->parser->createCrawler($html);

            // Parse tender listings based on portal type
            $tenders = $this->parseTenderListings($crawler, $baseUrl, $source);

            Log::info("Tenders parsed successfully", [
                'url' => $baseUrl,
                'tenders_count' => count($tenders),
            ]);

            return $tenders;
        } catch (\Throwable $e) {
            Log::error("Failed to fetch tenders", [
                'url' => $baseUrl,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Parse tender listings from crawler
     */
    protected function parseTenderListings(Crawler $crawler, string $baseUrl, SourceScraper $source): array
    {
        $config = $source->configuration ?? [];
        $tenders = [];

        // Determine portal type and use appropriate selectors
        if (str_contains($baseUrl, 'tenders.go.ke')) {
            $tenders = $this->parseTendersGoKe($crawler, $baseUrl);
        } elseif (str_contains($baseUrl, 'supplier.treasury.go.ke')) {
            $tenders = $this->parseIfmisTenders($crawler, $baseUrl);
        } else {
            // Generic tender listing parser
            $tenders = $this->parseGenericTenders($crawler, $baseUrl, $config);
        }

        return $tenders;
    }

    /**
     * Parse tenders from tenders.go.ke
     */
    protected function parseTendersGoKe(Crawler $crawler, string $baseUrl): array
    {
        $tenders = [];

        try {
            // Common selectors for tenders.go.ke
            $crawler->filter('.tender-item, .tender-listing, tr.tender-row')->each(function (Crawler $node) use (&$tenders, $baseUrl) {
                $tender = $this->extractTenderData($node, $baseUrl);
                if ($tender) {
                    $tenders[] = $tender;
                }
            });
        } catch (\Throwable $e) {
            Log::warning("Failed to parse tenders.go.ke listings", ['error' => $e->getMessage()]);
        }

        return $tenders;
    }

    /**
     * Parse tenders from IFMIS supplier portal
     */
    protected function parseIfmisTenders(Crawler $crawler, string $baseUrl): array
    {
        $tenders = [];

        try {
            // IFMIS uses table format
            $crawler->filter('table.tender-table tr, table tbody tr')->each(function (Crawler $node, $i) use (&$tenders, $baseUrl) {
                // Skip header row
                if ($i === 0) {
                    return;
                }

                $tender = $this->extractTenderFromRow($node, $baseUrl);
                if ($tender) {
                    $tenders[] = $tender;
                }
            });
        } catch (\Throwable $e) {
            Log::warning("Failed to parse IFMIS tenders", ['error' => $e->getMessage()]);
        }

        return $tenders;
    }

    /**
     * Parse generic tender listings
     */
    protected function parseGenericTenders(Crawler $crawler, string $baseUrl, array $config): array
    {
        $tenders = [];
        $selector = $config['tender_selector'] ?? '.tender, .opportunity, article';

        try {
            $crawler->filter($selector)->each(function (Crawler $node) use (&$tenders, $baseUrl) {
                $tender = $this->extractTenderData($node, $baseUrl);
                if ($tender) {
                    $tenders[] = $tender;
                }
            });
        } catch (\Throwable $e) {
            Log::warning("Failed to parse generic tenders", ['error' => $e->getMessage()]);
        }

        return $tenders;
    }

    /**
     * Extract tender data from node
     */
    protected function extractTenderData(Crawler $node, string $baseUrl): ?array
    {
        try {
            // Extract title
            $title = $this->parser->extractText($node, 'h2, h3, .title, .tender-title, td:first-child');
            if (!$title) {
                return null;
            }

            // Extract tender details
            $description = $this->parser->extractText($node, '.description, .details, p, td:nth-child(2)');
            $tenderNumber = $this->extractTenderNumber($node);
            $organization = $this->parser->extractText($node, '.organization, .entity, .procuring-entity');
            $category = $this->parser->extractText($node, '.category, .type');
            
            // Extract dates
            $publishedDate = $this->extractDate($node, '.published, .date-published, .posted');
            $closingDate = $this->extractDate($node, '.closing, .deadline, .closes');
            
            // Extract value/budget
            $value = $this->extractValue($node);
            
            // Extract URL
            $url = $this->parser->extractAttribute($node, 'a', 'href');
            if ($url && !$this->parser->isValidUrl($url)) {
                $url = $this->makeAbsoluteUrl($url, $baseUrl);
            }

            return [
                'title' => $title,
                'description' => $description,
                'url' => $url,
                'published_at' => $publishedDate,
                'metadata' => [
                    'tender_number' => $tenderNumber,
                    'organization' => $organization,
                    'category' => $category,
                    'closing_date' => $closingDate,
                    'value' => $value,
                    'location' => $this->extractLocation($node),
                    'eligibility' => $this->extractEligibility($node),
                ],
            ];
        } catch (\Throwable $e) {
            Log::debug("Failed to extract tender data", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Extract tender data from table row
     */
    protected function extractTenderFromRow(Crawler $node, string $baseUrl): ?array
    {
        try {
            $cells = $node->filter('td');
            
            if ($cells->count() < 2) {
                return null;
            }

            // Typical table structure: Tender Number | Title | Organization | Closing Date
            $title = $this->parser->cleanText($cells->eq(1)->text());
            if (!$title) {
                return null;
            }

            $tenderNumber = $this->parser->cleanText($cells->eq(0)->text());
            $organization = $cells->count() > 2 ? $this->parser->cleanText($cells->eq(2)->text()) : null;
            $closingDate = $cells->count() > 3 ? $this->parser->cleanText($cells->eq(3)->text()) : null;

            // Extract URL
            $url = null;
            if ($cells->count() > 0) {
                $link = $cells->filter('a')->first();
                if ($link->count() > 0) {
                    $url = $link->attr('href');
                    if ($url && !filter_var($url, FILTER_VALIDATE_URL)) {
                        $url = $this->makeAbsoluteUrl($url, $baseUrl);
                    }
                }
            }

            return [
                'title' => $title,
                'description' => null,
                'url' => $url,
                'published_at' => now(),
                'metadata' => [
                    'tender_number' => $tenderNumber,
                    'organization' => $organization,
                    'closing_date' => $this->parseDate($closingDate),
                    'source_type' => 'table',
                ],
            ];
        } catch (\Throwable $e) {
            Log::debug("Failed to extract tender from row", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Extract tender number
     */
    protected function extractTenderNumber(Crawler $node): ?string
    {
        $patterns = [
            '/tender[:\s#]*([A-Z0-9\-\/]+)/i',
            '/ref[:\s#]*([A-Z0-9\-\/]+)/i',
            '/no[:\s#]*([A-Z0-9\-\/]+)/i',
        ];

        $text = $node->text();
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    /**
     * Extract date from node
     */
    protected function extractDate(Crawler $node, string $selector): ?\Carbon\Carbon
    {
        $dateText = $this->parser->extractText($node, $selector);
        return $dateText ? $this->parseDate($dateText) : null;
    }

    /**
     * Parse date string
     */
    protected function parseDate(?string $dateText): ?\Carbon\Carbon
    {
        if (!$dateText) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($dateText);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Extract tender value/budget
     */
    protected function extractValue(Crawler $node): ?array
    {
        $text = $node->text();
        
        // Match currency patterns
        $patterns = [
            '/KES?\s*([0-9,]+(?:\.[0-9]{2})?)\s*(million|billion|M|B)?/i',
            '/USD?\s*([0-9,]+(?:\.[0-9]{2})?)\s*(million|billion|M|B)?/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $amount = (float) str_replace(',', '', $matches[1]);
                
                // Apply multiplier if present
                if (isset($matches[2])) {
                    $multiplier = strtolower($matches[2]);
                    if (in_array($multiplier, ['million', 'm'])) {
                        $amount *= 1000000;
                    } elseif (in_array($multiplier, ['billion', 'b'])) {
                        $amount *= 1000000000;
                    }
                }

                $currency = str_starts_with($matches[0], 'USD') || str_contains($matches[0], '$') ? 'USD' : 'KES';

                return [
                    'amount' => $amount,
                    'currency' => $currency,
                    'formatted' => $matches[0],
                ];
            }
        }

        return null;
    }

    /**
     * Extract location
     */
    protected function extractLocation(Crawler $node): ?string
    {
        return $this->parser->extractText($node, '.location, .region, .county');
    }

    /**
     * Extract eligibility criteria
     */
    protected function extractEligibility(Crawler $node): ?string
    {
        return $this->parser->extractText($node, '.eligibility, .requirements');
    }

    /**
     * Make absolute URL
     */
    protected function makeAbsoluteUrl(string $relativeUrl, string $baseUrl): string
    {
        $parsed = parse_url($baseUrl);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';

        if (str_starts_with($relativeUrl, '//')) {
            return $scheme . ':' . $relativeUrl;
        }

        if (str_starts_with($relativeUrl, '/')) {
            return "{$scheme}://{$host}{$relativeUrl}";
        }

        return $baseUrl . '/' . $relativeUrl;
    }

    /**
     * Parse tender data to standardized format
     */
    public function parse(array $rawItem, SourceScraper $source): array
    {
        return array_merge($rawItem, [
            'source_name' => $this->extractSourceName($source),
            'external_id' => $this->generateExternalId($rawItem),
            'content' => $this->buildTenderContent($rawItem),
        ]);
    }

    /**
     * Build tender content for keyword matching
     */
    protected function buildTenderContent(array $tender): string
    {
        $parts = [
            $tender['title'] ?? '',
            $tender['description'] ?? '',
            $tender['metadata']['organization'] ?? '',
            $tender['metadata']['category'] ?? '',
        ];

        return implode(' ', array_filter($parts));
    }

    /**
     * Extract source name
     */
    protected function extractSourceName(SourceScraper $source): string
    {
        if (isset($source->configuration['source_name'])) {
            return $source->configuration['source_name'];
        }

        $url = $source->base_url;
        if (str_contains($url, 'tenders.go.ke')) {
            return 'Kenya Government Tenders';
        } elseif (str_contains($url, 'supplier.treasury.go.ke')) {
            return 'IFMIS Supplier Portal';
        }

        $host = parse_url($url, PHP_URL_HOST);
        return ucwords(str_replace(['www.', '.com', '.co.ke', '-', '_'], [' ', '', '', ' ', ' '], $host));
    }

    /**
     * Generate external ID
     */
    protected function generateExternalId(array $item): ?string
    {
        // Use tender number if available
        if (!empty($item['metadata']['tender_number'])) {
            return 'tender_' . md5($item['metadata']['tender_number']);
        }

        // Fallback to URL or title hash
        if (!empty($item['url'])) {
            return 'tender_' . md5($item['url']);
        }

        if (!empty($item['title'])) {
            return 'tender_' . md5($item['title']);
        }

        return null;
    }

    /**
     * Get the source type
     */
    public function getType(): string
    {
        return 'tender';
    }

    /**
     * Get the source category
     */
    public function getCategory(): string
    {
        return 'tender';
    }

    /**
     * Get default configuration
     */
    public function getDefaultConfiguration(): array
    {
        return [
            'tender_selector' => '.tender, .opportunity',
            'fetch_limit' => 50,
            'min_value' => 0, // Minimum tender value to include
            'categories' => [], // Filter by specific categories
            'exclude_expired' => true, // Exclude expired tenders
        ];
    }

    /**
     * Get required configuration keys
     */
    protected function getRequiredConfigKeys(): array
    {
        return [];
    }

    /**
     * Get Kenya tender portals
     */
    public static function getKenyaTenderPortals(): array
    {
        return [
            [
                'name' => 'Kenya Government Tenders',
                'url' => 'https://tenders.go.ke/website/tenders/search',
                'description' => 'Official government procurement portal',
            ],
            [
                'name' => 'IFMIS Supplier Portal',
                'url' => 'https://www.supplier.treasury.go.ke/supplier/public_notices',
                'description' => 'Integrated Financial Management Information System',
            ],
        ];
    }
}
