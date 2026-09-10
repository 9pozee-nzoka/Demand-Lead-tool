<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $organization = auth()->user()->organization;
        
        // Get current subscription
        $subscription = Subscription::where('organization_id', $organization->id)
            ->orderBy('created_at', 'desc')
            ->first();

        // Get all available plans
        $plans = Plan::where('is_active', true)
            ->orderBy('monthly_price')
            ->get();

        // Get current month usage
        $currentMonth = Carbon::now()->startOfMonth();
        $usage = UsageRecord::where('organization_id', $organization->id)
            ->where('period', '>=', $currentMonth)
            ->first();

        // Calculate usage metrics
        $usageMetrics = [
            'keywords' => \App\Models\Keyword::whereHas('project', function($q) use ($organization) {
                $q->where('organization_id', $organization->id);
            })->count(),
            'leads' => $organization->leads()->where('created_at', '>=', $currentMonth)->count(),
            'opportunities' => $organization->opportunities()->where('created_at', '>=', $currentMonth)->count(),
            'api_calls' => $usage->api_calls ?? 0,
            'alerts_sent' => $usage->alerts_sent ?? 0,
        ];

        // Get usage history (last 6 months)
        $usageHistory = UsageRecord::where('organization_id', $organization->id)
            ->where('period', '>=', Carbon::now()->subMonths(6))
            ->orderBy('period')
            ->get();

        // Mock invoices (in production, integrate with payment provider)
        $invoices = collect([
            [
                'id' => 'INV-' . date('Ym') . '-001',
                'date' => Carbon::now()->startOfMonth(),
                'amount' => $subscription->plan->price ?? 0,
                'status' => 'paid',
                'period' => Carbon::now()->format('F Y'),
            ],
            [
                'id' => 'INV-' . date('Ym', strtotime('-1 month')) . '-001',
                'date' => Carbon::now()->subMonth()->startOfMonth(),
                'amount' => $subscription->plan->price ?? 0,
                'status' => 'paid',
                'period' => Carbon::now()->subMonth()->format('F Y'),
            ],
            [
                'id' => 'INV-' . date('Ym', strtotime('-2 months')) . '-001',
                'date' => Carbon::now()->subMonths(2)->startOfMonth(),
                'amount' => $subscription->plan->price ?? 0,
                'status' => 'paid',
                'period' => Carbon::now()->subMonths(2)->format('F Y'),
            ],
        ]);

        return view('billing.index', compact(
            'organization',
            'subscription',
            'plans',
            'usageMetrics',
            'usageHistory',
            'invoices'
        ));
    }

    public function changePlan(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $organization = auth()->user()->organization;
        $newPlan = Plan::findOrFail($validated['plan_id']);

        // Update or create subscription
        $subscription = Subscription::updateOrCreate(
            ['organization_id' => $organization->id],
            [
                'plan_id' => $newPlan->id,
                'status' => 'active',
                'current_period_start' => Carbon::now(),
                'current_period_end' => Carbon::now()->addMonth(),
            ]
        );

        // Update organization plan
        $organization->update(['plan_id' => $newPlan->id]);

        return redirect()->route('billing.index')
            ->with('success', "Successfully upgraded to {$newPlan->name} plan!");
    }

    public function cancelSubscription(Request $request)
    {
        $organization = auth()->user()->organization;
        
        $subscription = Subscription::where('organization_id', $organization->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($subscription) {
            $subscription->update([
                'status' => 'canceled',
                'canceled_at' => Carbon::now(),
            ]);
        }

        return redirect()->route('billing.index')
            ->with('success', 'Subscription canceled. You can continue using the service until the end of your billing period.');
    }
}
