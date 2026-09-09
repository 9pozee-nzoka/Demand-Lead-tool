@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Header -->
            <div class="mb-4">
                <h1 class="h3 mb-0">Generate Landing Page</h1>
                <p class="text-muted">Select an opportunity to generate an AI-powered landing page</p>
            </div>

            <!-- Generation Form -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('landing-pages.generate') }}" method="POST">
                        @csrf

                        <!-- Opportunity Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Select Opportunity</label>
                            <p class="small text-muted mb-3">
                                Choose a high-scoring opportunity to create a landing page that converts
                            </p>

                            @if($opportunities->count() > 0)
                                <div class="list-group">
                                    @foreach($opportunities as $opportunity)
                                        <label class="list-group-item list-group-item-action cursor-pointer">
                                            <div class="d-flex align-items-start">
                                                <input type="radio" 
                                                       name="opportunity_id" 
                                                       value="{{ $opportunity->id }}" 
                                                       class="form-check-input me-3 mt-1"
                                                       required>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div>
                                                            <h6 class="mb-1">
                                                                {{ $opportunity->keyword->keyword ?? 'Unknown Keyword' }}
                                                            </h6>
                                                            <div class="small text-muted">
                                                                {{ $opportunity->keyword->location ?? 'Global' }}
                                                            </div>
                                                        </div>
                                                        <div class="text-end">
                                                            @php
                                                                $scoreClass = $opportunity->opportunity_score >= 80 ? 'success' : 
                                                                             ($opportunity->opportunity_score >= 60 ? 'warning' : 'secondary');
                                                            @endphp
                                                            <div class="badge bg-{{ $scoreClass }} mb-1">
                                                                Score: {{ $opportunity->opportunity_score }}/100
                                                            </div>
                                                            <div class="small text-muted">
                                                                {{ ucfirst($opportunity->priority) }} Priority
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Opportunity Details -->
                                                    <div class="row g-2 small">
                                                        <div class="col-md-4">
                                                            <i class="bi bi-graph-up text-primary"></i>
                                                            <strong>Growth:</strong> {{ $opportunity->growth_score }}/100
                                                        </div>
                                                        <div class="col-md-4">
                                                            <i class="bi bi-search text-info"></i>
                                                            <strong>Volume:</strong> {{ number_format($opportunity->keyword->search_volume ?? 0) }}
                                                        </div>
                                                        <div class="col-md-4">
                                                            <i class="bi bi-bullseye text-success"></i>
                                                            <strong>Intent:</strong> {{ $opportunity->intent_score }}/100
                                                        </div>
                                                    </div>

                                                    @if($opportunity->ai_explanation)
                                                        <div class="mt-2 p-2 bg-light rounded small">
                                                            <i class="bi bi-lightbulb text-warning"></i>
                                                            <strong>AI Insight:</strong> 
                                                            {{ Str::limit($opportunity->ai_explanation, 150) }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>

                                @error('opportunity_id')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror

                                <!-- Info Box -->
                                <div class="alert alert-info mt-4 mb-0">
                                    <div class="d-flex">
                                        <i class="bi bi-info-circle fs-4 me-3"></i>
                                        <div>
                                            <strong>What happens next?</strong>
                                            <ul class="mb-0 mt-2 small">
                                                <li>AI generates compelling headlines, benefits, and features</li>
                                                <li>SEO-optimized meta tags are automatically created</li>
                                                <li>Professional template with lead capture form included</li>
                                                <li>You can customize everything before publishing</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="{{ route('landing-pages.index') }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left"></i> Back
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-magic"></i> Generate Landing Page
                                    </button>
                                </div>
                            @else
                                <!-- No Opportunities -->
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <strong>No opportunities available</strong>
                                    <p class="mb-0 mt-2">
                                        You need to create opportunities first. 
                                        <a href="{{ route('opportunities.index') }}">View opportunities</a> or 
                                        <a href="{{ route('trends.index') }}">explore trends</a> to find high-potential keywords.
                                    </p>
                                </div>

                                <div class="text-center mt-4">
                                    <a href="{{ route('opportunities.index') }}" class="btn btn-primary">
                                        <i class="bi bi-arrow-left"></i> View Opportunities
                                    </a>
                                </div>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <!-- How It Works -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="bi bi-question-circle text-primary"></i> How Landing Page Generation Works
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="d-flex">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                     style="width: 40px; height: 40px; flex-shrink: 0;">
                                    <strong>1</strong>
                                </div>
                                <div>
                                    <h6>AI Analysis</h6>
                                    <p class="small text-muted mb-0">
                                        Analyzes keyword, search volume, trends, and opportunity score
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                     style="width: 40px; height: 40px; flex-shrink: 0;">
                                    <strong>2</strong>
                                </div>
                                <div>
                                    <h6>Content Generation</h6>
                                    <p class="small text-muted mb-0">
                                        Creates headlines, benefits, features, FAQs, and social proof
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                     style="width: 40px; height: 40px; flex-shrink: 0;">
                                    <strong>3</strong>
                                </div>
                                <div>
                                    <h6>SEO Optimization</h6>
                                    <p class="small text-muted mb-0">
                                        Optimizes meta tags, keywords, and structure for search engines
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.cursor-pointer {
    cursor: pointer;
}
.list-group-item:hover {
    background-color: #f8f9fa;
}
.list-group-item:has(input:checked) {
    background-color: #e7f3ff;
    border-color: #0d6efd;
}
</style>
@endsection
