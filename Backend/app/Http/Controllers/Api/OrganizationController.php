<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    /**
     * GET /api/v1/organization
     */
    public function show(Request $request): JsonResponse
    {
        $org = $request->user()
            ->organization
            ->load(['plan', 'activeSubscription']);

        return response()->json($org);
    }

    /**
     * PATCH /api/v1/organization
     */
    public function update(UpdateOrganizationRequest $request): JsonResponse
    {
        $org = $request->user()->organization;
        $org->update($request->validated());

        AuditLog::record('organization.updated', $org, $request->validated());

        return response()->json($org->fresh()->load('plan'));
    }
}
