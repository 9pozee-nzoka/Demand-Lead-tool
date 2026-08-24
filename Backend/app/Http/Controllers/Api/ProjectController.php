<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $projects = Project::forOrganization($request->user()->organization_id)
            ->active()
            ->withCount(['keywords', 'opportunities', 'leads'])
            ->orderBy('name')
            ->get();

        return response()->json($projects);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'industry'         => ['nullable', 'string', 'max:100'],
            'country'          => ['nullable', 'string', 'size:2'],
            'default_location' => ['nullable', 'string', 'max:255'],
        ]);

        $project = Project::create([
            'organization_id' => $request->user()->organization_id,
            ...$data,
        ]);

        return response()->json($project, 201);
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request, $project);

        return response()->json($project->load(['keywords', 'opportunities' => fn ($q) => $q->active()->limit(10)]));
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request, $project);

        $data = $request->validate([
            'name'             => ['sometimes', 'string', 'max:255'],
            'industry'         => ['nullable', 'string', 'max:100'],
            'country'          => ['nullable', 'string', 'size:2'],
            'default_location' => ['nullable', 'string', 'max:255'],
            'status'           => ['sometimes', Rule::in(['active', 'paused', 'archived'])],
        ]);

        $project->update($data);

        return response()->json($project->fresh());
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request, $project);
        $project->delete();

        return response()->json(['message' => 'Project archived.']);
    }

    private function authorizeProject(Request $request, Project $project): void
    {
        if ((int) $project->organization_id !== (int) $request->user()->organization_id) {
            abort(403);
        }
    }
}
