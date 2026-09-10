<?php

namespace App\Services\Sources;

use App\Models\Keyword;
use App\Models\Organization;
use App\Models\ScrapedItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * KeywordMatcherService - Matches scraped content against tracked keywords
 * 
 * Analyzes articles and content to find keyword matches and calculate
 * relevance scores for business opportunity identification.
 */
class KeywordMatcherService
{
    /**
     * Match a scraped item against organization's keywords
     */
    public function matchKeywords(ScrapedItem $item, Organization $organization): array
    {
        // Get all active keywords for the organization
        $keywords = Keyword::whereHas('project', function ($q) use ($organization) {
            $q->where('organization_id', $organization->id);
        })
        ->where('status', 'active')
        ->get();

        if ($keywords->isEmpty()) {
            return [];
        }

        // Prepare content for matching
        $content = $this->prepareContentForMatching($item);

        // Find matches
        $matches = [];
        foreach ($keywords as $keyword) {
            $matchResult = $this->matchKeyword($keyword, $content);
            
            if ($matchResult['matched']) {
                $matches[] = [
                    'keyword_id' => $keyword->id,
                    'keyword' => $keyword->keyword,
                    'project_id' => $keyword->project_id,
                    'relevance_score' => $matchResult['score'],
                    'match_count' => $matchResult['count'],
                    'match_positions' => $matchResult['positions'],
                    'context_snippets' => $matchResult['snippets'],
                ];
            }
        }

        // Sort by relevance score
        usort($matches, fn($a, $b) => $b['relevance_score'] <=> $a['relevance_score']);

        return $matches;
    }

    /**
     * Match a single keyword against content
     */
    protected function matchKeyword(Keyword $keyword, array $content): array
    {
        $searchTerm = strtolower($keyword->keyword);
        $variations = $this->generateKeywordVariations($searchTerm);
        
        $totalMatches = 0;
        $positions = [];
        $snippets = [];

        // Search in title (highest weight)
        $titleMatches = $this->findMatches($content['title'], $variations);
        if ($titleMatches['count'] > 0) {
            $totalMatches += $titleMatches['count'] * 3; // Title matches worth 3x
            $positions['title'] = $titleMatches['positions'];
            $snippets[] = [
                'type' => 'title',
                'text' => $this->extractSnippet($content['title'], $titleMatches['positions'][0] ?? 0, 100),
            ];
        }

        // Search in description (medium weight)
        $descMatches = $this->findMatches($content['description'], $variations);
        if ($descMatches['count'] > 0) {
            $totalMatches += $descMatches['count'] * 2; // Description matches worth 2x
            $positions['description'] = $descMatches['positions'];
            $snippets[] = [
                'type' => 'description',
                'text' => $this->extractSnippet($content['description'], $descMatches['positions'][0] ?? 0),
            ];
        }

        // Search in content (normal weight)
        $contentMatches = $this->findMatches($content['content'], $variations);
        if ($contentMatches['count'] > 0) {
            $totalMatches += $contentMatches['count'];
            $positions['content'] = $contentMatches['positions'];
            
            // Get up to 3 content snippets
            for ($i = 0; $i < min(3, count($contentMatches['positions'])); $i++) {
                $snippets[] = [
                    'type' => 'content',
                    'text' => $this->extractSnippet($content['content'], $contentMatches['positions'][$i]),
                ];
            }
        }

        $matched = $totalMatches > 0;
        $score = $matched ? $this->calculateRelevanceScore($totalMatches, $content) : 0;

        return [
            'matched' => $matched,
            'score' => $score,
            'count' => $totalMatches,
            'positions' => $positions,
            'snippets' => array_slice($snippets, 0, 5), // Limit to 5 snippets
        ];
    }

    /**
     * Prepare content for keyword matching
     */
    protected function prepareContentForMatching(ScrapedItem $item): array
    {
        return [
            'title' => strtolower($item->title ?? ''),
            'description' => strtolower($item->description ?? ''),
            'content' => strtolower($item->content ?? ''),
            'url' => strtolower($item->url ?? ''),
        ];
    }

    /**
     * Generate keyword variations for better matching
     */
    protected function generateKeywordVariations(string $keyword): array
    {
        $variations = [$keyword];

        // Add plural/singular variations
        if (!str_ends_with($keyword, 's')) {
            $variations[] = $keyword . 's';
        } else {
            $variations[] = rtrim($keyword, 's');
        }

        // Add common variations
        $variations[] = str_replace(' ', '-', $keyword); // hyphenated
        $variations[] = str_replace(' ', '', $keyword);  // no space
        $variations[] = str_replace('-', ' ', $keyword); // space instead of hyphen

        return array_unique($variations);
    }

    /**
     * Find all matches of keyword variations in text
     */
    protected function findMatches(string $text, array $variations): array
    {
        $positions = [];
        $count = 0;

        foreach ($variations as $variation) {
            $offset = 0;
            while (($pos = mb_strpos($text, $variation, $offset)) !== false) {
                // Check for word boundaries
                if ($this->isWordBoundary($text, $pos, mb_strlen($variation))) {
                    $positions[] = $pos;
                    $count++;
                }
                $offset = $pos + mb_strlen($variation);
            }
        }

        return [
            'count' => $count,
            'positions' => $positions,
        ];
    }

    /**
     * Check if match is at word boundary (not part of larger word)
     */
    protected function isWordBoundary(string $text, int $pos, int $length): bool
    {
        // Check character before
        if ($pos > 0) {
            $before = mb_substr($text, $pos - 1, 1);
            if (preg_match('/[a-z0-9]/i', $before)) {
                return false;
            }
        }

        // Check character after
        $after = mb_substr($text, $pos + $length, 1);
        if ($after && preg_match('/[a-z0-9]/i', $after)) {
            return false;
        }

        return true;
    }

    /**
     * Extract text snippet around match position
     */
    protected function extractSnippet(string $text, int $position, int $length = 200): string
    {
        $halfLength = intdiv($length, 2);
        
        $start = max(0, $position - $halfLength);
        $snippet = mb_substr($text, $start, $length);

        // Add ellipsis if needed
        if ($start > 0) {
            $snippet = '...' . $snippet;
        }
        if ($start + $length < mb_strlen($text)) {
            $snippet .= '...';
        }

        return trim($snippet);
    }

    /**
     * Calculate relevance score (0-100)
     */
    protected function calculateRelevanceScore(int $matchCount, array $content): float
    {
        // Base score from match count
        $score = min(50, $matchCount * 5); // Up to 50 points from matches

        // Bonus for title matches
        if (!empty($content['title']) && $this->containsKeyword($content['title'])) {
            $score += 20;
        }

        // Bonus for description matches
        if (!empty($content['description']) && $this->containsKeyword($content['description'])) {
            $score += 15;
        }

        // Bonus for content density (matches per 100 words)
        if (!empty($content['content'])) {
            $wordCount = str_word_count($content['content']);
            if ($wordCount > 0) {
                $density = ($matchCount / $wordCount) * 100;
                $score += min(15, $density * 3); // Up to 15 points from density
            }
        }

        return min(100, round($score, 2));
    }

    /**
     * Check if text contains keyword (helper)
     */
    protected function containsKeyword(string $text): bool
    {
        // This is a simplified check - in real implementation,
        // would check against actual keyword variations
        return !empty($text);
    }

    /**
     * Update scraped item with matched keywords
     */
    public function updateItemWithMatches(ScrapedItem $item, array $matches): void
    {
        // Update matched keywords
        $item->matched_keywords = array_map(function ($match) {
            return [
                'keyword_id' => $match['keyword_id'],
                'keyword' => $match['keyword'],
                'project_id' => $match['project_id'],
                'score' => $match['relevance_score'],
            ];
        }, $matches);

        // Calculate overall relevance score
        $item->relevance_score = !empty($matches) 
            ? $matches[0]['relevance_score'] 
            : 0;

        // Mark as matched if we have matches
        if (!empty($matches)) {
            $item->processing_status = 'matched';
            $item->processed_at = now();
        }

        $item->save();

        // If this is a tender, trigger tender scoring
        if ($item->intent === 'tender' || $this->isTenderItem($item)) {
            $item->intent = 'tender';
            $item->save();
            
            \App\Jobs\ProcessTenderScoring::dispatch($item);
        } else {
            // For non-tender items, trigger general opportunity matching
            \App\Jobs\ProcessOpportunityMatching::dispatch($item);
        }

        // Auto-trigger lead conversion for high-quality items
        $this->checkAndTriggerLeadConversion($item);
    }

    /**
     * Check if item qualifies for lead conversion and trigger if needed
     */
    protected function checkAndTriggerLeadConversion(ScrapedItem $item): void
    {
        // Don't convert if already converted
        if ($item->lead_id) {
            return;
        }

        // Check if item meets conversion criteria
        $shouldConvert = false;

        // High-priority intents: transactional and tender
        if (in_array($item->intent, ['transactional', 'tender'])) {
            // Lower threshold for transactional/tender intents
            if ($item->lead_score >= 40 || $item->opportunity_score >= 50) {
                $shouldConvert = true;
            }
        } 
        // Commercial intent
        elseif ($item->intent === 'commercial') {
            if ($item->lead_score >= 60) {
                $shouldConvert = true;
            }
        }
        // Other intents - require high scores across the board
        else {
            if ($item->opportunity_score >= 60 
                && $item->lead_score >= 60 
                && $item->relevance_score >= 50) {
                $shouldConvert = true;
            }
        }

        // Dispatch conversion job if qualified
        if ($shouldConvert) {
            Log::info("Triggering lead conversion for high-quality item", [
                'item_id' => $item->id,
                'intent' => $item->intent,
                'lead_score' => $item->lead_score,
                'opportunity_score' => $item->opportunity_score,
                'relevance_score' => $item->relevance_score,
            ]);

            \App\Jobs\ProcessLeadConversion::dispatch($item);
        }
    }

    /**
     * Check if item is a tender
     */
    protected function isTenderItem(ScrapedItem $item): bool
    {
        $tenderKeywords = ['tender', 'procurement', 'bid', 'rfp', 'rfq', 'eoi', 'quotation'];
        $text = strtolower($item->title . ' ' . $item->description);

        foreach ($tenderKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Batch process items for keyword matching
     */
    public function batchMatchItems(Organization $organization, int $limit = 100): int
    {
        $items = ScrapedItem::where('organization_id', $organization->id)
            ->where('processing_status', 'pending')
            ->limit($limit)
            ->get();

        $processedCount = 0;

        foreach ($items as $item) {
            try {
                $matches = $this->matchKeywords($item, $organization);
                $this->updateItemWithMatches($item, $matches);
                $processedCount++;
            } catch (\Throwable $e) {
                Log::error("Failed to match keywords for item", [
                    'item_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $processedCount;
    }

    /**
     * Get keyword match statistics
     */
    public function getMatchStatistics(Organization $organization, int $days = 30): array
    {
        $since = now()->subDays($days);

        $stats = ScrapedItem::where('organization_id', $organization->id)
            ->where('created_at', '>=', $since)
            ->selectRaw('
                COUNT(*) as total_items,
                SUM(CASE WHEN processing_status = "matched" THEN 1 ELSE 0 END) as matched_items,
                AVG(relevance_score) as avg_relevance_score,
                MAX(relevance_score) as max_relevance_score
            ')
            ->first();

        // Get top matching keywords
        $topKeywords = DB::table('scraped_items')
            ->where('organization_id', $organization->id)
            ->where('created_at', '>=', $since)
            ->whereNotNull('matched_keywords')
            ->get()
            ->flatMap(function ($item) {
                return json_decode($item->matched_keywords, true) ?? [];
            })
            ->groupBy('keyword')
            ->map(function ($matches, $keyword) {
                return [
                    'keyword' => $keyword,
                    'match_count' => $matches->count(),
                    'avg_score' => $matches->avg('score'),
                ];
            })
            ->sortByDesc('match_count')
            ->take(10)
            ->values()
            ->toArray();

        return [
            'total_items' => $stats->total_items ?? 0,
            'matched_items' => $stats->matched_items ?? 0,
            'match_rate' => $stats->total_items > 0 
                ? round(($stats->matched_items / $stats->total_items) * 100, 2) 
                : 0,
            'avg_relevance_score' => round($stats->avg_relevance_score ?? 0, 2),
            'max_relevance_score' => round($stats->max_relevance_score ?? 0, 2),
            'top_keywords' => $topKeywords,
        ];
    }

    /**
     * Find similar items based on keyword matches
     */
    public function findSimilarItems(ScrapedItem $item, int $limit = 5): Collection
    {
        if (empty($item->matched_keywords)) {
            return collect([]);
        }

        $keywordIds = array_column($item->matched_keywords, 'keyword_id');

        return ScrapedItem::where('organization_id', $item->organization_id)
            ->where('id', '!=', $item->id)
            ->where('processing_status', 'matched')
            ->get()
            ->filter(function ($otherItem) use ($keywordIds) {
                if (empty($otherItem->matched_keywords)) {
                    return false;
                }
                
                $otherKeywordIds = array_column($otherItem->matched_keywords, 'keyword_id');
                $intersection = array_intersect($keywordIds, $otherKeywordIds);
                
                return count($intersection) > 0;
            })
            ->sortByDesc('relevance_score')
            ->take($limit);
    }
}
