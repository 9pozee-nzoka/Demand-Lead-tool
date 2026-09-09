@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-amber-600 to-orange-600 bg-clip-text text-transparent">
                My Tasks
            </h1>
            <p class="text-gray-500 mt-1">Your personal task list</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('tasks.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                All Tasks
            </a>
            <a href="{{ route('tasks.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-sm font-semibold rounded-xl shadow-lg hover:from-amber-600 hover:to-orange-600 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Task
            </a>
        </div>
    </div>

    @php
        $sections = [
            'overdue'     => ['label' => 'Overdue',       'color' => 'red',    'empty' => false],
            'due_today'   => ['label' => 'Due Today',     'color' => 'amber',  'empty' => false],
            'upcoming'    => ['label' => 'Upcoming',      'color' => 'sky',    'empty' => false],
            'pending'     => ['label' => 'Pending',       'color' => 'gray',   'empty' => false],
        ];
        $borderColors = ['red' => 'border-red-200', 'amber' => 'border-amber-200', 'sky' => 'border-sky-200', 'gray' => 'border-gray-200'];
        $headerColors = ['red' => 'bg-red-50 text-red-700', 'amber' => 'bg-amber-50 text-amber-700', 'sky' => 'bg-sky-50 text-sky-700', 'gray' => 'bg-gray-50 text-gray-700'];
        $badgeColors  = ['red' => 'bg-red-100 text-red-700', 'amber' => 'bg-amber-100 text-amber-700', 'sky' => 'bg-sky-100 text-sky-700', 'gray' => 'bg-gray-100 text-gray-600'];
    @endphp

    <div class="space-y-6">
        @foreach($sections as $key => $section)
            @if(isset($tasksByStatus[$key]) && $tasksByStatus[$key]->count() > 0)
            @php $color = $section['color']; $tasks = $tasksByStatus[$key]; @endphp
            <div class="bg-white rounded-2xl shadow-sm border {{ $borderColors[$color] }} overflow-hidden">

                {{-- Section Header --}}
                <div class="px-6 py-4 border-b {{ $borderColors[$color] }} {{ $headerColors[$color] }} flex items-center justify-between">
                    <h3 class="text-sm font-bold uppercase tracking-wider">{{ $section['label'] }}</h3>
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $badgeColors[$color] }}">
                        {{ $tasks->count() }}
                    </span>
                </div>

                {{-- Task List --}}
                <ul class="divide-y divide-gray-50">
                    @foreach($tasks as $task)
                    <li class="px-6 py-4 hover:bg-gray-50 transition-colors group flex items-start gap-4">

                        {{-- Complete Button --}}
                        <button onclick="completeTask({{ $task->id }})"
                                class="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full border-2 border-gray-300 hover:border-emerald-500 transition flex items-center justify-center">
                        </button>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <a href="{{ route('tasks.show', $task) }}"
                                   class="font-semibold text-gray-900 hover:text-amber-600 transition-colors leading-snug">
                                    {{ $task->title }}
                                </a>
                                <a href="{{ route('tasks.edit', $task) }}"
                                   class="flex-shrink-0 opacity-0 group-hover:opacity-100 w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                            </div>
                            @if($task->description)
                                <p class="text-xs text-gray-400 mt-0.5">{{ Str::limit($task->description, 80) }}</p>
                            @endif
                            <div class="flex flex-wrap gap-3 mt-2 text-xs">
                                @if($task->due_at)
                                    <span class="{{ $key === 'overdue' ? 'text-red-500 font-semibold' : ($key === 'due_today' ? 'text-amber-600 font-semibold' : 'text-gray-400') }} flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        {{ $task->due_at->format('M d') }} · {{ $task->due_at->diffForHumans() }}
                                    </span>
                                @endif
                                @if($task->lead)
                                    <a href="{{ route('leads.show', $task->lead) }}"
                                       class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-sky-50 text-sky-600 hover:bg-sky-100 transition">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        {{ $task->lead->first_name ?? $task->lead->name }}
                                    </a>
                                @endif
                                @if($task->deal)
                                    <a href="{{ route('deals.show', $task->deal) }}"
                                       class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-violet-50 text-violet-600 hover:bg-violet-100 transition">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        {{ Str::limit($task->deal->title, 25) }}
                                    </a>
                                @endif
                                @if($task->type)
                                    <span class="text-gray-400">{{ ucfirst(str_replace('_', ' ', $task->type)) }}</span>
                                @endif
                            </div>
                        </div>

                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
        @endforeach

        {{-- All done state --}}
        @php
            $hasAny = false;
            foreach($sections as $key => $s) {
                if(isset($tasksByStatus[$key]) && $tasksByStatus[$key]->count() > 0) { $hasAny = true; break; }
            }
        @endphp
        @unless($hasAny)
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-emerald-100 to-teal-100 flex items-center justify-center mb-5">
                    <svg class="w-10 h-10 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">All caught up!</h3>
                <p class="text-gray-500 text-sm">No tasks assigned to you right now.</p>
            </div>
        @endunless
    </div>

</div>

<script>
async function completeTask(taskId) {
    await fetch(`/tasks/${taskId}/complete`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    });
    location.reload();
}
</script>
@endsection
