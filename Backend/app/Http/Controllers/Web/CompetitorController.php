<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Keyword;
use App\Services\AI\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CompetitorController extends Controller
{
    protected AIService $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function index(Request $request)
    {
        $organizationId = $request->user()->organization_id;

        // Get competitors from request or default list
        $competitors = $request->get('competitors', []);
        
        // If none provided, show form
        if (empty($competitors)) {
            return view('competitors.index', [
                'showForm' => true,
                'analysis' => null,
                'competitors' => [],
                'keywords' => [],
                'aiConfigured' => $this->aiService->isConfigured(),
            ]);
        }

        // Get keywords for context
        $keywords = Keyword::whereHas('project', function($q) use ($organizationId) {
            $q->where('organization_id', $organizationId);
        })
        ->where('status', 'active')
        ->limit(50)
        ->pluck('keyword')
        ->toArray();

        // Get analysis from cache or generate
        $cacheKey = "competitor_analysis_" . md5(json_encode($competitors));
        
        $analysis = Cache::remember($cacheKey, now()->addHours(12), function() use ($competitors, $keywords) {
            if (!$this->aiService->isConfigured()) {
                return $this->generateFallbackAnalysis($competitors, $keywords);
            }

            try {
                return $this->aiService->analyzeCompetitors($competitors, $keywords);
            } catch (\Exception $e) {
                \Log::warning('Competitor AI analysis failed', [
                    'error' => $e->getMessage(),
                ]);
                return $this->generateFallbackAnalysis($competitors, $keywords);
            }
        });

        // Get competitive keyword insights
        $competitiveKeywords = $this->getCompetitiveInsights($keywords);

        return view('competitors.index', [
            'showForm' => false,
            'analysis' => $analysis,
            'competitors' => $competitors,
            'keywords' => $keywords,
            'competitiveKeywords' => $competitiveKeywords,
            'aiConfigured' => $this->aiService->isConfigured(),
        ]);
    }

    /**
     * Analyze competitors (form submission)
     */
    public function analyze(Request $request)
    {
        $request->validate([
            'competitors' => 'required|string|max:500',
        ]);

        // Parse competitors (comma-separated)
        $competitorList = array_map('trim', explode(',', $request->competitors));
        $competitorList = array_filter($competitorList);

        if (empty($competitorList)) {
            return redirect()->route('competitors.index')
                ->with('error', 'Please enter at least one competitor.');
        }

        return redirect()->route('competitors.index', ['competitors' => $competitorList]);
    }

    /**
     * Get competitive insights from keywords
     */
    protected function getCompetitiveInsights($keywords): array
    {
        if (empty($keywords)) {
            return [];
        }

        $keywordModels = Keyword::whereIn('term', $keywords)
            ->where('status', 'active')
            ->with('project')
            ->get();

        // Find high-opportunity keywords
        $highOpportunity = $keywordModels->filter(function($k) {
            return in_array($k->trend_state, ['rising', 'spike']) 
                && $k->growth_rate_30d > 40;
        })
        ->sortByDesc('growth_rate_30d')
        ->take(5)
        ->map(function($k) {
            return [
                'term' => $k->term,
                'growth' => $k->growth_rate_30d,
                'state' => $k->trend_state,
                'interest' => $k->current_interest,
            ];
        })
        ->values()
        ->toArray();

        // Find defensive keywords (stable high volume)
        $defensive = $keywordModels->filter(function($k) {
            return $k->trend_state === 'stable' 
                && $k->current_interest > 70;
        })
        ->sortByDesc('current_interest')
        ->take(5)
        ->map(function($k) {
            return [
                'term' => $k->term,
                'interest' => $k->current_interest,
                'state' => $k->trend_state,
            ];
        })
        ->values()
        ->toArray();

        return [
            'high_opportunity' => $highOpportunity,
            'defensive' => $defensive,
        ];
    }

    /**
     * Generate fallback analysis when AI is unavailable
     */
    protected function generateFallbackAnalysis($competitors, $keywords): array
    {
        $competitorCount = count($competitors);
        $keywordCount = count($keywords);

        return [
            'strengths' => [
                "Analyzing {$competitorCount} competitors in your market",
                "Established market presence and brand recognition",
                "Likely have existing customer base and content",
            ],
            'weaknesses' => [
                "May not be tracking all {$keywordCount} keywords you're monitoring",
                "Potential gaps in emerging trend coverage",
                "Could be slow to adapt to market changes",
            ],
            'positioning_gaps' => [
                "Rising trends may not be fully addressed by competitors",
                "Opportunity to differentiate with faster response times",
                "Potential niche segments not yet targeted",
            ],
            'differentiation_opportunities' => [
                "Focus on emerging and high-growth keywords",
                "Leverage AI-powered demand intelligence",
                "Faster time-to-market for new trends",
                "Data-driven content and campaign strategy",
            ],
        ];
    }
}
