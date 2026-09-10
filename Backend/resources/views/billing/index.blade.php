@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">💳 Billing & Subscription</h1>
        <p class="text-gray-600 mt-1">Manage your subscription plan, usage, and billing history</p>
    </div>

    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    <!-- Current Plan -->
    <div class="mb-6">
        <div class="bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex justify-between items-start flex-wrap gap-4">
                <div>
                    <p class="text-indigo-200 text-sm mb-1">Current Plan</p>
                    <h2 class="text-3xl font-bold mb-2">
                        {{ $subscription->plan->name ?? 'Free Trial' }}
                    </h2>
                    <p class="text-indigo-100">
                        @if($subscription)
                            <span class="font-semibold">${{ number_format($subscription->plan->monthly_price) }}</span> per month
                            @if($subscription->status === 'canceled')
                                • <span class="bg-red-500 px-2 py-1 rounded text-sm">Canceled</span>
                            @else
                                • Renews {{ $subscription->current_period_end->format('M d, Y') }}
                            @endif
                        @else
                            Start your journey with a free trial
                        @endif
                    </p>
                </div>
                <div class="flex gap-2">
                    @if($subscription && $subscription->status !== 'canceled')
                    <button onclick="document.getElementById('changePlanModal').classList.remove('hidden')"
                            class="px-4 py-2 bg-white text-indigo-600 rounded-lg font-medium hover:bg-indigo-50 transition-colors">
                        Change Plan
                    </button>
                    @endif
                    
                    @if($subscription && $subscription->status === 'active')
                    <button onclick="confirmCancel()"
                            class="px-4 py-2 bg-red-500 text-white rounded-lg font-medium hover:bg-red-600 transition-colors">
                        Cancel
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Usage Metrics -->
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900 mb-3">📊 Current Month Usage</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Keywords -->
            <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-blue-500">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <p class="text-sm text-gray-600">Keywords Tracked</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $usageMetrics['keywords'] }}</p>
                    </div>
                    <span class="text-2xl">🔍</span>
                </div>
                @if($subscription && $subscription->plan->limit('keywords'))
                <div class="mt-2">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full" 
                             style="width: {{ min(($usageMetrics['keywords'] / $subscription->plan->limit('keywords')) * 100, 100) }}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $subscription->plan->limit('keywords') - $usageMetrics['keywords'] }} remaining
                    </p>
                </div>
                @endif
            </div>

            <!-- Leads -->
            <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-purple-500">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <p class="text-sm text-gray-600">Leads Generated</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $usageMetrics['leads'] }}</p>
                    </div>
                    <span class="text-2xl">👥</span>
                </div>
                @if($subscription && $subscription->plan->limit('leads_per_month'))
                <div class="mt-2">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-purple-500 h-2 rounded-full" 
                             style="width: {{ min(($usageMetrics['leads'] / $subscription->plan->limit('leads_per_month')) * 100, 100) }}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $subscription->plan->limit('leads_per_month') - $usageMetrics['leads'] }} remaining
                    </p>
                </div>
                @endif
            </div>

            <!-- Opportunities -->
            <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-yellow-500">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <p class="text-sm text-gray-600">Opportunities</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $usageMetrics['opportunities'] }}</p>
                    </div>
                    <span class="text-2xl">💡</span>
                </div>
                <p class="text-xs text-gray-500 mt-2">Unlimited</p>
            </div>

            <!-- API Calls -->
            <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-green-500">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <p class="text-sm text-gray-600">API Calls</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($usageMetrics['api_calls']) }}</p>
                    </div>
                    <span class="text-2xl">⚡</span>
                </div>
                @if($subscription && $subscription->plan->limit('api_calls'))
                <div class="mt-2">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" 
                             style="width: {{ min(($usageMetrics['api_calls'] / $subscription->plan->limit('api_calls')) * 100, 100) }}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ number_format($subscription->plan->limit('api_calls') - $usageMetrics['api_calls']) }} remaining
                    </p>
                </div>
                @endif
            </div>

            <!-- Alerts -->
            <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-orange-500">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <p class="text-sm text-gray-600">Alerts Sent</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $usageMetrics['alerts_sent'] }}</p>
                    </div>
                    <span class="text-2xl">🔔</span>
                </div>
                <p class="text-xs text-gray-500 mt-2">Unlimited</p>
            </div>
        </div>
    </div>

    <!-- Available Plans -->
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900 mb-3">💎 Available Plans</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($plans as $plan)
            <div class="bg-white rounded-xl shadow-sm border-2 {{ $subscription && $subscription->plan_id === $plan->id ? 'border-indigo-500' : 'border-gray-200' }} hover:border-indigo-300 transition-all duration-200 overflow-hidden">
                @if($subscription && $subscription->plan_id === $plan->id)
                <div class="bg-indigo-500 text-white text-center py-1 text-sm font-semibold">
                    Current Plan
                </div>
                @endif
                
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $plan->name }}</h3>
                    <div class="mb-4">
                        <span class="text-4xl font-bold text-gray-900">${{ number_format($plan->monthly_price) }}</span>
                        <span class="text-gray-600">/month</span>
                    </div>
                    
                    <ul class="space-y-2 mb-6">
                        @if($plan->limit('keywords'))
                        <li class="flex items-center text-sm text-gray-700">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ $plan->limit('keywords') }} keywords
                        </li>
                        @else
                        <li class="flex items-center text-sm text-gray-700">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Unlimited keywords
                        </li>
                        @endif

                        @if($plan->limit('leads_per_month'))
                        <li class="flex items-center text-sm text-gray-700">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ number_format($plan->limit('leads_per_month')) }} leads/month
                        </li>
                        @else
                        <li class="flex items-center text-sm text-gray-700">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Unlimited leads
                        </li>
                        @endif

                        <li class="flex items-center text-sm text-gray-700">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ $plan->limit('team_members') ?? 'Unlimited' }} team members
                        </li>

                        @if($plan->hasFeature('ai_insights'))
                        <li class="flex items-center text-sm text-gray-700">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            AI-powered insights
                        </li>
                        @endif

                        @if($plan->hasFeature('priority_support'))
                        <li class="flex items-center text-sm text-gray-700">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Priority support
                        </li>
                        @endif
                    </ul>

                    @if(!$subscription || $subscription->plan_id !== $plan->id)
                    <form action="{{ route('billing.change-plan') }}" method="POST">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <button type="submit" 
                                class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium">
                            {{ $subscription ? 'Switch to ' . $plan->name : 'Select Plan' }}
                        </button>
                    </form>
                    @else
                    <div class="w-full px-4 py-2 bg-gray-100 text-gray-500 rounded-lg text-center font-medium">
                        Current Plan
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Billing History -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">📄 Billing History</h2>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($invoices as $invoice)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $invoice['id'] }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $invoice['period'] }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500">{{ $invoice['date']->format('M d, Y') }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-semibold text-gray-900">${{ number_format($invoice['amount']) }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                            {{ $invoice['status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ ucfirst($invoice['status']) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <button class="text-indigo-600 hover:text-indigo-900 font-medium">
                            Download PDF
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        No billing history available
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Change Plan Modal -->
<div id="changePlanModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Change Your Plan</h3>
        <p class="text-gray-600 mb-6">
            Select a new plan from the options above. Your billing will be prorated for the current period.
        </p>
        <button onclick="document.getElementById('changePlanModal').classList.add('hidden')"
                class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
            Got it
        </button>
    </div>
</div>

<script>
function confirmCancel() {
    if(confirm('Are you sure you want to cancel your subscription? You will continue to have access until the end of your billing period.')) {
        fetch('{{ route('billing.cancel') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            location.reload();
        });
    }
}
</script>
@endsection
