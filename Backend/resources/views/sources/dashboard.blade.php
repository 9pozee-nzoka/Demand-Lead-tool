@extends('layouts.app')

@section('title', 'Data Sources Dashboard')

@section('content')
<div x-data="sourcesManager()" x-init="init()" class="py-8">
    <!-- Header -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Data Sources</h1>
                <p class="text-gray-600 mt-1">Manage RSS feeds, tender portals, and webhooks</p>
            </div>
            <button @click="openCreateModal()" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition transform hover:scale-105">
                <svg class="w-5 h-5 inline-block mr-2 -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Source
            </button>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8" x-show="summary">
            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Total Sources</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1" x-text="summary.total || 0"></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Active</p>
                        <p class="text-3xl font-bold text-green-600 mt-1" x-text="summary.active || 0"></p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Paused</p>
                        <p class="text-3xl font-bold text-yellow-600 mt-1" x-text="summary.paused || 0"></p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Errors</p>
                        <p class="text-3xl font-bold text-red-600 mt-1" x-text="summary.error || 0"></p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <input type="text" 
                           x-model="searchQuery" 
                           @input="applyFilters()"
                           placeholder="Search sources..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <select x-model="filterType" @change="applyFilters()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="all">All Types</option>
                        <option value="rss">RSS</option>
                        <option value="tender">Tender</option>
                        <option value="webhook">Webhook</option>
                    </select>
                </div>
                <div>
                    <select x-model="filterStatus" @change="applyFilters()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="all">All Status</option>
                        <option value="active">Active</option>
                        <option value="paused">Paused</option>
                        <option value="error">Error</option>
                    </select>
                </div>
                <div>
                    <button @click="loadSources()" class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition">
                        <svg class="w-5 h-5 inline-block mr-2 -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- Sources Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <template x-if="loading">
                <div class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                    <p class="ml-4 text-gray-600">Loading sources...</p>
                </div>
            </template>

            <template x-if="!loading && filteredSources.length > 0">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Source Name</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Last Run</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="source in filteredSources" :key="source.id">
                                <tr class="hover:bg-gray-50 cursor-pointer transition" @click="viewDetails(source)">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-semibold text-gray-900" x-text="source.name"></div>
                                        <div class="text-xs text-gray-500" x-text="source.base_url"></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full"
                                              :class="{
                                                  'bg-orange-100 text-orange-800': source.type === 'rss',
                                                  'bg-blue-100 text-blue-800': source.type === 'tender',
                                                  'bg-purple-100 text-purple-800': source.type === 'webhook'
                                              }"
                                              x-text="source.type.toUpperCase()"></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full"
                                              :class="{
                                                  'bg-green-100 text-green-800': source.status === 'active',
                                                  'bg-yellow-100 text-yellow-800': source.status === 'paused',
                                                  'bg-red-100 text-red-800': source.status === 'error'
                                              }"
                                              x-text="source.status.charAt(0).toUpperCase() + source.status.slice(1)"></span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700" x-text="source.last_run_at ? new Date(source.last_run_at).toLocaleString() : 'Never'"></td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center space-x-2" @click.stop>
                                            <button @click="runSource(source)" class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition" title="Run Now">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </button>
                                            <button @click="toggleStatus(source)" class="p-2 hover:bg-gray-50 rounded-lg transition" 
                                                    :class="source.status === 'active' ? 'text-yellow-600' : 'text-green-600'"
                                                    :title="source.status === 'active' ? 'Pause' : 'Activate'">
                                                <svg x-show="source.status === 'active'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <svg x-show="source.status !== 'active'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </button>
                                            <button @click="deleteSource(source)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>

            <!-- Empty State -->
            <template x-if="!loading && filteredSources.length === 0">
                <div class="text-center py-16">
                    <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">No sources found</h3>
                    <p class="mt-2 text-gray-600">Get started by adding your first data source</p>
                    <button @click="openCreateModal()" class="mt-6 px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg transition">
                        Add Your First Source
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- Modals can be added here later -->
</div>

<script>
function sourcesManager() {
    return {
        sources: [],
        filteredSources: [],
        summary: { total: 0, active: 0, paused: 0, error: 0 },
        loading: false,
        searchQuery: '',
        filterType: 'all',
        filterStatus: 'all',

        async init() {
            await this.loadSources();
        },

        async loadSources() {
            this.loading = true;
            try {
                const response = await fetch('/api/v1/sources', {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                this.sources = data.data || [];
                this.summary = data.summary || { total: 0, active: 0, paused: 0, error: 0 };
                this.applyFilters();
            } catch (error) {
                console.error('Failed to load sources:', error);
                this.sources = [];
                this.filteredSources = [];
            } finally {
                this.loading = false;
            }
        },

        applyFilters() {
            this.filteredSources = this.sources.filter(source => {
                const matchesType = this.filterType === 'all' || source.type === this.filterType;
                const matchesStatus = this.filterStatus === 'all' || source.status === this.filterStatus;
                const matchesSearch = !this.searchQuery || 
                    source.name.toLowerCase().includes(this.searchQuery.toLowerCase());
                
                return matchesType && matchesStatus && matchesSearch;
            });
        },

        openCreateModal() {
            alert('Create modal feature coming soon!');
        },

        viewDetails(source) {
            alert(`Viewing details for: ${source.name}`);
        },

        async runSource(source) {
            if (confirm(`Run source "${source.name}" now?`)) {
                try {
                    const response = await fetch(`/api/v1/sources/${source.id}/run`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    alert(`Source ran successfully!`);
                    await this.loadSources();
                } catch (error) {
                    alert('Failed to run source');
                }
            }
        },

        async toggleStatus(source) {
            const action = source.status === 'active' ? 'pause' : 'activate';
            try {
                await fetch(`/api/v1/sources/${source.id}/${action}`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
                        'Accept': 'application/json'
                    }
                });
                alert(`Source ${action}d successfully`);
                await this.loadSources();
            } catch (error) {
                alert(`Failed to ${action} source`);
            }
        },

        async deleteSource(source) {
            if (confirm(`Are you sure you want to delete "${source.name}"? This cannot be undone.`)) {
                try {
                    await fetch(`/api/v1/sources/${source.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
                            'Accept': 'application/json'
                        }
                    });
                    alert('Source deleted successfully');
                    await this.loadSources();
                } catch (error) {
                    alert('Failed to delete source');
                }
            }
        }
    }
}
</script>
@endsection
