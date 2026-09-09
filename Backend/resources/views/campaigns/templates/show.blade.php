@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-neutral-900 dark:text-white">{{ $template->name }}</h1>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                    Created {{ $template->created_at->diffForHumans() }}
                    @if($template->creator)
                        by {{ $template->creator->name }}
                    @endif
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('campaigns.templates.preview', $template) }}" 
                   class="px-4 py-2 bg-neutral-100 dark:bg-neutral-700 text-neutral-900 dark:text-white rounded-lg hover:bg-neutral-200 dark:hover:bg-neutral-600 transition">
                    Preview
                </a>
                <a href="{{ route('campaigns.templates.edit', $template) }}" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Edit Template
                </a>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <!-- Template Info -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-4">Template Information</h2>
            
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Category</label>
                    <p class="mt-1 text-neutral-900 dark:text-white">
                        @if($template->category)
                            <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-900 dark:bg-blue-900 dark:text-blue-200">
                                {{ ucfirst(str_replace('_', ' ', $template->category)) }}
                            </span>
                        @else
                            <span class="text-neutral-400">Not specified</span>
                        @endif
                    </p>
                </div>

                <div>
                    <label class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Status</label>
                    <p class="mt-1">
                        @if($template->is_active)
                            <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium bg-emerald-100 text-emerald-900 dark:bg-emerald-900 dark:text-emerald-200">
                                Active
                            </span>
                        @else
                            <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium bg-neutral-100 text-neutral-900 dark:bg-neutral-700 dark:text-neutral-200">
                                Inactive
                            </span>
                        @endif
                    </p>
                </div>

                @if($template->description)
                <div class="col-span-2">
                    <label class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Description</label>
                    <p class="mt-1 text-neutral-900 dark:text-white">{{ $template->description }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Email Subject -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-4">Email Subject</h2>
            <div class="p-4 bg-neutral-50 dark:bg-neutral-900 rounded-lg">
                <p class="text-neutral-900 dark:text-white font-medium">{{ $template->subject }}</p>
            </div>
        </div>

        <!-- Email Body -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-4">Email Body</h2>
            <div class="p-4 bg-neutral-50 dark:bg-neutral-900 rounded-lg">
                <pre class="text-sm text-neutral-900 dark:text-white whitespace-pre-wrap font-mono overflow-x-auto">{{ $template->body }}</pre>
            </div>
        </div>

        <!-- Usage Statistics -->
        @if($template->campaigns && $template->campaigns->count() > 0)
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-4">
                Campaigns Using This Template ({{ $template->campaigns->count() }})
            </h2>
            
            <div class="space-y-3">
                @foreach($template->campaigns as $campaign)
                <div class="flex items-center justify-between p-3 bg-neutral-50 dark:bg-neutral-900 rounded-lg">
                    <div>
                        <p class="font-medium text-neutral-900 dark:text-white">{{ $campaign->name }}</p>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $campaign->status }} • Created {{ $campaign->created_at->format('M d, Y') }}
                        </p>
                    </div>
                    <a href="{{ route('campaigns.show', $campaign) }}" 
                       class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                        View →
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Actions -->
        <div class="flex gap-3 justify-between">
            <form action="{{ route('campaigns.templates.destroy', $template) }}" 
                  method="POST" 
                  onsubmit="return confirm('Are you sure you want to delete this template? This action cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" 
                        class="px-6 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition">
                    Delete Template
                </button>
            </form>

            <div class="flex gap-3">
                <a href="{{ route('campaigns.templates.index') }}" 
                   class="px-6 py-2 bg-neutral-100 dark:bg-neutral-700 text-neutral-900 dark:text-white rounded-lg hover:bg-neutral-200 dark:hover:bg-neutral-600 transition">
                    Back to Templates
                </a>
                <a href="{{ route('campaigns.create', ['template' => $template->id]) }}" 
                   class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Use Template
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
