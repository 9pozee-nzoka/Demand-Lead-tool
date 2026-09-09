@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Landing Page Analytics</h1>
            <p class="text-muted">{{ $landingPage->title }}</p>
        </div>
        <a href="{{ route('landing-pages.edit', $landingPage) }}" class="btn btn-outline-secondary">
            <i class="bi bi-pencil"></i> Edit Page
        </a>
    </div>

    <!-- Key Metrics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Total Views</p>
                            <h3 class="mb-0">{{ number_format($stats['views']) }}</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="bi bi-eye text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Conversions</p>
                            <h3 class="mb-0">{{ number_format($stats['conversions']) }}</h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="bi bi-check-circle text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Conversion Rate</p>
                            <h3 class="mb-0">{{ $stats['conversion_rate'] }}%</h3>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded">
                            <i class="bi bi-percent text-info fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">SEO Score</p>
                            <h3 class="mb-0">{{ $stats['seo_score'] }}/100</h3>
                        </div>
                        @php
                            $seoClass = $stats['seo_score'] >= 80 ? 'success' : ($stats['seo_score'] >= 60 ? 'warning' : 'danger');
                        @endphp
                        <div class="bg-{{ $seoClass }} bg-opacity-10 p-3 rounded">
                            <i class="bi bi-search text-{{ $seoClass }} fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Time-Based Metrics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Today</h6>
                    <h4>{{ number_format($stats['leads_today']) }} leads</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">This Week</h6>
                    <h4>{{ number_format($stats['leads_this_week']) }} leads</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">This Month</h6>
                    <h4>{{ number_format($stats['leads_this_month']) }} leads</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Leads Over Time -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Leads Over Time (Last 30 Days)</h5>
                </div>
                <div class="card-body">
                    @if($leadsOverTime->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th class="text-end">Leads</th>
                                        <th>Trend</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($leadsOverTime as $day)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($day->date)->format('M d, Y') }}</td>
                                            <td class="text-end"><strong>{{ $day->count }}</strong></td>
                                            <td>
                                                <div class="progress" style="height: 5px;">
                                                    @php
                                                        $max = $leadsOverTime->max('count');
                                                        $percentage = $max > 0 ? ($day->count / $max) * 100 : 0;
                                                    @endphp
                                                    <div class="progress-bar bg-primary" 
                                                         style="width: {{ $percentage }}%"></div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted text-center py-4">No lead data available yet</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Lead Quality Distribution -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Lead Quality</h5>
                </div>
                <div class="card-body">
                    @if($leadQuality->count() > 0)
                        @foreach($leadQuality as $quality)
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-capitalize">{{ $quality->quality }}</span>
                                    <strong>{{ $quality->count }}</strong>
                                </div>
                                @php
                                    $total = $leadQuality->sum('count');
                                    $percentage = round(($quality->count / $total) * 100);
                                    $qualityClass = match($quality->quality) {
                                        'hot' => 'danger',
                                        'warm' => 'warning',
                                        'potential' => 'info',
                                        default => 'secondary'
                                    };
                                @endphp
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-{{ $qualityClass }}" 
                                         style="width: {{ $percentage }}%">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted text-center py-4">No quality data yet</p>
                    @endif
                </div>
            </div>

            <!-- Page Info -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body">
                    <h6 class="mb-3">Page Information</h6>
                    <div class="mb-2">
                        <small class="text-muted">Keyword:</small><br>
                        <strong>{{ $landingPage->keyword->keyword ?? 'N/A' }}</strong>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Status:</small><br>
                        @if($landingPage->status === 'published')
                            <span class="badge bg-success">Published</span>
                        @else
                            <span class="badge bg-secondary">Draft</span>
                        @endif
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Created:</small><br>
                        {{ $landingPage->created_at->format('M d, Y') }}
                    </div>
                    @if($landingPage->published_at)
                        <div>
                            <small class="text-muted">Published:</small><br>
                            {{ $landingPage->published_at->format('M d, Y') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
