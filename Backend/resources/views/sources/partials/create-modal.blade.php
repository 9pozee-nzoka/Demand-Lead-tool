<!-- Create Source Modal -->
<div x-show="showCreateModal" 
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto" 
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div x-show="showCreateModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
             @click="showCreateModal = false"></div>

        <!-- Modal panel -->
        <div x-show="showCreateModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white">Add Data Source</h3>
                    <button @click="showCreateModal = false" class="text-white hover:text-gray-200 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Step Indicator -->
            <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
                <div class="flex items-center justify-center space-x-8">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full" 
                             :class="createStep >= 1 ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-600'">
                            1
                        </div>
                        <span class="ml-2 text-sm font-medium" :class="createStep >= 1 ? 'text-gray-900' : 'text-gray-500'">Choose Type</span>
                    </div>
                    <div class="w-16 h-1 bg-gray-300" :class="createStep >= 2 ? 'bg-blue-600' : ''"></div>
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full" 
                             :class="createStep >= 2 ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-600'">
                            2
                        </div>
                        <span class="ml-2 text-sm font-medium" :class="createStep >= 2 ? 'text-gray-900' : 'text-gray-500'">Select Preset</span>
                    </div>
                    <div class="w-16 h-1 bg-gray-300" :class="createStep >= 3 ? 'bg-blue-600' : ''"></div>
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full" 
                             :class="createStep >= 3 ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-600'">
                            3
                        </div>
                        <span class="ml-2 text-sm font-medium" :class="createStep >= 3 ? 'text-gray-900' : 'text-gray-500'">Configure</span>
                    </div>
                </div>
            </div>

            <div class="px-6 py-6">
                <!-- Step 1: Choose Type -->
                <div x-show="createStep === 1">
                    <p class="text-gray-600 mb-6">Choose the type of data source you want to add</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- RSS -->
                        <div @click="selectedType = 'rss'; createStep = 2" 
                             class="bg-gradient-to-br from-orange-50 to-white border-2 border-orange-200 hover:border-orange-400 rounded-xl p-6 cursor-pointer transition transform hover:scale-105 hover:shadow-lg">
                            <div class="w-16 h-16 bg-orange-500 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 3a1 1 0 000 2c5.523 0 10 4.477 10 10a1 1 0 102 0C17 8.373 11.627 3 5 3z"/>
                                    <path d="M4 9a1 1 0 011-1 7 7 0 017 7 1 1 0 11-2 0 5 5 0 00-5-5 1 1 0 01-1-1zM3 15a2 2 0 114 0 2 2 0 01-4 0z"/>
                                </svg>
                            </div>
                            <h4 class="text-lg font-bold text-gray-900 text-center mb-2">RSS Feed</h4>
                            <p class="text-sm text-gray-600 text-center">Monitor news feeds, blogs, and publications for relevant content</p>
                        </div>

                        <!-- Tender -->
                        <div @click="selectedType = 'tender'; createStep = 2" 
                             class="bg-gradient-to-br from-blue-50 to-white border-2 border-blue-200 hover:border-blue-400 rounded-xl p-6 cursor-pointer transition transform hover:scale-105 hover:shadow-lg">
                            <div class="w-16 h-16 bg-blue-500 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                                </svg>
                            </div>
                            <h4 class="text-lg font-bold text-gray-900 text-center mb-2">Tender Portal</h4>
                            <p class="text-sm text-gray-600 text-center">Track government and corporate tenders automatically</p>
                        </div>

                        <!-- Webhook -->
                        <div @click="selectedType = 'webhook'; createStep = 2" 
                             class="bg-gradient-to-br from-purple-50 to-white border-2 border-purple-200 hover:border-purple-400 rounded-xl p-6 cursor-pointer transition transform hover:scale-105 hover:shadow-lg">
                            <div class="w-16 h-16 bg-purple-500 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <h4 class="text-lg font-bold text-gray-900 text-center mb-2">Webhook</h4>
                            <p class="text-sm text-gray-600 text-center">Capture leads from your website forms and integrations</p>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Select Preset -->
                <div x-show="createStep === 2">
                    <p class="text-gray-600 mb-6">Choose a preset or create a custom <span x-text="selectedType"></span> source</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-96 overflow-y-auto">
                        <!-- Presets -->
                        <template x-for="preset in templates[selectedType]?.presets" :key="preset.name">
                            <div @click="selectedPreset = preset; createFromPreset()" 
                                 class="bg-white border-2 border-gray-200 hover:border-blue-400 rounded-lg p-4 cursor-pointer transition transform hover:scale-105">
                                <h5 class="font-semibold text-gray-900 mb-1" x-text="preset.name"></h5>
                                <p class="text-sm text-gray-500 mb-2" x-text="preset.url"></p>
                                <p class="text-xs text-gray-600" x-text="preset.description"></p>
                            </div>
                        </template>

                        <!-- Custom -->
                        <div @click="selectedPreset = null; createStep = 3" 
                             class="bg-gradient-to-br from-blue-50 to-white border-2 border-dashed border-blue-300 hover:border-blue-500 rounded-lg p-4 cursor-pointer transition flex items-center justify-center">
                            <div class="text-center">
                                <svg class="w-12 h-12 text-blue-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <h5 class="font-semibold text-gray-900 mb-1">Custom <span x-text="selectedType" class="capitalize"></span></h5>
                                <p class="text-sm text-gray-600">Enter your own configuration</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Configure -->
                <div x-show="createStep === 3">
                    <form @submit.prevent="submitCustomSource()" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Source Name *</label>
                            <input type="text" 
                                   x-model="formData.name" 
                                   required
                                   placeholder="My News Feed"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <span x-text="selectedType === 'rss' ? 'Feed URL' : 'Base URL'"></span> *
                            </label>
                            <input type="url" 
                                   x-model="formData.base_url" 
                                   required
                                   placeholder="https://example.com/feed"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Keywords (comma-separated)</label>
                            <input type="text" 
                                   x-model="formData.keywords" 
                                   placeholder="business, technology, investment"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">Optional: Filter content by keywords</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Schedule</label>
                            <select x-model="formData.schedule" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="0 */4 * * *">Every 4 hours</option>
                                <option value="0 */6 * * *">Every 6 hours</option>
                                <option value="0 */12 * * *">Every 12 hours</option>
                                <option value="0 0 * * *">Daily at midnight</option>
                                <option value="0 8 * * *">Daily at 8 AM</option>
                                <option value="0 8,14 * * *">Twice daily (8 AM, 2 PM)</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t border-gray-200">
                <button @click="createStep > 1 ? createStep-- : (showCreateModal = false)" 
                        class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition">
                    <span x-text="createStep > 1 ? 'Back' : 'Cancel'"></span>
                </button>
                <button x-show="createStep === 3" 
                        @click="submitCustomSource()"
                        class="px-6 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold rounded-lg shadow-lg transition">
                    Create Source
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function createFromPreset() {
        return async function() {
            const template = this.templates[this.selectedType];
            const keywords = template.configuration.keywords || [];
            
            const payload = {
                name: this.selectedPreset.name,
                type: this.selectedType,
                base_url: this.selectedPreset.url,
                configuration: {
                    ...template.configuration,
                    keywords: keywords
                },
                schedule: template.schedule
            };

            // Add preset-specific config
            if (this.selectedType === 'tender' && this.selectedPreset.selector) {
                payload.configuration.tender_selector = this.selectedPreset.selector;
            }

            try {
                const response = await fetch('/api/v1/sources', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                if (response.ok) {
                    this.showNotification('Source created successfully!', 'success');
                    this.showCreateModal = false;
                    await this.loadSources();
                } else {
                    const error = await response.json();
                    this.showNotification(error.message || 'Failed to create source', 'error');
                }
            } catch (error) {
                this.showNotification('Failed to create source', 'error');
            }
        };
    }

    function submitCustomSource() {
        return async function() {
            const template = this.templates[this.selectedType];
            const keywords = this.formData.keywords 
                ? this.formData.keywords.split(',').map(k => k.trim()).filter(k => k)
                : [];

            const payload = {
                name: this.formData.name,
                type: this.selectedType,
                base_url: this.formData.base_url,
                configuration: {
                    ...template.configuration,
                    keywords: keywords
                },
                schedule: this.formData.schedule
            };

            try {
                const response = await fetch('/api/v1/sources', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                if (response.ok) {
                    this.showNotification('Source created successfully!', 'success');
                    this.showCreateModal = false;
                    await this.loadSources();
                } else {
                    const error = await response.json();
                    this.showNotification(error.message || 'Failed to create source', 'error');
                }
            } catch (error) {
                this.showNotification('Failed to create source', 'error');
            }
        };
    }

    // Make functions available to Alpine
    window.createFromPreset = createFromPreset;
    window.submitCustomSource = submitCustomSource;
</script>
@endpush
