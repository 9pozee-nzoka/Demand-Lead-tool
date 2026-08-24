<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * GET /api/v1/users
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::forOrganization($request->user()->organization_id)
            ->orderBy('name')
            ->get();

        return response()->json($users);
    }

    /**
     * POST /api/v1/users
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'organization_id' => $request->user()->organization_id,
            'name'            => $data['name'],
            'email'           => $data['email'],
            'phone'           => $data['phone'] ?? null,
            'password'        => $data['password'],
            'role'            => $data['role'],
            'status'          => 'active',
        ]);

        AuditLog::record('user.created', $user, ['role' => $user->role]);

        return response()->json($user, 201);
    }

    /**
     * GET /api/v1/users/{user}
     */
    public function show(Request $request, User $user): JsonResponse
    {
        $this->authorizeOrgAccess($request, $user);

        return response()->json($user->load('assignedLeads', 'assignedDeals'));
    }

    /**
     * PATCH /api/v1/users/{user}
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorizeOrgAccess($request, $user);

        $user->update($request->validated());

        AuditLog::record('user.updated', $user, $request->validated());

        return response()->json($user->fresh());
    }

    /**
     * DELETE /api/v1/users/{user}
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorizeOrgAccess($request, $user);

        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Insufficient permissions.'], 403);
        }

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        AuditLog::record('user.deleted', $user);
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'User removed.']);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function authorizeOrgAccess(Request $request, User $user): void
    {
        if ((int) $user->organization_id !== (int) $request->user()->organization_id) {
            abort(403, 'This resource does not belong to your organization.');
        }
    }
}
