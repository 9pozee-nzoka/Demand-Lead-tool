@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="mb-4">
        <div class="bg-gradient-to-r from-green-600 to-emerald-600 rounded-xl shadow-lg p-6">
            <div class="flex justify-between items-center flex-wrap gap-4">
                <div class="text-white">
                    <h1 class="text-3xl font-bold mb-2">
                        📊 Reports & Analytics
                    </h1>
                    <p class="text-green-100">
                        Revenue tracking, funnel analysis, and ROI metrics • {{ $startDate->format('M d') }} - {{ $endDate->format('M d, Y') }}
                    </p>
                </div>
                <div class="flex gap-2 flex-wrap">
                    <!-- Period Selector -->
                    <div class="inline-flex rounded-lg bg-white/20 p-1">
                        <a href="{{ route('reports.index', ['period' => 7]) }}" 
                           class="px-4 py-2 rounded-md {{ $period == 7 ? 'bg-white text-green-600' : 'text-white hover:bg-white/10' }} transition-all duration-200 font-medium">
                            7D
                        </a>
                        <a href="{{ route('reports.index', ['period' => 30]) }}" 
                           class="px-4 py-2 rounded-md {{ $period == 30 ? 'bg-white text-green-600' : 'text-white hover:bg-white/10' }} transition-all duration-200 font-medium">
                            30D
                        </a>
                        <a href="{{ route('reports.index', ['period' => 90]) }}" 
                           class="px-4 py-2 rounded-md {{ $period == 90 ? 'bg-white text-green-600' : 'text-white hover:bg-white/10' }} transition-all duration-200 font-medium">
                            90D
                        </a>
                    </div>

                    <!-- Export Button -->
                    <button class="px-4 py-2 bg-white text-green-600 rounded-lg font-medium hover:bg-green-50 transition-all duration-200 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Export PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue KPIs -->
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900 mb-3">💰 Revenue Overview</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 p-5">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <p class="text-gray-500 text-xs uppercase font-semibold tracking-wide mb-1">Total Revenue</p>
                        <h3 class="text-3xl font-bold text-gray-900">${{ number_format($revenue['total']) }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center">
                        <span class="text-2xl">💵</span>
                    </div>
                </div>
                <p class="text-sm text-gray-600">From {{ $revenue['deals_won'] }} won deals</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 p-5">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <p class="text-gray-500 text-xs uppercase font-semibold tracking-wide mb-1">Pipeline Value</p>
                        <h3 class="text-3xl font-bold text-gray-900">${{ number_format($revenue['pipeline']) }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                        <span class="text-2xl">🎯</span>
                    </div>
                </div>
                <p class="text-sm text-gray-600">Active deals in pipeline</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 p-5">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <p class="text-gray-500 text-xs uppercase font-semibold tracking-wide mb-1">Avg Deal Size</p>
                        <h3 class="text-3xl font-bold text-gray-900">${{ number_format($revenue['avgDealSize']) }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                        <span class="text-2xl">📊</span>
                    </div>
                </div>
                <p class="text-sm text-gray-600">Per closed deal</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 p-5">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <p class="text-gray-500 text-xs uppercase font-semibold tracking-wide mb-1">ROI</p>
                        <h3 class="text-3xl font-bold {{ $roi['percentage'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $roi['percentage'] >= 0 ? '+' : '' }}{{ number_format($roi['percentage']) }}%
                        </h3>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-yellow-500 to-orange-600 flex items-center justify-center">
                        <span class="text-2xl">📈</span>
                    </div>
                </div>
                <p class="text-sm text-gray-600">Return on investment</p>
            </div>
        </div>
    </div>

    <!-- Conversion Funnel -->
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900 mb-3">🎯 Conversion Funnel</h2>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Funnel Visualization -->
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Pipeline Stages</h3>
                    <span class="text-sm text-gray-500">End-to-end: {{ $conversions['end_to_end'] }}%</span>
                </div>
                
                <!-- Funnel Bars -->
                <div class="space-y-3">
                    <!-- Opportunities -->
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700">💡 Opportunities</span>
                            <span class="text-gray-600">{{ $funnel['opportunities'] }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-8 relative overflow-hidden">
                            <div class="bg-gradient-to-r from-yellow-400 to-yellow-500 h-8 rounded-full flex items-center justify-center text-white font-semibold text-sm" 
                                 style="width: 100%">
                                100%
                            </div>
                        </div>
                    </div>

                    <!-- Leads -->
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700">👥 Leads Generated</span>
                            <span class="text-gray-600">{{ $funnel['leads'] }} ({{ $conversions['opportunity_to_lead'] }}%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-8 relative overflow-hidden">
                            <div class="bg-gradient-to-r from-blue-400 to-blue-500 h-8 rounded-full flex items-center justify-center text-white font-semibold text-sm" 
                                 style="width: {{ $conversions['opportunity_to_lead'] }}%">
                                {{ $conversions['opportunity_to_lead'] }}%
                            </div>
                        </div>
                    </div>

                    <!-- Deals -->
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700">💼 Deals Created</span>
                            <span class="text-gray-600">{{ $funnel['deals'] }} ({{ $conversions['lead_to_deal'] }}%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-8 relative overflow-hidden">
                            <div class="bg-gradient-to-r from-purple-400 to-purple-500 h-8 rounded-full flex items-center justify-center text-white font-semibold text-sm" 
                                 style="width: {{ $conversions['lead_to_deal'] }}%">
                                {{ $conversions['lead_to_deal'] }}%
                            </div>
                        </div>
                    </div>

                    <!-- Won -->
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700">✅ Deals Won</span>
                            <span class="text-gray-600">{{ $funnel['conversions'] }} ({{ $conversions['deal_to_won'] }}%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-8 relative overflow-hidden">
                            <div class="bg-gradient-to-r from-green-400 to-green-500 h-8 rounded-full flex items-center justify-center text-white font-semibold text-sm" 
                                 style="width: {{ $conversions['deal_to_won'] }}%">
                                {{ $conversions['deal_to_won'] }}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Conversion Rates -->
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Conversion Rates</h3>
                <div class="space-y-4">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-blue-700 mb-1">{{ $conversions['opportunity_to_lead'] }}%</div>
                            <div class="text-sm text-blue-600 font-medium">Opportunity → Lead</div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-purple-700 mb-1">{{ $conversions['lead_to_deal'] }}%</div>
                            <div class="text-sm text-purple-600 font-medium">Lead → Deal</div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-green-700 mb-1">{{ $conversions['deal_to_won'] }}%</div>
                            <div class="text-sm text-green-600 font-medium">Deal → Won</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <!-- Revenue Trend -->
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <span class="text-xl">📈</span> Revenue Trend
            </h3>
            <div style="height: 250px;">
                <canvas id="revenueTrendChart"></canvas>
            </div>
        </div>

        <!-- Lead Sources -->
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <span class="text-xl">🎯</span> Lead Sources
            </h3>
            <div style="height: 250px;">
                <canvas id="leadSourcesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Performing Keywords -->
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h2 class="text-xl font-bold text-gray-900 mb-4">🏆 Top Performing Keywords</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keyword</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Opportunities</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($topKeywords as $index => $keyword)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                @if($index === 0)
                                    <span class="text-2xl">🥇</span>
                                @elseif($index === 1)
                                    <span class="text-2xl">🥈</span>
                                @elseif($index === 2)
                                    <span class="text-2xl">🥉</span>
                                @else
                                    <span class="text-gray-500 font-semibold">#{{ $index + 1 }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $keyword->term }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-sm font-semibold bg-green-100 text-green-800 rounded-full">
                                {{ $keyword->opportunities_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                {{ ucfirst($keyword->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                            No keyword data available for this period
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ROI Breakdown -->
    <div class="mt-6 bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-6 border border-indigo-100">
        <h2 class="text-xl font-bold text-gray-900 mb-4">💎 ROI Breakdown</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg p-4 border border-gray-200">
                <p class="text-sm text-gray-600 mb-1">Investment</p>
                <p class="text-2xl font-bold text-gray-900">${{ number_format($roi['investment']) }}</p>
            </div>
            <div class="bg-white rounded-lg p-4 border border-gray-200">
                <p class="text-sm text-gray-600 mb-1">Revenue Generated</p>
                <p class="text-2xl font-bold text-green-600">${{ number_format($roi['revenue']) }}</p>
            </div>
            <div class="bg-white rounded-lg p-4 border border-gray-200">
                <p class="text-sm text-gray-600 mb-1">Net Profit</p>
                <p class="text-2xl font-bold {{ $roi['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    ${{ number_format($roi['profit']) }}
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
Chart.defaults.font.family = "'Inter', -apple-system, sans-serif";

// Revenue Trend Chart
new Chart(document.getElementById('revenueTrendChart'), {
    type: 'line',
    data: @json($revenueChartData),
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0, 0, 0, 0.05)' } },
            x: { grid: { display: false } }
        }
    }
});

// Lead Sources Chart
new Chart(document.getElementById('leadSourcesChart'), {
    type: 'doughnut',
    data: @json($sourceChartData),
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
</script>
@endsection
