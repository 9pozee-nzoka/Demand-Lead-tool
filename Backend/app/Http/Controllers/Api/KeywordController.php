<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\CollectKeywordData;
use App\Models\Keyword;
use App\Models\KeywordLocation;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class KeywordController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate(['project_id' => ['required', 'integer']]);

        $project = Project::findOrFail($request->project_id);
        $this->authorizeProject($request, $project);

        $keywords = Keyword::where('project_id', $project->id)
            ->with(['locations', 'measurements' => fn ($q) => $q->latest('date')->limit(30)])
            ->active()
            ->orderBy('keyword')
            ->get();

        return response()->json($keywords);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'keyword'    => ['required', 'string', 'max:255'],
            'category'   => ['nullable', 'string', 'max:100'],
            'intent'     => ['nullable', Rule::in(['informational','commercial','transactional','local','unknown'])],
            'priority'   => ['nullable', Rule::in(['low','medium','high'])],
            'locations'  => ['nullable', 'array'],
            'locations.*.country' => ['required_with:locations', 'string', 'size:2'],
            'locations.*.city'    => ['nullable', 'string', 'max:100'],
            'locations.*.region'  => ['nullable', 'string', 'max:100'],
        ]);

        $project = Project::findOrFail($data['project_id']);
        $this->authorizeProject($request, $project);

        $keyword = Keyword::create([
            'project_id'         => $project->id,
            'keyword'            => $data['keyword'],
            'normalized_keyword' => Str::lower(trim($data['keyword'])),
            'category'           => $data['category'] ?? null,
            'intent'             => $data['intent'] ?? 'unknown',
            'priority'           => $data['priority'] ?? 'medium',
        ]);

        foreach ($data['locations'] ?? [] as $loc) {
            KeywordLocation::create([
                'keyword_id' => $keyword->id,
                'country'    => $loc['country'],
                'region'     => $loc['region'] ?? null,
                'city'       => $loc['city'] ?? null,
                'type'       => isset($loc['city']) ? 'city' : (isset($loc['region']) ? 'region' : 'country'),
            ]);
        }

        // Dispatch initial data collection job
        CollectKeywordData::dispatch($keyword->id);

        return response()->json($keyword->load('locations'), 201);
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'keywords'   => ['required', 'array', 'min:1', 'max:100'],
            'keywords.*' => ['required', 'string', 'max:255'],
        ]);

        $project = Project::findOrFail($data['project_id']);
        $this->authorizeProject($request, $project);

        $created = [];
        foreach ($data['keywords'] as $kw) {
            $keyword   = Keyword::create([
                'project_id'         => $project->id,
                'keyword'            => $kw,
                'normalized_keyword' => Str::lower(trim($kw)),
            ]);
            $created[] = $keyword;
            CollectKeywordData::dispatch($keyword->id);
        }

        return response()->json(['created' => count($created), 'keywords' => $created], 201);
    }

    public function show(Request $request, Keyword $keyword): JsonResponse
    {
        $this->authorizeKeyword($request, $keyword);

        return response()->json($keyword->load('locations', 'measurements'));
    }

    public function update(Request $request, Keyword $keyword): JsonResponse
    {
        $this->authorizeKeyword($request, $keyword);

        $data = $request->validate([
            'category' => ['nullable', 'string', 'max:100'],
            'intent'   => ['nullable', Rule::in(['informational','commercial','transactional','local','unknown'])],
            'priority' => ['nullable', Rule::in(['low','medium','high'])],
            'status'   => ['sometimes', Rule::in(['active','paused','archived'])],
        ]);

        $keyword->update($data);

        return response()->json($keyword->fresh());
    }

    public function destroy(Request $request, Keyword $keyword): JsonResponse
    {
        $this->authorizeKeyword($request, $keyword);
        $keyword->delete();

        return response()->json(['message' => 'Keyword removed.']);
    }

    public function trend(Request $request, Keyword $keyword): JsonResponse
    {
        $this->authorizeKeyword($request, $keyword);

        $measurements = $keyword->measurements()
            ->orderBy('date')
            ->get(['date', 'interest', 'volume', 'growth', 'source']);

        return response()->json([
            'keyword'      => $keyword->keyword,
            'trend_state'  => $this->computeTrendState($measurements),
            'measurements' => $measurements,
        ]);
    }

    public function history(Request $request, Keyword $keyword): JsonResponse
    {
        $this->authorizeKeyword($request, $keyword);

        $days = min((int) $request->query('days', 90), 365);
        $measurements = $keyword->measurements()
            ->inPeriod($days)
            ->orderBy('date')
            ->get();

        return response()->json($measurements);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function authorizeProject(Request $request, Project $project): void
    {
        if ((int) $project->organization_id !== (int) $request->user()->organization_id) {
            abort(403);
        }
    }

    private function authorizeKeyword(Request $request, Keyword $keyword): void
    {
        $this->authorizeProject($request, $keyword->project);
    }

    private function computeTrendState($measurements): string
    {
        if ($measurements->isEmpty()) {
            return 'normal';
        }

        $latestGrowth = (float) $measurements->last()?->growth;

        return match (true) {
            $latestGrowth >= 100 => 'spike',
            $latestGrowth >= 50  => 'rapidly_rising',
            $latestGrowth >= 20  => 'rising',
            $latestGrowth >= 5   => 'emerging',
            $latestGrowth <= -20 => 'declining',
            default              => 'stable',
        };
    }
}
