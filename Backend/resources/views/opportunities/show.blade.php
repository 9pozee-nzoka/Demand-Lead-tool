@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex items-center text-sm text-gray-600 mb-4">
            <a href="{{ route('opportunities.index') }}" class="hover:text-gray-900">Opportunities</a>
            <svg class="h-4 w-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-gray-900">{{ $opportunity->name }}</span>
        </div>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $opportunity->name }}</h1>
                <p class="text-gray-600 mt-1">
                    <a href="{{ route('projects.show', $opportunity->project) }}" class="text-blue-600 hover:text-blue-700">
                        {{ $opportunity->project->name }}
                    </a>
                </p>
            </div>
            <div class="flex items-center space-x-3">
                @php
                    $priority = $opportunity->priority;
                    $badgeClass = match($priority) {
                        'very_high' => 'bg-red-100 text-red-800',
                        'high' => 'bg-orange-100 text-orange-800',
                        'moderate' => 'bg-blue-100 text-blue-800',
                        default => 'bg-gray-100 text-gray-800',
                    };
                @endphp
                <span class="px-3 py-1 text-sm font-medium rounded-full {{ $badgeClass }}">
                    {{ str_replace('_', ' ', ucfirst($priority)) }} Priority
                </span>
            </div>
        </div>
    </div>

    <!-- Score Card -->
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-sm p-6 mb-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-100 text-sm font-medium mb-1">Opportunity Score</p>
                <p class="text-5xl font-bold">{{ $opportunity->opportunity_score }}</p>
                <p class="text-blue-100 text-sm mt-2">{{ $opportunity->priority_label }} Priority</p>
            </div>
            <div class="text-right">
                <div class="mb-4">
                    <p class="text-blue-100 text-sm mb-1">Status</p>
                    <span class="inline-block px-3 py-1 bg-white bg-opacity-20 text-white text-sm font-medium rounded-full">
                        {{ ucfirst($opportunity->status) }}
                    </span>
                </div>
                <div>
                    <p class="text-blue-100 text-sm mb-1">Detected</p>
                    <p class="text-white font-medium">{{ $opportunity->detected_at->format('M d, Y') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Score Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Score Components -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Score Breakdown</h2>
            <div class="space-y-4">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Growth Score</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $opportunity->growth_score ?? 0 }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $opportunity->growth_score ?? 0 }}%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Intent Score</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $opportunity->intent_score ?? 0 }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: {{ $opportunity->intent_score ?? 0 }}%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Volume Score</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $opportunity->volume_score ?? 0 }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-purple-600 h-2 rounded-full" style="width: {{ $opportunity->volume_score ?? 0 }}%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Geo Score</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $opportunity->geo_score ?? 0 }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-orange-600 h-2 rounded-full" style="width: {{ $opportunity->geo_score ?? 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description & Actions -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Details</h2>
            <div class="prose prose-sm text-gray-600 mb-6">
                {{ $opportunity->description }}
            </div>

            @if($opportunity->ai_explanation)
                <div class="mb-6 p-4 bg-blue-50 border border-blue-100 rounded-lg">
                    <h3 class="text-sm font-semibold text-blue-900 mb-2">AI Analysis</h3>
                    <p class="text-sm text-blue-800">{{ $opportunity->ai_explanation }}</p>
                </div>
            @endif

            <div class="space-y-3">
                @if($opportunity->status === 'open')
                    <form action="{{ route('opportunities.update', $opportunity) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="in_progress">
                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                            Start Working on This
                        </button>
                    </form>
                @endif

                @if($opportunity->status !== 'dismissed')
                    <form action="{{ route('opportunities.update', $opportunity) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="dismissed">
                        <button type="submit" class="w-full px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                            Dismiss Opportunity
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <!-- Recommended Actions -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Recommended Actions</h2>
        <div class="space-y-3">
            <div class="flex items-start p-4 bg-gray-50 rounded-lg">
                <div class="p-2 bg-blue-100 rounded-lg mr-4">
                    <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 mb-1">Create Landing Page</h3>
                    <p class="text-sm text-gray-600">Build a targeted landing page to capture this demand</p>
                </div>
            </div>

            <div class="flex items-start p-4 bg-gray-50 rounded-lg">
                <div class="p-2 bg-green-100 rounded-lg mr-4">
                    <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 mb-1">Launch Campaign</h3>
                    <p class="text-sm text-gray-600">Start a marketing campaign targeting this opportunity</p>
                </div>
            </div>

            <div class="flex items-start p-4 bg-gray-50 rounded-lg">
                <div class="p-2 bg-purple-100 rounded-lg mr-4">
                    <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 mb-1">Set Up Alert</h3>
                    <p class="text-sm text-gray-600">Get notified when this opportunity scores higher</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
