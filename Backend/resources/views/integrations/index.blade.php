@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex justify-between items-center flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    🔌 Data Integrations
                </h1>
                <p class="mt-1 text-gray-600">
                    Connect external data sources to enrich your demand intelligence
                </p>
            </div>
            <a href="{{ route('integrations.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition shadow-sm">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Integration
            </a>
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

            {{-- Active Integrations --}}
            @if($sources->count() > 0)
                <div class="bg-white shadow-sm rounded-lg overflow-hidden mb-8">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Active Integrations</h3>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($sources as $source)
                            @php
                                $provider = collect($availableProviders)->firstWhere('type', $source->type);
                                $statusColors = [
                                    'active' => 'bg-green-100 text-green-800',
                                    'paused' => 'bg-yellow-100 text-yellow-800',
                                    'error'  => 'bg-red-100 text-red-800',
                                ];
                            @endphp
                            <div class="px-6 py-4 hover:bg-gray-50 transition">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center flex-1">
                                        <div class="flex-shrink-0 w-12 h-12 bg-{{ $provider['color'] ?? 'gray' }}-100 rounded-lg flex items-center justify-center">
                                            <span class="text-2xl">
                                                @if($source->type === 'google_trends') 📈
                                                @elseif($source->type === 'google_ads') 📢
                                                @elseif($source->type === 'search_console') 🔍
                                                @elseif($source->type === 'africas_talking') 📱
                                                @elseif($source->type === 'webhook') 🔗
                                                @else 🔌
                                                @endif
                                            </span>
                                        </div>
                                        <div class="ml-4 flex-1">
                                            <div class="flex items-center">
                                                <a href="{{ route('integrations.show', $source) }}" class="text-lg font-medium text-gray-900 hover:text-indigo-600">
                                                    {{ $source->name }}
                                                </a>
                                                <span class="ml-3 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$source->status] ?? 'bg-gray-100 text-gray-800' }}">
                                                    {{ ucfirst($source->status) }}
                                                </span>
                                            </div>
                                            <p class="mt-1 text-sm text-gray-600">
                                                {{ $provider['label'] ?? ucfirst(str_replace('_', ' ', $source->type)) }}
                                            </p>
                                            @if($source->last_sync_at)
                                                <p class="mt-1 text-xs text-gray-500">
                                                    Last synced: {{ $source->last_sync_at->diffForHumans() }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        @if($source->status === 'active')
                                            <form action="{{ route('integrations.pause', $source) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 text-sm text-yellow-700 hover:text-yellow-800 hover:bg-yellow-50 rounded transition">
                                                    Pause
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('integrations.activate', $source) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 text-sm text-green-700 hover:text-green-800 hover:bg-green-50 rounded transition">
                                                    Activate
                                                </button>
                                            </form>
                                        @endif
                                        
                                        <form action="{{ route('integrations.test', $source) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 text-sm text-blue-700 hover:text-blue-800 hover:bg-blue-50 rounded transition">
                                                Test
                                            </button>
                                        </form>
                                        
                                        <a href="{{ route('integrations.edit', $source) }}" class="px-3 py-1.5 text-sm text-indigo-700 hover:text-indigo-800 hover:bg-indigo-50 rounded transition">
                                            Edit
                                        </a>
                                        
                                        <form action="{{ route('integrations.destroy', $source) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this integration?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1.5 text-sm text-red-700 hover:text-red-800 hover:bg-red-50 rounded transition">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Available Providers --}}
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Available Providers</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Connect these data sources to enhance your demand intelligence
                    </p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                    @foreach($availableProviders as $provider)
                        @php
                            $isConnected = $sources->contains('type', $provider['type']);
                        @endphp
                        <div class="border border-gray-200 rounded-lg p-6 hover:border-{{ $provider['color'] }}-500 hover:shadow-md transition {{ $isConnected ? 'bg-gray-50' : '' }}">
                            <div class="flex items-start justify-between mb-4">
                                <div class="w-10 h-10 bg-{{ $provider['color'] }}-100 rounded-lg flex items-center justify-center">
                                    <span class="text-2xl">
                                        @if($provider['type'] === 'google_trends') 📈
                                        @elseif($provider['type'] === 'google_ads') 📢
                                        @elseif($provider['type'] === 'search_console') 🔍
                                        @elseif($provider['type'] === 'africas_talking') 📱
                                        @elseif($provider['type'] === 'webhook') 🔗
                                        @endif
                                    </span>
                                </div>
                                @if($isConnected)
                                    <span class="px-2 py-1 text-xs font-medium text-green-700 bg-green-100 rounded-full">Connected</span>
                                @endif
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-2">
                                {{ $provider['label'] }}
                            </h4>
                            <p class="text-sm text-gray-600 mb-4">
                                {{ $provider['desc'] }}
                            </p>
                            <a href="{{ route('integrations.create', ['type' => $provider['type']]) }}" 
                               class="inline-flex items-center text-sm font-medium text-{{ $provider['color'] }}-600 hover:text-{{ $provider['color'] }}-700">
                                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                {{ $isConnected ? 'Add Another' : 'Connect' }}
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
