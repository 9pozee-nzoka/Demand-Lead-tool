<?php

namespace App\Contracts;

use App\Models\ScrapeJob;
use App\Models\SourceScraper;

/**
 * Interface DataSourceInterface
 * 
 * All data source providers (RSS, Tender, Webhook, etc.) must implement this interface
 * to ensure consistent behavior across the multi-source intelligence platform.
 */
interface DataSourceInterface
{
    /**
     * Validate the source configuration
     * 
     * @param array $configuration Source-specific settings
     * @return bool True if configuration is valid
     */
    public function validateConfiguration(array $configuration): bool;

    /**
     * Test the connection to the data source
     * 
     * @param SourceScraper $source
     * @return array ['success' => bool, 'message' => string, 'metadata' => array]
     */
    public function testConnection(SourceScraper $source): array;

    /**
     * Fetch data from the source
     * 
     * @param SourceScraper $source
     * @param ScrapeJob $job
     * @return array Array of raw items fetched from the source
     */
    public function fetch(SourceScraper $source, ScrapeJob $job): array;

    /**
     * Parse raw item data into standardized format
     * 
     * @param array $rawItem Raw data from the source
     * @param SourceScraper $source
     * @return array Normalized item data
     */
    public function parse(array $rawItem, SourceScraper $source): array;

    /**
     * Normalize parsed data to ScrapedItem attributes
     * 
     * @param array $parsedData
     * @return array Array ready for ScrapedItem creation
     */
    public function normalize(array $parsedData): array;

    /**
     * Get the source type identifier
     * 
     * @return string One of: rss, tender, webhook, api, scraper
     */
    public function getType(): string;

    /**
     * Get the source category
     * 
     * @return string One of: news, tender, lead_capture, market_data
     */
    public function getCategory(): string;

    /**
     * Get default configuration for this source type
     * 
     * @return array Default configuration structure
     */
    public function getDefaultConfiguration(): array;

    /**
     * Handle any cleanup or post-processing after a successful fetch
     * 
     * @param SourceScraper $source
     * @param ScrapeJob $job
     * @return void
     */
    public function afterFetch(SourceScraper $source, ScrapeJob $job): void;

    /**
     * Handle errors that occur during fetch
     * 
     * @param SourceScraper $source
     * @param ScrapeJob $job
     * @param \Throwable $exception
     * @return void
     */
    public function handleError(SourceScraper $source, ScrapeJob $job, \Throwable $exception): void;
}
