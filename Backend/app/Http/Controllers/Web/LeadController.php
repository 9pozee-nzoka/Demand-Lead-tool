<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $query = Lead::where('organization_id', $request->user()->organization_id)
            ->with(['opportunity', 'assignedUser']);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('score_label')) {
            $query->where('score_label', $request->score_label);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%')
                  ->orWhere('company', 'like', '%' . $request->search . '%');
            });
        }

        $leads = $query->orderBy('lead_score', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $teamMembers = User::where('organization_id', $request->user()->organization_id)
            ->where('status', 'active')
            ->get();

        return view('leads.index', compact('leads', 'teamMembers'));
    }

    public function create()
    {
        $projects = auth()->user()->organization->projects;
        $opportunities = auth()->user()->organization->opportunities()
            ->with('keyword')
            ->where('status', '!=', 'dismissed')
            ->orderBy('opportunity_score', 'desc')
            ->get();
        
        return view('leads.create', compact('projects', 'opportunities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'opportunity_id' => 'nullable|exists:opportunities,id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'location' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'message' => 'nullable|string',
            'source' => 'required|string|in:landing_page,whatsapp,phone,email,chat,referral,organic,paid,social,manual',
            'budget_range' => 'nullable|string|in:low,medium,high,enterprise',
            'company_size' => 'nullable|string|in:1-10,11-50,51-200,201+',
        ]);

        $validated['organization_id'] = auth()->user()->organization_id;
        $validated['status'] = 'new';

        $lead = Lead::create($validated);

        // Score the lead
        $scoringService = app(\App\Services\Leads\LeadScoringService::class);
        $scoringService->scoreAndUpdate($lead);

        return redirect()->route('leads.show', $lead)
            ->with('success', 'Lead created successfully.');
    }

    public function show(Lead $lead)
    {
        $this->authorize('view', $lead);

        $lead->load([
            'opportunity.keyword',
            'assignedUser',
            'project',
            'events' => function($q) {
                $q->orderBy('created_at', 'desc');
            },
        ]);

        $teamMembers = User::where('organization_id', auth()->user()->organization_id)
            ->where('status', 'active')
            ->get();

        return view('leads.show', compact('lead', 'teamMembers'));
    }

    public function edit(Lead $lead)
    {
        $this->authorize('update', $lead);

        $projects = auth()->user()->organization->projects;
        $opportunities = auth()->user()->organization->opportunities()
            ->with('keyword')
            ->where('status', '!=', 'dismissed')
            ->orderBy('opportunity_score', 'desc')
            ->get();
        
        return view('leads.edit', compact('lead', 'projects', 'opportunities'));
    }

    public function update(Request $request, Lead $lead)
    {
        $this->authorize('update', $lead);

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'opportunity_id' => 'nullable|exists:opportunities,id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'location' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'message' => 'nullable|string',
            'source' => 'required|string|in:landing_page,whatsapp,phone,email,chat,referral,organic,paid,social,manual',
            'budget_range' => 'nullable|string|in:low,medium,high,enterprise',
            'company_size' => 'nullable|string|in:1-10,11-50,51-200,201+',
            'status' => 'required|string|in:new,contacted,qualified,quotation,negotiation,won,lost,unqualified',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $lead->update($validated);

        // Re-score if significant fields changed
        if ($request->has(['message', 'location', 'budget_range', 'company_size'])) {
            $scoringService = app(\App\Services\Leads\LeadScoringService::class);
            $scoringService->scoreAndUpdate($lead);
        }

        return redirect()->route('leads.show', $lead)
            ->with('success', 'Lead updated successfully.');
    }

    public function destroy(Lead $lead)
    {
        $this->authorize('delete', $lead);

        $lead->delete();

        return redirect()->route('leads.index')
            ->with('success', 'Lead deleted successfully.');
    }

    public function assign(Request $request, Lead $lead)
    {
        $this->authorize('update', $lead);

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $lead->update([
            'assigned_to' => $validated['assigned_to'],
            'status' => 'assigned',
        ]);

        // Log event
        $lead->events()->create([
            'event_type' => 'lead_assigned',
            'description' => 'Lead assigned to ' . User::find($validated['assigned_to'])->name,
            'performed_by' => $request->user()->id,
        ]);

        return redirect()->route('leads.show', $lead)
            ->with('success', 'Lead assigned successfully.');
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        $this->authorize('update', $lead);

        $validated = $request->validate([
            'status' => 'required|in:new,contacted,qualified,converted,lost',
            'notes' => 'nullable|string',
        ]);

        $lead->update(['status' => $validated['status']]);

        // Log event
        $lead->events()->create([
            'event_type' => 'status_changed',
            'description' => 'Status changed to ' . $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'performed_by' => $request->user()->id,
        ]);

        return redirect()->route('leads.show', $lead)
            ->with('success', 'Lead status updated.');
    }
}
