@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-neutral-900 dark:text-white">Email Templates</h1>
            <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Create and manage reusable email templates</p>
        </div>
        <a href="{{ route('campaigns.templates.create') }}" 
           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            Create Template
        </a>
    </div>

    <!-- Templates Grid -->
    @if($templates->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($templates as $template)
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 hover:border-blue-500 dark:hover:border-blue-500 transition">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-neutral-900 dark:text-white mb-1">
                        {{ $template->name }}
                    </h3>
                    @if($template->category)
                    <span class="inline-flex px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-900 dark:bg-blue-900 dark:text-blue-200">
                        {{ ucfirst($template->category) }}
                    </span>
                    @endif
                </div>
                
                @if($template->is_active)
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-900 dark:bg-emerald-900 dark:text-emerald-200">
                    Active
                </span>
                @else
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-neutral-100 text-neutral-900 dark:bg-neutral-700 dark:text-neutral-200">
                    Inactive
                </span>
                @endif
            </div>

            @if($template->description)
            <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-4 line-clamp-2">
                {{ $template->description }}
            </p>
            @endif

            <div class="mb-4 p-3 bg-neutral-50 dark:bg-neutral-900 rounded-lg">
                <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400 mb-1">Subject:</p>
                <p class="text-sm text-neutral-900 dark:text-white line-clamp-1">{{ $template->subject }}</p>
            </div>

            <div class="flex items-center justify-between text-xs text-neutral-500 dark:text-neutral-400 mb-4">
                <span>{{ $template->campaigns_count ?? 0 }} campaigns</span>
                <span>{{ $template->created_at->format('M d, Y') }}</span>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('campaigns.templates.show', $template) }}" 
                   class="flex-1 px-3 py-2 bg-neutral-100 dark:bg-neutral-700 text-neutral-900 dark:text-white rounded-lg hover:bg-neutral-200 dark:hover:bg-neutral-600 transition text-sm text-center">
                    View
                </a>
                <a href="{{ route('campaigns.templates.edit', $template) }}" 
                   class="flex-1 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm text-center">
                    Edit
                </a>
                <a href="{{ route('campaigns.templates.preview', $template) }}" 
                   class="px-3 py-2 bg-neutral-100 dark:bg-neutral-700 text-neutral-900 dark:text-white rounded-lg hover:bg-neutral-200 dark:hover:bg-neutral-600 transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </a>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Pagination -->
    @if($templates->hasPages())
    <div class="mt-8">
        {{ $templates->links() }}
    </div>
    @endif

    @else
    <!-- Empty State -->
    <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-neutral-900 dark:text-white">No email templates</h3>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Get started by creating your first template.</p>
        <div class="mt-6">
            <a href="{{ route('campaigns.templates.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Create Template
            </a>
        </div>
    </div>
    @endif
</div>
@endsection
