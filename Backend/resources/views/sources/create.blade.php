@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('sources.index') }}" class="text-gray-600 hover:text-gray-900">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    Add New Data Source
                </h1>
                <p class="mt-1 text-gray-600">
                    Configure a new source for automated data collection
                </p>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded max-w-4xl">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">There were errors with your submission</h3>
                    <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('sources.store') }}" method="POST" class="max-w-4xl mx-auto">
        @csrf

        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Basic Information</h2>

            <div class="space-y-6">
                <!-- Source Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Source Name *
                    </label>
                    <input type="text" name="name" id="name" required
                           value="{{ old('name') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="e.g., Kenya Government Tenders">
                    <p class="mt-1 text-sm text-gray-500">
                        A descriptive name for this data source
                    </p>
                </div>

                <!-- Source Type -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                        Source Type *
                    </label>
                    <select name="type" id="type" required
                            onchange="updateTypeFields(this.value)"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select source type...</option>
                        @foreach($availableProviders as $provider)
                            <option value="{{ $provider['type'] }}" {{ old('type') === $provider['type'] ? 'selected' : '' }}>
                                {{ $provider['label'] }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-sm text-gray-500">
                        Choose the type of data source to monitor
                    </p>
                </div>

                <!-- Base URL -->
                <div>
                    <label for="base_url" class="block text-sm font-medium text-gray-700 mb-2">
                        URL / Endpoint *
                    </label>
                    <input type="url" name="base_url" id="base_url" required
                           value="{{ old('base_url') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="https://example.com/feed">
                    <p class="mt-1 text-sm text-gray-500" id="url-help">
                        The URL to monitor (RSS feed, API endpoint, or website)
                    </p>
                </div>

                <!-- Schedule -->
                <div>
                    <label for="schedule" class="block text-sm font-medium text-gray-700 mb-2">
                        Collection Schedule
                    </label>
                    <select name="schedule" id="schedule"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="0 */6 * * *" {{ old('schedule') === '0 */6 * * *' ? 'selected' : '' }}>Every 6 hours (Default)</option>
                        <option value="0 */2 * * *" {{ old('schedule') === '0 */2 * * *' ? 'selected' : '' }}>Every 2 hours</option>
                        <option value="0 * * * *" {{ old('schedule') === '0 * * * *' ? 'selected' : '' }}>Every hour</option>
                        <option value="*/30 * * * *" {{ old('schedule') === '*/30 * * * *' ? 'selected' : '' }}>Every 30 minutes</option>
                        <option value="0 8 * * *" {{ old('schedule') === '0 8 * * *' ? 'selected' : '' }}>Daily at 8:00 AM</option>
                        <option value="0 0 * * 1" {{ old('schedule') === '0 0 * * 1' ? 'selected' : '' }}>Weekly on Monday</option>
                    </select>
                    <p class="mt-1 text-sm text-gray-500">
                        How often to check for new data
                    </p>
                </div>
            </div>
        </div>

        <!-- Advanced Configuration -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6" id="advanced-config" style="display: none;">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Scraper Configuration</h2>
            
            <div class="space-y-4">
                <p class="text-sm text-gray-600 mb-4">
                    Define CSS selectors to extract data from the page. Leave empty to use automatic detection.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Item Selector -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Item Container Selector
                        </label>
                        <input type="text" name="configuration[item_selector]"
                               value="{{ old('configuration.item_selector') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                               placeholder=".tender-item, article">
                        <p class="mt-1 text-xs text-gray-500">CSS selector for each item</p>
                    </div>

                    <!-- Title Selector -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Title Selector
                        </label>
                        <input type="text" name="configuration[title_selector]"
                               value="{{ old('configuration.title_selector') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                               placeholder="h2.title, .tender-title">
                        <p class="mt-1 text-xs text-gray-500">Selector for title</p>
                    </div>

                    <!-- Description Selector -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Description Selector
                        </label>
                        <input type="text" name="configuration[description_selector]"
                               value="{{ old('configuration.description_selector') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                               placeholder="p.description, .summary">
                        <p class="mt-1 text-xs text-gray-500">Selector for description</p>
                    </div>

                    <!-- URL Selector -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Link Selector
                        </label>
                        <input type="text" name="configuration[url_selector]"
                               value="{{ old('configuration.url_selector') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                               placeholder="a.details, a">
                        <p class="mt-1 text-xs text-gray-500">Selector for detail link</p>
                    </div>

                    <!-- Date Selector -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Date Selector
                        </label>
                        <input type="text" name="configuration[date_selector]"
                               value="{{ old('configuration.date_selector') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                               placeholder=".date, time">
                        <p class="mt-1 text-xs text-gray-500">Selector for publication date</p>
                    </div>

                    <!-- ID Selector -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            ID/Reference Selector
                        </label>
                        <input type="text" name="configuration[id_selector]"
                               value="{{ old('configuration.id_selector') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                               placeholder=".reference, .id">
                        <p class="mt-1 text-xs text-gray-500">Selector for unique ID</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Configuration -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6" id="api-config" style="display: none;">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">API Configuration</h2>
            
            <div class="space-y-4">
                <!-- API Key -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        API Key (Optional)
                    </label>
                    <input type="password" name="credentials"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                           placeholder="Enter API key if required">
                    <p class="mt-1 text-sm text-gray-500">
                        API key will be encrypted and sent as Bearer token
                    </p>
                </div>

                <!-- Data Path -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Items Path in Response
                    </label>
                    <input type="text" name="configuration[items_path]"
                           value="{{ old('configuration.items_path', 'data') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                           placeholder="data.results">
                    <p class="mt-1 text-sm text-gray-500">
                        JSON path to the items array (e.g., "data.results")
                    </p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-4">
            <a href="{{ route('sources.index') }}" 
               class="px-6 py-2.5 border-2 border-gray-300 hover:border-gray-400 text-gray-700 font-medium rounded-lg transition">
                Cancel
            </a>
            <button type="submit" 
                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition shadow-sm">
                Create Source
            </button>
        </div>
    </form>
</div>

<script>
function updateTypeFields(type) {
    const urlHelp = document.getElementById('url-help');
    const advancedConfig = document.getElementById('advanced-config');
    const apiConfig = document.getElementById('api-config');
    
    // Hide all configs first
    advancedConfig.style.display = 'none';
    apiConfig.style.display = 'none';
    
    // Update help text and show relevant config
    switch(type) {
        case 'rss':
            urlHelp.textContent = 'URL of the RSS/Atom feed (e.g., https://example.com/feed.xml)';
            break;
        case 'tender':
            urlHelp.textContent = 'URL of the tender listings page';
            advancedConfig.style.display = 'block';
            break;
        case 'scraper':
            urlHelp.textContent = 'URL of the website to scrape';
            advancedConfig.style.display = 'block';
            break;
        case 'api':
            urlHelp.textContent = 'API endpoint URL';
            apiConfig.style.display = 'block';
            break;
        case 'webhook':
            urlHelp.textContent = 'Webhook receiver endpoint will be generated automatically';
            document.getElementById('base_url').value = '{{ url("/") }}/api/webhook/' + Math.random().toString(36).substr(2, 9);
            document.getElementById('base_url').readOnly = true;
            break;
        default:
            urlHelp.textContent = 'The URL to monitor (RSS feed, API endpoint, or website)';
            document.getElementById('base_url').readOnly = false;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    if (typeSelect.value) {
        updateTypeFields(typeSelect.value);
    }
});
</script>
@endsection
