@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-neutral-900 dark:text-white">{{ $task->title }}</h1>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                    Created {{ $task->created_at->diffForHumans() }}
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('tasks.edit', $task) }}" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Edit Task
                </a>
                <form action="{{ route('tasks.destroy', $task) }}" method="POST" onsubmit="return confirm('Are you sure?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Task Details Card -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-4">Task Details</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Description</label>
                        <p class="mt-1 text-neutral-900 dark:text-white">
                            {{ $task->description ?: 'No description provided.' }}
                        </p>
                    </div>

                    @if($task->notes)
                    <div>
                        <label class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Notes</label>
                        <p class="mt-1 text-neutral-900 dark:text-white whitespace-pre-wrap">{{ $task->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Related Items -->
            @if($task->lead || $task->deal)
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-4">Related Items</h2>
                
                <div class="space-y-3">
                    @if($task->lead)
                    <div class="flex items-center justify-between p-3 bg-neutral-50 dark:bg-neutral-900 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-neutral-900 dark:text-white">Lead</p>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ $task->lead->name }}</p>
                        </div>
                        <a href="{{ route('leads.show', $task->lead) }}" 
                           class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                            View Lead →
                        </a>
                    </div>
                    @endif

                    @if($task->deal)
                    <div class="flex items-center justify-between p-3 bg-neutral-50 dark:bg-neutral-900 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-neutral-900 dark:text-white">Deal</p>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ $task->deal->title }}</p>
                        </div>
                        <a href="{{ route('deals.show', $task->deal) }}" 
                           class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                            View Deal →
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status Card -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <h3 class="text-sm font-semibold text-neutral-900 dark:text-white mb-4">Status</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="text-xs text-neutral-500 dark:text-neutral-400">Status</label>
                        <div class="mt-1">
                            @php
                                $statusColors = [
                                    'pending' => 'bg-amber-100 text-amber-900 dark:bg-amber-900 dark:text-amber-200',
                                    'in_progress' => 'bg-blue-100 text-blue-900 dark:bg-blue-900 dark:text-blue-200',
                                    'completed' => 'bg-emerald-100 text-emerald-900 dark:bg-emerald-900 dark:text-emerald-200',
                                    'cancelled' => 'bg-neutral-100 text-neutral-900 dark:bg-neutral-700 dark:text-neutral-200',
                                ];
                            @endphp
                            <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium {{ $statusColors[$task->status] ?? 'bg-neutral-100 text-neutral-900' }}">
                                {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs text-neutral-500 dark:text-neutral-400">Priority</label>
                        <div class="mt-1">
                            @php
                                $priorityColors = [
                                    'low' => 'bg-neutral-100 text-neutral-900 dark:bg-neutral-700 dark:text-neutral-200',
                                    'medium' => 'bg-amber-100 text-amber-900 dark:bg-amber-900 dark:text-amber-200',
                                    'high' => 'bg-rose-100 text-rose-900 dark:bg-rose-900 dark:text-rose-200',
                                ];
                            @endphp
                            <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium {{ $priorityColors[$task->priority] ?? 'bg-neutral-100 text-neutral-900' }}">
                                {{ ucfirst($task->priority) }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs text-neutral-500 dark:text-neutral-400">Type</label>
                        <p class="mt-1 text-sm text-neutral-900 dark:text-white">
                            {{ ucfirst(str_replace('_', ' ', $task->type)) }}
                        </p>
                    </div>

                    <div>
                        <label class="text-xs text-neutral-500 dark:text-neutral-400">Due Date</label>
                        <p class="mt-1 text-sm text-neutral-900 dark:text-white">
                            @if($task->due_date)
                                {{ \Carbon\Carbon::parse($task->due_date)->format('M d, Y') }}
                                @if(\Carbon\Carbon::parse($task->due_date)->isPast() && $task->status !== 'completed')
                                    <span class="text-rose-600 text-xs ml-1">(Overdue)</span>
                                @endif
                            @else
                                No due date
                            @endif
                        </p>
                    </div>

                    @if($task->assignedUser)
                    <div>
                        <label class="text-xs text-neutral-500 dark:text-neutral-400">Assigned To</label>
                        <p class="mt-1 text-sm text-neutral-900 dark:text-white">{{ $task->assignedUser->name }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Actions Card -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <h3 class="text-sm font-semibold text-neutral-900 dark:text-white mb-4">Quick Actions</h3>
                
                <div class="space-y-2">
                    @if($task->status !== 'completed')
                    <form action="{{ route('tasks.update', $task) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="completed">
                        <button type="submit" 
                                class="w-full px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm">
                            Mark as Complete
                        </button>
                    </form>
                    @endif

                    @if($task->status === 'pending')
                    <form action="{{ route('tasks.update', $task) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="in_progress">
                        <button type="submit" 
                                class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
                            Start Task
                        </button>
                    </form>
                    @endif

                    <a href="{{ route('tasks.index') }}" 
                       class="block w-full px-4 py-2 bg-neutral-100 dark:bg-neutral-700 text-neutral-900 dark:text-white rounded-lg hover:bg-neutral-200 dark:hover:bg-neutral-600 transition text-sm text-center">
                        Back to Tasks
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
