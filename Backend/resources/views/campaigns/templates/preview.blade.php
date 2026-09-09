@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-neutral-900 dark:text-white">Template Preview</h1>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">{{ $template->name }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('campaigns.templates.show', $template) }}" 
                   class="px-4 py-2 bg-neutral-100 dark:bg-neutral-700 text-neutral-900 dark:text-white rounded-lg hover:bg-neutral-200 dark:hover:bg-neutral-600 transition">
                    Back
                </a>
                <a href="{{ route('campaigns.templates.edit', $template) }}" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Edit Template
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Email Preview -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 overflow-hidden">
                <!-- Email Header -->
                <div class="bg-neutral-50 dark:bg-neutral-900 border-b border-neutral-200 dark:border-neutral-700 px-6 py-4">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold">
                            C
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <p class="text-sm font-semibold text-neutral-900 dark:text-white">Company Name</p>
                                <span class="text-xs text-neutral-500 dark:text-neutral-400">&lt;noreply@company.com&gt;</span>
                            </div>
                            <p class="text-sm text-neutral-600 dark:text-neutral-400">
                                to: lead@example.com
                            </p>
                        </div>
                    </div>
                    
                    <!-- Subject Line -->
                    <div class="mt-4 pt-4 border-t border-neutral-200 dark:border-neutral-700">
                        <p class="text-lg font-semibold text-neutral-900 dark:text-white">
                            {{ $preview }}
                        </p>
                    </div>
                </div>

                <!-- Email Body -->
                <div class="p-6">
                    <div class="prose prose-neutral dark:prose-invert max-w-none">
                        {!! $preview !!}
                    </div>
                    
                    <!-- Footer -->
                    <div class="mt-8 pt-6 border-t border-neutral-200 dark:border-neutral-700">
                        <p class="text-xs text-neutral-500 dark:text-neutral-400 text-center">
                            This is a preview with sample data. Actual emails will use real lead information.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Info Sidebar -->
        <div class="space-y-6">
            <!-- Sample Data Used -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <h3 class="text-sm font-semibold text-neutral-900 dark:text-white mb-4">Sample Data Used</h3>
                <div class="space-y-3 text-sm">
                    <div>
                        <p class="text-neutral-500 dark:text-neutral-400 text-xs">Company Name</p>
                        <p class="text-neutral-900 dark:text-white font-mono">DemandLead Inc.</p>
                    </div>
                    <div>
                        <p class="text-neutral-500 dark:text-neutral-400 text-xs">Lead Name</p>
                        <p class="text-neutral-900 dark:text-white font-mono">John Doe</p>
                    </div>
                    <div>
                        <p class="text-neutral-500 dark:text-neutral-400 text-xs">Lead Email</p>
                        <p class="text-neutral-900 dark:text-white font-mono">john@example.com</p>
                    </div>
                    <div>
                        <p class="text-neutral-500 dark:text-neutral-400 text-xs">Opportunity</p>
                        <p class="text-neutral-900 dark:text-white font-mono">Premium Package</p>
                    </div>
                    <div>
                        <p class="text-neutral-500 dark:text-neutral-400 text-xs">Current Date</p>
                        <p class="text-neutral-900 dark:text-white font-mono">{{ now()->format('F j, Y') }}</p>
                    </div>
                </div>
            </div>

            <!-- Template Stats -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <h3 class="text-sm font-semibold text-neutral-900 dark:text-white mb-4">Template Details</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-neutral-500 dark:text-neutral-400">Category</span>
                        <span class="text-neutral-900 dark:text-white font-medium">
                            {{ $template->category ? ucfirst(str_replace('_', ' ', $template->category)) : 'N/A' }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-500 dark:text-neutral-400">Status</span>
                        <span class="text-neutral-900 dark:text-white font-medium">
                            @if($template->is_active)
                                <span class="text-emerald-600">Active</span>
                            @else
                                <span class="text-neutral-400">Inactive</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-500 dark:text-neutral-400">Created</span>
                        <span class="text-neutral-900 dark:text-white font-medium">
                            {{ $template->created_at->format('M d, Y') }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-500 dark:text-neutral-400">Last Updated</span>
                        <span class="text-neutral-900 dark:text-white font-medium">
                            {{ $template->updated_at->diffForHumans() }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <h3 class="text-sm font-semibold text-neutral-900 dark:text-white mb-4">Quick Actions</h3>
                <div class="space-y-2">
                    <a href="{{ route('campaigns.create', ['template' => $template->id]) }}" 
                       class="block w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm text-center">
                        Use This Template
                    </a>
                    <a href="{{ route('campaigns.templates.edit', $template) }}" 
                       class="block w-full px-4 py-2 bg-neutral-100 dark:bg-neutral-700 text-neutral-900 dark:text-white rounded-lg hover:bg-neutral-200 dark:hover:bg-neutral-600 transition text-sm text-center">
                        Edit Template
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
