<?php

namespace App\Services\Sources;

use Carbon\Carbon;

/**
 * NormalizerService - Data normalization and standardization
 * 
 * Ensures consistent data format across all sources by:
 * - Standardizing dates and timestamps
 * - Normalizing text encoding and formatting
 * - Validating and cleaning URLs
 * - Extracting structured data
 */
class NormalizerService
{
    /**
     * Normalize raw scraped data to standard format
     */
    public function normalize(array $data): array
    {
        return [
            'title' => $this->normalizeTitle($data['title'] ?? null),
            'description' => $this->normalizeDescription($data['description'] ?? null),
            'content' => $this->normalizeContent($data['content'] ?? null),
            'url' => $this->normalizeUrl($data['url'] ?? null),
            'source_name' => $data['source_name'] ?? null,
            'published_at' => $this->normalizeDate($data['published_at'] ?? null),
            'external_id' => $data['external_id'] ?? null,
            'metadata' => $this->normalizeMetadata($data),
        ];
    }

    /**
     * Normalize title
     */
    public function normalizeTitle(?string $title): string
    {
        if (!$title) {
            return 'Untitled';
        }

        // Decode HTML entities
        $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Remove extra whitespace
        $title = preg_replace('/\s+/', ' ', $title);

        // Trim
        $title = trim($title);

        // Limit length
        if (mb_strlen($title) > 255) {
            $title = mb_substr($title, 0, 252) . '...';
        }

        return $title ?: 'Untitled';
    }

    /**
     * Normalize description
     */
    public function normalizeDescription(?string $description): ?string
    {
        if (!$description) {
            return null;
        }

        // Remove HTML tags
        $description = strip_tags($description);

        // Decode HTML entities
        $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Remove extra whitespace
        $description = preg_replace('/\s+/', ' ', $description);

        // Trim
        $description = trim($description);

        // Limit length to reasonable size
        if (mb_strlen($description) > 1000) {
            $description = mb_substr($description, 0, 997) . '...';
        }

        return $description ?: null;
    }

    /**
     * Normalize content
     */
    public function normalizeContent(?string $content): ?string
    {
        if (!$content) {
            return null;
        }

        // Remove HTML tags but preserve basic structure
        $content = strip_tags($content);

        // Decode HTML entities
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalize line breaks
        $content = preg_replace('/\r\n|\r/', "\n", $content);

        // Remove excessive newlines
        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        // Normalize whitespace
        $content = preg_replace('/[^\S\n]+/', ' ', $content);

        // Trim
        $content = trim($content);

        return $content ?: null;
    }

    /**
     * Normalize URL
     */
    public function normalizeUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        // Trim whitespace
        $url = trim($url);

        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        // Parse URL
        $parsed = parse_url($url);

        // Remove fragments
        unset($parsed['fragment']);

        // Rebuild URL
        $normalized = ($parsed['scheme'] ?? 'https') . '://';
        
        if (isset($parsed['host'])) {
            $normalized .= $parsed['host'];
        }
        
        if (isset($parsed['port'])) {
            $normalized .= ':' . $parsed['port'];
        }
        
        if (isset($parsed['path'])) {
            $normalized .= $parsed['path'];
        }
        
        if (isset($parsed['query'])) {
            $normalized .= '?' . $parsed['query'];
        }

        return $normalized;
    }

    /**
     * Normalize date/timestamp
     */
    public function normalizeDate($date): ?Carbon
    {
        if (!$date) {
            return null;
        }

        // Already a Carbon instance
        if ($date instanceof Carbon) {
            return $date;
        }

        // DateTime instance
        if ($date instanceof \DateTime) {
            return Carbon::instance($date);
        }

        // String date
        if (is_string($date)) {
            try {
                return Carbon::parse($date);
            } catch (\Throwable $e) {
                return null;
            }
        }

        // Timestamp
        if (is_numeric($date)) {
            try {
                return Carbon::createFromTimestamp($date);
            } catch (\Throwable $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Normalize metadata
     */
    public function normalizeMetadata(array $data): array
    {
        $metadata = [];

        // Extract author if present
        if (isset($data['author'])) {
            $metadata['author'] = $this->normalizeText($data['author']);
        }

        // Extract categories/tags
        if (isset($data['categories']) && is_array($data['categories'])) {
            $metadata['categories'] = array_map([$this, 'normalizeText'], $data['categories']);
        }

        // Extract image URL
        if (isset($data['image'])) {
            $metadata['image'] = $this->normalizeUrl($data['image']);
        }

        // Extract language
        if (isset($data['language'])) {
            $metadata['language'] = $this->normalizeLanguageCode($data['language']);
        }

        // Include any additional custom metadata
        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $metadata = array_merge($metadata, $data['metadata']);
        }

        return $metadata;
    }

    /**
     * Normalize text (general purpose)
     */
    public function normalizeText(?string $text): ?string
    {
        if (!$text) {
            return null;
        }

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        // Trim
        $text = trim($text);

        return $text ?: null;
    }

    /**
     * Normalize language code to ISO 639-1
     */
    public function normalizeLanguageCode(?string $lang): ?string
    {
        if (!$lang) {
            return null;
        }

        $lang = strtolower(trim($lang));

        // Map common variations to ISO codes
        $langMap = [
            'english' => 'en',
            'swahili' => 'sw',
            'french' => 'fr',
            'spanish' => 'es',
            'en-us' => 'en',
            'en-gb' => 'en',
        ];

        return $langMap[$lang] ?? $lang;
    }

    /**
     * Extract domain from URL
     */
    public function extractDomain(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $parsed = parse_url($url);
        return $parsed['host'] ?? null;
    }

    /**
     * Generate slug from text
     */
    public function generateSlug(string $text): string
    {
        // Convert to lowercase
        $slug = strtolower($text);

        // Replace non-alphanumeric characters with hyphens
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

        // Remove leading/trailing hyphens
        $slug = trim($slug, '-');

        // Limit length
        if (strlen($slug) > 100) {
            $slug = substr($slug, 0, 100);
            $slug = rtrim($slug, '-');
        }

        return $slug;
    }

    /**
     * Detect and normalize currency amounts
     */
    public function normalizeCurrency(string $text): ?array
    {
        // Match currency patterns (KES, USD, etc.)
        $patterns = [
            '/KES?\s*([0-9,]+(?:\.[0-9]{2})?)/i',
            '/USD?\s*([0-9,]+(?:\.[0-9]{2})?)/i',
            '/\$([0-9,]+(?:\.[0-9]{2})?)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $amount = str_replace(',', '', $matches[1]);
                
                // Determine currency
                $currency = 'KES';
                if (stripos($matches[0], 'USD') !== false || str_contains($matches[0], '$')) {
                    $currency = 'USD';
                }

                return [
                    'amount' => (float) $amount,
                    'currency' => $currency,
                    'formatted' => $matches[0],
                ];
            }
        }

        return null;
    }

    /**
     * Normalize phone number to E.164 format (Kenya)
     */
    public function normalizePhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        // Remove non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Handle different Kenyan formats
        if (preg_match('/^(254)?([17]\d{8})$/', $phone, $matches)) {
            return '+254' . $matches[2];
        }

        return null;
    }

    /**
     * Normalize email address
     */
    public function normalizeEmail(?string $email): ?string
    {
        if (!$email) {
            return null;
        }

        $email = strtolower(trim($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    /**
     * Calculate text similarity (0-100)
     */
    public function calculateSimilarity(string $text1, string $text2): float
    {
        similar_text($text1, $text2, $percent);
        return round($percent, 2);
    }

    /**
     * Detect intent from text (basic keyword matching)
     */
    public function detectIntent(string $text): string
    {
        $text = strtolower($text);

        // Commercial intent keywords
        $commercialKeywords = ['buy', 'purchase', 'order', 'pricing', 'cost', 'price', 'quotation', 'tender'];
        foreach ($commercialKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return 'commercial';
            }
        }

        // Transactional intent keywords
        $transactionalKeywords = ['apply', 'register', 'submit', 'bid', 'proposal'];
        foreach ($transactionalKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return 'transactional';
            }
        }

        // Tender-specific keywords
        $tenderKeywords = ['tender', 'procurement', 'rfp', 'rfq', 'eoi'];
        foreach ($tenderKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return 'tender';
            }
        }

        return 'informational';
    }
}
