<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SourceScraper;
use App\Services\Analytics\SourceAnalytics;
use App\Services\Sources\Providers\RssSource;
use App\Services\Sources\Providers\TenderSource;
use App\Services\Sources\SourceManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * SourceController - API for managing data sources
 */
class SourceController extends Controller
{
    protected SourceManager $manager;
    protected SourceAnalytics $analytics;

    public function __construct(SourceManager $manager, SourceAnalytics $analytics)
    {
        $this->manager = $manager;
        $this->analytics = $analytics;
    }

    /**
     * GET /api/v1/sources - List all sources
     */
    public function index(Request $request): JsonResponse
    {
        $organization = $request->user()->organization;

        $sources = SourceScraper::where('organization_id', $organization->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($source) => [
                'id' => $source->id,
                'name' => $source->name,
                'type' => $source->type,
                'category' => $source->category,
                'status' => $source->status,
                'base_url' => $source->base_url,
                'schedule' => $source->schedule,
                'last_run_at' => $source->last_run_at,
                'next_run_at' => $source->next_run_at,
                'success_count' => $source->success_count,
                'error_count' => $source->error_count,
                'uptime_percentage' => $this->calculateUptime($source),
                'created_at' => $source->created_at,
            ]);

        return response()->json([
            'data' => $sources,
            'summary' => [
                'total' => $sources->count(),
                'active' => $sources->where('status', 'active')->count(),
                'paused' => $sources->where('status', 'paused')->count(),
                'error' => $sources->where('status', 'error')->count(),
            ],
        ]);
    }

    /**
     * POST /api/v1/sources - Create a new source
     */
    public function store(Request $request): JsonResponse
    {
        $organization = $request->user()->organization;

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:rss,tender,webhook',
            'base_url' => 'nullable|url',
            'configuration' => 'sometimes|array',
            'schedule' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $source = $this->manager->createSource($organization, $request->all());

            // Generate webhook URL if webhook type
            $webhookUrl = null;
            if ($source->type === 'webhook') {
                $token = $source->configuration['authentication']['token'] ?? null;
                if ($token) {
                    $webhookUrl = url("/api/v1/webhooks/receive/{$source->id}/{$token}");
                }
            }

            return response()->json([
                'message' => 'Source created successfully',
                'data' => [
                    'id' => $source->id,
                    'name' => $source->name,
                    'type' => $source->type,
                    'status' => $source->status,
                    'webhook_url' => $webhookUrl,
                ],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to create source',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/sources/{id} - Get source details
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $organization = $request->user()->organization;

        $source = SourceScraper::where('organization_id', $organization->id)
            ->findOrFail($id);

        // Get recent jobs
        $recentJobs = $source->scrapeJobs()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($job) => [
                'id' => $job->id,
                'status' => $job->status,
                'items_found' => $job->items_found,
                'items_new' => $job->items_new,
                'items_updated' => $job->items_updated,
                'items_failed' => $job->items_failed,
                'duration' => $job->duration,
                'started_at' => $job->started_at,
                'completed_at' => $job->completed_at,
            ]);

        // Get recent items
        $recentItems = $source->scrapedItems()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'title' => $item->title,
                'intent' => $item->intent,
                'opportunity_score' => $item->opportunity_score,
                'lead_score' => $item->lead_score,
                'processing_status' => $item->processing_status,
                'lead_id' => $item->lead_id,
                'created_at' => $item->created_at,
            ]);

        // Get webhook URL
        $webhookUrl = null;
        if ($source->type === 'webhook') {
            $token = $source->configuration['authentication']['token'] ?? null;
            if ($token) {
                $webhookUrl = url("/api/v1/webhooks/receive/{$source->id}/{$token}");
            }
        }

        return response()->json([
            'data' => [
                'id' => $source->id,
                'name' => $source->name,
                'type' => $source->type,
                'category' => $source->category,
                'status' => $source->status,
                'base_url' => $source->base_url,
                'configuration' => $source->configuration,
                'schedule' => $source->schedule,
                'last_run_at' => $source->last_run_at,
                'next_run_at' => $source->next_run_at,
                'success_count' => $source->success_count,
                'error_count' => $source->error_count,
                'uptime_percentage' => $this->calculateUptime($source),
                'webhook_url' => $webhookUrl,
                'created_at' => $source->created_at,
                'updated_at' => $source->updated_at,
            ],
            'recent_jobs' => $recentJobs,
            'recent_items' => $recentItems,
            'stats' => $this->manager->getSourceStats($source),
        ]);
    }

    /**
     * PATCH /api/v1/sources/{id} - Update source
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $organization = $request->user()->organization;

        $source = SourceScraper::where('organization_id', $organization->id)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'base_url' => 'sometimes|nullable|url',
            'configuration' => 'sometimes|array',
            'schedule' => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $source = $this->manager->updateSource($source, $request->all());

            return response()->json([
                'message' => 'Source updated successfully',
                'data' => [
                    'id' => $source->id,
                    'name' => $source->name,
                    'status' => $source->status,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to update source',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/v1/sources/{id} - Delete source
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $organization = $request->user()->organization;

        $source = SourceScraper::where('organization_id', $organization->id)
            ->findOrFail($id);

        try {
            $this->manager->deleteSource($source);

            return response()->json([
                'message' => 'Source deleted successfully',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to delete source',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/sources/{id}/test - Test source connection
     */
    public function test(Request $request, int $id): JsonResponse
    {
        $organization = $request->user()->organization;

        $source = SourceScraper::where('organization_id', $organization->id)
            ->findOrFail($id);

        try {
            $result = $this->manager->testSource($source);

            return response()->json([
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Test failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/sources/{id}/run - Run source manually
     */
    public function run(Request $request, int $id): JsonResponse
    {
        $organization = $request->user()->organization;

        $source = SourceScraper::where('organization_id', $organization->id)
            ->findOrFail($id);

        try {
            $job = $this->manager->runSource($source);

            return response()->json([
                'message' => 'Source run completed',
                'data' => [
                    'job_id' => $job->id,
                    'status' => $job->status,
                    'items_found' => $job->items_found,
                    'items_new' => $job->items_new,
                    'items_updated' => $job->items_updated,
                    'items_failed' => $job->items_failed,
                    'duration' => $job->duration,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to run source',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/sources/{id}/pause - Pause source
     */
    public function pause(Request $request, int $id): JsonResponse
    {
        $organization = $request->user()->organization;

        $source = SourceScraper::where('organization_id', $organization->id)
            ->findOrFail($id);

        $this->manager->pauseSource($source);

        return response()->json([
            'message' => 'Source paused successfully',
        ]);
    }

    /**
     * POST /api/v1/sources/{id}/activate - Activate source
     */
    public function activate(Request $request, int $id): JsonResponse
    {
        $organization = $request->user()->organization;

        $source = SourceScraper::where('organization_id', $organization->id)
            ->findOrFail($id);

        $this->manager->activateSource($source);

        return response()->json([
            'message' => 'Source activated successfully',
        ]);
    }

    /**
     * GET /api/v1/sources/templates - Get source templates
     */
    public function templates(): JsonResponse
    {
        return response()->json([
            'data' => [
                'rss' => [
                    'type' => 'rss',
                    'name' => 'RSS Feed',
                    'description' => 'Monitor news feeds and blogs',
                    'presets' => RssSource::getKenyaNewsFeeds(),
                    'configuration' => [
                        'fetch_limit' => 50,
                        'keywords' => [],
                        'exclude_keywords' => [],
                        'min_content_length' => 100,
                        'fetch_full_article' => false,
                    ],
                    'schedule' => '0 */6 * * *',
                ],
                'tender' => [
                    'type' => 'tender',
                    'name' => 'Tender Portal',
                    'description' => 'Monitor government and corporate tenders',
                    'presets' => TenderSource::getKenyaTenderPortals(),
                    'configuration' => [
                        'tender_selector' => '.tender-row',
                        'fetch_limit' => 100,
                        'min_value' => 500000,
                        'categories' => ['ICT', 'Consultancy'],
                        'exclude_expired' => true,
                    ],
                    'schedule' => '0 8,14 * * *',
                ],
                'webhook' => [
                    'type' => 'webhook',
                    'name' => 'Webhook Receiver',
                    'description' => 'Capture leads from websites and forms',
                    'configuration' => [
                        'authentication' => [
                            'type' => 'token',
                            'token' => bin2hex(random_bytes(32)),
                        ],
                        'field_mapping' => [
                            'name' => 'name',
                            'email' => 'email',
                            'phone' => 'phone',
                            'company' => 'company',
                            'message' => 'message',
                        ],
                    ],
                    'schedule' => null,
                ],
            ],
        ]);
    }

    /**
     * GET /api/v1/sources/{id}/items - Get scraped items
     */
    public function items(Request $request, int $id): JsonResponse
    {
        $organization = $request->user()->organization;

        $source = SourceScraper::where('organization_id', $organization->id)
            ->findOrFail($id);

        $query = $source->scrapedItems()->orderBy('created_at', 'desc');

        // Filters
        if ($request->has('status')) {
            $query->where('processing_status', $request->input('status'));
        }

        if ($request->has('intent')) {
            $query->where('intent', $request->input('intent'));
        }

        $items = $query->paginate(20)->through(fn($item) => [
            'id' => $item->id,
            'title' => $item->title,
            'description' => substr($item->description, 0, 200),
            'url' => $item->url,
            'intent' => $item->intent,
            'opportunity_score' => $item->opportunity_score,
            'lead_score' => $item->lead_score,
            'relevance_score' => $item->relevance_score,
            'processing_status' => $item->processing_status,
            'lead_id' => $item->lead_id,
            'matched_keywords' => $item->matched_keywords,
            'created_at' => $item->created_at,
        ]);

        return response()->json($items);
    }

    /**
     * Calculate uptime percentage
     */
    protected function calculateUptime(SourceScraper $source): float
    {
        $total = $source->success_count + $source->error_count;
        
        if ($total === 0) {
            return 100.0;
        }

        return round(($source->success_count / $total) * 100, 2);
    }
}
