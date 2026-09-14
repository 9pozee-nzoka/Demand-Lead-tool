@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Trend Intelligence</h1>
                <p class="text-sm text-gray-600 mt-1">Real-time demand signals and market trends</p>
            </div>

            <!-- Insights Cards -->
            @if(!empty($trendsData['insights']))
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                @foreach($trendsData['insights'] as $insight)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            @if($insight['type'] === 'urgent')
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="h-2 w-2 bg-red-500 rounded-full animate-pulse"></span>
                                    <span class="text-xs font-semibold text-red-600 uppercase">{{ $insight['title'] }}</span>
                                </div>
                            @elseif($insight['type'] === 'opportunity')
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="h-2 w-2 bg-green-500 rounded-full"></span>
                                    <span class="text-xs font-semibold text-green-600 uppercase">{{ $insight['title'] }}</span>
                                </div>
                            @elseif($insight['type'] === 'warning')
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="h-2 w-2 bg-yellow-500 rounded-full"></span>
                                    <span class="text-xs font-semibold text-yellow-600 uppercase">{{ $insight['title'] }}</span>
                                </div>
                            @else
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="h-2 w-2 bg-blue-500 rounded-full"></span>
                                    <span class="text-xs font-semibold text-blue-600 uppercase">{{ $insight['title'] }}</span>
                                </div>
                            @endif
                            <p class="text-sm text-gray-700">{{ $insight['message'] }}</p>
                        </div>
                        <span class="text-2xl font-bold text-gray-900">{{ $insight['count'] }}</span>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Summary Stats -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900">{{ $trendsData['summary']['total_keywords'] }}</div>
                        <div class="text-xs text-gray-600 mt-1">Total Keywords</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600">{{ $trendsData['summary']['spike_count'] }}</div>
                        <div class="text-xs text-gray-600 mt-1">Spikes</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">{{ $trendsData['summary']['rising_count'] }}</div>
                        <div class="text-xs text-gray-600 mt-1">Rising</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ $trendsData['summary']['emerging_count'] }}</div>
                        <div class="text-xs text-gray-600 mt-1">Emerging</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-600">{{ $trendsData['summary']['stable_count'] }}</div>
                        <div class="text-xs text-gray-600 mt-1">Stable</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-600">{{ $trendsData['summary']['declining_count'] }}</div>
                        <div class="text-xs text-gray-600 mt-1">Declining</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">{{ $trendsData['summary']['action_required'] }}</div>
                        <div class="text-xs text-gray-600 mt-1">Action Required</div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        <a href="{{ route('trends.index', ['filter' => 'all']) }}" 
                           class="px-6 py-3 text-sm font-medium border-b-2 {{ $filter === 'all' ? 'border-purple-600 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            All Trends
                        </a>
                        <a href="{{ route('trends.index', ['filter' => 'spike']) }}" 
                           class="px-6 py-3 text-sm font-medium border-b-2 {{ $filter === 'spike' ? 'border-purple-600 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Spikes
                            @if($trendsData['summary']['spike_count'] > 0)
                            <span class="ml-2 bg-red-100 text-red-600 px-2 py-0.5 rounded-full text-xs">{{ $trendsData['summary']['spike_count'] }}</span>
                            @endif
                        </a>
                        <a href="{{ route('trends.index', ['filter' => 'rising']) }}" 
                           class="px-6 py-3 text-sm font-medium border-b-2 {{ $filter === 'rising' ? 'border-purple-600 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Rising
                            @if($trendsData['summary']['rising_count'] > 0)
                            <span class="ml-2 bg-green-100 text-green-600 px-2 py-0.5 rounded-full text-xs">{{ $trendsData['summary']['rising_count'] }}</span>
                            @endif
                        </a>
                        <a href="{{ route('trends.index', ['filter' => 'emerging']) }}" 
                           class="px-6 py-3 text-sm font-medium border-b-2 {{ $filter === 'emerging' ? 'border-purple-600 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Emerging
                        </a>
                        <a href="{{ route('trends.index', ['filter' => 'declining']) }}" 
                           class="px-6 py-3 text-sm font-medium border-b-2 {{ $filter === 'declining' ? 'border-purple-600 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Declining
                        </a>
                    </nav>
                </div>

                <!-- Trends Table -->
                <div class="p-6">
                    @php
                        $displayTrends = $filter === 'all' 
                            ? array_merge(
                                $trendsData['trends']['spike'],
                                $trendsData['trends']['rising'],
                                $trendsData['trends']['emerging'],
                                $trendsData['trends']['stable'],
                                $trendsData['trends']['declining']
                            )
                            : $trendsData['trends'][$filter] ?? [];
                    @endphp

                    @if(empty($displayTrends))
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No trends found</h3>
                            <p class="mt-1 text-sm text-gray-500">Start tracking keywords to see trend data.</p>
                            <div class="mt-6">
                                <a href="{{ route('keywords.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                                    Add Keywords
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keyword</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">State</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Current</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">7d Growth</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">30d Growth</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Volatility</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($displayTrends as $trend)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $trend['term'] }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm text-gray-600">{{ $trend['project'] }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @php
                                                $stateColors = [
                                                    'spike' => 'bg-red-100 text-red-800',
                                                    'rising' => 'bg-green-100 text-green-800',
                                                    'emerging' => 'bg-blue-100 text-blue-800',
                                                    'stable' => 'bg-gray-100 text-gray-800',
                                                    'declining' => 'bg-yellow-100 text-yellow-800',
                                                    'unknown' => 'bg-gray-100 text-gray-600',
                                                ];
                                            @endphp
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $stateColors[$trend['trend_state']] ?? 'bg-gray-100 text-gray-800' }}">
                                                {{ ucfirst($trend['trend_state']) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm text-gray-900">
                                            {{ number_format($trend['current_interest'], 0) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right">
                                            @if($trend['growth_7d'] > 0)
                                                <span class="text-sm font-medium text-green-600">+{{ number_format($trend['growth_7d'], 1) }}%</span>
                                            @elseif($trend['growth_7d'] < 0)
                                                <span class="text-sm font-medium text-red-600">{{ number_format($trend['growth_7d'], 1) }}%</span>
                                            @else
                                                <span class="text-sm text-gray-500">0%</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right">
                                            @if($trend['growth_30d'] > 0)
                                                <span class="text-sm font-medium text-green-600">+{{ number_format($trend['growth_30d'], 1) }}%</span>
                                            @elseif($trend['growth_30d'] < 0)
                                                <span class="text-sm font-medium text-red-600">{{ number_format($trend['growth_30d'], 1) }}%</span>
                                            @else
                                                <span class="text-sm text-gray-500">0%</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right">
                                            <span class="text-sm text-gray-600">{{ number_format($trend['volatility'], 1) }}</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            {{ $trend['last_measured'] ?? 'Never' }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Stats Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Trending Now -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">🔥 Trending Now</h3>
                    @if($trending->isEmpty())
                        <p class="text-sm text-gray-500">No trending keywords</p>
                    @else
                        <div class="space-y-3">
                            @foreach($trending as $keyword)
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-gray-900 truncate">{{ $keyword->keyword }}</div>
                                    <div class="text-xs text-gray-500">{{ $keyword->project->name }}</div>
                                </div>
                                <span class="ml-2 text-sm font-semibold text-green-600">+{{ number_format($keyword->growth_rate_7d, 0) }}%</span>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Emerging Opportunities -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">✨ Emerging</h3>
                    @if($emerging->isEmpty())
                        <p class="text-sm text-gray-500">No emerging keywords</p>
                    @else
                        <div class="space-y-3">
                            @foreach($emerging as $keyword)
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-gray-900 truncate">{{ $keyword->keyword }}</div>
                                    <div class="text-xs text-gray-500">{{ $keyword->project->name }}</div>
                                </div>
                                <span class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">New</span>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Declining -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">⚠️ Declining</h3>
                    @if($declining->isEmpty())
                        <p class="text-sm text-gray-500">No declining keywords</p>
                    @else
                        <div class="space-y-3">
                            @foreach($declining as $keyword)
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-gray-900 truncate">{{ $keyword->keyword }}</div>
                                    <div class="text-xs text-gray-500">{{ $keyword->project->name }}</div>
                                </div>
                                <span class="ml-2 text-sm font-semibold text-red-600">{{ number_format($keyword->growth_rate_7d, 0) }}%</span>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
