<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI Service
 * 
 * OpenAI API integration for:
 * - Intent classification
 * - Keyword clustering
 * - Opportunity explanations
 * - Content generation
 * 
 * Sprint 7
 */
class AIService
{
    protected string $apiKey;
    protected ?string $organization;
    protected string $model;
    protected int $maxTokens;
    protected float $temperature;

    public function __construct()
    {
        $this->apiKey = config('services.openai.key') ?? '';
        $this->organization = config('services.openai.organization');
        $this->model = config('services.openai.model', 'gpt-4o-mini');
        $this->maxTokens = config('services.openai.max_tokens', 500);
        $this->temperature = config('services.openai.temperature', 0.7);
    }

    /**
     * Classify search intent for a keyword
     * 
     * Returns: informational, navigational, commercial, transactional, local
     */
    public function classifyIntent(string $keyword, ?string $context = null): array
    {
        $prompt = "Classify the search intent for this keyword: '{$keyword}'";
        
        if ($context) {
            $prompt .= "\nContext: {$context}";
        }

        $prompt .= "\n\nReturn ONLY a JSON object with these fields:
- intent: one of [informational, navigational, commercial, transactional, local]
- confidence: number 0-100
- explanation: brief 1-sentence reason
- buyer_journey_stage: one of [awareness, consideration, decision]

Example: {\"intent\":\"transactional\",\"confidence\":90,\"explanation\":\"Contains purchase-oriented words\",\"buyer_journey_stage\":\"decision\"}";

        try {
            $response = $this->complete($prompt, [
                'temperature' => 0.3, // Lower for classification
                'max_tokens' => 150,
            ]);

            return $this->parseJsonResponse($response, [
                'intent' => 'informational',
                'confidence' => 50,
                'explanation' => 'Unable to classify',
                'buyer_journey_stage' => 'awareness',
            ]);
        } catch (\Exception $e) {
            Log::error('AI intent classification failed', [
                'keyword' => $keyword,
                'error' => $e->getMessage(),
            ]);

            return [
                'intent' => 'informational',
                'confidence' => 0,
                'explanation' => 'Classification failed',
                'buyer_journey_stage' => 'awareness',
            ];
        }
    }

    /**
     * Cluster related keywords
     */
    public function clusterKeywords(array $keywords, int $maxClusters = 5): array
    {
        if (empty($keywords)) {
            return [];
        }

        $keywordList = implode(', ', array_map(fn($k) => $k['term'] ?? $k, $keywords));

        $prompt = "Analyze these keywords and group them into {$maxClusters} or fewer semantic clusters:\n{$keywordList}\n\n";
        $prompt .= "Return ONLY a JSON array where each cluster has:
- name: short cluster name (2-4 words)
- keywords: array of keywords in this cluster
- theme: one sentence describing the common theme
- intent: primary intent (informational/commercial/transactional/local)

Example: [{\"name\":\"Product Research\",\"keywords\":[\"best laptops\",\"laptop reviews\"],\"theme\":\"Users researching laptop purchases\",\"intent\":\"commercial\"}]";

        try {
            $response = $this->complete($prompt, [
                'temperature' => 0.5,
                'max_tokens' => 800,
            ]);

            $clusters = $this->parseJsonResponse($response, []);
            
            return is_array($clusters) ? $clusters : [];
        } catch (\Exception $e) {
            Log::error('AI keyword clustering failed', [
                'keyword_count' => count($keywords),
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Generate explanation for opportunity score
     */
    public function explainOpportunityScore(array $scoreBreakdown): string
    {
        $prompt = "Explain this opportunity score in 2-3 sentences for a business user:\n\n";
        $prompt .= json_encode($scoreBreakdown, JSON_PRETTY_PRINT);
        $prompt .= "\n\nFocus on the strongest factors and actionable insights. Be specific and concise.";

        try {
            $response = $this->complete($prompt, [
                'temperature' => 0.7,
                'max_tokens' => 150,
            ]);

            return trim($response);
        } catch (\Exception $e) {
            Log::error('AI opportunity explanation failed', [
                'error' => $e->getMessage(),
            ]);

            return 'Unable to generate explanation.';
        }
    }

    /**
     * Analyze market gaps
     */
    public function analyzeMarketGaps(array $keywords, array $competitors = []): array
    {
        $keywordList = implode(', ', array_map(fn($k) => $k['term'] ?? $k, $keywords));
        $competitorList = !empty($competitors) ? implode(', ', $competitors) : 'none provided';

        $prompt = "Analyze these keywords for market gaps and opportunities:\nKeywords: {$keywordList}\nCompetitors: {$competitorList}\n\n";
        $prompt .= "Return ONLY a JSON object with:
- gaps: array of 3-5 specific market gaps (each is a string describing the gap)
- opportunities: array of 3-5 specific opportunities (each is a string)
- recommendations: array of 3-5 specific action recommendations (each is a string)

Example: {\"gaps\":[\"No content targeting beginners\"],\"opportunities\":[\"Create beginner guides\"],\"recommendations\":[\"Publish 101-style content\"]}";

        try {
            $response = $this->complete($prompt, [
                'temperature' => 0.6,
                'max_tokens' => 600,
            ]);

            return $this->parseJsonResponse($response, [
                'gaps' => [],
                'opportunities' => [],
                'recommendations' => [],
            ]);
        } catch (\Exception $e) {
            Log::error('AI market gap analysis failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'gaps' => [],
                'opportunities' => [],
                'recommendations' => [],
            ];
        }
    }

    /**
     * Generate landing page content suggestions
     */
    public function generateLandingPageContent(string $keyword, array $context = []): array
    {
        $prompt = "Generate landing page content ideas for keyword: '{$keyword}'\n";
        
        if (!empty($context)) {
            $prompt .= "Context: " . json_encode($context) . "\n";
        }

        $prompt .= "\nReturn ONLY a JSON object with:
- headline: compelling H1 (10 words max)
- subheadline: supporting text (20 words max)
- value_propositions: array of 3 key benefits (each 10 words max)
- cta_text: call-to-action button text (3-5 words)
- content_sections: array of 3 section titles

Example: {\"headline\":\"Best Laptops 2026\",\"subheadline\":\"Expert reviews\",\"value_propositions\":[\"Compare top brands\"],\"cta_text\":\"View Reviews\",\"content_sections\":[\"Top Picks\"]}";

        try {
            $response = $this->complete($prompt, [
                'temperature' => 0.8,
                'max_tokens' => 400,
            ]);

            return $this->parseJsonResponse($response, [
                'headline' => '',
                'subheadline' => '',
                'value_propositions' => [],
                'cta_text' => '',
                'content_sections' => [],
            ]);
        } catch (\Exception $e) {
            Log::error('AI landing page generation failed', [
                'keyword' => $keyword,
                'error' => $e->getMessage(),
            ]);

            return [
                'headline' => '',
                'subheadline' => '',
                'value_propositions' => [],
                'cta_text' => '',
                'content_sections' => [],
            ];
        }
    }

    /**
     * Analyze competitor positioning
     */
    public function analyzeCompetitors(array $competitors, array $keywords = []): array
    {
        $competitorList = implode(', ', $competitors);
        $keywordContext = !empty($keywords) ? implode(', ', array_slice($keywords, 0, 10)) : '';

        $prompt = "Analyze these competitors: {$competitorList}\n";
        
        if ($keywordContext) {
            $prompt .= "In context of keywords: {$keywordContext}\n";
        }

        $prompt .= "\nReturn ONLY a JSON object with:
- strengths: array of 3-5 competitor strengths
- weaknesses: array of 3-5 competitor weaknesses
- positioning_gaps: array of 3-5 gaps in market positioning
- differentiation_opportunities: array of 3-5 ways to differentiate

Each item should be a concise string (15 words max).";

        try {
            $response = $this->complete($prompt, [
                'temperature' => 0.6,
                'max_tokens' => 500,
            ]);

            return $this->parseJsonResponse($response, [
                'strengths' => [],
                'weaknesses' => [],
                'positioning_gaps' => [],
                'differentiation_opportunities' => [],
            ]);
        } catch (\Exception $e) {
            Log::error('AI competitor analysis failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'strengths' => [],
                'weaknesses' => [],
                'positioning_gaps' => [],
                'differentiation_opportunities' => [],
            ];
        }
    }

    /**
     * Core completion method
     */
    protected function complete(string $prompt, array $options = []): string
    {
        if (empty($this->apiKey)) {
            throw new \Exception('OpenAI API key not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])
        ->timeout(30)
        ->post('https://api.openai.com/v1/chat/completions', [
            'model' => $options['model'] ?? $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a marketing intelligence assistant. Always return valid JSON when requested. Be concise and actionable.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
            'temperature' => $options['temperature'] ?? $this->temperature,
        ]);

        if (!$response->successful()) {
            throw new \Exception('OpenAI API request failed: ' . $response->body());
        }

        $data = $response->json();

        if (!isset($data['choices'][0]['message']['content'])) {
            throw new \Exception('Invalid OpenAI API response structure');
        }

        return $data['choices'][0]['message']['content'];
    }

    /**
     * Parse JSON response with fallback
     */
    protected function parseJsonResponse(string $response, $default = null)
    {
        // Remove markdown code blocks if present
        $response = preg_replace('/```json\s*|\s*```/', '', $response);
        $response = trim($response);

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Failed to parse AI JSON response', [
                'response' => $response,
                'error' => json_last_error_msg(),
            ]);
            
            return $default;
        }

        return $decoded;
    }

    /**
     * Check if AI service is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Batch process multiple items efficiently
     */
    public function batchClassifyIntents(array $keywords): array
    {
        $results = [];

        foreach ($keywords as $keyword) {
            $term = is_array($keyword) ? ($keyword['term'] ?? '') : $keyword;
            
            if (empty($term)) {
                continue;
            }

            $results[$term] = $this->classifyIntent($term);
            
            // Rate limiting: small delay between requests
            usleep(100000); // 0.1 second
        }

        return $results;
    }
}
