<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protects all /api/admin/* routes.
 * Requires the authenticated user to have is_super_admin = true.
 * This is completely separate from the tenant role system.
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json([
                'message' => 'Super-admin access required.',
            ], 403);
        }

        return $next($request);
    }
}
