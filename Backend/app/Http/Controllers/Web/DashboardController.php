<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Keyword;
use App\Models\Opportunity;
use App\Models\Lead;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $orgId = $user->organization_id;

        // Get summary stats
        $stats = [
            'projects' => Project::where('organization_id', $orgId)->count(),
            'keywords' => Keyword::whereHas('project', function($q) use ($orgId) {
                $q->where('organization_id', $orgId);
            })->where('status', 'active')->count(),
            'opportunities' => Opportunity::where('organization_id', $orgId)
                ->whereIn('status', ['detected', 'reviewed', 'actioned'])
                ->count(),
            'leads' => Lead::where('organization_id', $orgId)
                ->whereIn('status', ['new', 'contacted', 'qualified'])
                ->count(),
        ];

        // Get recent opportunities
        $opportunities = Opportunity::where('organization_id', $orgId)
            ->whereIn('status', ['detected', 'reviewed', 'actioned'])
            ->orderBy('opportunity_score', 'desc')
            ->limit(5)
            ->get();

        // Get recent leads
        $leads = Lead::where('organization_id', $orgId)
            ->whereIn('status', ['new', 'contacted'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard', compact('stats', 'opportunities', 'leads'));
    }
}
