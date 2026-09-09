<?php

namespace App\Services\LandingPages;

use App\Models\LandingPage;
use App\Models\Opportunity;
use App\Models\Keyword;
use App\Services\AI\AIService;
use Illuminate\Support\Str;

class LandingPageGenerator
{
    protected AIService $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Generate a complete landing page from an opportunity
     */
    public function generateFromOpportunity(Opportunity $opportunity): LandingPage
    {
        $keyword = $opportunity->keyword;
        
        // Generate AI content
        $content = $this->generateContent($keyword, $opportunity);
        
        // Create landing page
        return LandingPage::create([
            'organization_id' => $opportunity->organization_id,
            'opportunity_id' => $opportunity->id,
            'keyword_id' => $keyword->id,
            'title' => $content['headline'],
            'slug' => $this->generateUniqueSlug($content['headline']),
            'headline' => $content['headline'],
            'subheadline' => $content['subheadline'],
            'hero_content' => $content['hero_content'],
            'benefits' => $content['benefits'],
            'features' => $content['features'],
            'social_proof' => $content['social_proof'],
            'cta_text' => $content['cta_text'],
            'cta_subtext' => $content['cta_subtext'],
            'faq' => $content['faq'],
            'meta_title' => $content['meta_title'],
            'meta_description' => $content['meta_description'],
            'template' => 'default',
            'status' => 'draft',
            'seo_score' => $this->calculateSeoScore($content, $keyword),
        ]);
    }

    /**
     * Generate AI-powered content for landing page
     */
    protected function generateContent(Keyword $keyword, Opportunity $opportunity): array
    {
        $prompt = $this->buildContentPrompt($keyword, $opportunity);
        
        $response = $this->aiService->chat($prompt, [
            'max_tokens' => 2000,
            'temperature' => 0.8,
        ]);

        return $this->parseAIResponse($response);
    }

    /**
     * Build comprehensive prompt for AI content generation
     */
    protected function buildContentPrompt(Keyword $keyword, Opportunity $opportunity): string
    {
        return <<<PROMPT
Generate comprehensive landing page content for a business opportunity:

KEYWORD: {$keyword->keyword}
SEARCH VOLUME: {$keyword->search_volume}
TREND: {$keyword->trend_state}
OPPORTUNITY SCORE: {$opportunity->opportunity_score}/100
TARGET LOCATION: {$keyword->location}

Create a high-converting landing page with:

1. HEADLINE (10-15 words): Attention-grabbing, benefit-focused
2. SUBHEADLINE (20-30 words): Supporting detail, builds interest
3. HERO CONTENT (50-80 words): Compelling introduction paragraph
4. BENEFITS (3-5 bullets): Customer-centric benefits, not features
5. FEATURES (3-4 points): Product/service features with brief descriptions
6. SOCIAL PROOF (2-3 testimonial templates): Realistic customer quotes
7. CTA TEXT: Primary call-to-action button text (2-5 words)
8. CTA SUBTEXT: Supporting text under CTA (10-15 words)
9. FAQ (3-5 questions): Common objections addressed
10. META TITLE (50-60 chars): SEO-optimized page title
11. META DESCRIPTION (150-160 chars): SEO description with keyword

Format as JSON:
{
  "headline": "...",
  "subheadline": "...",
  "hero_content": "...",
  "benefits": ["benefit 1", "benefit 2", "benefit 3"],
  "features": [
    {"title": "Feature 1", "description": "..."},
    {"title": "Feature 2", "description": "..."}
  ],
  "social_proof": [
    {"name": "John D.", "role": "Business Owner", "quote": "..."},
    {"name": "Sarah M.", "role": "Manager", "quote": "..."}
  ],
  "cta_text": "Get Started Now",
  "cta_subtext": "No credit card required",
  "faq": [
    {"question": "...", "answer": "..."},
    {"question": "...", "answer": "..."}
  ],
  "meta_title": "...",
  "meta_description": "..."
}

Make it persuasive, professional, and optimized for conversions.
PROMPT;
    }

    /**
     * Parse AI response into structured content
     */
    protected function parseAIResponse(string $response): array
    {
        // Try to extract JSON from response
        $json = $this->extractJson($response);
        
        if ($json) {
            return [
                'headline' => $json['headline'] ?? 'Transform Your Business Today',
                'subheadline' => $json['subheadline'] ?? 'Discover solutions that drive real results',
                'hero_content' => $json['hero_content'] ?? '',
                'benefits' => json_encode($json['benefits'] ?? []),
                'features' => json_encode($json['features'] ?? []),
                'social_proof' => json_encode($json['social_proof'] ?? []),
                'cta_text' => $json['cta_text'] ?? 'Get Started',
                'cta_subtext' => $json['cta_subtext'] ?? 'Join thousands of satisfied customers',
                'faq' => json_encode($json['faq'] ?? []),
                'meta_title' => $json['meta_title'] ?? $json['headline'] ?? 'Landing Page',
                'meta_description' => $json['meta_description'] ?? $json['subheadline'] ?? '',
            ];
        }

        // Fallback: Use template
        return $this->getDefaultContent();
    }

    /**
     * Extract JSON from AI response
     */
    protected function extractJson(string $response): ?array
    {
        // Try to find JSON in the response
        if (preg_match('/\{.*\}/s', $response, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // Try decoding the whole response
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return null;
    }

    /**
     * Get default content template
     */
    protected function getDefaultContent(): array
    {
        return [
            'headline' => 'Transform Your Business Today',
            'subheadline' => 'Join thousands of businesses already benefiting from our solution',
            'hero_content' => 'Discover how our innovative solution can help you achieve your business goals faster and more efficiently than ever before.',
            'benefits' => json_encode([
                'Save time with automated processes',
                'Increase revenue with data-driven insights',
                'Scale effortlessly as you grow',
            ]),
            'features' => json_encode([
                ['title' => 'Easy Setup', 'description' => 'Get started in minutes, no technical skills required'],
                ['title' => 'Real-time Analytics', 'description' => 'Track your performance with live dashboards'],
                ['title' => '24/7 Support', 'description' => 'Our team is always here to help you succeed'],
            ]),
            'social_proof' => json_encode([
                ['name' => 'Alex T.', 'role' => 'CEO', 'quote' => 'This solution transformed how we do business. Highly recommended!'],
                ['name' => 'Maria S.', 'role' => 'Marketing Director', 'quote' => 'Best decision we made this year. ROI was immediate.'],
            ]),
            'cta_text' => 'Get Started Now',
            'cta_subtext' => 'No credit card required. Start your free trial today.',
            'faq' => json_encode([
                ['question' => 'How quickly can I get started?', 'answer' => 'You can be up and running in less than 5 minutes.'],
                ['question' => 'Is there a free trial?', 'answer' => 'Yes! Try it free for 14 days, no credit card required.'],
                ['question' => 'Can I cancel anytime?', 'answer' => 'Absolutely. Cancel with one click, no questions asked.'],
            ]),
            'meta_title' => 'Transform Your Business Today - Get Started Now',
            'meta_description' => 'Join thousands of businesses already benefiting from our solution. Save time, increase revenue, and scale effortlessly. Try it free for 14 days.',
        ];
    }

    /**
     * Generate unique slug for landing page
     */
    public function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (LandingPage::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Calculate SEO score for landing page
     */
    protected function calculateSeoScore(array $content, Keyword $keyword): int
    {
        $score = 0;
        $keywordLower = strtolower($keyword->keyword);

        // Check keyword in headline (20 points)
        if (stripos($content['headline'], $keywordLower) !== false) {
            $score += 20;
        }

        // Check keyword in subheadline (15 points)
        if (stripos($content['subheadline'], $keywordLower) !== false) {
            $score += 15;
        }

        // Check keyword in hero content (15 points)
        if (stripos($content['hero_content'], $keywordLower) !== false) {
            $score += 15;
        }

        // Check meta title length (10 points)
        $metaTitleLength = strlen($content['meta_title']);
        if ($metaTitleLength >= 50 && $metaTitleLength <= 60) {
            $score += 10;
        }

        // Check meta description length (10 points)
        $metaDescLength = strlen($content['meta_description']);
        if ($metaDescLength >= 150 && $metaDescLength <= 160) {
            $score += 10;
        }

        // Check keyword in meta title (15 points)
        if (stripos($content['meta_title'], $keywordLower) !== false) {
            $score += 15;
        }

        // Check keyword in meta description (15 points)
        if (stripos($content['meta_description'], $keywordLower) !== false) {
            $score += 15;
        }

        return min($score, 100);
    }

    /**
     * Regenerate specific section of landing page
     */
    public function regenerateSection(LandingPage $landingPage, string $section): array
    {
        $keyword = $landingPage->keyword;
        $prompt = $this->buildSectionPrompt($keyword, $section);
        
        $response = $this->aiService->chat($prompt, [
            'max_tokens' => 500,
            'temperature' => 0.8,
        ]);

        return $this->parseSectionResponse($response, $section);
    }

    /**
     * Build prompt for specific section regeneration
     */
    protected function buildSectionPrompt(Keyword $keyword, string $section): string
    {
        return match($section) {
            'headline' => "Generate 5 alternative headlines for a landing page targeting: {$keyword->keyword}. Make them benefit-focused and compelling. Return as JSON array: [\"headline 1\", \"headline 2\", ...]",
            'benefits' => "List 5 key benefits for customers searching for: {$keyword->keyword}. Focus on outcomes, not features. Return as JSON array.",
            'faq' => "Generate 5 FAQ questions and answers for: {$keyword->keyword}. Address common objections. Return as JSON array of objects with 'question' and 'answer' keys.",
            default => "Generate content for {$section} section about: {$keyword->keyword}",
        };
    }

    /**
     * Parse section-specific AI response
     */
    protected function parseSectionResponse(string $response, string $section): array
    {
        $json = $this->extractJson($response);
        return $json ?? ['options' => []];
    }

    /**
     * Optimize existing landing page for SEO
     */
    public function optimizeForSeo(LandingPage $landingPage): void
    {
        $keyword = $landingPage->keyword;
        $seoScore = $this->calculateSeoScore([
            'headline' => $landingPage->headline,
            'subheadline' => $landingPage->subheadline,
            'hero_content' => $landingPage->hero_content,
            'meta_title' => $landingPage->meta_title,
            'meta_description' => $landingPage->meta_description,
        ], $keyword);

        $landingPage->update(['seo_score' => $seoScore]);
    }
}
