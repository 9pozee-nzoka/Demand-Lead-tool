<?php

namespace App\Services\Sources;

use App\Models\SourceScraper;
use App\Models\Keyword;
use Illuminate\Support\Str;

/**
 * NormalizerService - Normalizes, enriches, and scores scraped items
 */
class NormalizerService
{
    /**
     * Normalize and enrich a scraped item
     */
    public function normalize(array $rawItem, SourceScraper $source): array
    {
        $title = $rawItem['title'] ?? '';
        $description = $rawItem['description'] ?? '';
        $content = $rawItem['content'] ?? '';
        
        $fullText = $title . ' ' . $description . ' ' . $content;

        return [
            'title' => $this->cleanText($title),
            'description' => $this->cleanText($description),
            'content' => $this->cleanText($content),
            'url' => $rawItem['url'] ?? null,
            'source_name' => $rawItem['source_name'] ?? $source->name,
            'published_at' => $rawItem['published_at'] ?? now(),
            
            // Intelligence scoring
            'intent' => $this->detectIntent($fullText, $source),
            'relevance_score' => $this->calculateRelevanceScore($fullText, $source),
            'lead_score' => $this->calculateLeadScore($fullText, $source),
            'opportunity_score' => $this->calculateOpportunityScore($fullText, $source),
            
            // Extracted data
            'matched_keywords' => $this->extractMatchedKeywords($fullText, $source),
            'extracted_entities' => $this->extractEntities($fullText),
            
            // Metadata
            'metadata' => [
                'word_count' => str_word_count($fullText),
                'has_contact_info' => $this->hasContactInfo($fullText),
                'has_price' => $this->hasPrice($fullText),
                'has_deadline' => $this->hasDeadline($fullText),
            ],
        ];
    }

    /**
     * Detect the intent of the content
     */
    private function detectIntent(string $text, SourceScraper $source): string
    {
        $text = strtolower($text);

        // Tender keywords
        if (preg_match('/\b(rfp|rfq|tender|bid|proposal|procurement)\b/i', $text)) {
            return 'tender';
        }

        // Transactional keywords
        if (preg_match('/\b(buy|purchase|order|quote|enquiry|inquiry|looking for|need)\b/i', $text)) {
            return 'transactional';
        }

        // Commercial keywords
        if (preg_match('/\b(price|cost|supplier|vendor|service provider)\b/i', $text)) {
            return 'commercial';
        }

        // Default to informational
        return 'informational';
    }

    /**
     * Calculate relevance score based on keyword matching
     */
    private function calculateRelevanceScore(string $text, SourceScraper $source): float
    {
        // Get keywords from the organization's projects
        $keywords = Keyword::whereHas('project', function ($q) use ($source) {
            $q->where('organization_id', $source->organization_id)
              ->where('status', 'active');
        })->pluck('keyword')->toArray();

        if (empty($keywords)) {
            return 0.0;
        }

        $text = strtolower($text);
        $matchCount = 0;

        foreach ($keywords as $keyword) {
            if (str_contains($text, strtolower($keyword))) {
                $matchCount++;
            }
        }

        return min(100, ($matchCount / count($keywords)) * 100);
    }

    /**
     * Calculate lead score
     */
    private function calculateLeadScore(string $text, SourceScraper $source): float
    {
        $score = 0;

        // Intent weight
        $intent = $this->detectIntent($text, $source);
        $score += match ($intent) {
            'tender' => 35,
            'transactional' => 30,
            'commercial' => 20,
            default => 5,
        };

        // Has contact info
        if ($this->hasContactInfo($text)) {
            $score += 25;
        }

        // Has specific requirements
        if ($this->hasPrice($text) || $this->hasDeadline($text)) {
            $score += 20;
        }

        // Source category bonus
        if ($source->category === 'tender' || $source->category === 'lead_capture') {
            $score += 20;
        }

        return min(100, $score);
    }

    /**
     * Calculate opportunity score
     */
    private function calculateOpportunityScore(string $text, SourceScraper $source): float
    {
        $score = 0;

        // Intent weight
        $intent = $this->detectIntent($text, $source);
        $score += match ($intent) {
            'tender' => 40,
            'transactional' => 30,
            'commercial' => 25,
            default => 10,
        };

        // Keyword relevance
        $relevance = $this->calculateRelevanceScore($text, $source);
        $score += ($relevance * 0.3);

        // Category bonus
        if ($source->category === 'tender') {
            $score += 15;
        }

        // Recency bonus (newer is better)
        $score += 15;

        return min(100, $score);
    }

    /**
     * Extract matched keywords
     */
    private function extractMatchedKeywords(string $text, SourceScraper $source): array
    {
        $keywords = Keyword::whereHas('project', function ($q) use ($source) {
            $q->where('organization_id', $source->organization_id)
              ->where('status', 'active');
        })->get();

        $matched = [];
        $text = strtolower($text);

        foreach ($keywords as $keyword) {
            if (str_contains($text, strtolower($keyword->keyword))) {
                $matched[] = $keyword->id;
            }
        }

        return $matched;
    }

    /**
     * Extract entities (companies, locations, contacts)
     */
    private function extractEntities(string $text): array
    {
        return [
            'emails' => $this->extractEmails($text),
            'phones' => $this->extractPhones($text),
            'amounts' => $this->extractAmounts($text),
            'locations' => $this->extractLocations($text),
        ];
    }

    /**
     * Check if text has contact information
     */
    private function hasContactInfo(string $text): bool
    {
        return preg_match('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', $text)
            || preg_match('/\b[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}\b/', $text);
    }

    /**
     * Check if text has price/amount information
     */
    private function hasPrice(string $text): bool
    {
        return preg_match('/\$[\d,]+|\b[\d,]+\s*(KSh|USD|EUR|GBP)\b/i', $text);
    }

    /**
     * Check if text has deadline information
     */
    private function hasDeadline(string $text): bool
    {
        return preg_match('/\b(deadline|due date|closing date|by|before)\b/i', $text)
            && preg_match('/\b\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}\b/', $text);
    }

    /**
     * Extract email addresses
     */
    private function extractEmails(string $text): array
    {
        preg_match_all('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', $text, $matches);
        return $matches[0] ?? [];
    }

    /**
     * Extract phone numbers
     */
    private function extractPhones(string $text): array
    {
        preg_match_all('/\b[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}\b/', $text, $matches);
        return $matches[0] ?? [];
    }

    /**
     * Extract monetary amounts
     */
    private function extractAmounts(string $text): array
    {
        preg_match_all('/\$[\d,]+|\b[\d,]+\s*(KSh|USD|EUR|GBP)\b/i', $text, $matches);
        return $matches[0] ?? [];
    }

    /**
     * Extract location mentions
     */
    private function extractLocations(string $text): array
    {
        // Simple pattern for common East African cities
        $locations = ['Nairobi', 'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret', 'Kampala', 'Dar es Salaam', 'Kigali'];
        $found = [];

        foreach ($locations as $location) {
            if (stripos($text, $location) !== false) {
                $found[] = $location;
            }
        }

        return $found;
    }

    /**
     * Clean text content
     */
    private function cleanText(?string $text): ?string
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
}
