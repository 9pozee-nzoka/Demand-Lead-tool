<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('organization_id', auth()->user()->organization_id)
            ->where('is_super_admin', false);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);

        // Stats
        $allUsers = User::where('organization_id', auth()->user()->organization_id)
            ->where('is_super_admin', false);
        
        $stats = [
            'active' => (clone $allUsers)->where('status', 'active')->count(),
            'pending' => (clone $allUsers)->where('status', 'pending')->count(),
            'admins' => (clone $allUsers)->whereIn('role', ['owner', 'admin'])->count(),
        ];

        return view('team.index', compact('users', 'stats'));
    }

    public function invite(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:viewer,analyst,marketing,sales,admin',
        ]);

        // Generate random password
        $password = Str::random(16);

        $user = User::create([
            'organization_id' => auth()->user()->organization_id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'password' => Hash::make($password),
            'status' => 'pending',
        ]);

        // TODO: Send invitation email with password
        // For now, we'll flash a message with the credentials
        // In production, you should send an email with a password reset link

        return redirect()->route('team.index')
            ->with('success', "Invitation sent to {$user->name}! (Password: {$password})");
    }

    public function updateStatus(Request $request, User $user)
    {
        // Check permissions
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Ensure same organization
        if ($user->organization_id !== auth()->user()->organization_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:active,inactive,pending',
        ]);

        $user->update(['status' => $validated['status']]);

        return response()->json(['success' => true]);
    }

    public function destroy(User $user)
    {
        // Check permissions
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Ensure same organization
        if ($user->organization_id !== auth()->user()->organization_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Can't delete owner
        if ($user->role === 'owner') {
            return response()->json(['success' => false, 'message' => 'Cannot delete owner'], 400);
        }

        // Can't delete yourself
        if ($user->id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Cannot delete yourself'], 400);
        }

        $user->delete();

        return response()->json(['success' => true]);
    }
}
