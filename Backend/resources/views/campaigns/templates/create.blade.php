@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-semibold text-neutral-900 dark:text-white">Create Email Template</h1>
        <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Create a reusable email template for campaigns</p>
    </div>

    <form action="{{ route('campaigns.templates.store') }}" method="POST">
        @csrf

        <div class="space-y-6">
            <!-- Basic Information -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-4">Basic Information</h2>
                
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                            Template Name *
                        </label>
                        <input type="text" 
                               name="name" 
                               id="name" 
                               value="{{ old('name') }}"
                               required
                               class="w-full px-4 py-2 bg-white dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-neutral-900 dark:text-white"
                               placeholder="e.g., Welcome Email">
                        @error('name')
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                            Description
                        </label>
                        <textarea name="description" 
                                  id="description" 
                                  rows="2"
                                  class="w-full px-4 py-2 bg-white dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-neutral-900 dark:text-white"
                                  placeholder="Brief description of this template">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="category" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                                Category
                            </label>
                            <select name="category" 
                                    id="category"
                                    class="w-full px-4 py-2 bg-white dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-neutral-900 dark:text-white">
                                <option value="welcome" {{ old('category') === 'welcome' ? 'selected' : '' }}>Welcome</option>
                                <option value="follow_up" {{ old('category') === 'follow_up' ? 'selected' : '' }}>Follow Up</option>
                                <option value="nurture" {{ old('category') === 'nurture' ? 'selected' : '' }}>Nurture</option>
                                <option value="promotional" {{ old('category') === 'promotional' ? 'selected' : '' }}>Promotional</option>
                                <option value="transactional" {{ old('category') === 'transactional' ? 'selected' : '' }}>Transactional</option>
                                <option value="other" {{ old('category') === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                                Status
                            </label>
                            <div class="flex items-center h-10">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" 
                                           name="is_active" 
                                           value="1" 
                                           {{ old('is_active', true) ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="relative w-11 h-6 bg-neutral-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-neutral-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-neutral-600 peer-checked:bg-blue-600"></div>
                                    <span class="ms-3 text-sm font-medium text-neutral-900 dark:text-neutral-300">Active</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Content -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-4">Email Content</h2>
                
                <div class="space-y-4">
                    <div>
                        <label for="subject" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                            Email Subject *
                        </label>
                        <input type="text" 
                               name="subject" 
                               id="subject" 
                               value="{{ old('subject') }}"
                               required
                               class="w-full px-4 py-2 bg-white dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-neutral-900 dark:text-white"
                               placeholder="e.g., Welcome to {{company_name}}!">
                        @error('subject')
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                            Use variables: {{"{{"}}company_name{{"}}"}}, {{"{{"}}lead_name{{"}}"}}, {{"{{"}}opportunity_title{{"}}"}}
                        </p>
                    </div>

                    <div>
                        <label for="body" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                            Email Body (HTML) *
                        </label>
                        <textarea name="body" 
                                  id="body" 
                                  rows="15"
                                  required
                                  class="w-full px-4 py-2 bg-white dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-neutral-900 dark:text-white font-mono text-sm"
                                  placeholder="<p>Hi {{lead_name}},</p><p>Welcome to our platform!</p>">{{ old('body') }}</textarea>
                        @error('body')
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                            Supports HTML and template variables
                        </p>
                    </div>
                </div>
            </div>

            <!-- Available Variables -->
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800 p-6">
                <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-200 mb-2">Available Template Variables</h3>
                <div class="grid grid-cols-2 gap-2 text-xs text-blue-800 dark:text-blue-300">
                    <code>{{"{{"}}company_name{{"}}"}}</code>
                    <code>{{"{{"}}lead_name{{"}}"}}</code>
                    <code>{{"{{"}}lead_email{{"}}"}}</code>
                    <code>{{"{{"}}opportunity_title{{"}}"}}</code>
                    <code>{{"{{"}}current_date{{"}}"}}</code>
                    <code>{{"{{"}}unsubscribe_link{{"}}"}}</code>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-3 justify-end">
                <a href="{{ route('campaigns.templates.index') }}" 
                   class="px-6 py-2 bg-neutral-100 dark:bg-neutral-700 text-neutral-900 dark:text-white rounded-lg hover:bg-neutral-200 dark:hover:bg-neutral-600 transition">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Create Template
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
