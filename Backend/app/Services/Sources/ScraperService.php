<?php

namespace App\Services\Sources;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ScraperService - HTTP client wrapper for web scraping
 * 
 * Provides rate limiting, user agent rotation, error handling,
 * and retry logic for HTTP requests.
 */
class ScraperService
{
    protected array $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    ];

    protected int $timeout = 30;
    protected int $retries = 3;
    protected int $retryDelay = 1000; // milliseconds

    /**
     * Fetch URL content
     */
    public function fetch(string $url, array $options = []): string
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->retries) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => $this->getRandomUserAgent(),
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                    'Accept-Encoding' => 'gzip, deflate',
                    'Connection' => 'keep-alive',
                    'Upgrade-Insecure-Requests' => '1',
                ])
                ->timeout($options['timeout'] ?? $this->timeout)
                ->retry($this->retries, $this->retryDelay)
                ->get($url);

                if ($response->successful()) {
                    return $response->body();
                }

                throw new \RuntimeException("HTTP {$response->status()}: Failed to fetch {$url}");
            } catch (\Throwable $e) {
                $lastException = $e;
                $attempt++;
                
                Log::warning("Fetch attempt {$attempt} failed", [
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $this->retries) {
                    usleep($this->retryDelay * 1000);
                }
            }
        }

        throw new \RuntimeException(
            "Failed to fetch {$url} after {$this->retries} attempts: " . $lastException->getMessage()
        );
    }

    /**
     * Fetch JSON data from URL
     */
    public function fetchJson(string $url, array $options = []): array
    {
        $response = Http::withHeaders([
            'User-Agent' => $this->getRandomUserAgent(),
            'Accept' => 'application/json',
        ])
        ->timeout($options['timeout'] ?? $this->timeout)
        ->retry($this->retries, $this->retryDelay)
        ->get($url);

        if (!$response->successful()) {
            throw new \RuntimeException("HTTP {$response->status()}: Failed to fetch JSON from {$url}");
        }

        return $response->json();
    }

    /**
     * Fetch XML/RSS feed
     */
    public function fetchXml(string $url, array $options = []): \SimpleXMLElement
    {
        $content = $this->fetch($url, $options);
        
        // Suppress XML parsing warnings
        libxml_use_internal_errors(true);
        
        $xml = simplexml_load_string($content);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            
            $errorMessages = array_map(fn($e) => $e->message, $errors);
            throw new \RuntimeException("Failed to parse XML: " . implode(', ', $errorMessages));
        }

        return $xml;
    }

    /**
     * POST request with data
     */
    public function post(string $url, array $data = [], array $options = []): string
    {
        $response = Http::withHeaders([
            'User-Agent' => $this->getRandomUserAgent(),
        ])
        ->timeout($options['timeout'] ?? $this->timeout)
        ->retry($this->retries, $this->retryDelay)
        ->post($url, $data);

        if (!$response->successful()) {
            throw new \RuntimeException("HTTP {$response->status()}: POST to {$url} failed");
        }

        return $response->body();
    }

    /**
     * Check if URL is accessible
     */
    public function isAccessible(string $url): bool
    {
        try {
            $response = Http::timeout(10)->head($url);
            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get random user agent
     */
    protected function getRandomUserAgent(): string
    {
        return $this->userAgents[array_rand($this->userAgents)];
    }

    /**
     * Set timeout
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Set retry attempts
     */
    public function setRetries(int $retries): self
    {
        $this->retries = $retries;
        return $this;
    }

    /**
     * Set retry delay in milliseconds
     */
    public function setRetryDelay(int $milliseconds): self
    {
        $this->retryDelay = $milliseconds;
        return $this;
    }
}
