<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Deal;
use App\Models\Keyword;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\View\View;

/**
 * Super Admin Dashboard
 * 
 * System-wide administration panel for managing all organizations,
 * users, and monitoring platform health.
 */
class AdminDashboardController extends Controller
{
    /**
     * Show super admin dashboard
     */
    public function index(): View
    {
        $now = now();
        $month = $now->copy()->startOfMonth();

        // System-wide metrics
        $metrics = [
            'totals' => [
                'organizations' => Organization::count(),
                'active_orgs' => Organization::where('status', 'active')->count(),
                'users' => User::count(),
                'keywords' => Keyword::count(),
                'opportunities' => Opportunity::count(),
                'leads' => Lead::count(),
                'revenue' => Deal::won()->sum('value'),
            ],
            'this_month' => [
                'new_orgs' => Organization::where('created_at', '>=', $month)->count(),
                'new_users' => User::where('created_at', '>=', $month)->count(),
                'new_leads' => Lead::where('created_at', '>=', $month)->count(),
                'revenue' => Deal::won()
                    ->whereMonth('won_at', $now->month)
                    ->whereYear('won_at', $now->year)
                    ->sum('value'),
            ],
        ];

        // Recent organizations (last 10)
        $recentOrgs = Organization::with(['plan', 'users' => fn($q) => $q->where('role', 'owner')->limit(1)])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Monthly signup trend (last 12 months)
        $monthlySignups = Organization::selectRaw("DATE_FORMAT(created_at,'%Y-%m') as month, COUNT(*) as count")
            ->where('created_at', '>=', $now->copy()->subMonths(11)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        // Fill missing months with 0
        $signupTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i)->format('Y-m');
            $signupTrend[$month] = $monthlySignups[$month] ?? 0;
        }

        // Recent admin actions
        $recentActions = AuditLog::with(['user:id,name,email', 'organization:id,name'])
            ->whereIn('action', ['admin.org.suspended', 'admin.org.active', 'admin.impersonate'])
            ->latest()
            ->limit(20)
            ->get();

        // System health checks
        $health = [
            'database' => $this->checkDatabaseHealth(),
            'queue' => $this->checkQueueHealth(),
            'failed_jobs' => $this->getFailedJobsCount(),
        ];

        return view('admin.dashboard', compact('metrics', 'recentOrgs', 'signupTrend', 'recentActions', 'health'));
    }

    /**
     * List all organizations
     */
    public function organizations(): View
    {
        $organizations = Organization::withCount(['users', 'projects'])
            ->with(['plan', 'activeSubscription'])
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('admin.organizations', compact('organizations'));
    }

    /**
     * Show organization detail
     */
    public function organizationDetail(Organization $organization): View
    {
        $organization->loadMissing([
            'plan',
            'activeSubscription.plan',
            'users',
            'projects',
        ]);

        // Activity stats
        $stats = [
            'keywords' => Keyword::whereHas('project', fn($q) => $q->where('organization_id', $organization->id))->count(),
            'opportunities' => Opportunity::where('organization_id', $organization->id)->count(),
            'leads' => Lead::where('organization_id', $organization->id)->count(),
            'deals' => Deal::where('organization_id', $organization->id)->count(),
            'revenue' => Deal::where('organization_id', $organization->id)->won()->sum('value'),
        ];

        // Recent audit log
        $auditLog = AuditLog::where('organization_id', $organization->id)
            ->with('user:id,name,email')
            ->latest()
            ->limit(50)
            ->get();

        return view('admin.organization-detail', compact('organization', 'stats', 'auditLog'));
    }

    /**
     * List all users across all organizations
     */
    public function users(): View
    {
        $query = User::with(['organization:id,name']);

        // Search filter
        if (request()->filled('search')) {
            $search = request('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('organization', fn($org) => $org->where('name', 'like', "%{$search}%"));
            });
        }

        // Role filter
        if (request()->filled('role')) {
            $query->where('role', request('role'));
        }

        // Status filter
        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        // Admin type filter
        if (request('admin_type') === 'super_admin') {
            $query->where('is_super_admin', true);
        } elseif (request('admin_type') === 'org_admin') {
            $query->whereIn('role', ['owner', 'admin'])->where('is_super_admin', false);
        }

        $users = $query->orderByDesc('created_at')->paginate(50);

        return view('admin.users', compact('users'));
    }

    /**
     * System audit log
     */
    public function auditLog(): View
    {
        $query = AuditLog::with(['user:id,name,email', 'organization:id,name']);

        // Search filter
        if (request()->filled('search')) {
            $search = request('search');
            $query->where(function($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                  ->orWhereHas('organization', fn($o) => $o->where('name', 'like', "%{$search}%"));
            });
        }

        // Action type filter
        if (request()->filled('action_type')) {
            $query->where('action', 'like', request('action_type') . '.%');
        }

        // Date range filter
        if (request()->filled('date_from')) {
            $query->whereDate('created_at', '>=', request('date_from'));
        }
        if (request()->filled('date_to')) {
            $query->whereDate('created_at', '<=', request('date_to'));
        }

        $logs = $query->latest()->paginate(100);

        return view('admin.audit-log', compact('logs'));
    }

    /**
     * System settings
     */
    public function settings(): View
    {
        return view('admin.settings');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper Methods
    // ─────────────────────────────────────────────────────────────────────────

    private function checkDatabaseHealth(): array
    {
        try {
            \DB::connection()->getPdo();
            return [
                'status' => 'healthy',
                'message' => 'Database connection active',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }
    }

    private function checkQueueHealth(): array
    {
        $pendingJobs = \DB::table('jobs')->count();
        
        return [
            'status' => $pendingJobs < 1000 ? 'healthy' : 'warning',
            'pending' => $pendingJobs,
            'message' => $pendingJobs < 1000 
                ? "Queue healthy ({$pendingJobs} pending)" 
                : "High queue backlog ({$pendingJobs} pending)",
        ];
    }

    private function getFailedJobsCount(): int
    {
        return \DB::table('failed_jobs')->count();
    }
}
