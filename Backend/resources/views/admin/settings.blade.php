@extends('components.admin-layout')

@section('content')
<div class="container-fluid px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-white mb-2">⚙️ System Settings</h1>
        <p class="text-red-200">Configure system-wide settings and feature flags</p>
    </div>

    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    <!-- Settings Categories -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- General Settings -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span>🌐</span> General
                </h2>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">Site Name</span>
                    </label>
                    <input type="text" value="SoarCorp Demand Intelligence" 
                           class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">Support Email</span>
                    </label>
                    <input type="email" value="support@soarcorp.co.ke" 
                           class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">Default Timezone</span>
                    </label>
                    <select class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option>Africa/Nairobi</option>
                        <option>UTC</option>
                        <option>America/New_York</option>
                        <option>Europe/London</option>
                    </select>
                </div>
                <button class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    Save General Settings
                </button>
            </div>
        </div>

        <!-- Feature Flags -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span>🚀</span> Features
                </h2>
            </div>
            <div class="p-6 space-y-4">
                <label class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700">New User Registration</span>
                    <div class="relative inline-block w-12 align-middle select-none">
                        <input type="checkbox" checked class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer"/>
                        <label class="toggle-label block overflow-hidden h-6 rounded-full bg-green-500 cursor-pointer"></label>
                    </div>
                </label>

                <label class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700">AI Features</span>
                    <div class="relative inline-block w-12 align-middle select-none">
                        <input type="checkbox" checked class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer"/>
                        <label class="toggle-label block overflow-hidden h-6 rounded-full bg-green-500 cursor-pointer"></label>
                    </div>
                </label>

                <label class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700">WhatsApp Integration</span>
                    <div class="relative inline-block w-12 align-middle select-none">
                        <input type="checkbox" checked class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer"/>
                        <label class="toggle-label block overflow-hidden h-6 rounded-full bg-green-500 cursor-pointer"></label>
                    </div>
                </label>

                <label class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700">Email Alerts</span>
                    <div class="relative inline-block w-12 align-middle select-none">
                        <input type="checkbox" checked class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer"/>
                        <label class="toggle-label block overflow-hidden h-6 rounded-full bg-green-500 cursor-pointer"></label>
                    </div>
                </label>

                <label class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700">SMS Alerts</span>
                    <div class="relative inline-block w-12 align-middle select-none">
                        <input type="checkbox" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer"/>
                        <label class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"></label>
                    </div>
                </label>

                <button class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium">
                    Save Feature Flags
                </button>
            </div>
        </div>

        <!-- API & Integrations -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span>🔌</span> API & Integrations
                </h2>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700">SERPAPI Key</span>
                        <span class="text-xs text-gray-500">Google Trends</span>
                    </label>
                    <input type="password" value="{{ config('services.serpapi.key') ? '••••••••••' : '' }}" 
                           placeholder="Not configured"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 text-sm">
                </div>

                <div>
                    <label class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700">OpenAI API Key</span>
                        <span class="text-xs text-gray-500">AI Features</span>
                    </label>
                    <input type="password" value="{{ config('services.openai.key') ? '••••••••••' : '' }}" 
                           placeholder="Not configured"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 text-sm">
                </div>

                <div>
                    <label class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700">WhatsApp Token</span>
                        <span class="text-xs text-gray-500">Meta Business</span>
                    </label>
                    <input type="password" value="{{ config('services.whatsapp.token') ? '••••••••••' : '' }}" 
                           placeholder="Not configured"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 text-sm">
                </div>

                <button class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                    Update API Keys
                </button>
            </div>
        </div>
    </div>

    <!-- System Maintenance -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Maintenance Mode -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span>🔧</span> Maintenance Mode
                </h2>
            </div>
            <div class="p-6">
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-red-800 mb-2">
                        <strong>⚠️ Warning:</strong> Enabling maintenance mode will make the site unavailable to all users except super admins.
                    </p>
                </div>
                <label class="flex items-center justify-between mb-4">
                    <span class="text-sm font-medium text-gray-700">Enable Maintenance Mode</span>
                    <div class="relative inline-block w-12 align-middle select-none">
                        <input type="checkbox" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer"/>
                        <label class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"></label>
                    </div>
                </label>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Maintenance Message</label>
                    <textarea rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500"
                              placeholder="We're performing scheduled maintenance. We'll be back shortly."></textarea>
                </div>
            </div>
        </div>

        <!-- Cache Management -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span>⚡</span> Cache & Performance
                </h2>
            </div>
            <div class="p-6 space-y-3">
                <button onclick="clearCache('config')" 
                        class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium flex items-center justify-between">
                    <span>Clear Config Cache</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>

                <button onclick="clearCache('route')" 
                        class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium flex items-center justify-between">
                    <span>Clear Route Cache</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>

                <button onclick="clearCache('view')" 
                        class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium flex items-center justify-between">
                    <span>Clear View Cache</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>

                <button onclick="clearCache('all')" 
                        class="w-full px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors font-medium flex items-center justify-between">
                    <span>Clear All Caches</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- System Information -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-gray-700 to-gray-800 px-6 py-4">
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <span>ℹ️</span> System Information
            </h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Laravel Version</p>
                    <p class="text-lg font-bold text-gray-900">{{ app()->version() }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">PHP Version</p>
                    <p class="text-lg font-bold text-gray-900">{{ PHP_VERSION }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Environment</p>
                    <p class="text-lg font-bold text-gray-900">{{ config('app.env') }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Debug Mode</p>
                    <p class="text-lg font-bold {{ config('app.debug') ? 'text-red-600' : 'text-green-600' }}">
                        {{ config('app.debug') ? 'Enabled' : 'Disabled' }}
                    </p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Cache Driver</p>
                    <p class="text-lg font-bold text-gray-900">{{ config('cache.default') }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Queue Driver</p>
                    <p class="text-lg font-bold text-gray-900">{{ config('queue.default') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function clearCache(type) {
    if(confirm(`Clear ${type} cache?`)) {
        fetch(`/super-admin/cache/clear/${type}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message || 'Cache cleared successfully');
        })
        .catch(error => {
            alert('Error clearing cache');
        });
    }
}
</script>

<style>
.toggle-checkbox:checked {
    right: 0;
    border-color: #22c55e;
}
.toggle-checkbox:checked + .toggle-label {
    background-color: #22c55e;
}
</style>
@endsection
