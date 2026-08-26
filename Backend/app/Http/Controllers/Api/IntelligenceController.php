<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DemandCluster;
use App\Models\KeywordMeasurement;
use App\Models\Project;
use App\Services\Intelligence\IntentClassificationService;
use App\Services\Intelligence\KeywordClusteringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntelligenceController extends Controller
{
    public function __construct(
        private readonly IntentClassificationService $intentService,
        private readonly KeywordClusteringService    $clusterService,
    ) {}

    // ── Market Gaps ──────────────────────────────────────────────────────────

    /**
     * GET /api/v1/intelligence/market-gaps?project_id=&growth_threshold=&competition_threshold=
     */
    public function marketGaps(Request $request): JsonResponse
    {
        $orgId = $request->user()->organization_id;

        $projectId = $request->integer('project_id');
        if ($projectId) {
            $project = Project::where('id', $projectId)
                ->where('organization_id', $orgId)
                ->firstOrFail();
        }

        $gaps = collect();

        $query = Project::where('organization_id', $orgId)->where('status', 'active');
        if ($projectId) $query->where('id', $projectId);

        $query->each(function (Project $project) use ($request, &$gaps) {
            $projectGaps = $this->clusterService->detectMarketGaps(
                projectId:            $project->id,
                growthThreshold:      (float) $request->input('growth_threshold', 20.0),
                competitionThreshold: (float) $request->input('competition_threshold', 0.4),
            );

            // Add project name to each cluster
            $projectGaps->each(fn ($c) => $c->project_name = $project->name);
            $gaps = $gaps->merge($projectGaps);
        });

        return response()->json([
            'data'  => $gaps->sortByDesc('score')->values(),
            'total' => $gaps->count(),
        ]);
    }

    // ── Clusters ─────────────────────────────────────────────────────────────

    /**
     * GET /api/v1/intelligence/clusters?project_id=
     */
    public function clusters(Request $request): JsonResponse
    {
        $orgId = $request->user()->organization_id;

        $clusters = DemandCluster::whereHas('project', fn ($q) => $q->where('organization_id', $orgId))
            ->with(['keywords:id,keyword,intent,priority', 'project:id,name'])
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->project_id))
            ->withCount('keywords')
            ->orderByDesc('score')
            ->paginate(20);

        return response()->json($clusters);
    }

    /**
     * POST /api/v1/intelligence/cluster?project_id=
     * Trigger re-clustering for a project.
     */
    public function recluster(Request $request): JsonResponse
    {
        $request->validate(['project_id' => ['required', 'integer', 'exists:projects,id']]);

        $project = Project::where('id', $request->project_id)
            ->where('organization_id', $request->user()->organization_id)
            ->firstOrFail();

        $count = $this->clusterService->clusterProject($project->id);

        return response()->json([
            'message'  => "Clustering complete. {$count} clusters created/updated.",
            'clusters' => $count,
        ]);
    }

    // ── Intent classification ─────────────────────────────────────────────────

    /**
     * POST /api/v1/intelligence/classify-intents?project_id= (or org-wide)
     * Run intent classification on all unknown-intent keywords.
     */
    public function classifyIntents(Request $request): JsonResponse
    {
        $orgId   = $request->user()->organization_id;
        $updated = $this->intentService->batchClassify($orgId);

        return response()->json([
            'message' => "Intent classification complete. {$updated} keywords updated.",
            'updated' => $updated,
        ]);
    }

    // ── Competitor signals ────────────────────────────────────────────────────

    /**
     * GET /api/v1/intelligence/competitors
     * Returns keywords where competitor demand is rising (high competition score)
     * in the last 14 days — indicates competitors are driving search volume.
     */
    public function competitors(Request $request): JsonResponse
    {
        $orgId = $request->user()->organization_id;

        $signals = KeywordMeasurement::whereHas('keyword.project', fn ($q) =>
                $q->where('organization_id', $orgId))
            ->with(['keyword:id,keyword,intent,priority,project_id', 'keyword.project:id,name'])
            ->whereNotNull('competition')
            ->where('competition', '>=', (float) $request->input('min_competition', 0.5))
            ->where('date', '>=', now()->subDays(14)->format('Y-m-d'))
            ->orderByDesc('competition')
            ->limit(50)
            ->get()
            ->map(fn ($m) => [
                'keyword'         => $m->keyword?->keyword,
                'intent'          => $m->keyword?->intent,
                'project'         => $m->keyword?->project?->name,
                'competition'     => (float) $m->competition,
                'interest'        => $m->interest,
                'growth'          => (float) ($m->growth ?? 0),
                'geo'             => $m->geo,
                'date'            => $m->date,
                'competitor_level'=> $this->competitorLevel((float) $m->competition),
            ]);

        return response()->json([
            'data'  => $signals,
            'total' => $signals->count(),
        ]);
    }

    private function competitorLevel(float $competition): string
    {
        return match (true) {
            $competition >= 0.8 => 'very_high',
            $competition >= 0.6 => 'high',
            $competition >= 0.4 => 'medium',
            default             => 'low',
        };
    }
}
