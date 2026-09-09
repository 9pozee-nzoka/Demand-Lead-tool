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
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('deals.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900">{{ $deal->title }}</h1>
            </div>
            <div class="flex items-center gap-2 pl-8">
                @php
                    $statusStyles = ['won' => 'bg-emerald-100 text-emerald-700', 'lost' => 'bg-red-100 text-red-700', 'open' => 'bg-violet-100 text-violet-700'];
                @endphp
                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $statusStyles[$deal->status] ?? 'bg-gray-100 text-gray-700' }}">
                    {{ ucfirst($deal->status) }}
                </span>
                <span class="text-gray-400 text-sm">
                    {{ ucwords(str_replace('_', ' ', $deal->stage)) }}
                </span>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('deals.edit', $deal) }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Main Column --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Deal Info Card --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-violet-50 to-indigo-50">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Deal Information</h3>
                </div>
                <div class="p-6">
                    {{-- Value Hero --}}
                    <div class="mb-6 text-center bg-gradient-to-br from-violet-50 to-indigo-50 rounded-xl py-5">
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Deal Value</p>
                        <p class="text-4xl font-bold text-violet-600">${{ number_format($deal->value ?? 0, 2) }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $deal->currency ?? 'USD' }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        @if($deal->lead)
                        <div>
                            <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Lead</p>
                            <a href="{{ route('leads.show', $deal->lead) }}"
                               class="font-medium text-violet-600 hover:underline">
                                {{ $deal->lead->first_name ?? '' }} {{ $deal->lead->last_name ?? $deal->lead->name }}
                            </a>
                        </div>
                        @endif
                        @if($deal->contact)
                        <div>
                            <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Contact</p>
                            <p class="font-medium text-gray-800">{{ $deal->contact->first_name }} {{ $deal->contact->last_name }}</p>
                        </div>
                        @endif
                        @if($deal->assignedUser)
                        <div>
                            <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Assigned To</p>
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-violet-400 to-purple-400 flex items-center justify-center text-white text-xs font-bold">
                                    {{ strtoupper(substr($deal->assignedUser->name, 0, 1)) }}
                                </div>
                                <span class="font-medium text-gray-800">{{ $deal->assignedUser->name }}</span>
                            </div>
                        </div>
                        @endif
                        @if($deal->expected_close_at)
                        <div>
                            <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Expected Close</p>
                            @php $isPast = $deal->expected_close_at->isPast() && $deal->status === 'open'; @endphp
                            <p class="font-medium {{ $isPast ? 'text-red-600' : 'text-gray-800' }}">
                                {{ $deal->expected_close_at->format('M d, Y') }}
                                @if($isPast) <span class="text-xs text-red-400">(overdue)</span> @endif
                            </p>
                        </div>
                        @endif
                    </div>

                    @if($deal->lost_reason)
                        <div class="mt-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                            <p class="text-xs text-red-600 font-semibold uppercase tracking-wider mb-1">Lost Reason</p>
                            <p class="text-sm text-red-700">{{ $deal->lost_reason }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Notes --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">
                        Notes
                        <span class="ml-2 px-2 py-0.5 bg-gray-100 text-gray-500 text-xs rounded-full font-normal normal-case">
                            {{ $deal->notes->count() }}
                        </span>
                    </h3>
                </div>
                <div class="divide-y divide-gray-50">
                    @forelse($deal->notes as $note)
                        <div class="px-6 py-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-sky-400 to-blue-400 flex items-center justify-center text-white text-xs font-bold">
                                        {{ strtoupper(substr($note->user->name ?? '?', 0, 1)) }}
                                    </div>
                                    <span class="text-sm font-medium text-gray-800">{{ $note->user->name ?? 'Unknown' }}</span>
                                </div>
                                <span class="text-xs text-gray-400">{{ $note->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-sm text-gray-600 pl-9">{{ $note->content }}</p>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-gray-400 text-sm">No notes yet</div>
                    @endforelse
                </div>
            </div>

            {{-- Tasks --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">
                        Tasks
                        <span class="ml-2 px-2 py-0.5 bg-gray-100 text-gray-500 text-xs rounded-full font-normal normal-case">
                            {{ $deal->tasks->count() }}
                        </span>
                    </h3>
                    <a href="{{ route('tasks.create') }}?deal_id={{ $deal->id }}"
                       class="text-xs text-violet-600 hover:underline font-medium">+ Add Task</a>
                </div>
                <div class="divide-y divide-gray-50">
                    @forelse($deal->tasks as $task)
                        <div class="px-6 py-4 flex items-center gap-3">
                            <div class="w-5 h-5 rounded-full flex-shrink-0 flex items-center justify-center
                                        {{ $task->status === 'completed' ? 'bg-emerald-500 text-white' : 'border-2 border-gray-300' }}">
                                @if($task->status === 'completed')
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="text-sm font-medium {{ $task->status === 'completed' ? 'line-through text-gray-400' : 'text-gray-800' }}">
                                    {{ $task->title }}
                                </span>
                                @if($task->due_at)
                                    <span class="ml-2 text-xs text-gray-400">{{ $task->due_at->format('M d') }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-gray-400 text-sm">No tasks yet</div>
                    @endforelse
                </div>
            </div>

        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">

            {{-- Quick Actions --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Quick Actions</h3>
                <div class="flex flex-col gap-3">
                    @if($deal->status === 'open')
                        <form action="{{ route('deals.mark-won', $deal) }}" method="POST">
                            @csrf @method('PATCH')
                            <button class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-500 text-white text-sm font-semibold rounded-xl hover:from-emerald-600 hover:to-teal-600 transition shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Mark as Won
                            </button>
                        </form>
                        <button onclick="document.getElementById('lostModal').classList.remove('hidden')"
                                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-red-50 text-red-600 border border-red-200 text-sm font-semibold rounded-xl hover:bg-red-100 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Mark as Lost
                        </button>
                    @endif
                    <a href="{{ route('deals.edit', $deal) }}"
                       class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit Deal
                    </a>
                    <form action="{{ route('deals.destroy', $deal) }}" method="POST"
                          onsubmit="return confirm('Delete this deal permanently?');">
                        @csrf @method('DELETE')
                        <button class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-red-600 text-sm font-medium rounded-xl hover:bg-red-50 hover:border-red-200 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Delete Deal
                        </button>
                    </form>
                </div>
            </div>

            {{-- Timeline --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Timeline</h3>
                <ol class="relative border-l border-gray-200 space-y-4 ml-2">
                    @if($deal->won_at)
                        <li class="ml-4">
                            <div class="absolute w-3 h-3 bg-emerald-500 rounded-full -left-1.5 mt-1"></div>
                            <p class="text-sm font-semibold text-emerald-700">Won</p>
                            <p class="text-xs text-gray-400">{{ $deal->won_at->format('M d, Y H:i') }}</p>
                        </li>
                    @endif
                    @if($deal->lost_at)
                        <li class="ml-4">
                            <div class="absolute w-3 h-3 bg-red-500 rounded-full -left-1.5 mt-1"></div>
                            <p class="text-sm font-semibold text-red-700">Lost</p>
                            <p class="text-xs text-gray-400">{{ $deal->lost_at->format('M d, Y H:i') }}</p>
                        </li>
                    @endif
                    <li class="ml-4">
                        <div class="absolute w-3 h-3 bg-sky-400 rounded-full -left-1.5 mt-1"></div>
                        <p class="text-sm font-medium text-gray-700">Last Updated</p>
                        <p class="text-xs text-gray-400">{{ $deal->updated_at->format('M d, Y H:i') }}</p>
                    </li>
                    <li class="ml-4">
                        <div class="absolute w-3 h-3 bg-gray-300 rounded-full -left-1.5 mt-1"></div>
                        <p class="text-sm font-medium text-gray-700">Created</p>
                        <p class="text-xs text-gray-400">{{ $deal->created_at->format('M d, Y H:i') }}</p>
                    </li>
                </ol>
            </div>

        </div>
    </div>
</div>

{{-- Mark as Lost Modal --}}
<div id="lostModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="document.getElementById('lostModal').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-1">Mark deal as lost</h3>
        <p class="text-sm text-gray-500 mb-5">Help your team learn by recording the reason.</p>
        <form action="{{ route('deals.mark-lost', $deal) }}" method="POST">
            @csrf @method('PATCH')
            <textarea name="lost_reason" rows="4" required
                      class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-400 resize-none mb-4"
                      placeholder="e.g. Budget constraints, chose a competitor..."></textarea>
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('lostModal').classList.add('hidden')"
                        class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-200 transition">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-2.5 bg-gradient-to-r from-red-500 to-rose-500 text-white text-sm font-semibold rounded-xl hover:from-red-600 hover:to-rose-600 transition">
                    Mark as Lost
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
