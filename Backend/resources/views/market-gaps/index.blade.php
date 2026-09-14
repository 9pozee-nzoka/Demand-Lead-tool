@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Market Gap Analysis</h1>
                    <p class="text-sm text-gray-600 mt-1">AI-powered insights to identify untapped opportunities</p>
                </div>
                <form action="{{ route('market-gaps.refresh') }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh Analysis
                    </button>
                </form>
            </div>

            @if(!$aiConfigured)
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <svg class="h-5 w-5 text-yellow-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <h3 class="text-sm font-medium text-yellow-800">AI Analysis Unavailable</h3>
                        <p class="text-sm text-yellow-700 mt-1">Configure OpenAI API key in .env to enable AI-powered market gap analysis. Currently showing basic rule-based analysis.</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</div>
                    <div class="text-xs text-gray-600 mt-1">Total Keywords</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="text-2xl font-bold text-green-600">{{ $stats['rising'] }}</div>
                    <div class="text-xs text-gray-600 mt-1">Rising Trends</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="text-2xl font-bold text-blue-600">{{ $stats['emerging'] }}</div>
                    <div class="text-xs text-gray-600 mt-1">Emerging</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="text-2xl font-bold text-red-600">{{ $stats['spike'] }}</div>
                    <div class="text-xs text-gray-600 mt-1">Spikes</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="text-2xl font-bold text-purple-600">{{ $stats['high_growth'] }}</div>
                    <div class="text-xs text-gray-600 mt-1">High Growth</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['avg_growth'] }}%</div>
                    <div class="text-xs text-gray-600 mt-1">Avg Growth</div>
                </div>
            </div>

            <!-- Main Analysis Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Market Gaps -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center mb-4">
                        <div class="h-10 w-10 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Market Gaps</h3>
                    </div>
                    @if(empty($analysis['gaps']))
                        <p class="text-sm text-gray-500">No market gaps identified</p>
                    @else
                        <ul class="space-y-3">
                            @foreach($analysis['gaps'] as $gap)
                            <li class="flex items-start">
                                <span class="h-5 w-5 text-red-500 mr-2 flex-shrink-0">•</span>
                                <span class="text-sm text-gray-700">{{ $gap }}</span>
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <!-- Opportunities -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center mb-4">
                        <div class="h-10 w-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Opportunities</h3>
                    </div>
                    @if(empty($analysis['opportunities']))
                        <p class="text-sm text-gray-500">No opportunities identified</p>
                    @else
                        <ul class="space-y-3">
                            @foreach($analysis['opportunities'] as $opportunity)
                            <li class="flex items-start">
                                <span class="h-5 w-5 text-green-500 mr-2 flex-shrink-0">✓</span>
                                <span class="text-sm text-gray-700">{{ $opportunity }}</span>
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <!-- Recommendations -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center mb-4">
                        <div class="h-10 w-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                            <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Action Items</h3>
                    </div>
                    @if(empty($analysis['recommendations']))
                        <p class="text-sm text-gray-500">No recommendations available</p>
                    @else
                        <ul class="space-y-3">
                            @foreach($analysis['recommendations'] as $recommendation)
                            <li class="flex items-start">
                                <span class="h-5 w-5 text-purple-500 mr-2 flex-shrink-0">→</span>
                                <span class="text-sm text-gray-700">{{ $recommendation }}</span>
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <!-- Untapped Keywords -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">🎯 Untapped Keywords</h3>
                    <p class="text-sm text-gray-600 mt-1">High-growth keywords without active campaigns</p>
                </div>
                <div class="p-6">
                    @if($untappedKeywords->isEmpty())
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-600">All trending keywords have active campaigns</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keyword</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">State</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">30d Growth</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Current</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($untappedKeywords as $keyword)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $keyword->keyword }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $keyword->project->name }}</td>
                                        <td class="px-4 py-3">
                                            @php
                                                $stateColors = [
                                                    'spike' => 'bg-red-100 text-red-800',
                                                    'rising' => 'bg-green-100 text-green-800',
                                                    'emerging' => 'bg-blue-100 text-blue-800',
                                                ];
                                            @endphp
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $stateColors[$keyword->trend_state] ?? 'bg-gray-100 text-gray-800' }}">
                                                {{ ucfirst($keyword->trend_state) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="text-sm font-semibold text-green-600">+{{ number_format($keyword->growth_rate_30d, 1) }}%</span>
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-900">{{ number_format($keyword->current_interest, 0) }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('opportunities.index', ['keyword' => $keyword->id]) }}" class="text-purple-600 hover:text-purple-700 text-sm font-medium">
                                                Create Opportunity →
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Coverage Gaps -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">📊 Coverage Gaps</h3>
                    <p class="text-sm text-gray-600 mt-1">High-interest keywords without sufficient coverage</p>
                </div>
                <div class="p-6">
                    @if($coverageGaps->isEmpty())
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-600">All high-interest keywords have sufficient coverage</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keyword</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Interest</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Opportunities</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($coverageGaps as $keyword)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $keyword->keyword }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $keyword->project->name }}</td>
                                        <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900">{{ number_format($keyword->current_interest, 0) }}</td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-600">0</td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('keywords.show', $keyword) }}" class="text-purple-600 hover:text-purple-700 text-sm font-medium">
                                                View Details →
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
