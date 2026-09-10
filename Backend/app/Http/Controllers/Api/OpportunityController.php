<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScrapedItem;
use App\Services\Intelligence\OpportunityMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * OpportunityController - API endpoints for opportunity insights
 */
class OpportunityController extends Controller
{
    protected OpportunityMatcher $matcher;

    public function __construct(OpportunityMatcher $matcher)
    {
        $this->matcher = $matcher;
    }

    /**
     * Get top opportunities for the authenticated organization
     */
    public function index(Request $request): JsonResponse
    {
        $organization = $request->user()->organization;
        
        $limit = $request->input('limit', 20);
        $minScore = $request->input('min_score', 50);

        $opportunities = ScrapedItem::where('organization_id', $organization->id)
            ->where('opportunity_score', '>=', $minScore)
            ->whereNull('lead_id')
            ->with('sourceScraper:id,name,type')
            ->orderByDesc('opportunity_score')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $analysis = $item->metadata['opportunity_analysis'] ?? [];
                
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'description' => $item->description,
                    'url' => $item->url,
                    'intent' => $item->intent,
                    'opportunity_score' => $item->opportunity_score,
                    'lead_score' => $item->lead_score,
                    'relevance_score' => $item->relevance_score,
                    'source' => [
                        'name' => $item->sourceScraper->name ?? null,
                        'type' => $item->sourceScraper->type ?? null,
                    ],
                    'analysis' => [
                        'is_opportunity' => $analysis['is_opportunity'] ?? false,
                        'confidence' => $analysis['confidence'] ?? 0,
                        'recommendation' => $analysis['recommendation'] ?? null,
                        'key_insights' => $analysis['key_insights'] ?? [],
                        'factors' => $analysis['factors'] ?? [],
                        'explanations' => $analysis['explanations'] ?? [],
                    ],
                    'entities' => $item->metadata['extracted_entities'] ?? [],
                    'matched_keywords' => $item->matched_keywords,
                    'created_at' => $item->created_at,
                ];
            });

        return response()->json([
            'data' => $opportunities,
            'meta' => [
                'count' => $opportunities->count(),
            ],
        ]);
    }

    /**
     * Get opportunity statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $organization = $request->user()->organization;
        $days = $request->input('days', 30);

        $stats = $this->matcher->getOpportunityStatistics($organization, $days);

        return response()->json([
            'data' => $stats,
            'period' => [
                'days' => $days,
                'from' => now()->subDays($days)->toDateString(),
                'to' => now()->toDateString(),
            ],
        ]);
    }

    /**
     * Get details of a specific opportunity
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $organization = $request->user()->organization;

        $item = ScrapedItem::where('organization_id', $organization->id)
            ->where('id', $id)
            ->with('sourceScraper')
            ->firstOrFail();

        $analysis = $item->metadata['opportunity_analysis'] ?? [];
        $entities = $item->metadata['extracted_entities'] ?? [];

        return response()->json([
            'data' => [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'content' => $item->content,
                'url' => $item->url,
                'intent' => $item->intent,
                'scores' => [
                    'opportunity' => $item->opportunity_score,
                    'lead' => $item->lead_score,
                    'relevance' => $item->relevance_score,
                ],
                'source' => [
                    'id' => $item->sourceScraper->id ?? null,
                    'name' => $item->sourceScraper->name ?? null,
                    'type' => $item->sourceScraper->type ?? null,
                    'category' => $item->sourceScraper->category ?? null,
                ],
                'analysis' => [
                    'is_opportunity' => $analysis['is_opportunity'] ?? false,
                    'score' => $analysis['score'] ?? $item->opportunity_score,
                    'confidence' => $analysis['confidence'] ?? 0,
                    'recommendation' => $analysis['recommendation'] ?? null,
                    'key_insights' => $analysis['key_insights'] ?? [],
                    'factors' => $analysis['factors'] ?? [],
                    'explanations' => $analysis['explanations'] ?? [],
                    'method' => $analysis['method'] ?? null,
                ],
                'entities' => $entities,
                'matched_keywords' => $item->matched_keywords,
                'metadata' => $item->metadata,
                'processing_status' => $item->processing_status,
                'lead_id' => $item->lead_id,
                'created_at' => $item->created_at,
                'processed_at' => $item->processed_at,
            ],
        ]);
    }

    /**
     * Manually reanalyze an opportunity
     */
    public function reanalyze(Request $request, int $id): JsonResponse
    {
        $organization = $request->user()->organization;

        $item = ScrapedItem::where('organization_id', $organization->id)
            ->where('id', $id)
            ->firstOrFail();

        // Clear cache to force fresh analysis
        \Illuminate\Support\Facades\Cache::forget("opportunity_analysis_{$item->content_hash}_{$organization->id}");

        $analysis = $this->matcher->analyze($item, $organization);
        $this->matcher->updateItemWithAnalysis($item, $analysis);

        return response()->json([
            'message' => 'Opportunity reanalyzed successfully',
            'data' => [
                'is_opportunity' => $analysis['is_opportunity'],
                'score' => $analysis['score'],
                'confidence' => $analysis['confidence'],
                'recommendation' => $analysis['recommendation'],
            ],
        ]);
    }
}
