<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\AlertRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AlertController extends Controller
{
    /**
     * GET /api/v1/alerts
     */
    public function index(Request $request): JsonResponse
    {
        $alerts = Alert::where('organization_id', $request->user()->organization_id)
            ->with(['opportunity.keyword', 'lead'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(25);

        return response()->json($alerts);
    }

    /**
     * GET /api/v1/alert-rules
     */
    public function indexRules(Request $request): JsonResponse
    {
        $rules = AlertRule::where('organization_id', $request->user()->organization_id)
            ->with('project')
            ->get();

        return response()->json($rules);
    }

    /**
     * POST /api/v1/alert-rules
     */
    public function storeRule(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'project_id'      => ['nullable', 'integer', 'exists:projects,id'],
            'minimum_score'   => ['nullable', 'numeric', 'min:0', 'max:100'],
            'minimum_growth'  => ['nullable', 'numeric', 'min:0'],
            'intent'          => ['nullable', Rule::in(['any','commercial','transactional','local'])],
            'location'        => ['nullable', 'string', 'max:255'],
            'cooldown'        => ['nullable', 'integer', 'min:1'],
            'channels'        => ['nullable', 'array'],
            'channels.*'      => ['string', Rule::in(['sms','email','whatsapp','push','dashboard','webhook'])],
            'recipients'      => ['nullable', 'array'],
            'quiet_hours'     => ['nullable', 'array'],
        ]);

        $rule = AlertRule::create([
            'organization_id' => $request->user()->organization_id,
            ...$data,
        ]);

        return response()->json($rule, 201);
    }

    /**
     * PATCH /api/v1/alert-rules/{rule}
     */
    public function updateRule(Request $request, AlertRule $alertRule): JsonResponse
    {
        $this->authorizeRule($request, $alertRule);

        $data = $request->validate([
            'name'           => ['sometimes', 'string', 'max:255'],
            'minimum_score'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'minimum_growth' => ['nullable', 'numeric', 'min:0'],
            'cooldown'       => ['nullable', 'integer', 'min:1'],
            'channels'       => ['nullable', 'array'],
            'status'         => ['sometimes', Rule::in(['active', 'paused'])],
        ]);

        $alertRule->update($data);

        return response()->json($alertRule->fresh());
    }

    /**
     * DELETE /api/v1/alert-rules/{rule}
     */
    public function destroyRule(Request $request, AlertRule $alertRule): JsonResponse
    {
        $this->authorizeRule($request, $alertRule);
        $alertRule->delete();

        return response()->json(['message' => 'Alert rule deleted.']);
    }

    private function authorizeRule(Request $request, AlertRule $rule): void
    {
        if ((int) $rule->organization_id !== (int) $request->user()->organization_id) {
            abort(403);
        }
    }
}
