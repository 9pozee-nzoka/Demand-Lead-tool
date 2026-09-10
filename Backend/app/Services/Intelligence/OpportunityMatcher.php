<?php

namespace App\Services\Intelligence;

use App\Models\Keyword;
use App\Models\ScrapedItem;
use App\Models\Organization;
use App\Services\AI\OpenAIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * OpportunityMatcher - Identifies business opportunities with explainability
 * 
 * Analyzes scraped items to determine if they represent genuine business
 * opportunities and provides human-readable explanations of why.
 */
class OpportunityMatcher
{
    protected OpenAIService $aiService;

    /**
     * Opportunity factors and their weights
     */
    protected array $opportunityFactors = [
        'timing' => 0.25,           // Is this time-sensitive?
        'value' => 0.25,            // What's the potential value?
        'relevance' => 0.20,        // How relevant to business?
        'competition' => 0.15,      // Competition level
        'actionability' => 0.15,    // Can we act on this?
    ];

    public function __construct(OpenAIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Analyze if item represents a business opportunity
     */
    public function analyze(ScrapedItem $item, Organization $organization): array
    {
        // Check cache
        $cacheKey = "opportunity_analysis_{$item->content_hash}_{$organization->id}";
        
        if ($cached = Cache::get($cacheKey)) {
            Log::info("Using cached opportunity analysis", ['item_id' => $item->id]);
            return $cached;
        }

        // Gather context
        $context = $this->gatherContext($item, $organization);

        // Rule-based analysis (fast)
        $ruleBasedResult = $this->ruleBasedAnalysis($item, $context);

        // If confidence is high, use rule-based result
        if ($ruleBasedResult['confidence'] >= 0.85) {
            Cache::put($cacheKey, $ruleBasedResult, now()->addDays(3));
            return $ruleBasedResult;
        }

        // AI-powered analysis with explainability
        try {
            $aiResult = $this->aiAnalysis($item, $context);
            Cache::put($cacheKey, $aiResult, now()->addDays(3));
            return $aiResult;
        } catch (\Throwable $e) {
            Log::error("AI opportunity analysis failed", [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
            
            return $ruleBasedResult;
        }
    }

    /**
     * Gather context for opportunity analysis
     */
    protected function gatherContext(ScrapedItem $item, Organization $organization): array
    {
        return [
            'matched_keywords' => $item->matched_keywords ?? [],
            'intent' => $item->intent,
            'extracted_entities' => $item->metadata['extracted_entities'] ?? [],
            'organization_keywords' => Keyword::whereHas('project', function ($q) use ($organization) {
                $q->where('organization_id', $organization->id);
            })
            ->where('status', 'active')
            ->pluck('keyword')
            ->toArray(),
            'source_type' => $item->sourceScraper->type ?? 'unknown',
            'item_metadata' => $item->metadata ?? [],
        ];
    }

    /**
     * Rule-based opportunity analysis
     */
    protected function ruleBasedAnalysis(ScrapedItem $item, array $context): array
    {
        $factors = [
            'timing' => 0,
            'value' => 0,
            'relevance' => 0,
            'competition' => 0,
            'actionability' => 0,
        ];

        $explanations = [];

        // Timing factor
        if ($item->intent === 'tender') {
            $metadata = $item->metadata ?? [];
            $closingDate = $metadata['closing_date'] ?? null;
            
            if ($closingDate) {
                $daysUntilClose = now()->diffInDays($closingDate, false);
                
                if ($daysUntilClose > 0 && $daysUntilClose <= 30) {
                    $factors['timing'] = 1.0;
                    $explanations['timing'] = "Tender closes in {$daysUntilClose} days - immediate action required";
                } elseif ($daysUntilClose > 30 && $daysUntilClose <= 60) {
                    $factors['timing'] = 0.7;
                    $explanations['timing'] = "Tender closes in {$daysUntilClose} days - good preparation window";
                } else {
                    $factors['timing'] = 0.3;
                    $explanations['timing'] = "Tender timeline is either too tight or too far out";
                }
            }
        } elseif ($item->intent === 'transactional') {
            $factors['timing'] = 0.95;
            $explanations['timing'] = "Lead is ready to buy now - high urgency";
        } elseif ($item->intent === 'commercial') {
            $factors['timing'] = 0.6;
            $explanations['timing'] = "Prospect is actively researching - good timing for engagement";
        }

        // Value factor
        if (!empty($item->metadata['value']['amount'])) {
            $amount = $item->metadata['value']['amount'];
            
            if ($amount >= 10000000) { // 10M+
                $factors['value'] = 1.0;
                $explanations['value'] = "High-value opportunity: " . number_format($amount) . " KES";
            } elseif ($amount >= 2000000) { // 2M+
                $factors['value'] = 0.75;
                $explanations['value'] = "Medium-value opportunity: " . number_format($amount) . " KES";
            } else {
                $factors['value'] = 0.5;
                $explanations['value'] = "Entry-level opportunity: " . number_format($amount) . " KES";
            }
        } elseif ($item->intent === 'transactional') {
            $factors['value'] = 0.7;
            $explanations['value'] = "Direct lead inquiry - potential for conversion";
        }

        // Relevance factor
        $keywordMatches = count($item->matched_keywords ?? []);
        if ($keywordMatches >= 3) {
            $factors['relevance'] = 1.0;
            $explanations['relevance'] = "Matches {$keywordMatches} of your tracked keywords - highly relevant";
        } elseif ($keywordMatches >= 2) {
            $factors['relevance'] = 0.75;
            $explanations['relevance'] = "Matches {$keywordMatches} tracked keywords - good fit";
        } elseif ($keywordMatches >= 1) {
            $factors['relevance'] = 0.5;
            $explanations['relevance'] = "Matches {$keywordMatches} tracked keyword";
        } else {
            $factors['relevance'] = 0.2;
            $explanations['relevance'] = "No direct keyword matches";
        }

        // Competition factor (inverse - lower competition is better)
        if ($item->intent === 'tender') {
            $metadata = $item->metadata ?? [];
            $eligibility = $metadata['eligibility'] ?? 'open';
            
            if ($eligibility === 'restricted') {
                $factors['competition'] = 0.8;
                $explanations['competition'] = "Restricted tender - lower competition";
            } else {
                $factors['competition'] = 0.5;
                $explanations['competition'] = "Open tender - expect higher competition";
            }
        } elseif ($item->intent === 'transactional') {
            $factors['competition'] = 0.9;
            $explanations['competition'] = "Direct inquiry - you're first to respond";
        } else {
            $factors['competition'] = 0.6;
            $explanations['competition'] = "Standard competitive environment";
        }

        // Actionability factor
        $hasContact = !empty($item->metadata['email']) || !empty($item->metadata['phone']);
        $hasOrg = !empty($item->metadata['organization']);
        
        if ($hasContact && $hasOrg) {
            $factors['actionability'] = 1.0;
            $explanations['actionability'] = "Has contact details and organization - can reach out immediately";
        } elseif ($hasContact || $hasOrg) {
            $factors['actionability'] = 0.7;
            $explanations['actionability'] = "Has some contact information - follow-up possible";
        } else {
            $factors['actionability'] = 0.3;
            $explanations['actionability'] = "Limited contact information - requires research";
        }

        // Calculate overall score
        $score = 0;
        foreach ($factors as $factor => $value) {
            $score += $value * $this->opportunityFactors[$factor];
        }
        $score = round($score * 100, 2);

        // Determine if it's an opportunity
        $isOpportunity = $score >= 50;
        $confidence = $this->calculateConfidence($factors);

        return [
            'is_opportunity' => $isOpportunity,
            'score' => $score,
            'confidence' => $confidence,
            'factors' => $factors,
            'explanations' => $explanations,
            'recommendation' => $this->generateRecommendation($score, $item->intent),
            'method' => 'rule_based',
        ];
    }

    /**
     * AI-powered opportunity analysis with explainability
     */
    protected function aiAnalysis(ScrapedItem $item, array $context): array
    {
        $prompt = $this->buildAnalysisPrompt($item, $context);

        $response = $this->aiService->chat([
            [
                'role' => 'system',
                'content' => 'You are an expert business analyst specializing in opportunity identification. Provide clear, actionable explanations. Respond only with valid JSON.',
            ],
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ], [
            'temperature' => 0.3,
            'max_tokens' => 800,
        ]);

        $result = json_decode($response, true);

        if (!$result || !isset($result['is_opportunity'])) {
            throw new \RuntimeException("Invalid AI analysis response");
        }

        return array_merge($result, ['method' => 'ai']);
    }

    /**
     * Build analysis prompt
     */
    protected function buildAnalysisPrompt(ScrapedItem $item, array $context): string
    {
        $title = mb_substr($item->title, 0, 200);
        $description = mb_substr($item->description ?? '', 0, 500);
        $intent = $item->intent;
        $keywords = implode(', ', array_column($context['matched_keywords'], 'keyword'));
        $entities = json_encode($context['extracted_entities'], JSON_PRETTY_PRINT);

        return <<<PROMPT
Analyze whether this represents a business opportunity and explain why.

Title: {$title}

Description: {$description}

Intent: {$intent}

Matched Keywords: {$keywords}

Extracted Entities: {$entities}

Evaluate these factors:
1. Timing - Is this time-sensitive? Can we act now?
2. Value - What's the potential business value?
3. Relevance - How relevant to our business?
4. Competition - What's the competition level?
5. Actionability - Can we take immediate action?

Respond with JSON in this format:
{
  "is_opportunity": true,
  "score": 85,
  "confidence": 0.9,
  "factors": {
    "timing": 0.9,
    "value": 0.8,
    "relevance": 0.95,
    "competition": 0.7,
    "actionability": 0.85
  },
  "explanations": {
    "timing": "Clear explanation of timing factor",
    "value": "Clear explanation of value factor",
    "relevance": "Clear explanation of relevance factor",
    "competition": "Clear explanation of competition factor",
    "actionability": "Clear explanation of actionability factor"
  },
  "recommendation": "Specific action recommendation",
  "key_insights": [
    "First key insight",
    "Second key insight"
  ]
}

Score: 0-100 (50+ is opportunity)
Factors: 0-1 scale for each
Confidence: 0-1 (how confident in this assessment)
PROMPT;
    }

    /**
     * Calculate confidence score
     */
    protected function calculateConfidence(array $factors): float
    {
        // High confidence if factors are clear (very high or very low)
        $clarity = 0;
        $count = 0;

        foreach ($factors as $value) {
            // Distance from 0.5 (uncertain) indicates clarity
            $clarity += abs($value - 0.5) * 2;
            $count++;
        }

        $avgClarity = $count > 0 ? $clarity / $count : 0;
        return round($avgClarity, 3);
    }

    /**
     * Generate recommendation based on score and intent
     */
    protected function generateRecommendation(float $score, ?string $intent): string
    {
        if ($score >= 80) {
            return match ($intent) {
                'tender' => 'HIGH PRIORITY: Review tender requirements and prepare bid immediately',
                'transactional' => 'URGENT: Contact lead within 1 hour for maximum conversion',
                'commercial' => 'Act now: Reach out with tailored solution proposal',
                default => 'Excellent opportunity - take immediate action',
            };
        }

        if ($score >= 60) {
            return match ($intent) {
                'tender' => 'Good opportunity: Review requirements and assess fit',
                'transactional' => 'Contact lead within 24 hours',
                'commercial' => 'Add to nurture pipeline with targeted content',
                default => 'Good opportunity - schedule follow-up',
            };
        }

        if ($score >= 40) {
            return 'Monitor: Keep on radar but prioritize higher-value opportunities';
        }

        return 'Low priority: Only pursue if capacity allows';
    }

    /**
     * Update item with opportunity analysis
     */
    public function updateItemWithAnalysis(ScrapedItem $item, array $analysis): void
    {
        // Update opportunity score (this feeds into lead conversion logic)
        $item->opportunity_score = $analysis['score'];

        // Store full analysis in metadata
        $metadata = $item->metadata ?? [];
        $metadata['opportunity_analysis'] = $analysis;

        $item->metadata = $metadata;
        $item->save();

        Log::info("Updated item with opportunity analysis", [
            'item_id' => $item->id,
            'is_opportunity' => $analysis['is_opportunity'],
            'score' => $analysis['score'],
            'confidence' => $analysis['confidence'],
        ]);
    }

    /**
     * Batch analyze items
     */
    public function batchAnalyze(array $items, Organization $organization): array
    {
        $results = [];

        foreach ($items as $item) {
            try {
                $results[$item->id] = $this->analyze($item, $organization);
            } catch (\Throwable $e) {
                Log::error("Failed to analyze opportunity", [
                    'item_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);
                
                $results[$item->id] = [
                    'is_opportunity' => false,
                    'score' => 0,
                    'confidence' => 0,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Get opportunity statistics
     */
    public function getOpportunityStatistics(Organization $organization, int $days = 30): array
    {
        $since = now()->subDays($days);

        $items = ScrapedItem::where('organization_id', $organization->id)
            ->where('created_at', '>=', $since)
            ->whereNotNull('opportunity_score')
            ->get();

        $stats = [
            'total_analyzed' => $items->count(),
            'opportunities_found' => $items->where('opportunity_score', '>=', 50)->count(),
            'high_priority' => $items->where('opportunity_score', '>=', 80)->count(),
            'medium_priority' => $items->whereBetween('opportunity_score', [60, 79])->count(),
            'low_priority' => $items->whereBetween('opportunity_score', [40, 59])->count(),
            'avg_opportunity_score' => round($items->avg('opportunity_score'), 2),
            'by_intent' => [],
        ];

        // Breakdown by intent
        foreach ($items->groupBy('intent') as $intent => $intentItems) {
            $stats['by_intent'][$intent] = [
                'count' => $intentItems->count(),
                'opportunities' => $intentItems->where('opportunity_score', '>=', 50)->count(),
                'avg_score' => round($intentItems->avg('opportunity_score'), 2),
            ];
        }

        $stats['opportunity_rate'] = $stats['total_analyzed'] > 0
            ? round(($stats['opportunities_found'] / $stats['total_analyzed']) * 100, 1)
            : 0;

        return $stats;
    }

    /**
     * Get top opportunities
     */
    public function getTopOpportunities(Organization $organization, int $limit = 10): \Illuminate\Support\Collection
    {
        return ScrapedItem::where('organization_id', $organization->id)
            ->where('opportunity_score', '>=', 50)
            ->whereNull('lead_id') // Not yet converted
            ->orderByDesc('opportunity_score')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $analysis = $item->metadata['opportunity_analysis'] ?? null;
                
                return [
                    'item_id' => $item->id,
                    'title' => $item->title,
                    'intent' => $item->intent,
                    'opportunity_score' => $item->opportunity_score,
                    'recommendation' => $analysis['recommendation'] ?? null,
                    'key_insights' => $analysis['key_insights'] ?? [],
                    'created_at' => $item->created_at,
                    'url' => $item->url,
                ];
            });
    }
}
