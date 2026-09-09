@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Competitive Intelligence</h1>
                <p class="text-sm text-gray-600 mt-1">AI-powered competitor analysis and positioning insights</p>
            </div>

            @if(!$aiConfigured)
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <svg class="h-5 w-5 text-yellow-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <h3 class="text-sm font-medium text-yellow-800">AI Analysis Unavailable</h3>
                        <p class="text-sm text-yellow-700 mt-1">Configure OpenAI API key in .env to enable AI-powered competitor analysis. Currently showing basic rule-based analysis.</p>
                    </div>
                </div>
            </div>
            @endif

            @if($showForm || empty($competitors))
            <!-- Analysis Form -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Analyze Competitors</h3>
                <form action="{{ route('competitors.analyze') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="competitors" class="block text-sm font-medium text-gray-700 mb-2">
                            Enter competitor names or domains (comma-separated)
                        </label>
                        <input 
                            type="text" 
                            name="competitors" 
                            id="competitors" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500"
                            placeholder="competitor1.com, Competitor Name, competitor3.com"
                            value="{{ implode(', ', $competitors ?? []) }}"
                            required
                        >
                        <p class="mt-1 text-sm text-gray-500">Example: airbnb.com, Booking.com, Expedia</p>
                    </div>
                    <button 
                        type="submit" 
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700"
                    >
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Analyze
                    </button>
                </form>
            </div>

            <!-- Example Use Cases -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gradient-to-br from-purple-50 to-white rounded-lg border border-purple-200 p-6">
                    <div class="h-10 w-10 bg-purple-100 rounded-lg flex items-center justify-center mb-3">
                        <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h4 class="font-semibold text-gray-900 mb-2">Positioning Analysis</h4>
                    <p class="text-sm text-gray-600">Identify gaps in competitor positioning and find differentiation opportunities</p>
                </div>

                <div class="bg-gradient-to-br from-green-50 to-white rounded-lg border border-green-200 p-6">
                    <div class="h-10 w-10 bg-green-100 rounded-lg flex items-center justify-center mb-3">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h4 class="font-semibold text-gray-900 mb-2">Strengths & Weaknesses</h4>
                    <p class="text-sm text-gray-600">Understand competitor advantages and vulnerabilities in your market</p>
                </div>

                <div class="bg-gradient-to-br from-blue-50 to-white rounded-lg border border-blue-200 p-6">
                    <div class="h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center mb-3">
                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <h4 class="font-semibold text-gray-900 mb-2">Strategy Insights</h4>
                    <p class="text-sm text-gray-600">Get actionable recommendations for competitive advantage</p>
                </div>
            </div>

            @else
            <!-- Analysis Results -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Analyzing: {{ implode(', ', $competitors) }}</h3>
                        <p class="text-sm text-gray-600 mt-1">Based on {{ count($keywords) }} tracked keywords</p>
                    </div>
                    <a href="{{ route('competitors.index') }}" class="text-purple-600 hover:text-purple-700 text-sm font-medium">
                        ← New Analysis
                    </a>
                </div>
            </div>

            <!-- Analysis Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Strengths -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center mb-4">
                        <div class="h-10 w-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Competitor Strengths</h3>
                    </div>
                    @if(empty($analysis['strengths']))
                        <p class="text-sm text-gray-500">No strengths identified</p>
                    @else
                        <ul class="space-y-3">
                            @foreach($analysis['strengths'] as $strength)
                            <li class="flex items-start">
                                <span class="h-5 w-5 text-green-500 mr-2 flex-shrink-0">+</span>
                                <span class="text-sm text-gray-700">{{ $strength }}</span>
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <!-- Weaknesses -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center mb-4">
                        <div class="h-10 w-10 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Competitor Weaknesses</h3>
                    </div>
                    @if(empty($analysis['weaknesses']))
                        <p class="text-sm text-gray-500">No weaknesses identified</p>
                    @else
                        <ul class="space-y-3">
                            @foreach($analysis['weaknesses'] as $weakness)
                            <li class="flex items-start">
                                <span class="h-5 w-5 text-red-500 mr-2 flex-shrink-0">−</span>
                                <span class="text-sm text-gray-700">{{ $weakness }}</span>
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <!-- Positioning Gaps -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center mb-4">
                        <div class="h-10 w-10 bg-yellow-100 rounded-lg flex items-center justify-center mr-3">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Positioning Gaps</h3>
                    </div>
                    @if(empty($analysis['positioning_gaps']))
                        <p class="text-sm text-gray-500">No positioning gaps identified</p>
                    @else
                        <ul class="space-y-3">
                            @foreach($analysis['positioning_gaps'] as $gap)
                            <li class="flex items-start">
                                <span class="h-5 w-5 text-yellow-500 mr-2 flex-shrink-0">⚠</span>
                                <span class="text-sm text-gray-700">{{ $gap }}</span>
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <!-- Differentiation Opportunities -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center mb-4">
                        <div class="h-10 w-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                            <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">How to Differentiate</h3>
                    </div>
                    @if(empty($analysis['differentiation_opportunities']))
                        <p class="text-sm text-gray-500">No opportunities identified</p>
                    @else
                        <ul class="space-y-3">
                            @foreach($analysis['differentiation_opportunities'] as $opportunity)
                            <li class="flex items-start">
                                <span class="h-5 w-5 text-purple-500 mr-2 flex-shrink-0">→</span>
                                <span class="text-sm text-gray-700">{{ $opportunity }}</span>
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <!-- Competitive Keywords -->
            @if(!empty($competitiveKeywords))
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- High Opportunity Keywords -->
                @if(!empty($competitiveKeywords['high_opportunity']))
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">🎯 Attack Opportunities</h3>
                        <p class="text-sm text-gray-600 mt-1">High-growth keywords to target aggressively</p>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            @foreach($competitiveKeywords['high_opportunity'] as $keyword)
                            <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900">{{ $keyword['term'] }}</div>
                                    <div class="text-xs text-gray-600 mt-1">
                                        <span class="px-2 py-0.5 bg-green-100 text-green-800 rounded">{{ ucfirst($keyword['state']) }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-semibold text-green-600">+{{ number_format($keyword['growth'], 1) }}%</div>
                                    <div class="text-xs text-gray-600">growth</div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Defensive Keywords -->
                @if(!empty($competitiveKeywords['defensive']))
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">🛡️ Defend Your Position</h3>
                        <p class="text-sm text-gray-600 mt-1">High-volume keywords to protect</p>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            @foreach($competitiveKeywords['defensive'] as $keyword)
                            <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900">{{ $keyword['term'] }}</div>
                                    <div class="text-xs text-gray-600 mt-1">
                                        <span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded">{{ ucfirst($keyword['state']) }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-semibold text-blue-600">{{ number_format($keyword['interest'], 0) }}</div>
                                    <div class="text-xs text-gray-600">interest</div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>
            @endif
            @endif
        </div>
    </div>
</div>
@endsection
