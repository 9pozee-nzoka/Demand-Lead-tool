<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DealController extends Controller
{
    /**
     * Display CRM Kanban board
     */
    public function index(Request $request)
    {
        $organizationId = $request->user()->organization_id;

        // Get all open deals grouped by stage
        $stages = ['new', 'contacted', 'qualified', 'quotation', 'negotiation', 'won', 'lost'];
        
        $dealsByStage = [];
        foreach ($stages as $stage) {
            $dealsByStage[$stage] = Deal::where('organization_id', $organizationId)
                ->where('stage', $stage)
                ->where('status', '!=', 'archived')
                ->with(['lead', 'contact', 'assignedUser'])
                ->orderBy('expected_close_at', 'asc')
                ->get();
        }

        // Calculate stats
        $stats = [
            'total_deals' => Deal::where('organization_id', $organizationId)
                ->where('status', 'open')
                ->count(),
            'total_value' => Deal::where('organization_id', $organizationId)
                ->where('status', 'open')
                ->sum('value'),
            'won_this_month' => Deal::where('organization_id', $organizationId)
                ->where('status', 'won')
                ->whereMonth('won_at', now()->month)
                ->sum('value'),
            'close_rate' => $this->calculateCloseRate($organizationId),
        ];

        // Get team members for assignment
        $teamMembers = User::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->get();

        return view('crm.deals.index', compact('dealsByStage', 'stats', 'teamMembers', 'stages'));
    }

    /**
     * Show deal creation form
     */
    public function create(Request $request)
    {
        $organizationId = $request->user()->organization_id;

        $leads = Lead::where('organization_id', $organizationId)
            ->whereIn('status', ['new', 'qualified', 'contacted'])
            ->orderBy('lead_score', 'desc')
            ->get();

        $contacts = Contact::where('organization_id', $organizationId)
            ->orderBy('name')
            ->get();

        $teamMembers = User::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->get();

        return view('crm.deals.create', compact('leads', 'contacts', 'teamMembers'));
    }

    /**
     * Store new deal
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'lead_id' => 'nullable|exists:leads,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'value' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'stage' => 'required|in:new,qualified,proposal,negotiation,closed_won,closed_lost',
            'assigned_to' => 'nullable|exists:users,id',
            'expected_close_at' => 'nullable|date',
        ]);

        $validated['organization_id'] = $request->user()->organization_id;
        $validated['status'] = in_array($validated['stage'], ['closed_won', 'closed_lost']) 
            ? ($validated['stage'] === 'closed_won' ? 'won' : 'lost')
            : 'open';
        $validated['currency'] = $validated['currency'] ?? 'USD';

        $deal = Deal::create($validated);

        // Update lead status if lead is associated
        if ($deal->lead_id) {
            $deal->lead->update(['status' => 'converted']);
        }

        return redirect()
            ->route('deals.show', $deal)
            ->with('success', 'Deal created successfully!');
    }

    /**
     * Show single deal
     */
    public function show(Deal $deal)
    {
        $deal->load(['lead', 'contact', 'assignedUser', 'tasks', 'notes.user']);

        return view('crm.deals.show', compact('deal'));
    }

    /**
     * Show edit form
     */
    public function edit(Deal $deal)
    {
        $organizationId = auth()->user()->organization_id;

        $leads = Lead::where('organization_id', $organizationId)->get();
        $contacts = Contact::where('organization_id', $organizationId)->get();
        $teamMembers = User::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->get();

        return view('crm.deals.edit', compact('deal', 'leads', 'contacts', 'teamMembers'));
    }

    /**
     * Update deal
     */
    public function update(Request $request, Deal $deal)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'lead_id' => 'nullable|exists:leads,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'value' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'stage' => 'required|in:new,qualified,proposal,negotiation,closed_won,closed_lost',
            'assigned_to' => 'nullable|exists:users,id',
            'expected_close_at' => 'nullable|date',
            'lost_reason' => 'nullable|string',
        ]);

        // Handle status based on stage
        if ($validated['stage'] === 'closed_won') {
            $validated['status'] = 'won';
            $validated['won_at'] = now();
        } elseif ($validated['stage'] === 'closed_lost') {
            $validated['status'] = 'lost';
            $validated['lost_at'] = now();
        } else {
            $validated['status'] = 'open';
        }

        $deal->update($validated);

        return redirect()
            ->route('deals.show', $deal)
            ->with('success', 'Deal updated successfully!');
    }

    /**
     * Update deal stage (for drag-and-drop)
     */
    public function updateStage(Request $request, Deal $deal)
    {
        $validated = $request->validate([
            'stage' => 'required|in:new,qualified,proposal,negotiation,closed_won,closed_lost',
        ]);

        $oldStage = $deal->stage;
        $newStage = $validated['stage'];

        // Update stage
        $deal->stage = $newStage;

        // Handle status changes
        if ($newStage === 'closed_won') {
            $deal->status = 'won';
            $deal->won_at = now();
        } elseif ($newStage === 'closed_lost') {
            $deal->status = 'lost';
            $deal->lost_at = now();
        } else {
            $deal->status = 'open';
        }

        $deal->save();

        return response()->json([
            'success' => true,
            'message' => "Deal moved from {$oldStage} to {$newStage}",
            'deal' => $deal->load(['lead', 'contact', 'assignedUser']),
        ]);
    }

    /**
     * Delete deal
     */
    public function destroy(Deal $deal)
    {
        $deal->delete();

        return redirect()
            ->route('deals.index')
            ->with('success', 'Deal deleted successfully.');
    }

    /**
     * Show pipeline analytics
     */
    public function analytics(Request $request)
    {
        $organizationId = $request->user()->organization_id;

        // Deals by stage
        $dealsByStage = Deal::where('organization_id', $organizationId)
            ->where('status', 'open')
            ->select('stage', DB::raw('COUNT(*) as count'), DB::raw('SUM(value) as total_value'))
            ->groupBy('stage')
            ->get();

        // Monthly revenue trend (last 12 months)
        $monthlyRevenue = Deal::where('organization_id', $organizationId)
            ->where('status', 'won')
            ->where('won_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw('DATE_FORMAT(won_at, "%Y-%m") as month'),
                DB::raw('SUM(value) as revenue'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Top performers
        $topPerformers = Deal::where('organization_id', $organizationId)
            ->where('status', 'won')
            ->whereNotNull('assigned_to')
            ->select('assigned_to', DB::raw('COUNT(*) as deals_won'), DB::raw('SUM(value) as total_revenue'))
            ->groupBy('assigned_to')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->with('assignedUser')
            ->get();

        // Average deal cycle time (days from creation to closed)
        $avgCycleTime = Deal::where('organization_id', $organizationId)
            ->where('status', 'won')
            ->whereNotNull('won_at')
            ->select(DB::raw('AVG(DATEDIFF(won_at, created_at)) as avg_days'))
            ->value('avg_days');

        // Conversion funnel
        $conversionFunnel = [
            'new' => Deal::where('organization_id', $organizationId)->where('stage', 'new')->count(),
            'qualified' => Deal::where('organization_id', $organizationId)->where('stage', 'qualified')->count(),
            'proposal' => Deal::where('organization_id', $organizationId)->where('stage', 'proposal')->count(),
            'negotiation' => Deal::where('organization_id', $organizationId)->where('stage', 'negotiation')->count(),
            'won' => Deal::where('organization_id', $organizationId)->where('status', 'won')->count(),
        ];

        $stats = [
            'total_pipeline_value' => Deal::where('organization_id', $organizationId)
                ->where('status', 'open')
                ->sum('value'),
            'total_won_value' => Deal::where('organization_id', $organizationId)
                ->where('status', 'won')
                ->sum('value'),
            'total_deals' => Deal::where('organization_id', $organizationId)->count(),
            'close_rate' => $this->calculateCloseRate($organizationId),
            'avg_deal_value' => Deal::where('organization_id', $organizationId)
                ->where('status', 'won')
                ->avg('value'),
            'avg_cycle_time' => round($avgCycleTime ?? 0),
        ];

        return view('crm.deals.analytics', compact(
            'dealsByStage',
            'monthlyRevenue',
            'topPerformers',
            'conversionFunnel',
            'stats'
        ));
    }

    /**
     * Calculate close rate percentage
     */
    protected function calculateCloseRate(int $organizationId): float
    {
        $totalDeals = Deal::where('organization_id', $organizationId)
            ->whereIn('status', ['won', 'lost'])
            ->count();

        if ($totalDeals === 0) {
            return 0;
        }

        $wonDeals = Deal::where('organization_id', $organizationId)
            ->where('status', 'won')
            ->count();

        return round(($wonDeals / $totalDeals) * 100, 1);
    }

    /**
     * Quick create deal from lead
     */
    public function createFromLead(Request $request, Lead $lead)
    {
        $organizationId = $request->user()->organization_id;

        $teamMembers = User::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->get();

        return view('crm.deals.create-from-lead', compact('lead', 'teamMembers'));
    }

    /**
     * Mark deal as won
     */
    public function markAsWon(Deal $deal)
    {
        $deal->update([
            'stage' => 'closed_won',
            'status' => 'won',
            'won_at' => now(),
        ]);

        return back()->with('success', 'Deal marked as won! 🎉');
    }

    /**
     * Mark deal as lost
     */
    public function markAsLost(Request $request, Deal $deal)
    {
        $validated = $request->validate([
            'lost_reason' => 'required|string|max:500',
        ]);

        $deal->update([
            'stage' => 'closed_lost',
            'status' => 'lost',
            'lost_at' => now(),
            'lost_reason' => $validated['lost_reason'],
        ]);

        return back()->with('success', 'Deal marked as lost.');
    }
}
