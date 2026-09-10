@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Data Sources</h1>
                <p class="text-gray-600 mt-1">Manage RSS feeds, tenders, webhooks, and other data collection sources</p>
            </div>
            @can('create', App\Models\SourceScraper::class)
            <a href="{{ route('sources.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Source
            </a>
            @endcan
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Sources</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total'] }}</p>
                </div>
                <div class="h-12 w-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Active</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['active'] }}</p>
                </div>
                <div class="h-12 w-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Paused</p>
                    <p class="text-2xl font-bold text-yellow-600 mt-1">{{ $stats['paused'] }}</p>
                </div>
                <div class="h-12 w-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Errors</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">{{ $stats['error'] }}</p>
                </div>
                <div class="h-12 w-12 bg-red-100 rounded-lg flex items-center justify-center">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <form method="GET" action="{{ route('sources.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Type Filter -->
            <div>
                <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    <option value="all">All Types</option>
                    <option value="rss" {{ request('type') === 'rss' ? 'selected' : '' }}>RSS Feed</option>
                    <option value="tender" {{ request('type') === 'tender' ? 'selected' : '' }}>Tender</option>
                    <option value="webhook" {{ request('type') === 'webhook' ? 'selected' : '' }}>Webhook</option>
                    <option value="api" {{ request('type') === 'api' ? 'selected' : '' }}>API</option>
                    <option value="scraper" {{ request('type') === 'scraper' ? 'selected' : '' }}>Web Scraper</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    <option value="all">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>Paused</option>
                    <option value="error" {{ request('status') === 'error' ? 'selected' : '' }}>Error</option>
                    <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
                </select>
            </div>

            <!-- Filter Button -->
            <div>
                <button type="submit" class="w-full px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700 transition">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Sources List -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        @if($sources->isEmpty())
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No data sources</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by adding your first data source.</p>
                @can('create', App\Models\SourceScraper::class)
                <div class="mt-6">
                    <a href="{{ route('sources.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Add Source
                    </a>
                </div>
                @endcan
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Run</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Success Rate</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($sources as $source)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <a href="{{ route('sources.show', $source) }}" class="hover:text-blue-600">
                                                {{ $source->name }}
                                            </a>
                                        </div>
                                        <div class="text-sm text-gray-500">{{ Str::limit($source->base_url, 50) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($source->type === 'rss') bg-purple-100 text-purple-800
                                    @elseif($source->type === 'tender') bg-green-100 text-green-800
                                    @elseif($source->type === 'webhook') bg-blue-100 text-blue-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($source->type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($source->status === 'active') bg-green-100 text-green-800
                                    @elseif($source->status === 'paused') bg-yellow-100 text-yellow-800
                                    @elseif($source->status === 'error') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($source->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $source->last_run_at ? $source->last_run_at->diffForHumans() : 'Never' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @php
                                    $total = $source->success_count + $source->error_count;
                                    $rate = $total > 0 ? round(($source->success_count / $total) * 100) : 0;
                                @endphp
                                <div class="flex items-center">
                                    <div class="flex-1">
                                        <div class="w-24 bg-gray-200 rounded-full h-2">
                                            <div class="bg-green-600 h-2 rounded-full" style="width: {{ $rate }}%"></div>
                                        </div>
                                    </div>
                                    <span class="ml-2">{{ $rate }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    <a href="{{ route('sources.show', $source) }}" class="text-blue-600 hover:text-blue-900" title="View">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    @can('update', $source)
                                    <form method="POST" action="{{ route('sources.run', $source) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-900" title="Run Now">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $sources->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
