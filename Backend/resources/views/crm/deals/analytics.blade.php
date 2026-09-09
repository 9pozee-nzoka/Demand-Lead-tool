@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-semibold text-neutral-900 dark:text-white">Deal Analytics</h1>
        <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Performance metrics and pipeline insights</p>
    </div>

    <!-- Overview Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Total Pipeline Value</h3>
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-3xl font-semibold text-neutral-900 dark:text-white">
                ${{ number_format($totalPipelineValue ?? 0, 0) }}
            </p>
            <p class="text-sm text-emerald-600 mt-1">+18% from last month</p>
        </div>

        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Won Deals</h3>
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-3xl font-semibold text-neutral-900 dark:text-white">{{ $wonDealsCount ?? 0 }}</p>
            <p class="text-sm text-emerald-600 mt-1">${{ number_format($wonDealsValue ?? 0, 0) }} revenue</p>
        </div>

        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Win Rate</h3>
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
            <p class="text-3xl font-semibold text-neutral-900 dark:text-white">{{ $winRate ?? 32 }}%</p>
            <p class="text-sm text-emerald-600 mt-1">+5% from last month</p>
        </div>

        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Avg Deal Size</h3>
                <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
            </div>
            <p class="text-3xl font-semibold text-neutral-900 dark:text-white">
                ${{ number_format($avgDealSize ?? 0, 0) }}
            </p>
            <p class="text-sm text-rose-600 mt-1">-8% from last month</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Pipeline by Stage -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-6">Pipeline by Stage</h2>
            
            @if(isset($dealsByStage) && count($dealsByStage) > 0)
            <div class="space-y-4">
                @foreach($dealsByStage as $stage)
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-neutral-900 dark:text-white">
                            {{ ucfirst(str_replace('_', ' ', $stage['stage'])) }}
                        </span>
                        <span class="text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $stage['count'] }} deals • ${{ number_format($stage['value'], 0) }}
                        </span>
                    </div>
                    <div class="h-2 bg-neutral-200 dark:bg-neutral-700 rounded-full overflow-hidden">
                        @php
                            $stageColors = [
                                'lead' => 'bg-blue-600',
                                'qualified' => 'bg-amber-600',
                                'proposal' => 'bg-purple-600',
                                'negotiation' => 'bg-rose-600',
                                'closed_won' => 'bg-emerald-600',
                                'closed_lost' => 'bg-neutral-400',
                            ];
                        @endphp
                        <div class="h-full {{ $stageColors[$stage['stage']] ?? 'bg-blue-600' }} rounded-full" 
                             style="width: {{ $stage['percentage'] }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-12">
                <p class="text-neutral-500 dark:text-neutral-400">No deal data available</p>
            </div>
            @endif
        </div>

        <!-- Monthly Revenue Trend -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-6">Monthly Revenue (Last 6 Months)</h2>
            
            @if(isset($monthlyRevenue) && count($monthlyRevenue) > 0)
            <div class="h-64 flex items-end justify-between gap-2">
                @foreach($monthlyRevenue as $month)
                <div class="flex-1 flex flex-col items-center gap-2">
                    <div class="w-full bg-emerald-600 rounded-t hover:bg-emerald-700 transition cursor-pointer relative group" 
                         style="height: {{ ($month['revenue'] / max(array_column($monthlyRevenue, 'revenue'))) * 100 }}%"
                         title="{{ $month['month'] }}: ${{ number_format($month['revenue'], 0) }}">
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-neutral-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap">
                            ${{ number_format($month['revenue'], 0) }}
                        </div>
                    </div>
                    <span class="text-xs text-neutral-500 dark:text-neutral-400">
                        {{ \Carbon\Carbon::parse($month['month'])->format('M') }}
                    </span>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-12">
                <p class="text-neutral-500 dark:text-neutral-400">No revenue data available</p>
            </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Top Performers -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-6">Top Performers</h2>
            
            @if(isset($topPerformers) && count($topPerformers) > 0)
            <div class="space-y-3">
                @foreach($topPerformers as $index => $performer)
                <div class="flex items-center justify-between p-3 bg-neutral-50 dark:bg-neutral-900 rounded-lg">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-8 h-8 bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-200 rounded-full text-sm font-semibold">
                            {{ $index + 1 }}
                        </span>
                        <span class="text-sm font-medium text-neutral-900 dark:text-white">
                            {{ $performer['name'] }}
                        </span>
                    </div>
                    <span class="text-sm text-neutral-500 dark:text-neutral-400">
                        ${{ number_format($performer['revenue'], 0) }}
                    </span>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-12">
                <p class="text-neutral-500 dark:text-neutral-400">No performance data</p>
            </div>
            @endif
        </div>

        <!-- Deal Sources -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-6">Deal Sources</h2>
            
            <div class="space-y-4">
                @php
                    $sources = [
                        ['source' => 'Organic Search', 'count' => 45, 'value' => 125000],
                        ['source' => 'Direct', 'count' => 32, 'value' => 89000],
                        ['source' => 'Referral', 'count' => 28, 'value' => 76000],
                        ['source' => 'Social Media', 'count' => 15, 'value' => 42000],
                    ];
                @endphp
                @foreach($sources as $source)
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-neutral-900 dark:text-white">
                            {{ $source['source'] }}
                        </span>
                        <span class="text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $source['count'] }} deals
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 h-2 bg-neutral-200 dark:bg-neutral-700 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-600 rounded-full" style="width: {{ ($source['count'] / 45) * 100 }}%"></div>
                        </div>
                        <span class="text-xs text-neutral-500 dark:text-neutral-400">
                            ${{ number_format($source['value'] / 1000, 0) }}k
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Conversion Metrics -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-6">Conversion Metrics</h2>
            
            <div class="space-y-4">
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium text-blue-900 dark:text-blue-200">Lead → Qualified</span>
                        <span class="text-sm font-semibold text-blue-900 dark:text-blue-200">68%</span>
                    </div>
                    <div class="h-1.5 bg-blue-200 dark:bg-blue-800 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-600 rounded-full" style="width: 68%"></div>
                    </div>
                </div>

                <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium text-amber-900 dark:text-amber-200">Qualified → Proposal</span>
                        <span class="text-sm font-semibold text-amber-900 dark:text-amber-200">54%</span>
                    </div>
                    <div class="h-1.5 bg-amber-200 dark:bg-amber-800 rounded-full overflow-hidden">
                        <div class="h-full bg-amber-600 rounded-full" style="width: 54%"></div>
                    </div>
                </div>

                <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium text-purple-900 dark:text-purple-200">Proposal → Negotiation</span>
                        <span class="text-sm font-semibold text-purple-900 dark:text-purple-200">42%</span>
                    </div>
                    <div class="h-1.5 bg-purple-200 dark:bg-purple-800 rounded-full overflow-hidden">
                        <div class="h-full bg-purple-600 rounded-full" style="width: 42%"></div>
                    </div>
                </div>

                <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium text-emerald-900 dark:text-emerald-200">Negotiation → Won</span>
                        <span class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">75%</span>
                    </div>
                    <div class="h-1.5 bg-emerald-200 dark:bg-emerald-800 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-600 rounded-full" style="width: 75%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
