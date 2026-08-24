<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verify that any route-model-bound entity belongs to the authenticated user's
 * organization. Attach to resource routes that accept model bindings.
 *
 * Usage: ->middleware('tenant')
 */
class EnsureSameTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->organization_id) {
            return response()->json(['message' => 'Organization context required.'], 403);
        }

        // Check every route-model-bound parameter that has organization_id
        foreach ($request->route()->parameters() as $model) {
            if (
                is_object($model)
                && property_exists($model, 'organization_id')
                && (int) $model->organization_id !== (int) $user->organization_id
            ) {
                return response()->json(['message' => 'This resource does not belong to your organization.'], 403);
            }
        }

        return $next($request);
    }
}
