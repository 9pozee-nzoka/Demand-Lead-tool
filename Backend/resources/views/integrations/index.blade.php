@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">
            🔌 Integrations
        </h1>
        <p class="mt-2 text-gray-600">
            Connect external services and data sources
        </p>
    </div>

    <div class="space-y-6">
        
        <!-- Data Sources Section -->
        <div>
            <h3 class="text-lg font-semibold text-gray-900 mb-4">📊 Data Sources</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <!-- Google Trends -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3.064 7.51A9.996 9.996 0 0112 2c2.695 0 4.959.99 6.69 2.605l-2.867 2.868C14.786 6.482 13.468 5.977 12 5.977c-2.605 0-4.81 1.76-5.595 4.123-.2.6-.314 1.24-.314 1.9 0 .66.114 1.3.314 1.9.786 2.364 2.99 4.123 5.595 4.123 1.345 0 2.49-.355 3.386-.955a4.6 4.6 0 001.996-3.018H12v-3.868h9.418c.118.654.182 1.336.182 2.045 0 3.046-1.09 5.61-2.982 7.35C16.964 21.105 14.7 22 12 22A9.996 9.996 0 012 12c0-1.614.386-3.14 1.064-4.49z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Google Trends</h4>
                                <p class="text-xs text-gray-500">Keyword demand data</p>
                            </div>
                        </div>
                        @if(config('services.serpapi.key'))
                            <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                Active
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">
                                Demo Mode
                            </span>
                        @endif
                    </div>
                    
                    <p class="text-sm text-gray-600 mb-4">
                        @if(config('services.serpapi.key'))
                            Connected via SerpApi. Real-time trend data collection enabled.
                        @else
                            Using synthetic data for development. Add SERPAPI_KEY to enable real data.
                        @endif
                    </p>
                    
                    @if(config('services.serpapi.key'))
                        <div class="flex gap-2">
                            <button class="flex-1 px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                                Test Connection
                            </button>
                            <button class="flex-1 px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100">
                                Configure
                            </button>
                        </div>
                    @else
                        <a href="https://serpapi.com/pricing" target="_blank" 
                           class="block w-full px-3 py-2 text-sm font-medium text-center text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100">
                            Get API Key →
                        </a>
                    @endif
                </div>

                <!-- Search Console (Coming Soon) -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 opacity-60">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Search Console</h4>
                                <p class="text-xs text-gray-500">Google search data</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">
                            Coming Soon
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        Import search performance data, queries, and rankings from Google Search Console.
                    </p>
                </div>

                <!-- Analytics (Coming Soon) -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 opacity-60">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Google Analytics</h4>
                                <p class="text-xs text-gray-500">Website traffic data</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">
                            Coming Soon
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        Connect Google Analytics to track landing page performance and conversion rates.
                    </p>
                </div>

            </div>
        </div>

        <!-- Communication Section -->
        <div>
            <h3 class="text-lg font-semibold text-gray-900 mb-4">💬 Communication</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <!-- WhatsApp -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">WhatsApp Business</h4>
                                <p class="text-xs text-gray-500">Lead messaging</p>
                            </div>
                        </div>
                        @if(config('services.whatsapp.access_token'))
                            <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                Connected
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">
                                Not Connected
                            </span>
                        @endif
                    </div>
                    
                    <p class="text-sm text-gray-600 mb-4">
                        @if(config('services.whatsapp.access_token'))
                            Send messages, capture leads, and manage conversations via WhatsApp.
                        @else
                            Connect WhatsApp Business API to enable lead messaging and capture.
                        @endif
                    </p>
                    
                    <a href="{{ route('whatsapp.index') }}" 
                       class="block w-full px-3 py-2 text-sm font-medium text-center text-green-700 bg-green-50 rounded-lg hover:bg-green-100">
                        {{ config('services.whatsapp.access_token') ? 'Manage' : 'Connect' }} WhatsApp
                    </a>
                </div>

                <!-- Email (SMTP) -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Email (SMTP)</h4>
                                <p class="text-xs text-gray-500">Campaigns & alerts</p>
                            </div>
                        </div>
                        @if(config('mail.host'))
                            <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                Configured
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">
                                Not Set
                            </span>
                        @endif
                    </div>
                    
                    <p class="text-sm text-gray-600 mb-4">
                        SMTP: {{ config('mail.host') ?: 'Not configured' }}<br>
                        From: {{ config('mail.from.address') }}
                    </p>
                    
                    <button class="w-full px-3 py-2 text-sm font-medium text-purple-700 bg-purple-50 rounded-lg hover:bg-purple-100">
                        Test Email
                    </button>
                </div>

                <!-- SMS (Africa's Talking) -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">SMS Alerts</h4>
                                <p class="text-xs text-gray-500">Africa's Talking</p>
                            </div>
                        </div>
                        @if(config('services.africas_talking.api_key'))
                            <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                Active
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">
                                Not Set
                            </span>
                        @endif
                    </div>
                    
                    <p class="text-sm text-gray-600 mb-4">
                        Send SMS alerts for high-priority opportunities and leads.
                    </p>
                    
                    <button class="w-full px-3 py-2 text-sm font-medium text-indigo-700 bg-indigo-50 rounded-lg hover:bg-indigo-100">
                        {{ config('services.africas_talking.api_key') ? 'Test SMS' : 'Configure' }}
                    </button>
                </div>

            </div>
        </div>

        <!-- AI Section -->
        <div>
            <h3 class="text-lg font-semibold text-gray-900 mb-4">🤖 AI & Intelligence</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <!-- OpenAI -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">OpenAI</h4>
                                <p class="text-xs text-gray-500">AI explanations</p>
                            </div>
                        </div>
                        @if(config('services.openai.key'))
                            <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                Active
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">
                                Not Set
                            </span>
                        @endif
                    </div>
                    
                    <p class="text-sm text-gray-600 mb-4">
                        Model: {{ config('services.openai.model', 'gpt-4o-mini') }}<br>
                        Powers opportunity explanations and content generation.
                    </p>
                    
                    <button class="w-full px-3 py-2 text-sm font-medium text-teal-700 bg-teal-50 rounded-lg hover:bg-teal-100">
                        {{ config('services.openai.key') ? 'Test API' : 'Add API Key' }}
                    </button>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection
