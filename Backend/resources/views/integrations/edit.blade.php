@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('integrations.show', $dataSource) }}" class="text-gray-600 hover:text-gray-900">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    Edit Integration
                </h1>
                <p class="mt-1 text-gray-600">
                    Update {{ $dataSource->name }} settings
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

            <form action="{{ route('integrations.update', $dataSource) }}" method="POST" class="bg-white shadow-sm rounded-lg overflow-hidden">
                @csrf
                @method('PATCH')

                <div class="p-6 space-y-6">
                    
                    {{-- Provider Type (read-only) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Provider Type
                        </label>
                        <div class="flex items-center space-x-3 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="w-10 h-10 bg-{{ $provider['color'] ?? 'gray' }}-100 rounded-lg flex items-center justify-center">
                                <span class="text-2xl">
                                    @if($dataSource->type === 'google_trends') 📈
                                    @elseif($dataSource->type === 'google_ads') 📢
                                    @elseif($dataSource->type === 'search_console') 🔍
                                    @elseif($dataSource->type === 'africas_talking') 📱
                                    @elseif($dataSource->type === 'openai') 🤖
                                    @elseif($dataSource->type === 'webhook') 🔗
                                    @else 🔌
                                    @endif
                                </span>
                            </div>
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">{{ $provider['label'] ?? ucfirst(str_replace('_', ' ', $dataSource->type)) }}</div>
                                <div class="text-sm text-gray-600">{{ $provider['desc'] ?? '' }}</div>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Provider type cannot be changed after creation
                        </p>
                    </div>

                    {{-- Integration Name --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Integration Name *
                        </label>
                        <input type="text" name="name" id="name" required
                               value="{{ old('name', $dataSource->name) }}"
                               class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="e.g., Main Google Trends Feed">
                        <p class="mt-1 text-sm text-gray-500">
                            A descriptive name for this integration
                        </p>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                            Status
                        </label>
                        <select name="status" id="status"
                                class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="active" {{ $dataSource->status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="paused" {{ $dataSource->status === 'paused' ? 'selected' : '' }}>Paused</option>
                            <option value="error" {{ $dataSource->status === 'error' ? 'selected' : '' }}>Error</option>
                        </select>
                    </div>

                    {{-- Update Credentials Section --}}
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Update Credentials</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Leave fields empty to keep existing credentials
                        </p>

                        @php
                            $existingCreds = $dataSource->getCredentials();
                        @endphp

                        {{-- Dynamic credential fields based on provider type --}}
                        @if($dataSource->type === 'google_trends')
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                <p class="text-sm text-blue-800 dark:text-blue-200">
                                    ✨ No credentials required for Google Trends
                                </p>
                            </div>
                        
                        @elseif($dataSource->type === 'google_ads')
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Client ID
                                    </label>
                                    <input type="text" name="credentials[client_id]"
                                           value="{{ old('credentials.client_id') }}"
                                           class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                                           placeholder="{{ !empty($existingCreds['client_id']) ? '••••••••' : 'Your Google Ads client ID' }}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Client Secret
                                    </label>
                                    <input type="password" name="credentials[client_secret]"
                                           class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                                           placeholder="{{ !empty($existingCreds['client_secret']) ? '••••••••' : 'Your Google Ads client secret' }}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Refresh Token
                                    </label>
                                    <input type="text" name="credentials[refresh_token]"
                                           class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                                           placeholder="{{ !empty($existingCreds['refresh_token']) ? '••••••••' : 'OAuth refresh token' }}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Developer Token
                                    </label>
                                    <input type="text" name="credentials[developer_token]"
                                           class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                                           placeholder="{{ !empty($existingCreds['developer_token']) ? '••••••••' : 'Google Ads API developer token' }}">
                                </div>
                            </div>

                        @elseif($dataSource->type === 'search_console')
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Site URL
                                    </label>
                                    <input type="url" name="credentials[site_url]"
                                           value="{{ old('credentials.site_url', $existingCreds['site_url'] ?? '') }}"
                                           class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                                           placeholder="https://example.com">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Service Account JSON
                                    </label>
                                    <textarea name="credentials[service_account_json]" rows="6"
                                              class="w-full rounded-lg border-gray-300 font-mono text-xs focus:ring-indigo-500 focus:border-indigo-500"
                                              placeholder='{"type": "service_account", "project_id": "...", ...}'>{{ old('credentials.service_account_json') }}</textarea>
                                    @if(!empty($existingCreds['service_account_json']))
                                        <p class="mt-1 text-xs text-green-600 dark:text-green-400">✓ Service account currently configured</p>
                                    @endif
                                </div>
                            </div>

                        @elseif($dataSource->type === 'africas_talking')
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Username
                                    </label>
                                    <input type="text" name="credentials[username]"
                                           value="{{ old('credentials.username', $existingCreds['username'] ?? '') }}"
                                           class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                                           placeholder="Your Africa's Talking username">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        API Key
                                    </label>
                                    <input type="password" name="credentials[api_key]"
                                           class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                                           placeholder="{{ !empty($existingCreds['api_key']) ? '••••••••' : 'Your Africa\'s Talking API key' }}">
                                    @if(!empty($existingCreds['api_key']))
                                        <p class="mt-1 text-xs text-green-600 dark:text-green-400">✓ API key currently configured</p>
                                    @endif
                                </div>
                            </div>

                        @elseif($dataSource->type === 'openai')
                            <div class="space-y-4">
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
                                           placeholder="{{ !empty($existingCreds['api_key']) ? '••••••••' : 'sk-...' }}">
                                    @if(!empty($existingCreds['api_key']))
                                        <p class="mt-1 text-xs text-green-600">✓ API key currently configured</p>
                                    @endif
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
                                        <option value="gpt-4o" {{ ($existingCreds['model'] ?? 'gpt-4o') === 'gpt-4o' ? 'selected' : '' }}>gpt-4o (Recommended)</option>
                                        <option value="gpt-4o-mini" {{ ($existingCreds['model'] ?? '') === 'gpt-4o-mini' ? 'selected' : '' }}>gpt-4o-mini (Faster, cheaper)</option>
                                        <option value="gpt-4-turbo" {{ ($existingCreds['model'] ?? '') === 'gpt-4-turbo' ? 'selected' : '' }}>gpt-4-turbo</option>
                                        <option value="gpt-3.5-turbo" {{ ($existingCreds['model'] ?? '') === 'gpt-3.5-turbo' ? 'selected' : '' }}>gpt-3.5-turbo (Legacy)</option>
                                    </select>
                                    <p class="mt-1 text-sm text-gray-500">
                                        Current: {{ $existingCreds['model'] ?? 'gpt-4o (default)' }}
                                    </p>
                                </div>
                            </div>

                        @elseif($dataSource->type === 'webhook')
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Webhook URL
                                    </label>
                                    <input type="url" name="credentials[url]"
                                           value="{{ old('credentials.url', $existingCreds['url'] ?? '') }}"
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
                                           placeholder="{{ !empty($existingCreds['secret']) ? '••••••••' : 'Webhook secret for signature verification' }}">
                                    @if(!empty($existingCreds['secret']))
                                        <p class="mt-1 text-xs text-green-600 dark:text-green-400">✓ Secret currently configured</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Custom Headers (Optional)
                                    </label>
                                    <textarea name="credentials[headers]" rows="4"
                                              class="w-full rounded-lg border-gray-300 font-mono text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                              placeholder="Authorization: Bearer token&#10;X-Custom-Header: value">{{ old('credentials.headers', $existingCreds['headers'] ?? '') }}</textarea>
                                    <p class="mt-1 text-sm text-gray-500">
                                        One header per line in format: Header-Name: value
                                    </p>
                                </div>
                            </div>
                        @endif

                    </div>

                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
                    <a href="{{ route('integrations.show', $dataSource) }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition shadow-sm">
                        Save Changes
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>
@endsection
