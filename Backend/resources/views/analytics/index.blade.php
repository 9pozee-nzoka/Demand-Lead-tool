@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <!-- Header with Gradient -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body py-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div class="text-white mb-3 mb-md-0">
                            <h1 class="h2 mb-2 fw-bold">
                                <i class="bi bi-graph-up-arrow me-2"></i>Analytics Dashboard
                            </h1>
                            <p class="mb-0 opacity-75">
                                <i class="bi bi-calendar-range me-1"></i>
                                {{ $startDate->format('M d, Y') }} - {{ $endDate->format('M d, Y') }}
                            </p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <!-- Period Selector -->
                            <div class="btn-group" role="group">
                                <a href="{{ route('analytics.index', ['period' => 7]) }}" 
                                   class="btn {{ $period == 7 ? 'btn-light' : 'btn-outline-light' }}">
                                    7 Days
                                </a>
                                <a href="{{ route('analytics.index', ['period' => 30]) }}" 
                                   class="btn {{ $period == 30 ? 'btn-light' : 'btn-outline-light' }}">
                                    30 Days
                                </a>
                                <a href="{{ route('analytics.index', ['period' => 90]) }}" 
                                   class="btn {{ $period == 90 ? 'btn-light' : 'btn-outline-light' }}">
                                    90 Days
                                </a>
                            </div>

                            <!-- Export Dropdown -->
                            <div class="dropdown">
                                <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-download me-1"></i>Export
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('analytics.export-csv', ['period' => $period]) }}">
                                            <i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Summary (CSV)
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('analytics.export-detailed', ['type' => 'leads', 'period' => $period]) }}">
                                            <i class="bi bi-people text-primary me-2"></i>Leads Data (CSV)
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('analytics.export-detailed', ['type' => 'opportunities', 'period' => $period]) }}">
                                            <i class="bi bi-lightbulb text-warning me-2"></i>Opportunities (CSV)
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('analytics.export-detailed', ['type' => 'deals', 'period' => $period]) }}">
                                            <i class="bi bi-currency-dollar text-success me-2"></i>Deals (CSV)
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-4 mb-4">
        <!-- Keywords -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 text-uppercase small fw-bold">Keywords Tracked</p>
                            <h3 class="mb-0 fw-bold text-primary">{{ number_format($analytics['total_keywords']) }}</h3>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="bi bi-search text-white fs-4"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge {{ $changes['rising_keywords'] >= 0 ? 'bg-success' : 'bg-danger' }} me-2">
                            <i class="bi bi-arrow-{{ $changes['rising_keywords'] >= 0 ? 'up' : 'down' }}"></i>
                            {{ abs($changes['rising_keywords']) }}%
                        </span>
                        <small class="text-muted">{{ $analytics['rising_keywords'] }} rising</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Opportunities -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 text-uppercase small fw-bold">Opportunities</p>
                            <h3 class="mb-0 fw-bold text-warning">{{ number_format($analytics['total_opportunities']) }}</h3>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 50px; height: 50px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                            <i class="bi bi-lightbulb text-white fs-4"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge {{ $changes['total_opportunities'] >= 0 ? 'bg-success' : 'bg-danger' }} me-2">
                            <i class="bi bi-arrow-{{ $changes['total_opportunities'] >= 0 ? 'up' : 'down' }}"></i>
                            {{ abs($changes['total_opportunities']) }}%
                        </span>
                        <small class="text-muted">Avg score: {{ number_format($analytics['avg_opportunity_score'], 1) }}</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leads -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 text-uppercase small fw-bold">Leads Generated</p>
                            <h3 class="mb-0 fw-bold text-info">{{ number_format($analytics['total_leads']) }}</h3>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 50px; height: 50px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                            <i class="bi bi-people text-white fs-4"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge {{ $changes['total_leads'] >= 0 ? 'bg-success' : 'bg-danger' }} me-2">
                            <i class="bi bi-arrow-{{ $changes['total_leads'] >= 0 ? 'up' : 'down' }}"></i>
                            {{ abs($changes['total_leads']) }}%
                        </span>
                        <small class="text-muted">{{ $analytics['hot_leads'] }} hot leads</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 text-uppercase small fw-bold">Revenue</p>
                            <h3 class="mb-0 fw-bold text-success">${{ number_format($analytics['revenue']) }}</h3>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 50px; height: 50px; background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                            <i class="bi bi-currency-dollar text-white fs-4"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge {{ $changes['revenue'] >= 0 ? 'bg-success' : 'bg-danger' }} me-2">
                            <i class="bi bi-arrow-{{ $changes['revenue'] >= 0 ? 'up' : 'down' }}"></i>
                            {{ abs($changes['revenue']) }}%
                        </span>
                        <small class="text-muted">{{ $analytics['won_deals'] }} deals won</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Secondary Metrics -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small mb-3">Conversion Metrics</h6>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="mb-1 text-primary">{{ $analytics['total_leads'] > 0 ? number_format(($analytics['converted_leads'] / $analytics['total_leads']) * 100, 1) : 0 }}%</h4>
                                <small class="text-muted">Lead Conversion</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="mb-1 text-success">{{ $analytics['total_deals'] > 0 ? number_format(($analytics['won_deals'] / $analytics['total_deals']) * 100, 1) : 0 }}%</h4>
                                <small class="text-muted">Win Rate</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small mb-3">Average Values</h6>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="mb-1 text-info">{{ number_format($analytics['avg_lead_score'], 1) }}</h4>
                                <small class="text-muted">Lead Score</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="mb-1 text-warning">${{ number_format($analytics['avg_deal_size']) }}</h4>
                                <small class="text-muted">Deal Size</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small mb-3">Pipeline</h6>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="mb-1 text-primary">${{ number_format($analytics['pipeline_value'] / 1000, 1) }}K</h4>
                                <small class="text-muted">Pipeline Value</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="mb-1 text-success">{{ $analytics['alerts_sent'] }}</h4>
                                <small class="text-muted">Alerts Sent</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="bi bi-graph-up text-primary me-2"></i>Leads Overview
                    </h5>
                    <canvas id="leadsChart" height="80"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="bi bi-pie-chart text-info me-2"></i>Lead Sources
                    </h5>
                    <canvas id="leadSourcesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="bi bi-lightbulb text-warning me-2"></i>Opportunities Trend
                    </h5>
                    <canvas id="opportunitiesChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="bi bi-currency-dollar text-success me-2"></i>Revenue & Deals
                    </h5>
                    <canvas id="revenueChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 3 -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="bi bi-search text-primary me-2"></i>Keywords Trend
                    </h5>
                    <canvas id="trendsChart" height="80"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="bi bi-star text-warning me-2"></i>Opportunity Scores
                    </h5>
                    <canvas id="opportunityScoresChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Chart defaults
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.plugins.legend.display = true;
Chart.defaults.plugins.legend.position = 'top';

// Leads Chart
new Chart(document.getElementById('leadsChart'), {
    type: 'line',
    data: @json($charts['leads']),
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
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                titleColor: '#fff',
                bodyColor: '#fff',
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)',
                }
            },
            x: {
                grid: {
                    display: false,
                }
            }
        }
    }
});

// Lead Sources Pie Chart
new Chart(document.getElementById('leadSourcesChart'), {
    type: 'doughnut',
    data: @json($charts['leadSources']),
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
            }
        }
    }
});

// Opportunities Chart (dual axis)
new Chart(document.getElementById('opportunitiesChart'), {
    type: 'line',
    data: @json($charts['opportunities']),
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
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Count'
                },
                beginAtZero: true,
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Score'
                },
                grid: {
                    drawOnChartArea: false,
                },
                beginAtZero: true,
                max: 100,
            },
        }
    }
});

// Revenue Chart (mixed: bar + line)
new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: @json($charts['revenue']),
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
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Revenue ($)'
                },
                beginAtZero: true,
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Deals'
                },
                grid: {
                    drawOnChartArea: false,
                },
                beginAtZero: true,
            },
        }
    }
});

// Trends Chart
new Chart(document.getElementById('trendsChart'), {
    type: 'line',
    data: @json($charts['trends']),
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
            }
        },
        scales: {
            y: {
                beginAtZero: true,
            }
        }
    }
});

// Opportunity Scores Pie Chart
new Chart(document.getElementById('opportunityScoresChart'), {
    type: 'doughnut',
    data: @json($charts['opportunityScores']),
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
            }
        }
    }
});
</script>
@endsection
