<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verify that every route-model-bound Eloquent instance belongs to the
 * authenticated user's organization.
 *
 * NOTE: Uses `isset($model->organization_id)` — NOT `property_exists()`.
 * Eloquent stores attributes in the $attributes bag accessed via __get, so
 * property_exists() always returns false and the check would silently pass
 * without validating anything.
 */
class EnsureSameTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->organization_id) {
            return response()->json(['message' => 'Organization context required.'], 403);
        }

        foreach ($request->route()->parameters() as $model) {
            if (! $model instanceof Model) {
                continue;
            }

            // Only check models that actually have an organization_id column
            if (! isset($model->organization_id)) {
                continue;
            }

            if ((int) $model->organization_id !== (int) $user->organization_id) {
                return response()->json([
                    'message' => 'This resource does not belong to your organization.',
                ], 403);
            }
        }

        return $next($request);
    }
}
