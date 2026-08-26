<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Deal;
use App\Models\Keyword;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\Billing\UsageMeteringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function __construct(
        private readonly UsageMeteringService $metering,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // System metrics  —  GET /api/admin/metrics
    // ─────────────────────────────────────────────────────────────────────────

    public function metrics(): JsonResponse
    {
        $now   = now();
        $month = $now->startOfMonth()->copy();

        // Monthly signups for last 12 months (for the chart)
        $monthlySignups = Organization::selectRaw("DATE_FORMAT(created_at,'%Y-%m') as month, COUNT(*) as count")
            ->where('created_at', '>=', $now->copy()->subMonths(11)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json([
            'totals' => [
                'organizations'  => Organization::count(),
                'active_orgs'    => Organization::where('status', 'active')->count(),
                'users'          => User::count(),
                'keywords'       => Keyword::count(),
                'opportunities'  => Opportunity::count(),
                'leads'          => Lead::count(),
                'revenue'        => (float) Deal::won()->sum('value'),
            ],
            'this_month' => [
                'new_orgs'    => Organization::where('created_at', '>=', $month)->count(),
                'new_users'   => User::where('created_at', '>=', $month)->count(),
                'new_leads'   => Lead::where('created_at', '>=', $month)->count(),
                'revenue'     => (float) Deal::won()->whereMonth('won_at', $now->month)
                                    ->whereYear('won_at', $now->year)->sum('value'),
            ],
            'monthly_signups' => $monthlySignups,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Organizations  —  GET /api/admin/organizations
    // ─────────────────────────────────────────────────────────────────────────

    public function organizations(Request $request): JsonResponse
    {
        $query = Organization::withCount(['users', 'projects'])
            ->with(['plan:id,name,slug,monthly_price', 'activeSubscription:id,organization_id,status,renewal_at'])
            ->when($request->filled('search'), fn ($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('slug', 'like', "%{$request->search}%")
            )
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('plan'),   fn ($q) => $q->whereHas('plan', fn ($p) => $p->where('slug', $request->plan)))
            ->orderByDesc('created_at');

        return response()->json($query->paginate(25));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Org detail  —  GET /api/admin/organizations/{org}
    // ─────────────────────────────────────────────────────────────────────────

    public function orgDetail(Organization $organization): JsonResponse
    {
        $organization->loadMissing([
            'plan',
            'activeSubscription.plan',
            'users:id,organization_id,name,email,role,status,created_at,last_login_at',
        ]);

        $usage = $this->metering->snapshot($organization);

        // Recent audit trail (last 20 actions)
        $auditLog = AuditLog::where('organization_id', $organization->id)
            ->with('user:id,name,email')
            ->latest()
            ->limit(20)
            ->get();

        // Activity counts
        $stats = [
            'keywords'     => Keyword::whereHas('project', fn ($q) => $q->where('organization_id', $organization->id))->count(),
            'opportunities'=> Opportunity::whereHas('project', fn ($q) => $q->where('organization_id', $organization->id))->count(),
            'leads'        => Lead::where('organization_id', $organization->id)->count(),
            'deals'        => Deal::where('organization_id', $organization->id)->count(),
            'revenue'      => (float) Deal::where('organization_id', $organization->id)->won()->sum('value'),
        ];

        return response()->json([
            'organization' => $organization,
            'stats'        => $stats,
            'usage'        => $usage,
            'audit_log'    => $auditLog,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Suspend / activate  —  PATCH /api/admin/organizations/{org}/status
    // ─────────────────────────────────────────────────────────────────────────

    public function setOrgStatus(Request $request, Organization $organization): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,suspended'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $previous = $organization->status;
        $organization->update(['status' => $data['status']]);

        // Record the admin action in the audit log
        AuditLog::record(
            action:         "admin.org.{$data['status']}",
            entity:         $organization,
            metadata:       [
                'previous_status' => $previous,
                'reason'          => $data['reason'] ?? null,
                'admin_user_id'   => $request->user()->id,
            ],
            organizationId: $organization->id,
            userId:         $request->user()->id,
        );

        // If suspending, revoke all active tokens for org users
        if ($data['status'] === 'suspended') {
            User::where('organization_id', $organization->id)
                ->each(fn ($u) => $u->tokens()->delete());
        }

        return response()->json([
            'message'      => "Organization {$data['status']}.",
            'organization' => $organization->fresh(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Impersonate  —  POST /api/admin/organizations/{org}/impersonate
    // Issues a short-lived Sanctum token so the admin can log in as the org owner
    // ─────────────────────────────────────────────────────────────────────────

    public function impersonate(Request $request, Organization $organization): JsonResponse
    {
        $owner = User::where('organization_id', $organization->id)
            ->where('role', 'owner')
            ->where('status', 'active')
            ->firstOrFail();

        // Log the impersonation before issuing the token
        AuditLog::record(
            action:         'admin.impersonate',
            entity:         $owner,
            metadata:       [
                'admin_user_id'  => $request->user()->id,
                'admin_email'    => $request->user()->email,
                'target_org'     => $organization->name,
            ],
            organizationId: $organization->id,
            userId:         $request->user()->id,
        );

        // 1-hour expiring token labelled so it can be cleaned up
        $token = $owner->createToken(
            'admin-impersonate-' . now()->timestamp,
            ['*'],
            now()->addHour(),
        )->plainTextToken;

        return response()->json([
            'message'    => "Impersonation token issued for {$owner->email}.",
            'token'      => $token,
            'expires_at' => now()->addHour()->toIso8601String(),
            'user'       => $owner->load('organization'),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // System audit log  —  GET /api/admin/audit-log
    // ─────────────────────────────────────────────────────────────────────────

    public function auditLog(Request $request): JsonResponse
    {
        $log = AuditLog::with(['user:id,name,email', 'organization:id,name'])
            ->when($request->filled('organization_id'), fn ($q) => $q->where('organization_id', $request->organization_id))
            ->when($request->filled('action'),          fn ($q) => $q->where('action', 'like', "%{$request->action}%"))
            ->latest()
            ->paginate(50);

        return response()->json($log);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Recent signups  —  GET /api/admin/recent-signups
    // ─────────────────────────────────────────────────────────────────────────

    public function recentSignups(): JsonResponse
    {
        $orgs = Organization::with(['plan:id,name,slug', 'users' => fn ($q) => $q->where('role', 'owner')->limit(1)])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($org) => [
                'id'         => $org->id,
                'name'       => $org->name,
                'status'     => $org->status,
                'plan'       => $org->plan?->name ?? 'Free',
                'plan_slug'  => $org->plan?->slug ?? 'free',
                'owner'      => $org->users->first()?->email,
                'country'    => $org->country,
                'created_at' => $org->created_at,
            ]);

        return response()->json($orgs);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Admin profile  —  GET /api/admin/me
    // ─────────────────────────────────────────────────────────────────────────

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()->only(['id', 'name', 'email', 'is_super_admin']));
    }
}
