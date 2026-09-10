<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ImpersonateController extends Controller
{
    /**
     * Start impersonating a user
     */
    public function start(User $user): RedirectResponse
    {
        // Ensure only super admins can impersonate
        if (!Auth::user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        // Cannot impersonate yourself
        if (Auth::id() === $user->id) {
            return redirect()->back()->with('error', 'You cannot impersonate yourself.');
        }

        // Cannot impersonate another super admin
        if ($user->isSuperAdmin()) {
            return redirect()->back()->with('error', 'You cannot impersonate another super admin.');
        }

        // Store the original user ID in session
        Session::put('impersonate_original_id', Auth::id());

        // Log the impersonation for audit
        AuditLog::record(
            action: 'impersonate.start',
            entity: $user,
            metadata: [
                'admin_id' => Auth::id(),
                'admin_email' => Auth::user()->email,
                'admin_name' => Auth::user()->name,
                'target_user_id' => $user->id,
                'target_user_email' => $user->email,
                'target_user_name' => $user->name,
                'target_organization' => $user->organization->name ?? 'N/A',
                'description' => 'Super admin started impersonating user',
            ],
            organizationId: $user->organization_id,
            userId: Auth::id(),
        );

        // Switch to the target user
        Auth::login($user);

        return redirect()->route('dashboard')->with('success', "You are now impersonating {$user->name}. Click 'Stop Impersonating' to return to your account.");
    }

    /**
     * Stop impersonating and return to original user
     */
    public function stop(): RedirectResponse
    {
        // Get the original user ID from session
        $originalUserId = Session::get('impersonate_original_id');

        if (!$originalUserId) {
            return redirect()->route('dashboard')->with('error', 'You are not currently impersonating anyone.');
        }

        // Find the original user
        $originalUser = User::find($originalUserId);

        if (!$originalUser) {
            Session::forget('impersonate_original_id');
            return redirect()->route('login')->with('error', 'Original user not found. Please log in again.');
        }

        // Log the end of impersonation
        $impersonatedUser = Auth::user();
        
        AuditLog::record(
            action: 'impersonate.stop',
            entity: $impersonatedUser,
            metadata: [
                'admin_id' => $originalUser->id,
                'admin_email' => $originalUser->email,
                'admin_name' => $originalUser->name,
                'impersonated_user_id' => $impersonatedUser->id,
                'impersonated_user_email' => $impersonatedUser->email,
                'impersonated_user_name' => $impersonatedUser->name,
                'description' => 'Super admin stopped impersonating user',
            ],
            organizationId: $originalUser->organization_id,
            userId: $originalUser->id,
        );

        // Remove impersonation session
        Session::forget('impersonate_original_id');

        // Switch back to the original user
        Auth::login($originalUser);

        return redirect()->route('admin.dashboard')->with('success', 'You have stopped impersonating and returned to your account.');
    }

    /**
     * Check if currently impersonating
     */
    public function isImpersonating(): bool
    {
        return Session::has('impersonate_original_id');
    }
}
