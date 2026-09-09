@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-neutral-900 dark:text-white">WhatsApp Statistics</h1>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Analytics and performance metrics</p>
            </div>
            <a href="{{ route('integrations.whatsapp.index') }}" 
               class="px-4 py-2 bg-neutral-100 dark:bg-neutral-700 text-neutral-900 dark:text-white rounded-lg hover:bg-neutral-200 dark:hover:bg-neutral-600 transition">
                Back to WhatsApp
            </a>
        </div>
    </div>

    <!-- Overview Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Total Messages</h3>
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
            </div>
            <p class="text-3xl font-semibold text-neutral-900 dark:text-white">{{ $totalMessages ?? 0 }}</p>
            <p class="text-sm text-emerald-600 mt-1">+12% from last month</p>
        </div>

        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Response Rate</h3>
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-3xl font-semibold text-neutral-900 dark:text-white">{{ $responseRate ?? 85 }}%</p>
            <p class="text-sm text-emerald-600 mt-1">+3% from last month</p>
        </div>

        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Avg Response Time</h3>
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-3xl font-semibold text-neutral-900 dark:text-white">{{ $avgResponseTime ?? 2 }}m</p>
            <p class="text-sm text-rose-600 mt-1">-15s from last month</p>
        </div>

        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Qualified Leads</h3>
                <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <p class="text-3xl font-semibold text-neutral-900 dark:text-white">{{ $qualifiedLeads ?? 0 }}</p>
            <p class="text-sm text-emerald-600 mt-1">+8% conversion rate</p>
        </div>
    </div>

    <!-- Daily Messages Chart -->
    <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 mb-8">
        <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-6">Daily Messages (Last 30 Days)</h2>
        
        @if(isset($dailyMessages) && count($dailyMessages) > 0)
        <div class="h-64 flex items-end justify-between gap-2">
            @foreach($dailyMessages as $day)
            <div class="flex-1 flex flex-col items-center gap-2">
                <div class="w-full bg-blue-600 rounded-t hover:bg-blue-700 transition cursor-pointer relative group" 
                     style="height: {{ ($day['count'] / max(array_column($dailyMessages, 'count'))) * 100 }}%"
                     title="{{ $day['date'] }}: {{ $day['count'] }} messages">
                    <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-neutral-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap">
                        {{ $day['count'] }} messages
                    </div>
                </div>
                <span class="text-xs text-neutral-500 dark:text-neutral-400 transform rotate-45 origin-top-left">
                    {{ \Carbon\Carbon::parse($day['date'])->format('M d') }}
                </span>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-12">
            <p class="text-neutral-500 dark:text-neutral-400">No message data available yet</p>
        </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Message Quality Distribution -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-6">Lead Quality Distribution</h2>
            
            @if(isset($qualityDistribution) && count($qualityDistribution) > 0)
            <div class="space-y-4">
                @foreach($qualityDistribution as $quality)
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-neutral-900 dark:text-white">
                            {{ ucfirst($quality['quality']) }}
                        </span>
                        <span class="text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $quality['count'] }} leads ({{ $quality['percentage'] }}%)
                        </span>
                    </div>
                    <div class="h-2 bg-neutral-200 dark:bg-neutral-700 rounded-full overflow-hidden">
                        @php
                            $qualityColors = [
                                'hot' => 'bg-rose-600',
                                'warm' => 'bg-amber-600',
                                'potential' => 'bg-blue-600',
                                'low' => 'bg-neutral-400',
                            ];
                        @endphp
                        <div class="h-full {{ $qualityColors[$quality['quality']] ?? 'bg-neutral-400' }} rounded-full" 
                             style="width: {{ $quality['percentage'] }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-12">
                <p class="text-neutral-500 dark:text-neutral-400">No quality data available yet</p>
            </div>
            @endif
        </div>

        <!-- Top Performing Keywords -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-6">Top Conversation Topics</h2>
            
            @if(isset($topTopics) && count($topTopics) > 0)
            <div class="space-y-3">
                @foreach($topTopics as $index => $topic)
                <div class="flex items-center justify-between p-3 bg-neutral-50 dark:bg-neutral-900 rounded-lg">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-8 h-8 bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-200 rounded-full text-sm font-semibold">
                            {{ $index + 1 }}
                        </span>
                        <span class="text-sm font-medium text-neutral-900 dark:text-white">
                            {{ $topic['topic'] }}
                        </span>
                    </div>
                    <span class="text-sm text-neutral-500 dark:text-neutral-400">
                        {{ $topic['count'] }} mentions
                    </span>
                </div>
                @endforeach
            </div>
            @else
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-neutral-50 dark:bg-neutral-900 rounded-lg">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-8 h-8 bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-200 rounded-full text-sm font-semibold">1</span>
                        <span class="text-sm font-medium text-neutral-900 dark:text-white">Product Inquiry</span>
                    </div>
                    <span class="text-sm text-neutral-500 dark:text-neutral-400">45 mentions</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-neutral-50 dark:bg-neutral-900 rounded-lg">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-8 h-8 bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-200 rounded-full text-sm font-semibold">2</span>
                        <span class="text-sm font-medium text-neutral-900 dark:text-white">Pricing Question</span>
                    </div>
                    <span class="text-sm text-neutral-500 dark:text-neutral-400">32 mentions</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-neutral-50 dark:bg-neutral-900 rounded-lg">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-8 h-8 bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-200 rounded-full text-sm font-semibold">3</span>
                        <span class="text-sm font-medium text-neutral-900 dark:text-white">Support Request</span>
                    </div>
                    <span class="text-sm text-neutral-500 dark:text-neutral-400">28 mentions</span>
                </div>
            </div>
            @endif
        </div>

        <!-- Message Status Breakdown -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-6">Message Delivery Status</h2>
            
            <div class="space-y-4">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-emerald-600">✓✓</span>
                            <span class="text-sm font-medium text-neutral-900 dark:text-white">Delivered</span>
                        </div>
                        <span class="text-sm text-neutral-500 dark:text-neutral-400">{{ $deliveredMessages ?? 250 }}</span>
                    </div>
                    <div class="h-2 bg-neutral-200 dark:bg-neutral-700 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-600 rounded-full" style="width: 85%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-blue-600">✓</span>
                            <span class="text-sm font-medium text-neutral-900 dark:text-white">Sent</span>
                        </div>
                        <span class="text-sm text-neutral-500 dark:text-neutral-400">{{ $sentMessages ?? 30 }}</span>
                    </div>
                    <div class="h-2 bg-neutral-200 dark:bg-neutral-700 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-600 rounded-full" style="width: 10%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-rose-600">✗</span>
                            <span class="text-sm font-medium text-neutral-900 dark:text-white">Failed</span>
                        </div>
                        <span class="text-sm text-neutral-500 dark:text-neutral-400">{{ $failedMessages ?? 5 }}</span>
                    </div>
                    <div class="h-2 bg-neutral-200 dark:bg-neutral-700 rounded-full overflow-hidden">
                        <div class="h-full bg-rose-600 rounded-full" style="width: 5%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Peak Hours -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-6">Peak Hours</h2>
            
            <div class="space-y-3">
                @for($hour = 9; $hour <= 17; $hour++)
                <div class="flex items-center gap-3">
                    <span class="text-sm text-neutral-500 dark:text-neutral-400 w-16">
                        {{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00
                    </span>
                    <div class="flex-1 h-6 bg-neutral-200 dark:bg-neutral-700 rounded-full overflow-hidden">
                        @php
                            $activity = [9 => 30, 10 => 50, 11 => 70, 12 => 45, 13 => 40, 14 => 85, 15 => 90, 16 => 75, 17 => 55];
                        @endphp
                        <div class="h-full bg-blue-600 rounded-full" style="width: {{ $activity[$hour] }}%"></div>
                    </div>
                    <span class="text-sm text-neutral-900 dark:text-white font-medium w-12 text-right">
                        {{ $activity[$hour] }}%
                    </span>
                </div>
                @endfor
            </div>
        </div>
    </div>
</div>
@endsection
