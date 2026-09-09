<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    public function index(Request $request)
    {
        $query = Opportunity::where('organization_id', $request->user()->organization_id)
            ->with(['keyword.project']);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('min_score')) {
            $query->where('score', '>=', $request->min_score);
        }

        $opportunities = $query->orderBy('opportunity_score', 'desc')
            ->paginate(20);

        return view('opportunities.index', compact('opportunities'));
    }

    public function show(Opportunity $opportunity)
    {
        $this->authorize('view', $opportunity);

        $opportunity->load(['keyword.project', 'keyword.locations']);

        return view('opportunities.show', compact('opportunity'));
    }

    public function dismiss(Request $request, Opportunity $opportunity)
    {
        $this->authorize('update', $opportunity);

        $validated = $request->validate([
            'reason' => 'nullable|string',
        ]);

        $opportunity->update([
            'status' => 'dismissed',
            'dismissed_reason' => $validated['reason'] ?? null,
            'dismissed_at' => now(),
        ]);

        return redirect()->route('opportunities.index')
            ->with('success', 'Opportunity dismissed.');
    }

    public function restore(Opportunity $opportunity)
    {
        $this->authorize('update', $opportunity);

        $opportunity->update([
            'status' => 'open',
            'dismissed_reason' => null,
            'dismissed_at' => null,
        ]);

        return redirect()->route('opportunities.index')
            ->with('success', 'Opportunity restored.');
    }
}
