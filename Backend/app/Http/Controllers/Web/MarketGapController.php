<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Keyword;
use App\Services\AI\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MarketGapController extends Controller
{
    protected AIService $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function index(Request $request)
    {
        $organizationId = $request->user()->organization_id;

        // Get keywords for analysis
        $keywords = Keyword::whereHas('project', function($q) use ($organizationId) {
            $q->where('organization_id', $organizationId);
        })
        ->where('status', 'active')
        ->with('project')
        ->get();

        // Get analysis from cache or generate
        $cacheKey = "market_gaps_org_{$organizationId}";
        
        $analysis = Cache::remember($cacheKey, now()->addHours(6), function() use ($keywords) {
            if ($keywords->isEmpty()) {
                return [
                    'gaps' => [],
                    'opportunities' => [],
                    'recommendations' => [],
                ];
            }

            if (!$this->aiService->isConfigured()) {
                return $this->generateFallbackAnalysis($keywords);
            }

            try {
                return $this->aiService->analyzeMarketGaps(
                    $keywords->pluck('keyword')->toArray()
                );
            } catch (\Exception $e) {
                \Log::warning('Market gap AI analysis failed', [
                    'error' => $e->getMessage(),
                ]);
                return $this->generateFallbackAnalysis($keywords);
            }
        });

        // Get keyword stats
        $stats = $this->calculateStats($keywords);

        // Get untapped keywords (low competition + rising trends)
        $untappedKeywords = $this->getUntappedKeywords($keywords);

        // Get coverage gaps (keywords without content/campaigns)
        $coverageGaps = $this->getCoverageGaps($keywords);

        return view('market-gaps.index', [
            'analysis' => $analysis,
            'stats' => $stats,
            'untappedKeywords' => $untappedKeywords,
            'coverageGaps' => $coverageGaps,
            'keywords' => $keywords,
            'aiConfigured' => $this->aiService->isConfigured(),
        ]);
    }

    /**
     * Refresh analysis (clear cache)
     */
    public function refresh(Request $request)
    {
        $organizationId = $request->user()->organization_id;
        $cacheKey = "market_gaps_org_{$organizationId}";
        
        Cache::forget($cacheKey);

        return redirect()->route('market-gaps.index')
            ->with('success', 'Market gap analysis refreshed successfully.');
    }

    /**
     * Calculate keyword statistics
     */
    protected function calculateStats($keywords): array
    {
        $total = $keywords->count();
        
        $rising = $keywords->where('trend_state', 'rising')->count();
        $emerging = $keywords->where('trend_state', 'emerging')->count();
        $spike = $keywords->where('trend_state', 'spike')->count();
        
        $avgGrowth = $keywords->avg('growth_rate_30d') ?? 0;
        
        $highGrowth = $keywords->filter(function($k) {
            return $k->growth_rate_30d > 50;
        })->count();

        return [
            'total' => $total,
            'rising' => $rising,
            'emerging' => $emerging,
            'spike' => $spike,
            'high_growth' => $highGrowth,
            'avg_growth' => round($avgGrowth, 1),
        ];
    }

    /**
     * Get untapped keywords (opportunities with low current coverage)
     */
    protected function getUntappedKeywords($keywords)
    {
        return $keywords->filter(function($keyword) {
            // Rising or emerging with good growth
            $isTrending = in_array($keyword->trend_state, ['rising', 'emerging', 'spike']);
            $hasGrowth = $keyword->growth_rate_30d > 30;
            
            // Low current activity (no opportunities or leads)
            $lowCoverage = $keyword->opportunities()->count() === 0;

            return $isTrending && $hasGrowth && $lowCoverage;
        })
        ->sortByDesc('growth_rate_30d')
        ->take(10)
        ->values();
    }

    /**
     * Get coverage gaps (keywords without sufficient action)
     */
    protected function getCoverageGaps($keywords)
    {
        return $keywords->filter(function($keyword) {
            // Has interest but no landing pages or campaigns
            $hasInterest = $keyword->current_interest > 50;
            
            // Count related activities
            $opportunityCount = $keyword->opportunities()->count();
            
            return $hasInterest && $opportunityCount === 0;
        })
        ->sortByDesc('current_interest')
        ->take(10)
        ->values();
    }

    /**
     * Generate fallback analysis when AI is unavailable
     */
    protected function generateFallbackAnalysis($keywords): array
    {
        $gaps = [];
        $opportunities = [];
        $recommendations = [];

        // Analyze trending keywords
        $trendingCount = $keywords->whereIn('trend_state', ['rising', 'spike'])->count();
        if ($trendingCount > 0) {
            $gaps[] = "{$trendingCount} trending keywords may lack sufficient content coverage";
            $opportunities[] = "Create content for {$trendingCount} rising trend keywords";
            $recommendations[] = "Review trending keywords and prioritize content creation";
        }

        // Analyze emerging keywords
        $emergingCount = $keywords->where('trend_state', 'emerging')->count();
        if ($emergingCount > 0) {
            $gaps[] = "{$emergingCount} emerging keywords could be early opportunities";
            $opportunities[] = "Monitor and validate {$emergingCount} emerging trends";
            $recommendations[] = "Set up tracking for emerging keywords";
        }

        // Analyze high growth without campaigns
        $highGrowthNoCoverage = $keywords->filter(function($k) {
            return $k->growth_rate_30d > 50 && $k->opportunities()->count() === 0;
        })->count();

        if ($highGrowthNoCoverage > 0) {
            $gaps[] = "{$highGrowthNoCoverage} high-growth keywords without campaigns";
            $opportunities[] = "Launch campaigns for {$highGrowthNoCoverage} untapped keywords";
            $recommendations[] = "Create opportunities for high-growth keywords";
        }

        // Default messages if no gaps found
        if (empty($gaps)) {
            $gaps[] = "No significant market gaps detected";
            $opportunities[] = "Continue monitoring keyword performance";
            $recommendations[] = "Add more keywords to expand coverage";
        }

        return [
            'gaps' => $gaps,
            'opportunities' => $opportunities,
            'recommendations' => $recommendations,
        ];
    }
}
