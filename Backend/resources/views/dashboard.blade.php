@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Hero Section -->
    <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg shadow-lg p-8 mb-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2">Welcome back, {{ Auth::user()->name }}! 👋</h1>
                <p class="text-blue-100 text-lg">{{ Auth::user()->organization->name ?? 'Your Organization' }} • AI Demand Intelligence Platform</p>
                <p class="text-blue-200 mt-2 text-sm">Know what your market wants before competitors do</p>
            </div>
            <div class="hidden lg:block">
                <div class="bg-white bg-opacity-20 backdrop-blur-sm rounded-lg p-6 text-center">
                    <div class="text-3xl font-bold">{{ $stats['opportunities'] }}</div>
                    <div class="text-sm text-blue-100 mt-1">Active Opportunities</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Projects Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                    </svg>
                </div>
                <a href="{{ route('projects.create') }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">+ New</a>
            </div>
            <div class="text-3xl font-bold text-gray-900 mb-1">{{ $stats['projects'] }}</div>
            <div class="text-sm text-gray-600 mb-3">Active Projects</div>
            <a href="{{ route('projects.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">View all →</a>
        </div>

        <!-- Keywords Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-green-100 rounded-lg">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                    </svg>
                </div>
                <a href="{{ route('keywords.create') }}" class="text-green-600 hover:text-green-700 text-sm font-medium">+ Add</a>
            </div>
            <div class="text-3xl font-bold text-gray-900 mb-1">{{ $stats['keywords'] }}</div>
            <div class="text-sm text-gray-600 mb-3">Tracked Keywords</div>
            <a href="{{ route('keywords.index') }}" class="text-sm text-green-600 hover:text-green-700 font-medium">Manage →</a>
        </div>

        <!-- Opportunities Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-orange-100 rounded-lg">
                    <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                @if($stats['opportunities'] > 0)
                    <span class="text-xs px-2 py-1 bg-orange-100 text-orange-800 rounded-full font-medium">{{ $stats['opportunities'] }} new</span>
                @endif
            </div>
            <div class="text-3xl font-bold text-gray-900 mb-1">{{ $stats['opportunities'] }}</div>
            <div class="text-sm text-gray-600 mb-3">Open Opportunities</div>
            <a href="{{ route('opportunities.index') }}" class="text-sm text-orange-600 hover:text-orange-700 font-medium">Review →</a>
        </div>

        <!-- Leads Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <a href="{{ route('leads.create') }}" class="text-purple-600 hover:text-purple-700 text-sm font-medium">+ Add</a>
            </div>
            <div class="text-3xl font-bold text-gray-900 mb-1">{{ $stats['leads'] }}</div>
            <div class="text-sm text-gray-600 mb-3">Active Leads</div>
            <a href="{{ route('leads.index') }}" class="text-sm text-purple-600 hover:text-purple-700 font-medium">Manage →</a>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('projects.create') }}" class="flex items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition group">
                <div class="p-2 bg-blue-100 rounded-lg mr-4 group-hover:bg-blue-200 transition">
                    <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <div>
                    <div class="font-medium text-gray-900">Create Project</div>
                    <div class="text-sm text-gray-600">Start tracking demand</div>
                </div>
            </a>

            <a href="{{ route('keywords.create') }}" class="flex items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-green-500 hover:bg-green-50 transition group">
                <div class="p-2 bg-green-100 rounded-lg mr-4 group-hover:bg-green-200 transition">
                    <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <div>
                    <div class="font-medium text-gray-900">Add Keywords</div>
                    <div class="text-sm text-gray-600">Monitor search demand</div>
                </div>
            </a>

            <a href="{{ route('leads.create') }}" class="flex items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-purple-500 hover:bg-purple-50 transition group">
                <div class="p-2 bg-purple-100 rounded-lg mr-4 group-hover:bg-purple-200 transition">
                    <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <div>
                    <div class="font-medium text-gray-900">Add Lead</div>
                    <div class="text-sm text-gray-600">Capture new prospect</div>
                </div>
            </a>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content - Opportunities -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Top Opportunities</h3>
                            <p class="text-sm text-gray-600 mt-1">High-scoring demand opportunities ready for action</p>
                        </div>
                        <a href="{{ route('opportunities.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">View all →</a>
                    </div>
                </div>
                @if($opportunities->count() > 0)
                    <div class="divide-y divide-gray-200">
                        @foreach($opportunities as $opportunity)
                            <a href="{{ route('opportunities.show', $opportunity) }}" class="block p-6 hover:bg-gray-50 transition">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-2">
                                            <h4 class="font-semibold text-gray-900">{{ $opportunity->name ?? $opportunity->keyword->term ?? 'Opportunity' }}</h4>
                                            @php
                                                $priorityClass = match($opportunity->priority) {
                                                    'very_high' => 'bg-red-100 text-red-800',
                                                    'high' => 'bg-orange-100 text-orange-800',
                                                    'moderate' => 'bg-blue-100 text-blue-800',
                                                    default => 'bg-gray-100 text-gray-800',
                                                };
                                            @endphp
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $priorityClass }}">
                                                {{ ucfirst(str_replace('_', ' ', $opportunity->priority)) }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ $opportunity->description ?? 'Analyze this opportunity for potential business growth' }}</p>
                                        <div class="flex items-center space-x-4 text-sm text-gray-500">
                                            <div class="flex items-center">
                                                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ $opportunity->detected_at->diffForHumans() }}
                                            </div>
                                            <div class="flex items-center">
                                                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                </svg>
                                                {{ $opportunity->project->name }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ml-4 flex-shrink-0 text-right">
                                        <div class="text-3xl font-bold text-gray-900">{{ $opportunity->opportunity_score }}</div>
                                        <div class="text-xs text-gray-500">score</div>
                                        <div class="mt-2 w-16 bg-gray-200 rounded-full h-2">
                                            <div class="h-2 rounded-full {{ $opportunity->opportunity_score >= 80 ? 'bg-green-600' : ($opportunity->opportunity_score >= 60 ? 'bg-blue-600' : 'bg-yellow-600') }}" 
                                                 style="width: {{ $opportunity->opportunity_score }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="p-12 text-center">
                        <svg class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No opportunities yet</h3>
                        <p class="text-gray-600 mb-6">Add keywords to start detecting rising market demand</p>
                        <a href="{{ route('keywords.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add First Keyword
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar - Recent Leads -->
        <div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Leads</h3>
                        <a href="{{ route('leads.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">View all →</a>
                    </div>
                </div>
                @if($leads->count() > 0)
                    <div class="divide-y divide-gray-200">
                        @foreach($leads as $lead)
                            <a href="{{ route('leads.show', $lead) }}" class="block p-4 hover:bg-gray-50 transition">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 font-semibold">
                                        {{ substr($lead->name, 0, 1) }}
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <div class="flex items-center justify-between mb-1">
                                            <p class="text-sm font-medium text-gray-900">{{ $lead->name }}</p>
                                            @php
                                                $qualityClass = match($lead->quality) {
                                                    'hot' => 'bg-red-100 text-red-800',
                                                    'warm' => 'bg-orange-100 text-orange-800',
                                                    'potential' => 'bg-blue-100 text-blue-800',
                                                    default => 'bg-gray-100 text-gray-800',
                                                };
                                            @endphp
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $qualityClass }}">
                                                {{ ucfirst($lead->quality ?? 'New') }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-600 mb-1">{{ $lead->email }}</p>
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-gray-500">{{ $lead->created_at->diffForHumans() }}</span>
                                            @php
                                                $statusClass = match($lead->status) {
                                                    'new' => 'text-blue-600',
                                                    'contacted' => 'text-yellow-600',
                                                    'qualified' => 'text-green-600',
                                                    default => 'text-gray-600',
                                                };
                                            @endphp
                                            <span class="text-xs font-medium {{ $statusClass }}">{{ ucfirst($lead->status) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center">
                        <svg class="h-12 w-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <p class="text-sm text-gray-600 mb-4">No leads yet</p>
                        <a href="{{ route('leads.create') }}" class="text-sm text-purple-600 hover:text-purple-700 font-medium">Add first lead →</a>
                    </div>
                @endif
            </div>

            <!-- Getting Started Card -->
            <div class="mt-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg border border-blue-200 p-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h4 class="text-sm font-semibold text-blue-900 mb-2">Getting Started</h4>
                        <ul class="space-y-2 text-xs text-blue-800">
                            <li class="flex items-center">
                                <svg class="h-4 w-4 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                Create your first project
                            </li>
                            <li class="flex items-center">
                                <svg class="h-4 w-4 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                Add keywords to track
                            </li>
                            <li class="flex items-center opacity-50">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Review opportunities
                            </li>
                            <li class="flex items-center opacity-50">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Capture & convert leads
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
