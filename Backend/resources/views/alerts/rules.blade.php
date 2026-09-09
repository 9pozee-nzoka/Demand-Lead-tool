@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Alert Rules</h1>
                    <p class="text-sm text-gray-600 mt-1">Configure conditions for automatic notifications</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('alerts.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        ← Back to Alerts
                    </a>
                    <a href="{{ route('alerts.rules.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                        + Create Rule
                    </a>
                </div>
            </div>

            <!-- Rules List -->
            @if($rules->isEmpty())
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No alert rules</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating your first alert rule.</p>
                    <div class="mt-6">
                        <a href="{{ route('alerts.rules.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                            Create Alert Rule
                        </a>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @foreach($rules as $rule)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $rule->name }}</h3>
                                    <span class="px-2 py-1 text-xs font-medium rounded {{ $rule->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($rule->status) }}
                                    </span>
                                </div>
                                @if($rule->project)
                                <p class="text-sm text-gray-600">Project: {{ $rule->project->name }}</p>
                                @endif
                            </div>
                            <form action="{{ route('alerts.rules.toggle', $rule) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-gray-400 hover:text-gray-600">
                                    @if($rule->status === 'active')
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    @else
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    @endif
                                </button>
                            </form>
                        </div>

                        <div class="space-y-3 mb-4">
                            <div class="flex items-center gap-2 text-sm">
                                <span class="text-gray-500">Channels:</span>
                                <div class="flex gap-1">
                                    @if(is_array($rule->channels))
                                        @foreach($rule->channels as $channel)
                                        <span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded text-xs">{{ ucfirst($channel) }}</span>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                            
                            <div class="text-sm text-gray-600">
                                <span class="font-medium">Growth Threshold:</span> {{ $rule->minimum_growth }}%
                            </div>
                            
                            <div class="text-sm text-gray-600">
                                <span class="font-medium">Min Score:</span> {{ $rule->minimum_score }}
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <a href="{{ route('alerts.rules.edit', $rule) }}" class="flex-1 text-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Edit
                            </a>
                            <form action="{{ route('alerts.rules.destroy', $rule) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this rule?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-4 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
