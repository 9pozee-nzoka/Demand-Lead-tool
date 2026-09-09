@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">

    {{-- Session Alerts --}}
    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 rounded-xl px-5 py-4">
            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-amber-600 to-orange-600 bg-clip-text text-transparent">
                Tasks
            </h1>
            <p class="text-gray-500 mt-1">Manage your team's tasks and follow-ups</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('tasks.my-tasks') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50 shadow-sm transition">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                My Tasks
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

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">Total</p>
                <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($stats['total']) }}</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-sky-500 to-blue-500 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">Pending</p>
                <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($stats['pending']) }}</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-rose-500 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">Overdue</p>
                <p class="text-2xl font-bold text-red-600 mt-0.5">{{ number_format($stats['overdue']) }}</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">Done Today</p>
                <p class="text-2xl font-bold text-emerald-600 mt-0.5">{{ number_format($stats['completed_today']) }}</p>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4 mb-5">
        <div class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider">View</label>
                <select onchange="window.location.href='?filter='+this.value"
                        class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-400">
                    <option value="all"    {{ $filter === 'all' ? 'selected' : '' }}>All Tasks</option>
                    <option value="my"     {{ $filter === 'my' ? 'selected' : '' }}>My Tasks</option>
                    <option value="overdue"{{ $filter === 'overdue' ? 'selected' : '' }}>Overdue</option>
                    <option value="today"  {{ $filter === 'today' ? 'selected' : '' }}>Due Today</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider">Status</label>
                <select onchange="applyFilter('status', this.value)"
                        class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-400">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <a href="{{ route('tasks.index') }}"
               class="px-4 py-2 text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 transition">
                Clear
            </a>
        </div>
    </div>

    {{-- Tasks Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

        @if($tasks->count() > 0)

            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">
                    Tasks
                    <span class="ml-2 px-2 py-0.5 bg-gray-100 text-gray-500 text-xs rounded-full font-normal normal-case">
                        {{ $tasks->total() }} total
                    </span>
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-3 text-left">Task</th>
                            <th class="px-4 py-3 text-left">Type</th>
                            <th class="px-4 py-3 text-left">Related To</th>
                            <th class="px-4 py-3 text-left">Assigned</th>
                            <th class="px-4 py-3 text-left">Due Date</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-right pr-6">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($tasks as $task)
                        @php
                            $isOverdue = $task->due_at && $task->due_at->isPast() && $task->status !== 'completed';
                            $isToday   = $task->due_at && $task->due_at->isToday();
                            $typeIcons = [
                                'call'      => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
                                'email'     => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                                'meeting'   => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                                'follow_up' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
                                'other'     => 'M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z',
                            ];
                            $statusConfig = [
                                'pending'     => ['bg-gray-100 text-gray-600',   'Pending'],
                                'in_progress' => ['bg-sky-100 text-sky-700',     'In Progress'],
                                'completed'   => ['bg-emerald-100 text-emerald-700','Done'],
                                'cancelled'   => ['bg-red-100 text-red-600',     'Cancelled'],
                            ];
                            [$statusStyle, $statusLabel] = $statusConfig[$task->status] ?? ['bg-gray-100 text-gray-600', ucfirst($task->status)];
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors group {{ $isOverdue ? 'bg-red-50/30' : '' }}">

                            {{-- Task Title --}}
                            <td class="px-6 py-4 max-w-xs">
                                <div class="flex items-start gap-3">
                                    {{-- Checkbox --}}
                                    <button onclick="toggleTaskStatus({{ $task->id }}, {{ $task->status !== 'completed' ? 'true' : 'false' }})"
                                            class="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full border-2 flex items-center justify-center transition
                                                   {{ $task->status === 'completed' ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-gray-300 hover:border-emerald-400' }}">
                                        @if($task->status === 'completed')
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        @endif
                                    </button>
                                    <div class="min-w-0">
                                        <a href="{{ route('tasks.show', $task) }}"
                                           class="font-semibold text-gray-900 hover:text-amber-600 transition-colors {{ $task->status === 'completed' ? 'line-through text-gray-400' : '' }}">
                                            {{ $task->title }}
                                        </a>
                                        @if($task->description)
                                            <p class="text-xs text-gray-400 mt-0.5 truncate">
                                                {{ Str::limit($task->description, 55) }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Type --}}
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-1.5 text-gray-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $typeIcons[$task->type] ?? $typeIcons['other'] }}"/>
                                    </svg>
                                    <span class="text-xs">{{ ucfirst(str_replace('_', ' ', $task->type ?? 'other')) }}</span>
                                </div>
                            </td>

                            {{-- Related To --}}
                            <td class="px-4 py-4">
                                <div class="flex flex-col gap-1">
                                    @if($task->lead)
                                        <a href="{{ route('leads.show', $task->lead) }}"
                                           class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-sky-50 text-sky-700 text-xs font-medium hover:bg-sky-100 transition w-fit">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            {{ Str::limit($task->lead->name ?? ($task->lead->first_name . ' ' . $task->lead->last_name), 20) }}
                                        </a>
                                    @endif
                                    @if($task->deal)
                                        <a href="{{ route('deals.show', $task->deal) }}"
                                           class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-violet-50 text-violet-700 text-xs font-medium hover:bg-violet-100 transition w-fit">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            {{ Str::limit($task->deal->title, 20) }}
                                        </a>
                                    @endif
                                    @if(!$task->lead && !$task->deal)
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Assignee --}}
                            <td class="px-4 py-4">
                                @if($task->assignedUser)
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-gradient-to-br from-violet-400 to-purple-400 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                            {{ strtoupper(substr($task->assignedUser->name, 0, 1)) }}
                                        </div>
                                        <span class="text-xs text-gray-700 truncate max-w-[90px]">{{ $task->assignedUser->name }}</span>
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs">Unassigned</span>
                                @endif
                            </td>

                            {{-- Due Date --}}
                            <td class="px-4 py-4">
                                @if($task->due_at)
                                    <div class="flex flex-col">
                                        <span class="text-xs font-semibold {{ $isOverdue ? 'text-red-600' : ($isToday ? 'text-amber-600' : 'text-gray-700') }}">
                                            {{ $task->due_at->format('M d, Y') }}
                                        </span>
                                        @if($isOverdue)
                                            <span class="text-xs text-red-500 mt-0.5">Overdue</span>
                                        @elseif($isToday)
                                            <span class="text-xs text-amber-500 mt-0.5">Due today</span>
                                        @else
                                            <span class="text-xs text-gray-400 mt-0.5">{{ $task->due_at->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs">No due date</span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-4 text-center">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusStyle }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-4 pr-6 text-right">
                                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    @if($task->status !== 'completed')
                                        <button onclick="completeTask({{ $task->id }})" title="Mark complete"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>
                                    @endif
                                    <a href="{{ route('tasks.edit', $task) }}" title="Edit"
                                       class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('tasks.destroy', $task) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Delete this task?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Delete"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>

                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($tasks->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                    {{ $tasks->links() }}
                </div>
            @endif

        @else

            <div class="flex flex-col items-center justify-center py-20 px-8 text-center">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-amber-100 to-orange-100 flex items-center justify-center mb-5">
                    <svg class="w-10 h-10 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No tasks found</h3>
                <p class="text-gray-500 text-sm max-w-sm mb-6">Stay on top of your pipeline by creating tasks for calls, follow-ups, and meetings.</p>
                <a href="{{ route('tasks.create') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-sm font-semibold rounded-xl shadow hover:from-amber-600 hover:to-orange-600 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Create Task
                </a>
            </div>

        @endif
    </div>

</div>

<script>
function applyFilter(param, value) {
    const url = new URL(window.location.href);
    value ? url.searchParams.set(param, value) : url.searchParams.delete(param);
    window.location.href = url.toString();
}

async function toggleTaskStatus(taskId, markComplete) {
    const status = markComplete ? 'completed' : 'pending';
    await fetch(`/tasks/${taskId}/status`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ status })
    });
    location.reload();
}

async function completeTask(taskId) {
    await fetch(`/tasks/${taskId}/complete`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    });
    location.reload();
}
</script>
@endsection
