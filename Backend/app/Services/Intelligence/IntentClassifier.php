<?php

namespace App\Services\Intelligence;

use App\Models\ScrapedItem;
use App\Services\AI\OpenAIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * IntentClassifier - AI-powered intent classification for scraped content
 * 
 * Classifies content into: informational, commercial, transactional, tender, navigational
 * Uses OpenAI for intelligent classification with confidence scoring
 */
class IntentClassifier
{
    protected OpenAIService $aiService;

    /**
     * Available intent types
     */
    protected array $intentTypes = [
        'informational' => 'User is researching or learning about a topic',
        'commercial' => 'User is investigating products/services before purchase',
        'transactional' => 'User is ready to purchase or take action immediately',
        'tender' => 'Government or corporate procurement opportunity',
        'navigational' => 'User is looking for a specific website or page',
    ];

    public function __construct(OpenAIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Classify the intent of a scraped item
     */
    public function classify(ScrapedItem $item): array
    {
        // Check cache first
        $cacheKey = "intent_classification_{$item->content_hash}";
        
        if ($cached = Cache::get($cacheKey)) {
            Log::info("Using cached intent classification", ['item_id' => $item->id]);
            return $cached;
        }

        // Try rule-based classification first (fast path)
        $ruleBasedResult = $this->ruleBasedClassification($item);
        
        if ($ruleBasedResult['confidence'] >= 0.85) {
            Cache::put($cacheKey, $ruleBasedResult, now()->addDays(7));
            return $ruleBasedResult;
        }

        // Fall back to AI classification for uncertain cases
        try {
            $aiResult = $this->aiClassification($item);
            Cache::put($cacheKey, $aiResult, now()->addDays(7));
            return $aiResult;
        } catch (\Throwable $e) {
            Log::error("AI classification failed, using rule-based fallback", [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
            
            return $ruleBasedResult;
        }
    }

    /**
     * Rule-based classification (fast, no API calls)
     */
    protected function ruleBasedClassification(ScrapedItem $item): array
    {
        $text = strtolower($item->title . ' ' . $item->description);
        $scores = [];

        // Tender detection
        $tenderKeywords = [
            'tender', 'procurement', 'bid', 'rfp', 'rfq', 'eoi', 'quotation',
            'expression of interest', 'request for proposal', 'request for quotation'
        ];
        $scores['tender'] = $this->calculateKeywordScore($text, $tenderKeywords);

        // Transactional detection
        $transactionalKeywords = [
            'buy', 'purchase', 'order', 'book', 'subscribe', 'register',
            'download', 'get quote', 'contact us', 'request demo', 'free trial',
            'sign up', 'apply now', 'get started'
        ];
        $scores['transactional'] = $this->calculateKeywordScore($text, $transactionalKeywords);

        // Commercial detection
        $commercialKeywords = [
            'review', 'comparison', 'vs', 'best', 'top', 'pricing', 'cost',
            'features', 'benefits', 'alternative', 'solution', 'service provider'
        ];
        $scores['commercial'] = $this->calculateKeywordScore($text, $commercialKeywords);

        // Navigational detection
        $navigationalKeywords = [
            'login', 'dashboard', 'portal', 'official website', 'contact',
            'about us', 'careers', 'support'
        ];
        $scores['navigational'] = $this->calculateKeywordScore($text, $navigationalKeywords);

        // Informational is default
        $scores['informational'] = 0.5;

        // Get highest scoring intent
        arsort($scores);
        $topIntent = array_key_first($scores);
        $confidence = $scores[$topIntent];

        return [
            'intent' => $topIntent,
            'confidence' => round($confidence, 3),
            'all_scores' => $scores,
            'method' => 'rule_based',
        ];
    }

    /**
     * AI-powered classification using OpenAI
     */
    protected function aiClassification(ScrapedItem $item): array
    {
        $prompt = $this->buildClassificationPrompt($item);

        $response = $this->aiService->chat([
            [
                'role' => 'system',
                'content' => 'You are an expert at classifying content intent for business intelligence. Respond only with valid JSON.',
            ],
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ], [
            'temperature' => 0.3,
            'max_tokens' => 300,
        ]);

        $result = json_decode($response, true);

        if (!$result || !isset($result['intent'])) {
            throw new \RuntimeException("Invalid AI classification response");
        }

        return [
            'intent' => $result['intent'],
            'confidence' => $result['confidence'] ?? 0.7,
            'reasoning' => $result['reasoning'] ?? null,
            'all_scores' => $result['scores'] ?? [],
            'method' => 'ai',
        ];
    }

    /**
     * Build classification prompt
     */
    protected function buildClassificationPrompt(ScrapedItem $item): string
    {
        $intentDescriptions = json_encode($this->intentTypes, JSON_PRETTY_PRINT);
        
        $title = mb_substr($item->title, 0, 200);
        $description = mb_substr($item->description ?? '', 0, 500);
        $content = mb_substr($item->content ?? '', 0, 1000);

        return <<<PROMPT
Classify the intent of this content.

Title: {$title}

Description: {$description}

Content excerpt: {$content}

Available intent types:
{$intentDescriptions}

Respond with JSON in this exact format:
{
  "intent": "the primary intent (one of: informational, commercial, transactional, tender, navigational)",
  "confidence": 0.95,
  "reasoning": "Brief explanation of why this intent was chosen",
  "scores": {
    "informational": 0.2,
    "commercial": 0.3,
    "transactional": 0.95,
    "tender": 0.1,
    "navigational": 0.05
  }
}
PROMPT;
    }

    /**
     * Calculate keyword score for rule-based classification
     */
    protected function calculateKeywordScore(string $text, array $keywords): float
    {
        $matchCount = 0;
        $totalWeight = 0;

        foreach ($keywords as $keyword) {
            if (str_contains($text, strtolower($keyword))) {
                $matchCount++;
                // Longer, more specific keywords get higher weight
                $totalWeight += mb_strlen($keyword) / 10;
            }
        }

        if ($matchCount === 0) {
            return 0;
        }

        // Score based on matches and specificity
        $score = min(1.0, ($matchCount * 0.2) + ($totalWeight * 0.1));
        
        return round($score, 3);
    }

    /**
     * Batch classify multiple items
     */
    public function batchClassify(array $items, int $batchSize = 20): array
    {
        $results = [];
        $chunks = array_chunk($items, $batchSize);

        foreach ($chunks as $chunk) {
            foreach ($chunk as $item) {
                try {
                    $results[$item->id] = $this->classify($item);
                } catch (\Throwable $e) {
                    Log::error("Failed to classify item", [
                        'item_id' => $item->id,
                        'error' => $e->getMessage(),
                    ]);
                    
                    $results[$item->id] = [
                        'intent' => 'informational',
                        'confidence' => 0.3,
                        'error' => $e->getMessage(),
                        'method' => 'error_fallback',
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Update item with classified intent
     */
    public function updateItemWithIntent(ScrapedItem $item, array $classification): void
    {
        $metadata = $item->metadata ?? [];
        $metadata['intent_classification'] = $classification;

        $item->intent = $classification['intent'];
        $item->metadata = $metadata;
        $item->save();

        Log::info("Updated item with intent classification", [
            'item_id' => $item->id,
            'intent' => $classification['intent'],
            'confidence' => $classification['confidence'],
            'method' => $classification['method'],
        ]);
    }

    /**
     * Get intent statistics for an organization
     */
    public function getIntentStatistics(int $organizationId, int $days = 30): array
    {
        $since = now()->subDays($days);

        $items = ScrapedItem::where('organization_id', $organizationId)
            ->where('created_at', '>=', $since)
            ->get();

        $stats = [
            'total' => $items->count(),
            'by_intent' => [],
            'avg_confidence' => 0,
            'method_breakdown' => [],
        ];

        foreach ($items as $item) {
            $intent = $item->intent ?? 'unknown';
            $stats['by_intent'][$intent] = ($stats['by_intent'][$intent] ?? 0) + 1;

            $classification = $item->metadata['intent_classification'] ?? null;
            if ($classification) {
                $method = $classification['method'] ?? 'unknown';
                $stats['method_breakdown'][$method] = ($stats['method_breakdown'][$method] ?? 0) + 1;
            }
        }

        // Calculate percentages
        foreach ($stats['by_intent'] as $intent => $count) {
            $stats['by_intent'][$intent] = [
                'count' => $count,
                'percentage' => round(($count / $stats['total']) * 100, 1),
            ];
        }

        return $stats;
    }

    /**
     * Validate and correct intent if needed
     */
    public function validateIntent(ScrapedItem $item): bool
    {
        $metadata = $item->metadata ?? [];
        $classification = $metadata['intent_classification'] ?? null;

        if (!$classification) {
            return false;
        }

        // If confidence is low, reclassify
        if (($classification['confidence'] ?? 0) < 0.5) {
            $newClassification = $this->classify($item);
            $this->updateItemWithIntent($item, $newClassification);
            return true;
        }

        return true;
    }
}
