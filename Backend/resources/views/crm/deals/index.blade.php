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
    @if(session('error'))
        <div class="mb-6 flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 rounded-xl px-5 py-4">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <span class="text-sm font-medium">{{ session('error') }}</span>
        </div>
    @endif

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-violet-600 to-indigo-600 bg-clip-text text-transparent">
                Sales Pipeline
            </h1>
            <p class="text-gray-500 mt-1">Drag deals between stages to move them through your pipeline</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('deals.analytics') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50 shadow-sm transition">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Analytics
            </a>
            <a href="{{ route('deals.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-violet-600 to-indigo-600 text-white text-sm font-semibold rounded-xl shadow-lg hover:from-violet-700 hover:to-indigo-700 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Deal
            </a>
        </div>
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-indigo-500 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">Active Deals</p>
                <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($stats['total_deals']) }}</p>
                <p class="text-xs text-indigo-600 mt-0.5 font-medium">In pipeline</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-sky-500 to-blue-500 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">Pipeline Value</p>
                <p class="text-2xl font-bold text-gray-900 mt-0.5">${{ number_format($stats['total_value']) }}</p>
                <p class="text-xs text-sky-600 mt-0.5 font-medium">Total value</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">Won This Month</p>
                <p class="text-2xl font-bold text-gray-900 mt-0.5">${{ number_format($stats['won_this_month']) }}</p>
                <p class="text-xs text-emerald-600 mt-0.5 font-medium">Closed deals</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">Close Rate</p>
                <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ $stats['close_rate'] }}%</p>
                <p class="text-xs text-amber-600 mt-0.5 font-medium">Win rate</p>
            </div>
        </div>

    </div>

    {{-- Kanban Board --}}
    @php
        $stageConfig = [
            'new'         => ['label' => 'New',          'dot' => 'bg-indigo-500',  'header' => 'bg-indigo-50 border-indigo-200',  'badge' => 'bg-indigo-100 text-indigo-700',  'icon_color' => 'text-indigo-400'],
            'contacted'   => ['label' => 'Contacted',    'dot' => 'bg-sky-500',     'header' => 'bg-sky-50 border-sky-200',        'badge' => 'bg-sky-100 text-sky-700',        'icon_color' => 'text-sky-400'],
            'qualified'   => ['label' => 'Qualified',    'dot' => 'bg-blue-500',    'header' => 'bg-blue-50 border-blue-200',      'badge' => 'bg-blue-100 text-blue-700',      'icon_color' => 'text-blue-400'],
            'quotation'   => ['label' => 'Quotation',    'dot' => 'bg-amber-500',   'header' => 'bg-amber-50 border-amber-200',    'badge' => 'bg-amber-100 text-amber-700',    'icon_color' => 'text-amber-400'],
            'negotiation' => ['label' => 'Negotiation',  'dot' => 'bg-purple-500',  'header' => 'bg-purple-50 border-purple-200',  'badge' => 'bg-purple-100 text-purple-700',  'icon_color' => 'text-purple-400'],
            'won'         => ['label' => 'Won 🎉',       'dot' => 'bg-emerald-500', 'header' => 'bg-emerald-50 border-emerald-200','badge' => 'bg-emerald-100 text-emerald-700','icon_color' => 'text-emerald-400'],
            'lost'        => ['label' => 'Lost',         'dot' => 'bg-red-400',     'header' => 'bg-red-50 border-red-200',        'badge' => 'bg-red-100 text-red-700',        'icon_color' => 'text-red-400'],
        ];
        $priorityColors = [
            'low'    => 'bg-gray-100 text-gray-600',
            'medium' => 'bg-amber-100 text-amber-700',
            'high'   => 'bg-orange-100 text-orange-700',
            'urgent' => 'bg-red-100 text-red-700',
        ];
    @endphp

    <div class="overflow-x-auto pb-4">
        <div class="flex gap-4" style="min-width: max-content;">

            @foreach($stages as $stage)
            @php
                $cfg   = $stageConfig[$stage];
                $deals = $dealsByStage[$stage];
                $stageValue = $deals->sum('value');
            @endphp

            <div class="w-64 flex-shrink-0 flex flex-col" id="column-{{ $stage }}">

                {{-- Column Header --}}
                <div class="rounded-xl border {{ $cfg['header'] }} px-4 py-3 mb-3 flex-shrink-0">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full {{ $cfg['dot'] }}"></span>
                            <span class="text-sm font-semibold text-gray-800">{{ $cfg['label'] }}</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $cfg['badge'] }}">
                            {{ $deals->count() }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 pl-4.5">
                        ${{ number_format($stageValue) }} total
                    </p>
                </div>

                {{-- Deal Cards --}}
                <div class="flex flex-col gap-3 kanban-column min-h-48" data-stage="{{ $stage }}">

                    @forelse($deals as $deal)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow cursor-grab group deal-card"
                         data-deal-id="{{ $deal->id }}"
                         draggable="true">

                        {{-- Deal Title + Menu --}}
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <a href="{{ route('deals.show', $deal) }}"
                               class="text-sm font-semibold text-gray-900 hover:text-indigo-600 leading-snug line-clamp-2 transition-colors">
                                {{ $deal->title }}
                            </a>
                            {{-- Dropdown Menu --}}
                            <div class="relative flex-shrink-0" x-data="{ open: false }">
                                <button @click="open = !open" @click.away="open = false"
                                        class="w-6 h-6 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition opacity-0 group-hover:opacity-100">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z"/>
                                    </svg>
                                </button>
                                <div x-show="open" x-cloak
                                     class="absolute right-0 top-7 w-40 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-30 text-sm">
                                    <a href="{{ route('deals.show', $deal) }}"
                                       class="flex items-center gap-2 px-3 py-2 text-gray-700 hover:bg-gray-50">
                                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        View Details
                                    </a>
                                    <a href="{{ route('deals.edit', $deal) }}"
                                       class="flex items-center gap-2 px-3 py-2 text-gray-700 hover:bg-gray-50">
                                        <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Edit
                                    </a>
                                    <div class="border-t border-gray-100 my-1"></div>
                                    <form action="{{ route('deals.mark-won', $deal) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button class="w-full flex items-center gap-2 px-3 py-2 text-emerald-700 hover:bg-emerald-50 text-left">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Mark Won
                                        </button>
                                    </form>
                                    <form action="{{ route('deals.mark-lost', $deal) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button class="w-full flex items-center gap-2 px-3 py-2 text-red-600 hover:bg-red-50 text-left">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Mark Lost
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- Value --}}
                        <div class="flex items-center gap-1.5 mb-3">
                            <span class="text-lg font-bold text-gray-900">${{ number_format($deal->value ?? 0) }}</span>
                            @if($deal->priority)
                                <span class="ml-auto px-2 py-0.5 rounded-full text-xs font-semibold {{ $priorityColors[$deal->priority] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ ucfirst($deal->priority) }}
                                </span>
                            @endif
                        </div>

                        {{-- Contact / Lead --}}
                        @if($deal->contact)
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-6 h-6 rounded-full bg-gradient-to-br from-indigo-400 to-purple-400 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                    {{ strtoupper(substr($deal->contact->first_name ?? '?', 0, 1)) }}
                                </div>
                                <span class="text-xs text-gray-600 truncate">{{ $deal->contact->first_name }} {{ $deal->contact->last_name }}</span>
                            </div>
                        @elseif($deal->lead)
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-6 h-6 rounded-full bg-gradient-to-br from-sky-400 to-blue-400 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                    {{ strtoupper(substr($deal->lead->first_name ?? '?', 0, 1)) }}
                                </div>
                                <span class="text-xs text-gray-600 truncate">{{ $deal->lead->first_name }} {{ $deal->lead->last_name }}</span>
                            </div>
                        @endif

                        {{-- Footer: Close date + Assignee --}}
                        <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-50">
                            @if($deal->expected_close_at)
                                @php
                                    $isOverdue = $deal->expected_close_at->isPast() && !in_array($stage, ['closed_won','closed_lost']);
                                @endphp
                                <span class="text-xs {{ $isOverdue ? 'text-red-500 font-semibold' : 'text-gray-400' }} flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    {{ $deal->expected_close_at->format('M d') }}
                                    @if($isOverdue) <span class="text-red-400">overdue</span> @endif
                                </span>
                            @else
                                <span></span>
                            @endif

                            @if($deal->assignedUser)
                                <div class="w-6 h-6 rounded-full bg-gradient-to-br from-violet-400 to-purple-400 flex items-center justify-center text-white text-xs font-bold"
                                     title="{{ $deal->assignedUser->name }}">
                                    {{ strtoupper(substr($deal->assignedUser->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>

                    </div>
                    @empty
                    <div class="flex flex-col items-center justify-center py-8 px-4 rounded-xl border-2 border-dashed border-gray-200 text-center">
                        <svg class="w-8 h-8 {{ $cfg['icon_color'] }} mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        <p class="text-xs text-gray-400">No deals</p>
                    </div>
                    @endforelse

                </div>
            </div>

            @endforeach
        </div>
    </div>

</div>

{{-- Drag-and-drop JS --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    let dragging = null;

    document.querySelectorAll('.deal-card').forEach(card => {
        card.addEventListener('dragstart', e => {
            dragging = card;
            card.classList.add('opacity-50', 'scale-95');
        });
        card.addEventListener('dragend', e => {
            card.classList.remove('opacity-50', 'scale-95');
            dragging = null;
        });
    });

    document.querySelectorAll('.kanban-column').forEach(col => {
        col.addEventListener('dragover', e => {
            e.preventDefault();
            col.classList.add('bg-indigo-50', 'rounded-xl');
        });
        col.addEventListener('dragleave', e => {
            col.classList.remove('bg-indigo-50', 'rounded-xl');
        });
        col.addEventListener('drop', e => {
            e.preventDefault();
            col.classList.remove('bg-indigo-50', 'rounded-xl');
            if (dragging && dragging.dataset.dealId) {
                const newStage = col.dataset.stage;
                col.insertBefore(dragging, col.querySelector('.empty-state'));

                // Send AJAX to update stage
                fetch(`/deals/${dragging.dataset.dealId}/update-stage`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ stage: newStage })
                });
            }
        });
    });
});
</script>
@endsection
