@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-semibold text-neutral-900 dark:text-white">Task Timeline</h1>
        <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
            View task history and activity
            @if($leadId || $dealId)
                for {{ $leadId ? 'lead' : 'deal' }}
            @endif
        </p>
    </div>

    <!-- Filters -->
    <div class="mb-6 flex gap-3">
        <a href="{{ route('tasks.index') }}" 
           class="px-4 py-2 bg-neutral-100 dark:bg-neutral-700 text-neutral-900 dark:text-white rounded-lg hover:bg-neutral-200 dark:hover:bg-neutral-600 transition text-sm">
            List View
        </a>
        <a href="{{ route('tasks.calendar') }}" 
           class="px-4 py-2 bg-neutral-100 dark:bg-neutral-700 text-neutral-900 dark:text-white rounded-lg hover:bg-neutral-200 dark:hover:bg-neutral-600 transition text-sm">
            Calendar View
        </a>
    </div>

    <!-- Timeline -->
    <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
        @forelse($tasks as $task)
        <div class="relative pb-8 {{ !$loop->last ? 'border-l-2 border-neutral-200 dark:border-neutral-700 ml-4' : 'ml-4' }}">
            <!-- Timeline dot -->
            <div class="absolute left-0 transform -translate-x-1/2 -translate-y-1">
                @php
                    $dotColors = [
                        'completed' => 'bg-emerald-500',
                        'in_progress' => 'bg-blue-500',
                        'pending' => 'bg-amber-500',
                        'cancelled' => 'bg-neutral-400',
                    ];
                @endphp
                <div class="h-3 w-3 rounded-full {{ $dotColors[$task->status] ?? 'bg-neutral-400' }} ring-4 ring-white dark:ring-neutral-800"></div>
            </div>

            <!-- Content -->
            <div class="ml-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-medium text-neutral-900 dark:text-white">{{ $task->title }}</h3>
                            @php
                                $statusColors = [
                                    'pending' => 'bg-amber-100 text-amber-900 dark:bg-amber-900 dark:text-amber-200',
                                    'in_progress' => 'bg-blue-100 text-blue-900 dark:bg-blue-900 dark:text-blue-200',
                                    'completed' => 'bg-emerald-100 text-emerald-900 dark:bg-emerald-900 dark:text-emerald-200',
                                    'cancelled' => 'bg-neutral-100 text-neutral-900 dark:bg-neutral-700 dark:text-neutral-200',
                                ];
                                $priorityColors = [
                                    'high' => 'bg-rose-100 text-rose-900 dark:bg-rose-900 dark:text-rose-200',
                                    'medium' => 'bg-amber-100 text-amber-900 dark:bg-amber-900 dark:text-amber-200',
                                    'low' => 'bg-neutral-100 text-neutral-900 dark:bg-neutral-700 dark:text-neutral-200',
                                ];
                            @endphp
                            <span class="px-2 py-0.5 rounded text-xs font-medium {{ $statusColors[$task->status] ?? 'bg-neutral-100' }}">
                                {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                            </span>
                            <span class="px-2 py-0.5 rounded text-xs font-medium {{ $priorityColors[$task->priority] ?? 'bg-neutral-100' }}">
                                {{ ucfirst($task->priority) }} Priority
                            </span>
                        </div>
                        
                        <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-1">
                            <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $task->type)) }}</span>
                            @if($task->due_date)
                                • Due {{ \Carbon\Carbon::parse($task->due_date)->format('M d, Y') }}
                            @endif
                            @if($task->assignedUser)
                                • Assigned to {{ $task->assignedUser->name }}
                            @endif
                        </p>

                        @if($task->description)
                        <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-2">{{ $task->description }}</p>
                        @endif

                        <p class="text-xs text-neutral-400 dark:text-neutral-500 mt-2">
                            Created {{ $task->created_at->diffForHumans() }}
                        </p>
                    </div>

                    <a href="{{ route('tasks.show', $task) }}" 
                       class="text-blue-600 hover:text-blue-700 text-sm font-medium ml-4">
                        View →
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-neutral-900 dark:text-white">No tasks found</h3>
            <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                @if($leadId || $dealId)
                    No tasks have been created for this {{ $leadId ? 'lead' : 'deal' }} yet.
                @else
                    Get started by creating a new task.
                @endif
            </p>
            <div class="mt-6">
                <a href="{{ route('tasks.create') }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Create Task
                </a>
            </div>
        </div>
        @endforelse
    </div>

    @if($tasks->count() > 0)
    <div class="mt-6 text-sm text-neutral-500 dark:text-neutral-400 text-center">
        <p>Showing {{ $tasks->count() }} tasks in chronological order.</p>
    </div>
    @endif
</div>
@endsection
