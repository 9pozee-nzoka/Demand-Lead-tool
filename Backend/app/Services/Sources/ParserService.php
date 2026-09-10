<?php

namespace App\Services\Sources;

use Symfony\Component\DomCrawler\Crawler;

/**
 * ParserService - HTML/XML parsing and data extraction
 * 
 * Provides utilities for parsing HTML, extracting content,
 * cleaning text, and handling malformed markup.
 */
class ParserService
{
    /**
     * Create a DOM crawler from HTML
     */
    public function createCrawler(string $html): Crawler
    {
        return new Crawler($html);
    }

    /**
     * Extract text content from HTML selector
     */
    public function extractText(Crawler $crawler, string $selector): ?string
    {
        try {
            $node = $crawler->filter($selector);
            
            if ($node->count() === 0) {
                return null;
            }

            return $this->cleanText($node->text());
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Extract attribute value from HTML selector
     */
    public function extractAttribute(Crawler $crawler, string $selector, string $attribute): ?string
    {
        try {
            $node = $crawler->filter($selector);
            
            if ($node->count() === 0) {
                return null;
            }

            return $node->attr($attribute);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Extract multiple elements matching selector
     */
    public function extractMultiple(Crawler $crawler, string $selector): array
    {
        try {
            $results = [];
            
            $crawler->filter($selector)->each(function (Crawler $node) use (&$results) {
                $results[] = $this->cleanText($node->text());
            });

            return array_filter($results);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Extract all links from HTML
     */
    public function extractLinks(Crawler $crawler, ?string $baseUrl = null): array
    {
        try {
            $links = [];
            
            $crawler->filter('a')->each(function (Crawler $node) use (&$links, $baseUrl) {
                $href = $node->attr('href');
                
                if ($href) {
                    // Convert relative URLs to absolute
                    if ($baseUrl && !$this->isAbsoluteUrl($href)) {
                        $href = $this->makeAbsoluteUrl($href, $baseUrl);
                    }
                    
                    $links[] = [
                        'url' => $href,
                        'text' => $this->cleanText($node->text()),
                    ];
                }
            });

            return $links;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Extract meta tag content
     */
    public function extractMeta(Crawler $crawler, string $name): ?string
    {
        try {
            // Try name attribute
            $node = $crawler->filter("meta[name='{$name}']");
            if ($node->count() > 0) {
                return $node->attr('content');
            }

            // Try property attribute (for Open Graph tags)
            $node = $crawler->filter("meta[property='{$name}']");
            if ($node->count() > 0) {
                return $node->attr('content');
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Clean and normalize text
     */
    public function cleanText(?string $text): ?string
    {
        if (!$text) {
            return null;
        }

        // Remove HTML tags
        $text = strip_tags($text);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        // Trim
        $text = trim($text);

        return $text ?: null;
    }

    /**
     * Strip HTML tags but preserve structure
     */
    public function stripTagsPreserveStructure(string $html): string
    {
        // Replace block elements with newlines
        $html = preg_replace('/<\/(p|div|h[1-6]|li|tr)>/i', "$0\n", $html);
        
        // Remove all tags
        $text = strip_tags($html);
        
        // Clean up multiple newlines
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
        return trim($text);
    }

    /**
     * Extract main content from article page
     */
    public function extractArticleContent(Crawler $crawler): ?string
    {
        // Try common article content selectors
        $selectors = [
            'article',
            '[role="article"]',
            '.article-content',
            '.post-content',
            '.entry-content',
            'main',
        ];

        foreach ($selectors as $selector) {
            try {
                $node = $crawler->filter($selector);
                if ($node->count() > 0) {
                    return $this->stripTagsPreserveStructure($node->html());
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Parse RSS/Atom feed
     */
    public function parseRssFeed(\SimpleXMLElement $xml): array
    {
        $items = [];

        // Detect feed type
        if (isset($xml->channel->item)) {
            // RSS 2.0
            foreach ($xml->channel->item as $item) {
                $items[] = $this->parseRssItem($item);
            }
        } elseif (isset($xml->entry)) {
            // Atom
            foreach ($xml->entry as $entry) {
                $items[] = $this->parseAtomEntry($entry);
            }
        }

        return $items;
    }

    /**
     * Parse RSS item
     */
    protected function parseRssItem(\SimpleXMLElement $item): array
    {
        return [
            'title' => $this->cleanText((string) $item->title),
            'description' => $this->cleanText((string) $item->description),
            'content' => $this->cleanText((string) ($item->children('content', true)->encoded ?? $item->description)),
            'url' => (string) $item->link,
            'published_at' => $this->parseDate((string) $item->pubDate),
            'author' => $this->cleanText((string) ($item->author ?? $item->children('dc', true)->creator)),
            'categories' => $this->extractRssCategories($item),
        ];
    }

    /**
     * Parse Atom entry
     */
    protected function parseAtomEntry(\SimpleXMLElement $entry): array
    {
        $link = '';
        if (isset($entry->link)) {
            $link = (string) $entry->link['href'];
        }

        return [
            'title' => $this->cleanText((string) $entry->title),
            'description' => $this->cleanText((string) $entry->summary),
            'content' => $this->cleanText((string) ($entry->content ?? $entry->summary)),
            'url' => $link,
            'published_at' => $this->parseDate((string) ($entry->published ?? $entry->updated)),
            'author' => $this->cleanText((string) ($entry->author->name ?? '')),
            'categories' => $this->extractAtomCategories($entry),
        ];
    }

    /**
     * Extract RSS categories
     */
    protected function extractRssCategories(\SimpleXMLElement $item): array
    {
        $categories = [];
        
        if (isset($item->category)) {
            foreach ($item->category as $category) {
                $categories[] = (string) $category;
            }
        }

        return $categories;
    }

    /**
     * Extract Atom categories
     */
    protected function extractAtomCategories(\SimpleXMLElement $entry): array
    {
        $categories = [];
        
        if (isset($entry->category)) {
            foreach ($entry->category as $category) {
                $categories[] = (string) $category['term'];
            }
        }

        return $categories;
    }

    /**
     * Parse date string to Carbon instance
     */
    protected function parseDate(?string $dateString): ?\Carbon\Carbon
    {
        if (!$dateString) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($dateString);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Check if URL is absolute
     */
    protected function isAbsoluteUrl(string $url): bool
    {
        return preg_match('/^https?:\/\//i', $url) === 1;
    }

    /**
     * Convert relative URL to absolute
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

        $path = $parsed['path'] ?? '/';
        $path = dirname($path);
        
        return "{$scheme}://{$host}{$path}/{$relativeUrl}";
    }

    /**
     * Extract email addresses from text
     */
    public function extractEmails(string $text): array
    {
        preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text, $matches);
        return array_unique($matches[0]);
    }

    /**
     * Extract phone numbers from text (Kenya format)
     */
    public function extractPhones(string $text): array
    {
        // Match Kenyan phone formats: +254..., 0..., 254...
        preg_match_all('/(?:\+254|254|0)[17]\d{8}/', $text, $matches);
        return array_unique($matches[0]);
    }
}
