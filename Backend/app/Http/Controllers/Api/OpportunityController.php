<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateOpportunityExplanation;
use App\Models\Opportunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OpportunityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Opportunity::whereHas('project', function ($q) use ($request) {
            $q->where('organization_id', $request->user()->organization_id);
        })->with(['keyword', 'location', 'project']);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('min_score')) {
            $query->where('opportunity_score', '>=', (float) $request->min_score);
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $opportunities = $query->orderByDesc('opportunity_score')->paginate(25);

        return response()->json($opportunities);
    }

    public function show(Request $request, Opportunity $opportunity): JsonResponse
    {
        $this->authorize($request, $opportunity);

        return response()->json(
            $opportunity->load(['keyword', 'location', 'project', 'campaigns', 'landingPages', 'leads'])
        );
    }

    public function update(Request $request, Opportunity $opportunity): JsonResponse
    {
        $this->authorize($request, $opportunity);

        $data = $request->validate([
            'status' => ['required', Rule::in(['reviewed', 'actioned', 'dismissed'])],
        ]);

        $opportunity->update($data);

        return response()->json($opportunity->fresh());
    }

    public function action(Request $request, Opportunity $opportunity): JsonResponse
    {
        $this->authorize($request, $opportunity);

        $data = $request->validate([
            'action' => ['required', Rule::in(['create_landing_page', 'create_campaign', 'send_alert', 'notify_sales'])],
        ]);

        // Update status to actioned
        $opportunity->update(['status' => 'actioned']);

        match ($data['action']) {
            'create_landing_page' => \App\Jobs\CreateLandingPageFromOpportunity::dispatchIf(
                class_exists(\App\Jobs\CreateLandingPageFromOpportunity::class), $opportunity->id
            ),
            'send_alert'   => \App\Jobs\SendAlert::dispatch($opportunity->id)->onQueue('notifications'),
            'notify_sales' => GenerateOpportunityExplanation::dispatch($opportunity->id)->onQueue('processing'),
            default        => null,
        };

        return response()->json([
            'message'     => 'Action queued.',
            'action'      => $data['action'],
            'opportunity' => $opportunity->fresh(),
        ]);
    }

    public function dismiss(Request $request, Opportunity $opportunity): JsonResponse
    {
        $this->authorize($request, $opportunity);

        $opportunity->update(['status' => 'dismissed']);

        return response()->json(['message' => 'Opportunity dismissed.']);
    }

    private function authorize(Request $request, Opportunity $opportunity): void
    {
        if ((int) $opportunity->project->organization_id !== (int) $request->user()->organization_id) {
            abort(403);
        }
    }
}
