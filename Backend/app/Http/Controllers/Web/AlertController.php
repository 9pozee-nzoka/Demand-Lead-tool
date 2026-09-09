<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\AlertRule;
use App\Models\User;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = $request->user()->organization_id;

        // Get recent alerts
        $alerts = Alert::where('organization_id', $organizationId)
            ->with(['opportunity', 'lead', 'rule'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get alert rules
        $rules = AlertRule::where('organization_id', $organizationId)
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total_alerts' => Alert::where('organization_id', $organizationId)->count(),
            'active_rules' => AlertRule::where('organization_id', $organizationId)->where('status', 'active')->count(),
            'sent_today' => Alert::where('organization_id', $organizationId)->whereDate('created_at', today())->count(),
            'unread' => Alert::where('organization_id', $organizationId)->where('status', '!=', 'read')->count(),
        ];

        return view('alerts.index', compact('alerts', 'rules', 'stats'));
    }

    public function rules(Request $request)
    {
        $organizationId = $request->user()->organization_id;

        $rules = AlertRule::where('organization_id', $organizationId)
            ->with('project')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('alerts.rules', compact('rules'));
    }

    public function createRule()
    {
        $projects = auth()->user()->organization->projects;
        $users = User::where('organization_id', auth()->user()->organization_id)
            ->where('status', 'active')
            ->get();

        return view('alerts.create-rule', compact('projects', 'users'));
    }

    public function storeRule(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'nullable|exists:projects,id',
            'alert_type' => 'required|in:spike,rising_trend,threshold,opportunity_detected,custom',
            'trigger_condition' => 'required|array',
            'channels' => 'required|array',
            'channels.*' => 'in:email,sms,in_app',
            'recipient_user_ids' => 'nullable|array',
            'recipient_user_ids.*' => 'exists:users,id',
            'is_active' => 'boolean',
        ]);

        $validated['organization_id'] = $request->user()->organization_id;
        $validated['status'] = $request->has('is_active') ? 'active' : 'paused';

        AlertRule::create($validated);

        return redirect()->route('alerts.rules')
            ->with('success', 'Alert rule created successfully.');
    }

    public function editRule(AlertRule $alertRule)
    {
        $this->authorize('view', $alertRule);

        $projects = auth()->user()->organization->projects;
        $users = User::where('organization_id', auth()->user()->organization_id)
            ->where('status', 'active')
            ->get();

        return view('alerts.edit-rule', compact('alertRule', 'projects', 'users'));
    }

    public function updateRule(Request $request, AlertRule $alertRule)
    {
        $this->authorize('update', $alertRule);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'nullable|exists:projects,id',
            'alert_type' => 'required|in:spike,rising_trend,threshold,opportunity_detected,custom',
            'trigger_condition' => 'required|array',
            'channels' => 'required|array',
            'channels.*' => 'in:email,sms,in_app',
            'recipient_user_ids' => 'nullable|array',
            'recipient_user_ids.*' => 'exists:users,id',
            'is_active' => 'boolean',
        ]);

        $validated['status'] = $request->has('is_active') ? 'active' : 'paused';

        $alertRule->update($validated);

        return redirect()->route('alerts.rules')
            ->with('success', 'Alert rule updated successfully.');
    }

    public function destroyRule(AlertRule $alertRule)
    {
        $this->authorize('delete', $alertRule);

        $alertRule->delete();

        return redirect()->route('alerts.rules')
            ->with('success', 'Alert rule deleted successfully.');
    }

    public function toggleRule(AlertRule $alertRule)
    {
        $this->authorize('update', $alertRule);

        $newStatus = $alertRule->status === 'active' ? 'paused' : 'active';
        
        $alertRule->update([
            'status' => $newStatus,
        ]);

        return redirect()->route('alerts.rules')
            ->with('success', 'Alert rule ' . ($newStatus === 'active' ? 'enabled' : 'disabled') . ' successfully.');
    }

    public function markAsRead(Alert $alert)
    {
        $this->authorize('view', $alert);

        $alert->update(['status' => 'read']);

        return back()->with('success', 'Alert marked as read.');
    }

    public function markAllAsRead(Request $request)
    {
        Alert::where('organization_id', $request->user()->organization_id)
            ->where('status', '!=', 'read')
            ->update(['status' => 'read']);

        return back()->with('success', 'All alerts marked as read.');
    }
}
