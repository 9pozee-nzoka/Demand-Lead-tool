@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="mb-4">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl shadow-lg p-6">
            <div class="flex justify-between items-center flex-wrap gap-4">
                <div class="text-white">
                    <h1 class="text-3xl font-bold mb-2">
                        📊 Analytics Dashboard
                    </h1>
                    <p class="text-indigo-100">
                        {{ $startDate->format('M d, Y') }} - {{ $endDate->format('M d, Y') }}
                    </p>
                </div>
                <div class="flex gap-2 flex-wrap">
                    <!-- Period Selector -->
                    <div class="inline-flex rounded-lg bg-white/20 p-1">
                        <a href="{{ route('analytics.index', ['period' => 7]) }}" 
                           class="px-4 py-2 rounded-md {{ $period == 7 ? 'bg-white text-indigo-600' : 'text-white hover:bg-white/10' }} transition-all duration-200 font-medium">
                            7D
                        </a>
                        <a href="{{ route('analytics.index', ['period' => 30]) }}" 
                           class="px-4 py-2 rounded-md {{ $period == 30 ? 'bg-white text-indigo-600' : 'text-white hover:bg-white/10' }} transition-all duration-200 font-medium">
                            30D
                        </a>
                        <a href="{{ route('analytics.index', ['period' => 90]) }}" 
                           class="px-4 py-2 rounded-md {{ $period == 90 ? 'bg-white text-indigo-600' : 'text-white hover:bg-white/10' }} transition-all duration-200 font-medium">
                            90D
                        </a>
                    </div>

                    <!-- Export Dropdown -->
                    <div class="relative group">
                        <button class="px-4 py-2 bg-white text-indigo-600 rounded-lg font-medium hover:bg-indigo-50 transition-all duration-200 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Export
                        </button>
                        <div class="hidden group-hover:block absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl z-10">
                            <a href="{{ route('analytics.export-csv', ['period' => $period]) }}" class="block px-4 py-3 hover:bg-gray-50 text-gray-700 border-b">
                                <span class="text-green-500">📊</span> Summary (CSV)
                            </a>
                            <a href="{{ route('analytics.export-detailed', ['type' => 'leads', 'period' => $period]) }}" class="block px-4 py-3 hover:bg-gray-50 text-gray-700 border-b">
                                <span class="text-blue-500">👥</span> Leads Data
                            </a>
                            <a href="{{ route('analytics.export-detailed', ['type' => 'opportunities', 'period' => $period]) }}" class="block px-4 py-3 hover:bg-gray-50 text-gray-700 border-b">
                                <span class="text-yellow-500">💡</span> Opportunities
                            </a>
                            <a href="{{ route('analytics.export-detailed', ['type' => 'deals', 'period' => $period]) }}" class="block px-4 py-3 hover:bg-gray-50 text-gray-700 rounded-b-lg">
                                <span class="text-green-500">💰</span> Deals
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Keywords -->
        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 p-5">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <p class="text-gray-500 text-xs uppercase font-semibold tracking-wide mb-1">Keywords</p>
                    <h3 class="text-3xl font-bold text-gray-900">{{ number_format($analytics['total_keywords']) }}</h3>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                    <span class="text-2xl">🔍</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $changes['rising_keywords'] >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ $changes['rising_keywords'] >= 0 ? '↑' : '↓' }} {{ abs($changes['rising_keywords']) }}%
                </span>
                <span class="text-sm text-gray-600">{{ $analytics['rising_keywords'] }} rising</span>
            </div>
        </div>

        <!-- Opportunities -->
        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 p-5">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <p class="text-gray-500 text-xs uppercase font-semibold tracking-wide mb-1">Opportunities</p>
                    <h3 class="text-3xl font-bold text-gray-900">{{ number_format($analytics['total_opportunities']) }}</h3>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-yellow-500 to-orange-600 flex items-center justify-center">
                    <span class="text-2xl">💡</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $changes['total_opportunities'] >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ $changes['total_opportunities'] >= 0 ? '↑' : '↓' }} {{ abs($changes['total_opportunities']) }}%
                </span>
                <span class="text-sm text-gray-600">Avg: {{ number_format($analytics['avg_opportunity_score'], 1) }}</span>
            </div>
        </div>

        <!-- Leads -->
        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 p-5">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <p class="text-gray-500 text-xs uppercase font-semibold tracking-wide mb-1">Leads</p>
                    <h3 class="text-3xl font-bold text-gray-900">{{ number_format($analytics['total_leads']) }}</h3>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center">
                    <span class="text-2xl">👥</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $changes['total_leads'] >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ $changes['total_leads'] >= 0 ? '↑' : '↓' }} {{ abs($changes['total_leads']) }}%
                </span>
                <span class="text-sm text-gray-600">{{ $analytics['hot_leads'] }} hot</span>
            </div>
        </div>

        <!-- Revenue -->
        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 p-5">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <p class="text-gray-500 text-xs uppercase font-semibold tracking-wide mb-1">Revenue</p>
                    <h3 class="text-3xl font-bold text-gray-900">${{ number_format($analytics['revenue']) }}</h3>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center">
                    <span class="text-2xl">💰</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $changes['revenue'] >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ $changes['revenue'] >= 0 ? '↑' : '↓' }} {{ abs($changes['revenue']) }}%
                </span>
                <span class="text-sm text-gray-600">{{ $analytics['won_deals'] }} won</span>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4">
            <div class="text-center">
                <div class="text-3xl font-bold text-blue-700 mb-1">
                    {{ $analytics['total_leads'] > 0 ? number_format(($analytics['converted_leads'] / $analytics['total_leads']) * 100, 1) : 0 }}%
                </div>
                <div class="text-sm text-blue-600 font-medium">Lead Conversion</div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4">
            <div class="text-center">
                <div class="text-3xl font-bold text-green-700 mb-1">
                    {{ $analytics['total_deals'] > 0 ? number_format(($analytics['won_deals'] / $analytics['total_deals']) * 100, 1) : 0 }}%
                </div>
                <div class="text-sm text-green-600 font-medium">Win Rate</div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4">
            <div class="text-center">
                <div class="text-3xl font-bold text-purple-700 mb-1">
                    ${{ number_format($analytics['avg_deal_size']) }}
                </div>
                <div class="text-sm text-purple-600 font-medium">Avg Deal Size</div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-4">
            <div class="text-center">
                <div class="text-3xl font-bold text-orange-700 mb-1">
                    ${{ number_format($analytics['pipeline_value'] / 1000, 1) }}K
                </div>
                <div class="text-sm text-orange-600 font-medium">Pipeline Value</div>
            </div>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <!-- Leads Trend -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-5">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <span class="text-xl">📈</span> Leads Trend
            </h3>
            <div style="height: 200px;">
                <canvas id="leadsChart"></canvas>
            </div>
        </div>

        <!-- Lead Sources -->
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <span class="text-xl">🎯</span> Lead Sources
            </h3>
            <div style="height: 200px;">
                <canvas id="leadSourcesChart"></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
        <!-- Opportunities -->
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <span class="text-xl">💡</span> Opportunities
            </h3>
            <div style="height: 220px;">
                <canvas id="opportunitiesChart"></canvas>
            </div>
        </div>

        <!-- Revenue & Deals -->
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <span class="text-xl">💰</span> Revenue & Deals
            </h3>
            <div style="height: 220px;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Keywords Trend -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-5">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <span class="text-xl">🔍</span> Keywords Trend
            </h3>
            <div style="height: 200px;">
                <canvas id="trendsChart"></canvas>
            </div>
        </div>

        <!-- Opportunity Scores -->
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <span class="text-xl">⭐</span> Scores
            </h3>
            <div style="height: 200px;">
                <canvas id="opportunityScoresChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Vibrant color palette
const colors = {
    blue: {
        solid: 'rgb(59, 130, 246)',
        light: 'rgba(59, 130, 246, 0.1)',
        gradient: ['rgba(59, 130, 246, 0.8)', 'rgba(37, 99, 235, 0.4)']
    },
    purple: {
        solid: 'rgb(147, 51, 234)',
        light: 'rgba(147, 51, 234, 0.1)',
        gradient: ['rgba(147, 51, 234, 0.8)', 'rgba(126, 34, 206, 0.4)']
    },
    green: {
        solid: 'rgb(34, 197, 94)',
        light: 'rgba(34, 197, 94, 0.1)',
        gradient: ['rgba(34, 197, 94, 0.8)', 'rgba(22, 163, 74, 0.4)']
    },
    orange: {
        solid: 'rgb(249, 115, 22)',
        light: 'rgba(249, 115, 22, 0.1)',
        gradient: ['rgba(249, 115, 22, 0.8)', 'rgba(234, 88, 12, 0.4)']
    },
    pink: {
        solid: 'rgb(236, 72, 153)',
        light: 'rgba(236, 72, 153, 0.1)',
        gradient: ['rgba(236, 72, 153, 0.8)', 'rgba(219, 39, 119, 0.4)']
    },
    yellow: {
        solid: 'rgb(234, 179, 8)',
        light: 'rgba(234, 179, 8, 0.1)',
        gradient: ['rgba(234, 179, 8, 0.8)', 'rgba(202, 138, 4, 0.4)']
    }
};

Chart.defaults.font.family = "'Inter', -apple-system, sans-serif";
Chart.defaults.font.size = 12;

// Leads Chart
new Chart(document.getElementById('leadsChart'), {
    type: 'line',
    data: @json($charts['leads']),
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: true, position: 'top' },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.85)',
                padding: 12,
                cornerRadius: 8
            }
        },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0, 0, 0, 0.05)' } },
            x: { grid: { display: false } }
        }
    }
});

// Lead Sources
new Chart(document.getElementById('leadSourcesChart'), {
    type: 'doughnut',
    data: @json($charts['leadSources']),
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: true, position: 'bottom' },
            tooltip: { backgroundColor: 'rgba(0, 0, 0, 0.85)', padding: 12, cornerRadius: 8 }
        }
    }
});

// Opportunities
new Chart(document.getElementById('opportunitiesChart'), {
    type: 'line',
    data: @json($charts['opportunities']),
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { display: true, position: 'top' } },
        scales: {
            y: { type: 'linear', display: true, position: 'left', beginAtZero: true, title: { display: true, text: 'Count' } },
            y1: { type: 'linear', display: true, position: 'right', beginAtZero: true, max: 100, title: { display: true, text: 'Score' }, grid: { drawOnChartArea: false } }
        }
    }
});

// Revenue
new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: @json($charts['revenue']),
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { display: true, position: 'top' } },
        scales: {
            y: { type: 'linear', display: true, position: 'left', beginAtZero: true, title: { display: true, text: 'Revenue ($)' } },
            y1: { type: 'linear', display: true, position: 'right', beginAtZero: true, title: { display: true, text: 'Deals' }, grid: { drawOnChartArea: false } }
        }
    }
});

// Trends
new Chart(document.getElementById('trendsChart'), {
    type: 'line',
    data: @json($charts['trends']),
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { display: true, position: 'top' } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0, 0, 0, 0.05)' } },
            x: { grid: { display: false } }
        }
    }
});

// Opportunity Scores
new Chart(document.getElementById('opportunityScoresChart'), {
    type: 'doughnut',
    data: @json($charts['opportunityScores']),
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: true, position: 'bottom' },
            tooltip: { backgroundColor: 'rgba(0, 0, 0, 0.85)', padding: 12, cornerRadius: 8 }
        }
    }
});
</script>
@endsection
