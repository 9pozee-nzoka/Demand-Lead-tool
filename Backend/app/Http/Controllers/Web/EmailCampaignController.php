<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaign;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\CampaignRecipient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmailCampaignController extends Controller
{
    /**
     * Display a listing of campaigns.
     */
    public function index(Request $request)
    {
        $query = EmailCampaign::forAuth()
            ->with(['creator', 'template', 'opportunity'])
            ->latest();

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->byStatus($request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $campaigns = $query->paginate(15);

        // Stats
        $stats = [
            'total' => EmailCampaign::forAuth()->count(),
            'draft' => EmailCampaign::forAuth()->byStatus('draft')->count(),
            'scheduled' => EmailCampaign::forAuth()->byStatus('scheduled')->count(),
            'sent' => EmailCampaign::forAuth()->byStatus('sent')->count(),
        ];

        return view('campaigns.index', compact('campaigns', 'stats'));
    }

    /**
     * Show the form for creating a new campaign.
     */
    public function create(Request $request)
    {
        $templates = EmailTemplate::forAuth()->active()->get();
        $opportunities = Opportunity::forAuth()
            ->whereIn('status', ['detected', 'reviewed', 'actioned'])
            ->get();

        $selectedTemplate = null;
        if ($request->filled('template_id')) {
            $selectedTemplate = EmailTemplate::forAuth()->find($request->template_id);
        }

        return view('campaigns.create', compact('templates', 'opportunities', 'selectedTemplate'));
    }

    /**
     * Store a newly created campaign.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'template_id' => 'nullable|exists:email_templates,id',
            'opportunity_id' => 'nullable|exists:opportunities,id',
            'subject' => 'required|string|max:255',
            'preview_text' => 'nullable|string|max:255',
            'html_content' => 'required|string',
            'text_content' => 'nullable|string',
            'audience_type' => 'required|in:all_leads,segment,manual,opportunity',
            'audience_filters' => 'nullable|array',
            'from_name' => 'nullable|string|max:255',
            'from_email' => 'nullable|email|max:255',
            'reply_to' => 'nullable|email|max:255',
            'track_opens' => 'boolean',
            'track_clicks' => 'boolean',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $validated['organization_id'] = Auth::user()->organization_id;
        $validated['created_by'] = Auth::id();
        $validated['status'] = $request->filled('scheduled_at') ? 'scheduled' : 'draft';
        $validated['track_opens'] = $request->boolean('track_opens', true);
        $validated['track_clicks'] = $request->boolean('track_clicks', true);

        $campaign = DB::transaction(function () use ($validated, $request) {
            $campaign = EmailCampaign::create($validated);

            // Add recipients based on audience type
            $this->addRecipients($campaign, $validated['audience_type'], $validated['audience_filters'] ?? []);

            // Increment template usage if used
            if ($campaign->template_id) {
                $campaign->template->incrementUsage();
            }

            return $campaign;
        });

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('success', 'Campaign created successfully.');
    }

    /**
     * Display the specified campaign.
     */
    public function show(EmailCampaign $campaign)
    {
        $this->authorize('view', $campaign);

        $campaign->load([
            'creator',
            'template',
            'opportunity',
            'recipients' => function ($query) {
                $query->latest()->limit(100);
            }
        ]);

        // Get recipient stats by status
        $recipientStats = CampaignRecipient::where('campaign_id', $campaign->id)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return view('campaigns.show', compact('campaign', 'recipientStats'));
    }

    /**
     * Show the form for editing the specified campaign.
     */
    public function edit(EmailCampaign $campaign)
    {
        $this->authorize('update', $campaign);

        if (!$campaign->isEditable()) {
            return redirect()
                ->route('campaigns.show', $campaign)
                ->with('error', 'This campaign cannot be edited.');
        }

        $templates = EmailTemplate::forAuth()->active()->get();
        $opportunities = Opportunity::forAuth()
            ->whereIn('status', ['detected', 'reviewed', 'actioned'])
            ->get();

        return view('campaigns.edit', compact('campaign', 'templates', 'opportunities'));
    }

    /**
     * Update the specified campaign.
     */
    public function update(Request $request, EmailCampaign $campaign)
    {
        $this->authorize('update', $campaign);

        if (!$campaign->isEditable()) {
            return redirect()
                ->route('campaigns.show', $campaign)
                ->with('error', 'This campaign cannot be edited.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'preview_text' => 'nullable|string|max:255',
            'html_content' => 'required|string',
            'text_content' => 'nullable|string',
            'from_name' => 'nullable|string|max:255',
            'from_email' => 'nullable|email|max:255',
            'reply_to' => 'nullable|email|max:255',
            'track_opens' => 'boolean',
            'track_clicks' => 'boolean',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $validated['track_opens'] = $request->boolean('track_opens', true);
        $validated['track_clicks'] = $request->boolean('track_clicks', true);

        // Update status if scheduled_at changed
        if ($request->filled('scheduled_at') && $campaign->status === 'draft') {
            $validated['status'] = 'scheduled';
        } elseif (!$request->filled('scheduled_at') && $campaign->status === 'scheduled') {
            $validated['status'] = 'draft';
        }

        $campaign->update($validated);

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('success', 'Campaign updated successfully.');
    }

    /**
     * Send or schedule campaign
     */
    public function send(Request $request, EmailCampaign $campaign)
    {
        $this->authorize('update', $campaign);

        if (!$campaign->canBeSent()) {
            return redirect()
                ->route('campaigns.show', $campaign)
                ->with('error', 'This campaign cannot be sent.');
        }

        $validated = $request->validate([
            'send_now' => 'boolean',
            'scheduled_at' => 'required_if:send_now,false|nullable|date|after:now',
        ]);

        if ($request->boolean('send_now')) {
            // Dispatch immediate send job (to be implemented)
            $campaign->update([
                'status' => 'sending',
                'started_at' => now(),
            ]);

            return redirect()
                ->route('campaigns.show', $campaign)
                ->with('success', 'Campaign is being sent.');
        } else {
            // Schedule for later
            $campaign->update([
                'status' => 'scheduled',
                'scheduled_at' => $validated['scheduled_at'],
            ]);

            return redirect()
                ->route('campaigns.show', $campaign)
                ->with('success', 'Campaign scheduled successfully.');
        }
    }

    /**
     * Pause a sending campaign
     */
    public function pause(EmailCampaign $campaign)
    {
        $this->authorize('update', $campaign);

        if ($campaign->status !== 'sending') {
            return redirect()
                ->route('campaigns.show', $campaign)
                ->with('error', 'Only sending campaigns can be paused.');
        }

        $campaign->update(['status' => 'paused']);

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('success', 'Campaign paused.');
    }

    /**
     * Cancel a scheduled campaign
     */
    public function cancel(EmailCampaign $campaign)
    {
        $this->authorize('update', $campaign);

        if ($campaign->status !== 'scheduled') {
            return redirect()
                ->route('campaigns.show', $campaign)
                ->with('error', 'Only scheduled campaigns can be cancelled.');
        }

        $campaign->update(['status' => 'cancelled']);

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('success', 'Campaign cancelled.');
    }

    /**
     * Duplicate a campaign
     */
    public function duplicate(EmailCampaign $campaign)
    {
        $this->authorize('view', $campaign);

        $newCampaign = $campaign->replicate();
        $newCampaign->name = $campaign->name . ' (Copy)';
        $newCampaign->status = 'draft';
        $newCampaign->scheduled_at = null;
        $newCampaign->started_at = null;
        $newCampaign->completed_at = null;
        $newCampaign->total_recipients = 0;
        $newCampaign->sent_count = 0;
        $newCampaign->delivered_count = 0;
        $newCampaign->opened_count = 0;
        $newCampaign->clicked_count = 0;
        $newCampaign->bounced_count = 0;
        $newCampaign->created_by = Auth::id();
        $newCampaign->save();

        return redirect()
            ->route('campaigns.edit', $newCampaign)
            ->with('success', 'Campaign duplicated successfully.');
    }

    /**
     * Remove the specified campaign.
     */
    public function destroy(EmailCampaign $campaign)
    {
        $this->authorize('delete', $campaign);

        if (in_array($campaign->status, ['sending', 'sent'])) {
            return redirect()
                ->route('campaigns.index')
                ->with('error', 'Sent or sending campaigns cannot be deleted.');
        }

        $campaign->delete();

        return redirect()
            ->route('campaigns.index')
            ->with('success', 'Campaign deleted successfully.');
    }

    /**
     * Add recipients to campaign based on audience type
     */
    protected function addRecipients(EmailCampaign $campaign, string $audienceType, array $filters)
    {
        $recipients = [];

        switch ($audienceType) {
            case 'all_leads':
                $leads = Lead::forOrganization($campaign->organization_id)
                    ->whereNotNull('email')
                    ->get();
                break;

            case 'opportunity':
                if ($campaign->opportunity_id) {
                    $leads = Lead::forOrganization($campaign->organization_id)
                        ->where('opportunity_id', $campaign->opportunity_id)
                        ->whereNotNull('email')
                        ->get();
                } else {
                    $leads = collect();
                }
                break;

            case 'segment':
                $query = Lead::forOrganization($campaign->organization_id)
                    ->whereNotNull('email');

                // Apply filters
                if (!empty($filters['status'])) {
                    $query->where('status', $filters['status']);
                }
                if (!empty($filters['score_min'])) {
                    $query->where('score', '>=', $filters['score_min']);
                }
                if (!empty($filters['created_after'])) {
                    $query->where('created_at', '>=', $filters['created_after']);
                }

                $leads = $query->get();
                break;

            default:
                $leads = collect();
        }

        // Create recipients
        foreach ($leads as $lead) {
            $recipients[] = [
                'campaign_id' => $campaign->id,
                'lead_id' => $lead->id,
                'email' => $lead->email,
                'first_name' => $lead->first_name,
                'last_name' => $lead->last_name,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($recipients)) {
            CampaignRecipient::insert($recipients);
            $campaign->update(['total_recipients' => count($recipients)]);
        }
    }
}
