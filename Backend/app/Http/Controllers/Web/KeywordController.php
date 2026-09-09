<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Keyword;
use App\Models\Project;
use Illuminate\Http\Request;

class KeywordController extends Controller
{
    public function index(Request $request)
    {
        $query = Keyword::whereHas('project', function($q) use ($request) {
            $q->where('organization_id', $request->user()->organization_id);
        })->with(['project', 'locations']);

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('search')) {
            $query->where('term', 'like', '%' . $request->search . '%');
        }

        $keywords = $query->orderBy('created_at', 'desc')->paginate(20);

        $projects = Project::where('organization_id', $request->user()->organization_id)
            ->where('status', 'active')
            ->get();

        return view('keywords.index', compact('keywords', 'projects'));
    }

    public function create()
    {
        $projects = Project::where('organization_id', auth()->user()->organization_id)
            ->where('status', 'active')
            ->get();

        return view('keywords.create', compact('projects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'term' => 'required|string|max:255',
            'locations' => 'required|string',
            'match_type' => 'required|in:exact,phrase,broad',
            'priority' => 'required|in:low,medium,high',
            'notes' => 'nullable|string',
        ]);

        // Parse locations (comma-separated country codes)
        $locationCodes = array_map('trim', explode(',', $validated['locations']));

        // Create keyword
        $keyword = Keyword::create([
            'project_id' => $validated['project_id'],
            'term' => $validated['term'],
            'match_type' => $validated['match_type'],
            'priority' => $validated['priority'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'active',
        ]);

        // Create keyword_locations records
        foreach ($locationCodes as $location) {
            if (!empty($location)) {
                $keyword->locations()->create([
                    'location' => strtoupper($location),
                ]);
            }
        }

        return redirect()->route('keywords.index')
            ->with('success', 'Keyword added successfully.');
    }

    public function show(Keyword $keyword)
    {
        $this->authorize('view', $keyword);

        $keyword->load(['project', 'locations', 'measurements' => function($q) {
            $q->orderBy('measured_at', 'desc')->limit(30);
        }]);

        return view('keywords.show', compact('keyword'));
    }

    public function destroy(Keyword $keyword)
    {
        $this->authorize('delete', $keyword);

        $keyword->delete();

        return redirect()->route('keywords.index')
            ->with('success', 'Keyword deleted successfully.');
    }
}
