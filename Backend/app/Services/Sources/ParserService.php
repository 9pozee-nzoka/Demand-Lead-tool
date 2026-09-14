<?php

namespace App\Services\Sources;

use App\Models\SourceScraper;
use Symfony\Component\DomCrawler\Crawler;

/**
 * ParserService - Extracts structured data from HTML/XML
 */
class ParserService
{
    /**
     * Parse tender listings from HTML
     */
    public function parseTenders(string $html, SourceScraper $source): array
    {
        $crawler = new Crawler($html);
        $config = $source->configuration;
        
        $items = [];
        $selector = $config['item_selector'] ?? 'article, .tender-item, .listing';
        
        $crawler->filter($selector)->each(function (Crawler $node) use (&$items, $config, $source) {
            try {
                $items[] = [
                    'title' => $this->extractText($node, $config['title_selector'] ?? 'h2, h3, .title'),
                    'description' => $this->extractText($node, $config['description_selector'] ?? 'p, .description'),
                    'content' => $node->text(),
                    'url' => $this->extractUrl($node, $config['url_selector'] ?? 'a', $source->base_url),
                    'published_at' => $this->extractDate($node, $config['date_selector'] ?? '.date, time'),
                    'external_id' => $this->extractText($node, $config['id_selector'] ?? '.id, .reference'),
                    'source_name' => $source->name,
                ];
            } catch (\Throwable $e) {
                // Skip malformed items
            }
        });

        return $items;
    }

    /**
     * Parse website content from HTML
     */
    public function parseWebsite(string $html, SourceScraper $source): array
    {
        $crawler = new Crawler($html);
        $config = $source->configuration;
        
        $items = [];
        $selector = $config['item_selector'] ?? 'article, .post, .entry';
        
        $crawler->filter($selector)->each(function (Crawler $node) use (&$items, $config, $source) {
            try {
                $items[] = [
                    'title' => $this->extractText($node, $config['title_selector'] ?? 'h1, h2, h3, .title'),
                    'description' => $this->extractText($node, $config['description_selector'] ?? 'p, .excerpt, .summary'),
                    'content' => $node->text(),
                    'url' => $this->extractUrl($node, $config['url_selector'] ?? 'a', $source->base_url),
                    'published_at' => $this->extractDate($node, $config['date_selector'] ?? 'time, .date, .published'),
                    'external_id' => null,
                    'source_name' => $source->name,
                ];
            } catch (\Throwable $e) {
                // Skip malformed items
            }
        });

        return $items;
    }

    /**
     * Extract text from a node using selector
     */
    private function extractText(Crawler $node, string $selector): ?string
    {
        try {
            $text = $node->filter($selector)->first()->text();
            return trim($text) ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Extract URL from a node using selector
     */
    private function extractUrl(Crawler $node, string $selector, string $baseUrl): ?string
    {
        try {
            $href = $node->filter($selector)->first()->attr('href');
            
            if (!$href) {
                return null;
            }

            // Convert relative URLs to absolute
            if (!parse_url($href, PHP_URL_SCHEME)) {
                $baseComponents = parse_url($baseUrl);
                $base = $baseComponents['scheme'] . '://' . $baseComponents['host'];
                $href = $base . '/' . ltrim($href, '/');
            }

            return $href;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Extract and parse date from a node
     */
    private function extractDate(Crawler $node, string $selector): ?string
    {
        try {
            $dateText = $node->filter($selector)->first()->text();
            $timestamp = strtotime($dateText);
            
            return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
