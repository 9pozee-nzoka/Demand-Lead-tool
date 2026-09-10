<!-- Source Details Modal -->
<div x-show="showDetailsModal" 
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto" 
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div x-show="showDetailsModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
             @click="showDetailsModal = false"></div>

        <!-- Modal panel -->
        <div x-show="showDetailsModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full"
             x-init="if (selectedSource) loadSourceDetails()">
            
            <template x-if="selectedSource">
                <div>
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                    <svg x-show="selectedSource.type === 'rss'" class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M5 3a1 1 0 000 2c5.523 0 10 4.477 10 10a1 1 0 102 0C17 8.373 11.627 3 5 3z"/>
                                        <path d="M4 9a1 1 0 011-1 7 7 0 017 7 1 1 0 11-2 0 5 5 0 00-5-5 1 1 0 01-1-1zM3 15a2 2 0 114 0 2 2 0 01-4 0z"/>
                                    </svg>
                                    <svg x-show="selectedSource.type === 'tender'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                                    </svg>
                                    <svg x-show="selectedSource.type === 'webhook'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-white" x-text="selectedSource.name"></h3>
                                    <p class="text-blue-100 text-sm" x-text="selectedSource.base_url"></p>
                                </div>
                            </div>
                            <button @click="showDetailsModal = false" class="text-white hover:text-gray-200 transition">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="px-6 py-6 max-h-[80vh] overflow-y-auto">
                        <!-- Stats Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                            <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                                <p class="text-sm text-gray-600 font-medium">Success Rate</p>
                                <p class="text-2xl font-bold text-blue-600 mt-1" x-text="`${selectedSource.uptime_percentage}%`"></p>
                            </div>
                            <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                                <p class="text-sm text-gray-600 font-medium">Successful Runs</p>
                                <p class="text-2xl font-bold text-green-600 mt-1" x-text="selectedSource.success_count || 0"></p>
                            </div>
                            <div class="bg-red-50 rounded-lg p-4 border border-red-200">
                                <p class="text-sm text-gray-600 font-medium">Failed Runs</p>
                                <p class="text-2xl font-bold text-red-600 mt-1" x-text="selectedSource.error_count || 0"></p>
                            </div>
                            <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                                <p class="text-sm text-gray-600 font-medium">Status</p>
                                <span class="inline-block mt-1 px-3 py-1 text-sm font-semibold rounded-full"
                                      :class="{
                                          'bg-green-100 text-green-800': selectedSource.status === 'active',
                                          'bg-yellow-100 text-yellow-800': selectedSource.status === 'paused',
                                          'bg-red-100 text-red-800': selectedSource.status === 'error'
                                      }"
                                      x-text="selectedSource.status.charAt(0).toUpperCase() + selectedSource.status.slice(1)"></span>
                            </div>
                        </div>

                        <!-- Tabs -->
                        <div x-data="{ activeTab: 'recent_jobs' }" class="mb-6">
                            <div class="border-b border-gray-200 mb-4">
                                <nav class="flex space-x-8">
                                    <button @click="activeTab = 'recent_jobs'" 
                                            class="py-2 px-1 border-b-2 font-medium text-sm transition"
                                            :class="activeTab === 'recent_jobs' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                                        Recent Jobs
                                    </button>
                                    <button @click="activeTab = 'recent_items'" 
                                            class="py-2 px-1 border-b-2 font-medium text-sm transition"
                                            :class="activeTab === 'recent_items' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                                        Recent Items
                                    </button>
                                    <button @click="activeTab = 'configuration'" 
                                            class="py-2 px-1 border-b-2 font-medium text-sm transition"
                                            :class="activeTab === 'configuration' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                                        Configuration
                                    </button>
                                </nav>
                            </div>

                            <!-- Recent Jobs Tab -->
                            <div x-show="activeTab === 'recent_jobs'" class="space-y-3">
                                <template x-if="sourceDetails && sourceDetails.recent_jobs && sourceDetails.recent_jobs.length > 0">
                                    <div>
                                        <template x-for="job in sourceDetails.recent_jobs" :key="job.id">
                                            <div class="bg-gray-50 rounded-lg p-4 mb-3 border border-gray-200">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full"
                                                          :class="{
                                                              'bg-green-100 text-green-800': job.status === 'completed',
                                                              'bg-yellow-100 text-yellow-800': job.status === 'running',
                                                              'bg-red-100 text-red-800': job.status === 'failed'
                                                          }"
                                                          x-text="job.status.charAt(0).toUpperCase() + job.status.slice(1)"></span>
                                                    <span class="text-sm text-gray-600" x-text="new Date(job.started_at).toLocaleString()"></span>
                                                </div>
                                                <div class="grid grid-cols-4 gap-4 text-sm">
                                                    <div>
                                                        <p class="text-gray-600">Items Found</p>
                                                        <p class="font-semibold text-gray-900" x-text="job.items_found || 0"></p>
                                                    </div>
                                                    <div>
                                                        <p class="text-gray-600">New Items</p>
                                                        <p class="font-semibold text-green-600" x-text="job.items_new || 0"></p>
                                                    </div>
                                                    <div>
                                                        <p class="text-gray-600">Updated</p>
                                                        <p class="font-semibold text-blue-600" x-text="job.items_updated || 0"></p>
                                                    </div>
                                                    <div>
                                                        <p class="text-gray-600">Duration</p>
                                                        <p class="font-semibold text-gray-900" x-text="`${(job.duration || 0).toFixed(2)}s`"></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!sourceDetails || !sourceDetails.recent_jobs || sourceDetails.recent_jobs.length === 0">
                                    <div class="text-center py-8 text-gray-500">
                                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                        <p>No jobs found yet</p>
                                    </div>
                                </template>
                            </div>

                            <!-- Recent Items Tab -->
                            <div x-show="activeTab === 'recent_items'" class="space-y-3">
                                <template x-if="sourceDetails && sourceDetails.recent_items && sourceDetails.recent_items.length > 0">
                                    <div>
                                        <template x-for="item in sourceDetails.recent_items" :key="item.id">
                                            <div class="bg-gray-50 rounded-lg p-4 mb-3 border border-gray-200 hover:border-blue-300 transition">
                                                <div class="flex items-start justify-between mb-2">
                                                    <a :href="item.url" target="_blank" class="text-blue-600 hover:text-blue-800 font-semibold text-sm hover:underline" x-text="item.title"></a>
                                                    <span x-show="item.lead_id" class="ml-2 px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                                                        Converted to Lead
                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-600 mb-3 line-clamp-2" x-text="item.description"></p>
                                                <div class="flex items-center justify-between text-xs">
                                                    <div class="flex space-x-4">
                                                        <span class="text-gray-600">Intent: <span class="font-semibold capitalize" x-text="item.intent || 'Unknown'"></span></span>
                                                        <span class="text-gray-600">Opp: <span class="font-semibold" :class="item.opportunity_score >= 70 ? 'text-green-600' : item.opportunity_score >= 40 ? 'text-yellow-600' : 'text-gray-600'" x-text="item.opportunity_score || 0"></span></span>
                                                        <span class="text-gray-600">Lead: <span class="font-semibold" :class="item.lead_score >= 70 ? 'text-green-600' : item.lead_score >= 40 ? 'text-yellow-600' : 'text-gray-600'" x-text="item.lead_score || 0"></span></span>
                                                    </div>
                                                    <span class="text-gray-500" x-text="new Date(item.created_at).toLocaleDateString()"></span>
                                                </div>
                                                <div x-show="item.matched_keywords && item.matched_keywords.length > 0" class="mt-2 flex flex-wrap gap-1">
                                                    <template x-for="keyword in item.matched_keywords" :key="keyword">
                                                        <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full" x-text="keyword"></span>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!sourceDetails || !sourceDetails.recent_items || sourceDetails.recent_items.length === 0">
                                    <div class="text-center py-8 text-gray-500">
                                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <p>No items scraped yet</p>
                                    </div>
                                </template>
                            </div>

                            <!-- Configuration Tab -->
                            <div x-show="activeTab === 'configuration'">
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                    <dl class="space-y-3">
                                        <div>
                                            <dt class="text-sm font-semibold text-gray-700">Source URL</dt>
                                            <dd class="mt-1 text-sm text-gray-900" x-text="selectedSource.base_url"></dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-semibold text-gray-700">Schedule</dt>
                                            <dd class="mt-1 text-sm text-gray-900" x-text="selectedSource.schedule || 'Not scheduled'"></dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-semibold text-gray-700">Last Run</dt>
                                            <dd class="mt-1 text-sm text-gray-900" x-text="selectedSource.last_run_at ? new Date(selectedSource.last_run_at).toLocaleString() : 'Never'"></dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-semibold text-gray-700">Next Scheduled Run</dt>
                                            <dd class="mt-1 text-sm text-gray-900" x-text="getNextRunText(selectedSource)"></dd>
                                        </div>
                                        <div x-show="selectedSource.type === 'webhook'">
                                            <dt class="text-sm font-semibold text-gray-700">Webhook URL</dt>
                                            <dd class="mt-1 text-sm text-gray-900 font-mono bg-white px-3 py-2 rounded border border-gray-300" x-text="selectedSource.webhook_url || 'Not generated'"></dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t border-gray-200">
                        <div class="flex space-x-3">
                            <button @click="runSource(selectedSource); showDetailsModal = false" 
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                                Run Now
                            </button>
                            <button @click="testSource(selectedSource)" 
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                                Test Connection
                            </button>
                        </div>
                        <button @click="showDetailsModal = false" 
                                class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition">
                            Close
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

@push('scripts')
<script>
    async function loadSourceDetails() {
        if (!this.selectedSource) return;
        
        try {
            const response = await fetch(`/api/v1/sources/${this.selectedSource.id}`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
                    'Accept': 'application/json'
                }
            });
            this.sourceDetails = await response.json();
        } catch (error) {
            console.error('Failed to load source details:', error);
        }
    }

    window.loadSourceDetails = loadSourceDetails;
</script>
@endpush
