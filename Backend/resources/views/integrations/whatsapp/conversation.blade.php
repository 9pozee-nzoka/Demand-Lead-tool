@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-neutral-900 dark:text-white">WhatsApp Conversation</h1>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                    Conversation with {{ $lead->name }}
                </p>
            </div>
            <a href="{{ route('integrations.whatsapp.index') }}" 
               class="px-4 py-2 bg-neutral-100 dark:bg-neutral-700 text-neutral-900 dark:text-white rounded-lg hover:bg-neutral-200 dark:hover:bg-neutral-600 transition">
                Back to WhatsApp
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Conversation Area -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 flex flex-col h-[600px]">
                <!-- Chat Header -->
                <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-emerald-600 rounded-full flex items-center justify-center text-white font-semibold">
                            {{ strtoupper(substr($lead->name, 0, 1)) }}
                        </div>
                        <div>
                            <h3 class="font-semibold text-neutral-900 dark:text-white">{{ $lead->name }}</h3>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ $lead->phone }}</p>
                        </div>
                    </div>
                </div>

                <!-- Messages Area -->
                <div class="flex-1 overflow-y-auto p-6 space-y-4">
                    @forelse($lead->whatsappMessages ?? [] as $message)
                    <div class="flex {{ $message['direction'] === 'outbound' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[70%]">
                            <div class="px-4 py-3 rounded-lg {{ $message['direction'] === 'outbound' 
                                ? 'bg-blue-600 text-white' 
                                : 'bg-neutral-100 dark:bg-neutral-700 text-neutral-900 dark:text-white' }}">
                                <p class="text-sm">{{ $message['content'] }}</p>
                            </div>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 {{ $message['direction'] === 'outbound' ? 'text-right' : '' }}">
                                {{ \Carbon\Carbon::parse($message['created_at'])->format('M d, g:i A') }}
                                @if($message['direction'] === 'outbound')
                                    @if($message['status'] === 'delivered')
                                        <span class="ml-1">✓✓</span>
                                    @elseif($message['status'] === 'sent')
                                        <span class="ml-1">✓</span>
                                    @elseif($message['status'] === 'failed')
                                        <span class="ml-1 text-rose-600">✗</span>
                                    @endif
                                @endif
                            </p>
                        </div>
                    </div>
                    @empty
                    <div class="flex items-center justify-center h-full">
                        <div class="text-center">
                            <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-neutral-900 dark:text-white">No messages yet</h3>
                            <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Start a conversation below</p>
                        </div>
                    </div>
                    @endforelse
                </div>

                <!-- Message Input -->
                <div class="px-6 py-4 border-t border-neutral-200 dark:border-neutral-700">
                    <form action="{{ route('integrations.whatsapp.send', $lead) }}" method="POST" class="flex gap-2">
                        @csrf
                        <input type="text" 
                               name="message" 
                               placeholder="Type a message..." 
                               required
                               class="flex-1 px-4 py-2 bg-white dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-neutral-900 dark:text-white">
                        <button type="submit" 
                                class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition">
                            Send
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Lead Info Sidebar -->
        <div class="space-y-6">
            <!-- Lead Details -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <h3 class="text-sm font-semibold text-neutral-900 dark:text-white mb-4">Lead Information</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="text-xs text-neutral-500 dark:text-neutral-400">Name</label>
                        <p class="mt-1 text-sm text-neutral-900 dark:text-white font-medium">{{ $lead->name }}</p>
                    </div>

                    <div>
                        <label class="text-xs text-neutral-500 dark:text-neutral-400">Phone</label>
                        <p class="mt-1 text-sm text-neutral-900 dark:text-white font-medium">{{ $lead->phone }}</p>
                    </div>

                    @if($lead->email)
                    <div>
                        <label class="text-xs text-neutral-500 dark:text-neutral-400">Email</label>
                        <p class="mt-1 text-sm text-neutral-900 dark:text-white">{{ $lead->email }}</p>
                    </div>
                    @endif

                    <div>
                        <label class="text-xs text-neutral-500 dark:text-neutral-400">Status</label>
                        <div class="mt-1">
                            @php
                                $statusColors = [
                                    'new' => 'bg-blue-100 text-blue-900 dark:bg-blue-900 dark:text-blue-200',
                                    'contacted' => 'bg-amber-100 text-amber-900 dark:bg-amber-900 dark:text-amber-200',
                                    'qualified' => 'bg-emerald-100 text-emerald-900 dark:bg-emerald-900 dark:text-emerald-200',
                                    'unqualified' => 'bg-neutral-100 text-neutral-900 dark:bg-neutral-700 dark:text-neutral-200',
                                ];
                            @endphp
                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$lead->status] ?? 'bg-neutral-100' }}">
                                {{ ucfirst($lead->status) }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs text-neutral-500 dark:text-neutral-400">Lead Score</label>
                        <div class="mt-1 flex items-center gap-2">
                            <div class="flex-1 h-2 bg-neutral-200 dark:bg-neutral-700 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-600 rounded-full" style="width: {{ $lead->score ?? 0 }}%"></div>
                            </div>
                            <span class="text-sm font-medium text-neutral-900 dark:text-white">{{ $lead->score ?? 0 }}</span>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs text-neutral-500 dark:text-neutral-400">Source</label>
                        <p class="mt-1 text-sm text-neutral-900 dark:text-white">
                            {{ $lead->source ? ucfirst(str_replace('_', ' ', $lead->source)) : 'WhatsApp' }}
                        </p>
                    </div>

                    <div>
                        <label class="text-xs text-neutral-500 dark:text-neutral-400">Created</label>
                        <p class="mt-1 text-sm text-neutral-900 dark:text-white">
                            {{ $lead->created_at->format('M d, Y') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <h3 class="text-sm font-semibold text-neutral-900 dark:text-white mb-4">Quick Actions</h3>
                
                <div class="space-y-2">
                    <a href="{{ route('leads.show', $lead) }}" 
                       class="block w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm text-center">
                        View Full Lead Profile
                    </a>
                    
                    @if($lead->status !== 'qualified')
                    <form action="{{ route('leads.qualify', $lead) }}" method="POST">
                        @csrf
                        <button type="submit" 
                                class="w-full px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm">
                            Qualify Lead
                        </button>
                    </form>
                    @endif

                    <form action="{{ route('leads.assign', $lead) }}" method="POST">
                        @csrf
                        <button type="submit" 
                                class="w-full px-4 py-2 bg-neutral-100 dark:bg-neutral-700 text-neutral-900 dark:text-white rounded-lg hover:bg-neutral-200 dark:hover:bg-neutral-600 transition text-sm">
                            Assign to Team Member
                        </button>
                    </form>
                </div>
            </div>

            <!-- Conversation Stats -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <h3 class="text-sm font-semibold text-neutral-900 dark:text-white mb-4">Conversation Stats</h3>
                
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-neutral-500 dark:text-neutral-400">Total Messages</span>
                        <span class="text-neutral-900 dark:text-white font-medium">
                            {{ count($lead->whatsappMessages ?? []) }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-500 dark:text-neutral-400">Response Time</span>
                        <span class="text-neutral-900 dark:text-white font-medium">~2 min</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-500 dark:text-neutral-400">Last Activity</span>
                        <span class="text-neutral-900 dark:text-white font-medium">
                            {{ $lead->updated_at->diffForHumans() }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
