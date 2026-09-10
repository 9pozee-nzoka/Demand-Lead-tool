<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ShareImpersonationData
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $isImpersonating = Session::has('impersonate_original_id');
            $originalUser = null;

            if ($isImpersonating) {
                $originalUserId = Session::get('impersonate_original_id');
                $originalUser = \App\Models\User::find($originalUserId);
            }

            View::share('isImpersonating', $isImpersonating);
            View::share('impersonatingOriginalUser', $originalUser);
        }

        return $next($request);
    }
}
