<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Lead::forOrganization($request->user()->organization_id)
            ->with(['opportunity', 'campaign', 'assignedUser', 'project']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('score_label')) {
            $query->where('score_label', $request->score_label);
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        return response()->json($query->orderByDesc('lead_score')->paginate(25));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id'     => ['required', 'integer', 'exists:projects,id'],
            'opportunity_id' => ['nullable', 'integer', 'exists:opportunities,id'],
            'campaign_id'    => ['nullable', 'integer', 'exists:campaigns,id'],
            'name'           => ['nullable', 'string', 'max:255'],
            'email'          => ['nullable', 'email', 'max:255'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'location'       => ['nullable', 'string', 'max:255'],
            'company'        => ['nullable', 'string', 'max:255'],
            'source'         => ['nullable', 'string', 'max:100'],
            'intent'         => ['nullable', Rule::in(['informational','commercial','transactional','local','unknown'])],
        ]);

        $lead = Lead::create([
            'organization_id' => $request->user()->organization_id,
            ...$data,
            'status'          => 'new',
        ]);

        LeadEvent::create([
            'lead_id'     => $lead->id,
            'type'        => 'lead_created',
            'metadata'    => ['source' => $lead->source],
            'occurred_at' => now(),
        ]);

        return response()->json($lead->load('opportunity', 'campaign'), 201);
    }

    public function show(Request $request, Lead $lead): JsonResponse
    {
        $this->authorizeLead($request, $lead);

        return response()->json(
            $lead->load(['opportunity', 'campaign', 'assignedUser', 'events', 'deals', 'tasks', 'notes', 'contact'])
        );
    }

    public function update(Request $request, Lead $lead): JsonResponse
    {
        $this->authorizeLead($request, $lead);

        $data = $request->validate([
            'name'     => ['sometimes', 'string', 'max:255'],
            'email'    => ['nullable', 'email'],
            'phone'    => ['nullable', 'string', 'max:30'],
            'location' => ['nullable', 'string', 'max:255'],
            'company'  => ['nullable', 'string', 'max:255'],
            'status'   => ['sometimes', Rule::in(['new','contacted','qualified','quotation','negotiation','won','lost','unqualified'])],
        ]);

        $lead->update($data);

        return response()->json($lead->fresh());
    }

    public function qualify(Request $request, Lead $lead): JsonResponse
    {
        $this->authorizeLead($request, $lead);

        $data = $request->validate([
            'qualification_summary' => ['required', 'string'],
            'qualification_data'    => ['nullable', 'array'],
            'lead_score'            => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $lead->fill($data);
        if (isset($data['lead_score'])) {
            $lead->recalculateLabel();
        }
        $lead->status       = 'qualified';
        $lead->qualified_at = now();
        $lead->save();

        LeadEvent::create([
            'lead_id'     => $lead->id,
            'type'        => 'lead_qualified',
            'metadata'    => ['score' => $lead->lead_score, 'label' => $lead->score_label],
            'occurred_at' => now(),
        ]);

        return response()->json($lead->fresh());
    }

    public function assign(Request $request, Lead $lead): JsonResponse
    {
        $this->authorizeLead($request, $lead);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $assignee = User::findOrFail($data['user_id']);
        if ((int) $assignee->organization_id !== (int) $request->user()->organization_id) {
            abort(422, 'User does not belong to your organization.');
        }

        $lead->update([
            'assigned_to'  => $assignee->id,
            'contacted_at' => $lead->contacted_at ?? now(),
        ]);

        LeadEvent::create([
            'lead_id'     => $lead->id,
            'type'        => 'lead_assigned',
            'metadata'    => ['assigned_to' => $assignee->id, 'name' => $assignee->name],
            'occurred_at' => now(),
        ]);

        return response()->json($lead->fresh()->load('assignedUser'));
    }

    public function convert(Request $request, Lead $lead): JsonResponse
    {
        $this->authorizeLead($request, $lead);

        $data = $request->validate([
            'deal_title' => ['nullable', 'string', 'max:255'],
            'deal_value' => ['nullable', 'numeric', 'min:0'],
        ]);

        $lead->update(['status' => 'won']);

        $deal = $lead->deals()->create([
            'organization_id' => $lead->organization_id,
            'title'           => $data['deal_title'] ?? "Deal from {$lead->name}",
            'value'           => $data['deal_value'] ?? null,
            'stage'           => 'new',
            'status'          => 'open',
            'assigned_to'     => $lead->assigned_to,
        ]);

        LeadEvent::create([
            'lead_id'     => $lead->id,
            'type'        => 'lead_converted',
            'metadata'    => ['deal_id' => $deal->id],
            'occurred_at' => now(),
        ]);

        return response()->json(['lead' => $lead->fresh(), 'deal' => $deal], 201);
    }

    private function authorizeLead(Request $request, Lead $lead): void
    {
        if ((int) $lead->organization_id !== (int) $request->user()->organization_id) {
            abort(403);
        }
    }
}
