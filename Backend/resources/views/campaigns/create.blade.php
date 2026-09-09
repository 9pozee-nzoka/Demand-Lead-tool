@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                Create Email Campaign
            </h1>
            <p class="text-gray-600 mt-1">Design and configure your email campaign</p>
        </div>

        <form action="{{ route('campaigns.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Campaign Details -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-4">Campaign Details</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Campaign Name *</label>
                        <input type="text" name="name" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            value="{{ old('name') }}">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Template (Optional)</label>
                        <select name="template_id" id="template_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Start from scratch</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}" {{ old('template_id') == $template->id ? 'selected' : '' }}>
                                    {{ $template->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subject Line *</label>
                        <input type="text" name="subject" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            value="{{ old('subject', $selectedTemplate->subject ?? '') }}">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Preview Text</label>
                        <input type="text" name="preview_text"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            value="{{ old('preview_text', $selectedTemplate->preview_text ?? '') }}"
                            placeholder="This appears in the inbox preview">
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-4">Email Content</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">HTML Content *</label>
                        <textarea name="html_content" rows="10" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                        >{{ old('html_content', $selectedTemplate->html_content ?? '<h1>Hello {{first_name}}!</h1><p>Your email content here...</p>') }}</textarea>
                        <p class="text-sm text-gray-500 mt-1">Use {{variable}} for personalization (e.g., {{first_name}}, {{last_name}}, {{company}})</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Plain Text Version (Optional)</label>
                        <textarea name="text_content" rows="6"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                        >{{ old('text_content', $selectedTemplate->text_content ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Audience -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-4">Audience</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Send To *</label>
                        <select name="audience_type" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="all_leads">All Leads</option>
                            <option value="opportunity">Leads from Opportunity</option>
                            <option value="segment">Segmented Leads</option>
                            <option value="manual">Manual Selection (Add Later)</option>
                        </select>
                    </div>

                    <div id="opportunity_selector" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Opportunity</label>
                        <select name="opportunity_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Choose an opportunity</option>
                            @foreach($opportunities as $opportunity)
                                <option value="{{ $opportunity->id }}">{{ $opportunity->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Settings -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-4">Settings</h3>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">From Name</label>
                            <input type="text" name="from_name"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                value="{{ old('from_name', auth()->user()->organization->name ?? '') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">From Email</label>
                            <input type="email" name="from_email"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                value="{{ old('from_email', auth()->user()->email) }}">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reply-To Email</label>
                        <input type="email" name="reply_to"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            value="{{ old('reply_to') }}">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Schedule Send (Optional)</label>
                        <input type="datetime-local" name="scheduled_at"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            value="{{ old('scheduled_at') }}" min="{{ now()->format('Y-m-d\TH:i') }}">
                        <p class="text-sm text-gray-500 mt-1">Leave empty to save as draft</p>
                    </div>

                    <div class="flex gap-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="track_opens" value="1" checked
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Track Opens</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="track_clicks" value="1" checked
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Track Clicks</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-between items-center">
                <a href="{{ route('campaigns.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 shadow-lg">
                    Create Campaign
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const audienceType = document.querySelector('[name="audience_type"]');
    const opportunitySelector = document.getElementById('opportunity_selector');
    
    audienceType.addEventListener('change', function() {
        if (this.value === 'opportunity') {
            opportunitySelector.classList.remove('hidden');
        } else {
            opportunitySelector.classList.add('hidden');
        }
    });
});
</script>
@endsection
