@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-semibold text-neutral-900 dark:text-white">Task Calendar</h1>
        <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">View all tasks on a calendar</p>
    </div>

    <!-- Calendar View (Simple implementation - can be enhanced with FullCalendar.js) -->
    <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">
                {{ now()->format('F Y') }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('tasks.index') }}" 
                   class="px-4 py-2 bg-neutral-100 dark:bg-neutral-700 text-neutral-900 dark:text-white rounded-lg hover:bg-neutral-200 dark:hover:bg-neutral-600 transition text-sm">
                    List View
                </a>
                <a href="{{ route('tasks.timeline') }}" 
                   class="px-4 py-2 bg-neutral-100 dark:bg-neutral-700 text-neutral-900 dark:text-white rounded-lg hover:bg-neutral-200 dark:hover:bg-neutral-600 transition text-sm">
                    Timeline View
                </a>
            </div>
        </div>

        <!-- Calendar Events List -->
        <div class="space-y-4">
            @forelse($events as $event)
            <div class="p-4 bg-neutral-50 dark:bg-neutral-900 rounded-lg border-l-4 
                        {{ $event['priority'] === 'high' ? 'border-rose-500' : ($event['priority'] === 'medium' ? 'border-amber-500' : 'border-blue-500') }}">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-medium text-neutral-900 dark:text-white">{{ $event['title'] }}</h3>
                            @php
                                $statusColors = [
                                    'pending' => 'bg-amber-100 text-amber-900 dark:bg-amber-900 dark:text-amber-200',
                                    'in_progress' => 'bg-blue-100 text-blue-900 dark:bg-blue-900 dark:text-blue-200',
                                    'completed' => 'bg-emerald-100 text-emerald-900 dark:bg-emerald-900 dark:text-emerald-200',
                                ];
                            @endphp
                            <span class="px-2 py-0.5 rounded text-xs font-medium {{ $statusColors[$event['status']] ?? 'bg-neutral-100' }}">
                                {{ ucfirst(str_replace('_', ' ', $event['status'])) }}
                            </span>
                        </div>
                        <p class="text-sm text-neutral-600 dark:text-neutral-400">
                            {{ \Carbon\Carbon::parse($event['start'])->format('M d, Y g:i A') }}
                        </p>
                        @if(isset($event['description']) && $event['description'])
                        <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">{{ $event['description'] }}</p>
                        @endif
                    </div>
                    @if(isset($event['task_id']))
                    <a href="{{ route('tasks.show', $event['task_id']) }}" 
                       class="text-blue-600 hover:text-blue-700 text-sm font-medium ml-4">
                        View →
                    </a>
                    @endif
                </div>
            </div>
            @empty
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-neutral-900 dark:text-white">No tasks scheduled</h3>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Get started by creating a new task.</p>
                <div class="mt-6">
                    <a href="{{ route('tasks.create') }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Create Task
                    </a>
                </div>
            </div>
            @endforelse
        </div>
    </div>

    @if(count($events) > 0)
    <div class="mt-6 text-sm text-neutral-500 dark:text-neutral-400 text-center">
        <p>Showing {{ count($events) }} upcoming tasks. 
           <a href="{{ route('tasks.index') }}" class="text-blue-600 hover:underline">View all tasks</a>
        </p>
    </div>
    @endif
</div>
@endsection
