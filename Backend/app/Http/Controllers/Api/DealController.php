<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DealController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $deals = Deal::where('organization_id', $request->user()->organization_id)
            ->with(['lead', 'contact', 'assignedUser'])
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($deals);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lead_id'           => ['nullable', 'integer', 'exists:leads,id'],
            'contact_id'        => ['nullable', 'integer', 'exists:contacts,id'],
            'title'             => ['required', 'string', 'max:255'],
            'value'             => ['nullable', 'numeric', 'min:0'],
            'currency'          => ['nullable', 'string', 'size:3'],
            'stage'             => ['nullable', Rule::in(['new','contacted','qualified','quotation','negotiation','won','lost'])],
            'assigned_to'       => ['nullable', 'integer', 'exists:users,id'],
            'expected_close_at' => ['nullable', 'date'],
        ]);

        $deal = Deal::create([
            'organization_id' => $request->user()->organization_id,
            ...$data,
        ]);

        return response()->json($deal->load('lead', 'contact'), 201);
    }

    public function update(Request $request, Deal $deal): JsonResponse
    {
        $this->authorizeDeal($request, $deal);

        $data = $request->validate([
            'title'             => ['sometimes', 'string', 'max:255'],
            'value'             => ['nullable', 'numeric', 'min:0'],
            'stage'             => ['sometimes', Rule::in(['new','contacted','qualified','quotation','negotiation','won','lost'])],
            'status'            => ['sometimes', Rule::in(['open','won','lost'])],
            'lost_reason'       => ['nullable', 'string'],
            'assigned_to'       => ['nullable', 'integer', 'exists:users,id'],
            'expected_close_at' => ['nullable', 'date'],
        ]);

        if (($data['status'] ?? null) === 'won' && ! $deal->won_at) {
            $data['won_at'] = now();
        }
        if (($data['status'] ?? null) === 'lost' && ! $deal->lost_at) {
            $data['lost_at'] = now();
        }

        $deal->update($data);

        return response()->json($deal->fresh());
    }

    private function authorizeDeal(Request $request, Deal $deal): void
    {
        if ((int) $deal->organization_id !== (int) $request->user()->organization_id) {
            abort(403);
        }
    }
}
