@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('integrations.index') }}" class="text-gray-600 hover:text-gray-900">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    Add New Integration
                </h1>
                <p class="mt-1 text-gray-600">
                    Connect a new data source to your organization
                </p>
            </div>
        </div>
    </div>
            
            @if($errors->any())
                <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            @foreach($errors->all() as $error)
                                <p class="text-sm text-red-700">{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <form action="{{ route('integrations.store') }}" method="POST" class="bg-white shadow-sm rounded-lg overflow-hidden">
                @csrf

                <div class="p-6 space-y-6">
                    
                    {{-- Provider Type Selection --}}
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                            Provider Type *
                        </label>
                        <select name="type" id="type" required
                                class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                                onchange="updateCredentialsForm(this.value)">
                            <option value="">Select a provider...</option>
                            @foreach($availableProviders as $provider)
                                <option value="{{ $provider['type'] }}" {{ $type === $provider['type'] ? 'selected' : '' }}>
                                    {{ $provider['label'] }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-sm text-gray-500">
                            Choose the data source you want to connect
                        </p>
                    </div>

                    {{-- Integration Name --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Integration Name *
                        </label>
                        <input type="text" name="name" id="name" required
                               value="{{ old('name') }}"
                               class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="e.g., Main Google Trends Feed">
                        <p class="mt-1 text-sm text-gray-500">
                            A descriptive name for this integration
                        </p>
                    </div>

                    {{-- Dynamic Credentials Forms --}}
                    <div id="credentials-section" class="hidden">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Credentials</h3>
                        
                        {{-- Google Trends (no credentials needed) --}}
                        <div id="creds-google_trends" class="credentials-form hidden">
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                <p class="text-sm text-blue-800 dark:text-blue-200">
                                    ✨ No credentials required! Google Trends provides public data that can be accessed without authentication.
                                </p>
                            </div>
                        </div>

                        {{-- Google Ads --}}
                        <div id="creds-google_ads" class="credentials-form hidden space-y-4">
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                    ⚠️ Google Ads API requires OAuth 2.0 authentication. This is a complex setup. Please refer to the documentation.
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Client ID
                                </label>
                                <input type="text" name="credentials[client_id]"
                                       class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="Your Google Ads client ID">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Client Secret
                                </label>
                                <input type="password" name="credentials[client_secret]"
                                       class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="Your Google Ads client secret">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Refresh Token
                                </label>
                                <input type="text" name="credentials[refresh_token]"
                                       class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="OAuth refresh token">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Developer Token
                                </label>
                                <input type="text" name="credentials[developer_token]"
                                       class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="Google Ads API developer token">
                            </div>
                        </div>

                        {{-- Search Console --}}
                        <div id="creds-search_console" class="credentials-form hidden space-y-4">
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                    ⚠️ Search Console requires OAuth 2.0 authentication. This is a complex setup.
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Site URL
                                </label>
                                <input type="url" name="credentials[site_url]"
                                       class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="https://example.com">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Service Account JSON
                                </label>
                                <textarea name="credentials[service_account_json]" rows="6"
                                          class="w-full rounded-lg border-gray-300 font-mono text-xs focus:ring-indigo-500 focus:border-indigo-500"
                                          placeholder='{"type": "service_account", "project_id": "...", ...}'></textarea>
                            </div>
                        </div>

                        {{-- Africa's Talking --}}
                        <div id="creds-africas_talking" class="credentials-form hidden space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Username
                                </label>
                                <input type="text" name="credentials[username]"
                                       class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="Your Africa's Talking username">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    API Key
                                </label>
                                <input type="password" name="credentials[api_key]"
                                       class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="Your Africa's Talking API key">
                            </div>
                        </div>

                        {{-- OpenAI --}}
                        <div id="creds-openai" class="credentials-form hidden space-y-4">
                            <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                                <p class="text-sm text-indigo-800">
                                    🤖 OpenAI powers intent analysis, keyword clustering, opportunity explanations, and landing page content generation.
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    API Key
                                </label>
                                <input type="password" name="credentials[api_key]"
                                       class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="sk-...">
                                <p class="mt-1 text-sm text-gray-500">
                                    Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank" class="text-indigo-600 hover:underline">platform.openai.com</a>
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Model (Optional)
                                </label>
                                <select name="credentials[model]"
                                        class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="gpt-4o">gpt-4o (Recommended)</option>
                                    <option value="gpt-4o-mini">gpt-4o-mini (Faster, cheaper)</option>
                                    <option value="gpt-4-turbo">gpt-4-turbo</option>
                                    <option value="gpt-3.5-turbo">gpt-3.5-turbo (Legacy)</option>
                                </select>
                                <p class="mt-1 text-sm text-gray-500">
                                    Default: gpt-4o
                                </p>
                            </div>
                        </div>

                        {{-- Webhook --}}
                        <div id="creds-webhook" class="credentials-form hidden space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Webhook URL
                                </label>
                                <input type="url" name="credentials[url]"
                                       class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="https://your-server.com/webhook">
                                <p class="mt-1 text-sm text-gray-500">
                                    We'll POST opportunity alerts to this URL
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Secret (Optional)
                                </label>
                                <input type="password" name="credentials[secret]"
                                       class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="Webhook secret for signature verification">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Custom Headers (Optional)
                                </label>
                                <textarea name="credentials[headers]" rows="4"
                                          class="w-full rounded-lg border-gray-300 font-mono text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                          placeholder="Authorization: Bearer token&#10;X-Custom-Header: value"></textarea>
                                <p class="mt-1 text-sm text-gray-500">
                                    One header per line in format: Header-Name: value
                                </p>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
                    <a href="{{ route('integrations.index') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition shadow-sm">
                        Create Integration
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>

<script>
    function updateCredentialsForm(type) {
        // Hide all credential forms
        document.querySelectorAll('.credentials-form').forEach(form => {
            form.classList.add('hidden');
        });

        // Show the section
        const section = document.getElementById('credentials-section');
        if (type) {
            section.classList.remove('hidden');
            
            // Show the specific form for this type
            const credForm = document.getElementById('creds-' + type);
            if (credForm) {
                credForm.classList.remove('hidden');
            }
        } else {
            section.classList.add('hidden');
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('type');
        if (typeSelect.value) {
            updateCredentialsForm(typeSelect.value);
        }
    });
</script>
@endsection
