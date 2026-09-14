@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('integrations.index') }}" class="text-gray-600 hover:text-gray-900">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        {{ $dataSource->name }}
                    </h1>
                    <p class="mt-1 text-gray-600">
                        {{ ucfirst(str_replace('_', ' ', $dataSource->type)) }} integration
                    </p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                @if($dataSource->status === 'active')
                    <form action="{{ route('integrations.pause', $dataSource) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 border border-yellow-600 text-yellow-700 hover:bg-yellow-50 rounded-lg transition">
                            Pause
                        </button>
                    </form>
                @else
                    <form action="{{ route('integrations.activate', $dataSource) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 border border-green-600 text-green-700 hover:bg-green-50 rounded-lg transition">
                            Activate
                        </button>
                    </form>
                @endif
                
                <form action="{{ route('integrations.test', $dataSource) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        Test Connection
                    </button>
                </form>
                
                <a href="{{ route('integrations.edit', $dataSource) }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
                    Edit
                </a>
            </div>
        </div>
    </div>
            
            @if(session('success'))
                <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">{{ $errors->first() }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                {{-- Left column: Stats --}}
                <div class="lg:col-span-1 space-y-6">
                    
                    {{-- Status Card --}}
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Status</h3>
                        <div class="space-y-4">
                            <div>
                                <div class="text-sm text-gray-600 mb-1">Current Status</div>
                                @php
                                    $statusColors = [
                                        'active' => 'bg-green-100 text-green-800',
                                        'paused' => 'bg-yellow-100 text-yellow-800',
                                        'error'  => 'bg-red-100 text-red-800',
                                    ];
                                @endphp
                                <span class="px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$dataSource->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($dataSource->status) }}
                                </span>
                            </div>
                            
                            @if($dataSource->last_sync_at)
                                <div>
                                    <div class="text-sm text-gray-600 mb-1">Last Sync</div>
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $dataSource->last_sync_at->format('M d, Y H:i') }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $dataSource->last_sync_at->diffForHumans() }}
                                    </div>
                                </div>
                            @endif
                            
                            <div>
                                <div class="text-sm text-gray-600 mb-1">Created</div>
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $dataSource->created_at->format('M d, Y') }}
                                </div>
                            </div>
                            
                            @if(!empty($dataSource->encrypted_credentials))
                                <div>
                                    <div class="text-sm text-gray-600 mb-1">Credentials</div>
                                    <div class="flex items-center text-sm text-green-600 dark:text-green-400">
                                        <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Configured
                                    </div>
                                </div>
                            @else
                                <div>
                                    <div class="text-sm text-gray-600 mb-1">Credentials</div>
                                    <div class="flex items-center text-sm text-gray-500">
                                        <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        Not configured
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Usage Stats --}}
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Usage Statistics</h3>
                        <div class="space-y-4">
                            <div>
                                <div class="text-sm text-gray-600 mb-1">Keywords Tracked</div>
                                <div class="text-2xl font-bold text-gray-900">{{ $keywordCount }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600 mb-1">Measurements (30 days)</div>
                                <div class="text-2xl font-bold text-gray-900">{{ number_format($measurementCount) }}</div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Right column: Recent Activity --}}
                <div class="lg:col-span-2 space-y-6">
                    
                    {{-- Test Results --}}
                    @if(isset($dataSource->sync_meta['last_test']))
                        <div class="bg-white shadow-sm rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Last Test Result</h3>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        @if($dataSource->status === 'active')
                                            <svg class="h-6 w-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        @else
                                            <svg class="h-6 w-6 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <p class="text-sm text-gray-700">{{ $dataSource->sync_meta['test_result'] ?? 'No test result available' }}</p>
                                        <p class="mt-1 text-xs text-gray-500">
                                            Tested {{ \Carbon\Carbon::parse($dataSource->sync_meta['last_test'])->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Recent Measurements --}}
                    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Recent Measurements</h3>
                        </div>
                        @if($recentMeasurements->count() > 0)
                            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($recentMeasurements as $measurement)
                                    <div class="px-6 py-4 hover:bg-gray-50 transition">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $measurement->keyword->keyword ?? 'Unknown' }}
                                                </div>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    {{ $measurement->date }} • Interest: {{ $measurement->interest_score ?? 0 }}
                                                </div>
                                            </div>
                                            @if(isset($measurement->growth_rate))
                                                <div class="text-sm font-medium {{ $measurement->growth_rate > 0 ? 'text-green-600' : 'text-red-600' }}">
                                                    {{ $measurement->growth_rate > 0 ? '+' : '' }}{{ number_format($measurement->growth_rate, 1) }}%
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="px-6 py-8 text-center text-gray-500">
                                <svg class="h-12 w-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                <p class="text-sm">No measurements recorded yet</p>
                            </div>
                        @endif
                    </div>

                    {{-- Danger Zone --}}
                    <div class="bg-white shadow-sm rounded-lg overflow-hidden border-2 border-red-200 dark:border-red-800">
                        <div class="px-6 py-4 bg-red-50 dark:bg-red-900/20 border-b border-red-200 dark:border-red-800">
                            <h3 class="text-lg font-semibold text-red-900 dark:text-red-200">Danger Zone</h3>
                        </div>
                        <div class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Delete this integration</p>
                                    <p class="text-sm text-gray-600 mt-1">This action cannot be undone. All data collected from this source will remain.</p>
                                </div>
                                <form action="{{ route('integrations.destroy', $dataSource) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this integration? This action cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                                        Delete Integration
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>

</div>
@endsection
