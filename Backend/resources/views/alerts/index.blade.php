@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Alerts</h1>
                    <p class="text-sm text-gray-600 mt-1">Monitor and manage your demand intelligence alerts</p>
                </div>
                <div class="flex gap-3">
                    @if($stats['unread'] > 0)
                    <form action="{{ route('alerts.read-all') }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Mark All as Read
                        </button>
                    </form>
                    @endif
                    <a href="{{ route('alerts.rules') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                        Manage Rules
                    </a>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['total_alerts'] }}</div>
                    <div class="text-xs text-gray-600 mt-1">Total Alerts</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="text-2xl font-bold text-purple-600">{{ $stats['active_rules'] }}</div>
                    <div class="text-xs text-gray-600 mt-1">Active Rules</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="text-2xl font-bold text-green-600">{{ $stats['sent_today'] }}</div>
                    <div class="text-xs text-gray-600 mt-1">Sent Today</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="text-2xl font-bold text-red-600">{{ $stats['unread'] }}</div>
                    <div class="text-xs text-gray-600 mt-1">Unread</div>
                </div>
            </div>

            <!-- Alerts List -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Alerts</h3>
                </div>

                @if($alerts->isEmpty())
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No alerts yet</h3>
                        <p class="mt-1 text-sm text-gray-500">Alert rules will trigger notifications when conditions are met.</p>
                        <div class="mt-6">
                            <a href="{{ route('alerts.rules.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                                Create Alert Rule
                            </a>
                        </div>
                    </div>
                @else
                    <div class="divide-y divide-gray-200">
                        @foreach($alerts as $alert)
                        <div class="p-6 hover:bg-gray-50 {{ $alert->status === 'read' ? 'opacity-75' : '' }}">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        @php
                                            $typeColors = [
                                                'spike' => 'bg-red-100 text-red-800',
                                                'rising_trend' => 'bg-green-100 text-green-800',
                                                'threshold' => 'bg-yellow-100 text-yellow-800',
                                                'opportunity_detected' => 'bg-purple-100 text-purple-800',
                                            ];
                                        @endphp
                                        <span class="px-2 py-1 text-xs font-semibold rounded {{ $typeColors[$alert->type] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ ucwords(str_replace('_', ' ', $alert->type)) }}
                                        </span>
                                        
                                        @if($alert->status !== 'read')
                                        <span class="h-2 w-2 bg-purple-600 rounded-full"></span>
                                        @endif

                                        <span class="text-xs text-gray-500">{{ $alert->created_at->diffForHumans() }}</span>
                                    </div>

                                    <h4 class="text-base font-semibold text-gray-900 mb-1">
                                        {{ ucwords(str_replace('_', ' ', $alert->type)) }} Alert
                                    </h4>
                                    <p class="text-sm text-gray-600 mb-3">{{ $alert->message }}</p>

                                    @if($alert->opportunity)
                                    <div class="flex items-center gap-4 text-sm">
                                        <span class="text-gray-600">
                                            <strong>Opportunity:</strong> {{ $alert->opportunity->title }}
                                            (Score: {{ number_format($alert->opportunity->opportunity_score, 0) }})
                                        </span>
                                    </div>
                                    @endif

                                    <div class="mt-3 flex items-center gap-3">
                                        @if($alert->opportunity_id)
                                        <a href="{{ route('opportunities.show', $alert->opportunity_id) }}" class="text-sm text-purple-600 hover:text-purple-700 font-medium">
                                            View Opportunity →
                                        </a>
                                        @elseif($alert->lead_id)
                                        <a href="{{ route('leads.show', $alert->lead_id) }}" class="text-sm text-purple-600 hover:text-purple-700 font-medium">
                                            View Lead →
                                        </a>
                                        @endif

                                        @if($alert->status !== 'read')
                                        <form action="{{ route('alerts.read', $alert) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-sm text-gray-600 hover:text-gray-700">
                                                Mark as Read
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </div>

                                <div class="ml-4 text-right">
                                    @php
                                        $statusColors = [
                                            'sent'    => 'bg-green-100 text-green-800',
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'failed'  => 'bg-red-100 text-red-800',
                                            'read'    => 'bg-gray-100 text-gray-800',
                                        ];
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-medium rounded {{ $statusColors[$alert->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($alert->status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $alerts->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
