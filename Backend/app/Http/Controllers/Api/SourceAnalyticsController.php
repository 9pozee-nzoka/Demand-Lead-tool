<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SourceScraper;
use App\Services\Analytics\SourceAnalytics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SourceAnalyticsController - API endpoints for source performance and ROI
 */
class SourceAnalyticsController extends Controller
{
    protected SourceAnalytics $analytics;

    public function __construct(SourceAnalytics $analytics)
    {
        $this->analytics = $analytics;
    }

    /**
     * Get performance analytics for all sources
     */
    public function index(Request $request): JsonResponse
    {
        $organization = $request->user()->organization;
        $days = $request->input('days', 30);

        $performance = $this->analytics->getSourcePerformance($organization, $days);

        return response()->json([
            'data' => $performance,
        ]);
    }

    /**
     * Get detailed metrics for a single source
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $organization = $request->user()->organization;

        $source = SourceScraper::where('organization_id', $organization->id)
            ->findOrFail($id);

        $days = $request->input('days', 30);
        $since = now()->subDays($days);

        $metrics = $this->analytics->getSourceMetrics($source, $since);

        return response()->json([
            'data' => $metrics,
        ]);
    }

    /**
     * Compare performance across source types
     */
    public function compareTypes(Request $request): JsonResponse
    {
        $organization = $request->user()->organization;
        $days = $request->input('days', 30);

        $comparison = $this->analytics->compareSourceTypes($organization, $days);

        return response()->json([
            'data' => $comparison,
            'period' => [
                'days' => $days,
                'from' => now()->subDays($days)->toDateString(),
                'to' => now()->toDateString(),
            ],
        ]);
    }

    /**
     * Get trending insights
     */
    public function insights(Request $request): JsonResponse
    {
        $organization = $request->user()->organization;
        $days = $request->input('days', 30);

        $insights = $this->analytics->getTrendingInsights($organization, $days);

        return response()->json([
            'data' => $insights,
        ]);
    }

    /**
     * Get optimization recommendations
     */
    public function recommendations(Request $request): JsonResponse
    {
        $organization = $request->user()->organization;

        $recommendations = $this->analytics->getOptimizationRecommendations($organization);

        return response()->json([
            'data' => $recommendations,
        ]);
    }
}
