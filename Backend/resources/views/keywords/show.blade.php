@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex items-center text-sm text-gray-600 mb-4">
            <a href="{{ route('keywords.index') }}" class="hover:text-gray-900">Keywords</a>
            <svg class="h-4 w-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-gray-900">{{ $keyword->term }}</span>
        </div>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $keyword->term }}</h1>
                <p class="text-gray-600 mt-1">
                    <a href="{{ route('projects.show', $keyword->project) }}" class="text-blue-600 hover:text-blue-700">
                        {{ $keyword->project->name }}
                    </a>
                </p>
            </div>
            <div class="flex items-center space-x-3">
                <span class="px-3 py-1 text-sm font-medium rounded-full {{ $keyword->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                    {{ ucfirst($keyword->status) }}
                </span>
                <a href="{{ route('keywords.edit', $keyword) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Current Interest</p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ $keyword->latestMeasurement->interest ?? 0 }}
                    </p>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Growth Rate</p>
                    <p class="text-2xl font-bold {{ ($keyword->latestMeasurement->growth_rate ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($keyword->latestMeasurement->growth_rate ?? 0, 1) }}%
                    </p>
                </div>
                <div class="p-3 {{ ($keyword->latestMeasurement->growth_rate ?? 0) >= 0 ? 'bg-green-100' : 'bg-red-100' }} rounded-lg">
                    <svg class="h-6 w-6 {{ ($keyword->latestMeasurement->growth_rate ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Search Volume</p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ number_format($keyword->latestMeasurement->search_volume ?? 0) }}
                    </p>
                </div>
                <div class="p-3 bg-purple-100 rounded-lg">
                    <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Locations</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $keyword->locations->count() }}</p>
                </div>
                <div class="p-3 bg-orange-100 rounded-lg">
                    <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Trend Chart -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">📈 Trend Analysis</h2>
            <div class="inline-flex rounded-lg bg-gray-100 p-1">
                <button onclick="updateChart(7)" 
                        class="px-3 py-1 rounded-md text-sm font-medium transition-colors duration-200 period-btn" 
                        data-period="7">
                    7D
                </button>
                <button onclick="updateChart(30)" 
                        class="px-3 py-1 rounded-md text-sm font-medium transition-colors duration-200 period-btn" 
                        data-period="30">
                    30D
                </button>
                <button onclick="updateChart(90)" 
                        class="px-3 py-1 rounded-md text-sm font-medium transition-colors duration-200 period-btn active" 
                        data-period="90">
                    90D
                </button>
            </div>
        </div>
        
        @if($measurements->count() > 0)
        <div style="height: 350px;">
            <canvas id="trendChart"></canvas>
        </div>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-blue-50 rounded-lg p-4">
                <p class="text-xs text-blue-600 font-medium mb-1">7-Day Average</p>
                <p class="text-2xl font-bold text-blue-700">{{ number_format($stats['avg_7d']) }}</p>
            </div>
            <div class="bg-green-50 rounded-lg p-4">
                <p class="text-xs text-green-600 font-medium mb-1">30-Day Average</p>
                <p class="text-2xl font-bold text-green-700">{{ number_format($stats['avg_30d']) }}</p>
            </div>
            <div class="bg-purple-50 rounded-lg p-4">
                <p class="text-xs text-purple-600 font-medium mb-1">Peak Interest</p>
                <p class="text-2xl font-bold text-purple-700">{{ number_format($stats['peak']) }}</p>
            </div>
        </div>
        @else
        <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
            <div class="text-center">
                <svg class="h-12 w-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <p class="text-gray-600">No historical data available yet</p>
                <p class="text-sm text-gray-500 mt-1">Data collection will begin shortly</p>
            </div>
        </div>
        @endif
    </div>

    <!-- Target Locations -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Target Locations</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @forelse($keyword->locations as $location)
                <div class="flex items-center p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <svg class="h-5 w-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="text-sm font-medium text-gray-900">{{ $location->country_code }}</span>
                </div>
            @empty
                <p class="text-gray-600 col-span-full">No locations configured</p>
            @endforelse
        </div>
    </div>
</div>

@if($measurements->count() > 0)
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Prepare data from backend
const allData = @json($chartData);
let currentChart = null;

// Initialize with 90 days
document.addEventListener('DOMContentLoaded', function() {
    updateChart(90);
});

function updateChart(days) {
    // Update active button
    document.querySelectorAll('.period-btn').forEach(btn => {
        if (btn.dataset.period == days) {
            btn.classList.add('active', 'bg-white', 'text-gray-900', 'shadow-sm');
            btn.classList.remove('text-gray-600');
        } else {
            btn.classList.remove('active', 'bg-white', 'text-gray-900', 'shadow-sm');
            btn.classList.add('text-gray-600');
        }
    });

    // Filter data
    const filteredData = allData.slice(-days);
    
    // Prepare chart data
    const chartData = {
        labels: filteredData.map(d => d.date),
        datasets: [
            {
                label: 'Interest Over Time',
                data: filteredData.map(d => d.interest),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4,
                yAxisID: 'y',
            },
            {
                label: 'Search Volume',
                data: filteredData.map(d => d.volume),
                borderColor: 'rgb(147, 51, 234)',
                backgroundColor: 'rgba(147, 51, 234, 0.1)',
                fill: true,
                tension: 0.4,
                yAxisID: 'y1',
            }
        ]
    };

    // Destroy existing chart
    if (currentChart) {
        currentChart.destroy();
    }

    // Create new chart
    const ctx = document.getElementById('trendChart').getContext('2d');
    currentChart = new Chart(ctx, {
        type: 'line',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.85)',
                    padding: 12,
                    cornerRadius: 8,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Interest (0-100)',
                        font: {
                            weight: 'bold'
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Search Volume',
                        font: {
                            weight: 'bold'
                        }
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                },
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }
        }
    });
}
</script>
@endif
@endsection
