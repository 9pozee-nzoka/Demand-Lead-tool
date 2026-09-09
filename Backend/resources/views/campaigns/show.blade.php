@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-start mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $campaign->name }}</h1>
            <p class="text-gray-600 mt-1">Created {{ $campaign->created_at->diffForHumans() }} by {{ $campaign->creator->name }}</p>
        </div>
        <div class="flex gap-3">
            @if($campaign->isEditable())
                <a href="{{ route('campaigns.edit', $campaign) }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                    <i class="fas fa-edit mr-2"></i>Edit
                </a>
            @endif
            
            @if($campaign->status === 'draft' && $campaign->total_recipients > 0)
                <form action="{{ route('campaigns.send', $campaign) }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="send_now" value="1">
                    <button type="submit" class="px-6 py-2 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800">
                        <i class="fas fa-paper-plane mr-2"></i>Send Now
                    </button>
                </form>
            @endif

            @if($campaign->status === 'scheduled')
                <form action="{{ route('campaigns.cancel', $campaign) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Cancel
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Status Badge -->
    @php
        $statusColors = [
            'draft' => 'bg-gray-100 text-gray-800',
            'scheduled' => 'bg-yellow-100 text-yellow-800',
            'sending' => 'bg-blue-100 text-blue-800',
            'sent' => 'bg-green-100 text-green-800',
            'paused' => 'bg-orange-100 text-orange-800',
            'cancelled' => 'bg-red-100 text-red-800',
        ];
    @endphp
    <div class="mb-6">
        <span class="px-4 py-2 inline-flex text-sm leading-5 font-semibold rounded-full {{ $statusColors[$campaign->status] ?? 'bg-gray-100 text-gray-800' }}">
            {{ ucfirst($campaign->status) }}
        </span>
    </div>

    <!-- Stats Grid -->
    @if($campaign->status === 'sent')
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-gray-500 text-sm">Delivered</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($campaign->delivered_count) }}</p>
            <p class="text-xs text-gray-500 mt-1">of {{ number_format($campaign->total_recipients) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-gray-500 text-sm">Open Rate</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ $campaign->open_rate }}%</p>
            <p class="text-xs text-gray-500 mt-1">{{ number_format($campaign->opened_count) }} opens</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-gray-500 text-sm">Click Rate</p>
            <p class="text-2xl font-bold text-purple-600 mt-1">{{ $campaign->click_rate }}%</p>
            <p class="text-xs text-gray-500 mt-1">{{ number_format($campaign->clicked_count) }} clicks</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-gray-500 text-sm">Bounce Rate</p>
            <p class="text-2xl font-bold text-orange-600 mt-1">{{ $campaign->bounce_rate }}%</p>
            <p class="text-xs text-gray-500 mt-1">{{ number_format($campaign->bounced_count) }} bounces</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-gray-500 text-sm">Unsubscribes</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ $campaign->unsubscribe_rate }}%</p>
            <p class="text-xs text-gray-500 mt-1">{{ number_format($campaign->unsubscribed_count) }} total</p>
        </div>
    </div>
    @endif

    <!-- Campaign Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2 space-y-6">
            <!-- Email Content -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-4">Email Content</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-500">Subject Line</p>
                        <p class="font-medium">{{ $campaign->subject }}</p>
                    </div>
                    @if($campaign->preview_text)
                    <div>
                        <p class="text-sm text-gray-500">Preview Text</p>
                        <p class="text-gray-700">{{ $campaign->preview_text }}</p>
                    </div>
                    @endif
                    <div>
                        <p class="text-sm text-gray-500 mb-2">HTML Preview</p>
                        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50 max-h-96 overflow-auto">
                            {!! $campaign->html_content !!}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recipients -->
            @if($campaign->total_recipients > 0)
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-4">Recipients ({{ number_format($campaign->total_recipients) }})</h3>
                
                @if(!empty($recipientStats))
                <div class="mb-4 flex flex-wrap gap-2">
                    @foreach($recipientStats as $status => $count)
                        <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">
                            {{ ucfirst($status) }}: {{ $count }}
                        </span>
                    @endforeach
                </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Activity</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($campaign->recipients->take(10) as $recipient)
                            <tr>
                                <td class="px-4 py-3 text-sm">{{ $recipient->email }}</td>
                                <td class="px-4 py-3 text-sm">{{ $recipient->full_name ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100">{{ ucfirst($recipient->status) }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    @if($recipient->opened_at)
                                        <div>Opened {{ $recipient->open_count }}x</div>
                                    @endif
                                    @if($recipient->clicked_at)
                                        <div>Clicked {{ $recipient->click_count }}x</div>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($campaign->total_recipients > 10)
                    <p class="text-sm text-gray-500 mt-4 text-center">Showing 10 of {{ number_format($campaign->total_recipients) }} recipients</p>
                @endif
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Campaign Info -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-4">Campaign Info</h3>
                <div class="space-y-3 text-sm">
                    @if($campaign->scheduled_at)
                    <div>
                        <p class="text-gray-500">Scheduled For</p>
                        <p class="font-medium">{{ $campaign->scheduled_at->format('M d, Y g:i A') }}</p>
                    </div>
                    @endif
                    @if($campaign->started_at)
                    <div>
                        <p class="text-gray-500">Started At</p>
                        <p class="font-medium">{{ $campaign->started_at->format('M d, Y g:i A') }}</p>
                    </div>
                    @endif
                    @if($campaign->completed_at)
                    <div>
                        <p class="text-gray-500">Completed At</p>
                        <p class="font-medium">{{ $campaign->completed_at->format('M d, Y g:i A') }}</p>
                    </div>
                    @endif
                    <div>
                        <p class="text-gray-500">Audience Type</p>
                        <p class="font-medium">{{ ucwords(str_replace('_', ' ', $campaign->audience_type)) }}</p>
                    </div>
                    @if($campaign->template)
                    <div>
                        <p class="text-gray-500">Template</p>
                        <a href="{{ route('campaigns.templates.show', $campaign->template) }}" class="font-medium text-blue-600 hover:underline">
                            {{ $campaign->template->name }}
                        </a>
                    </div>
                    @endif
                    @if($campaign->opportunity)
                    <div>
                        <p class="text-gray-500">Opportunity</p>
                        <a href="{{ route('opportunities.show', $campaign->opportunity) }}" class="font-medium text-blue-600 hover:underline">
                            {{ $campaign->opportunity->title }}
                        </a>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Sender Info -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-4">Sender Info</h3>
                <div class="space-y-3 text-sm">
                    @if($campaign->from_name)
                    <div>
                        <p class="text-gray-500">From Name</p>
                        <p class="font-medium">{{ $campaign->from_name }}</p>
                    </div>
                    @endif
                    @if($campaign->from_email)
                    <div>
                        <p class="text-gray-500">From Email</p>
                        <p class="font-medium">{{ $campaign->from_email }}</p>
                    </div>
                    @endif
                    @if($campaign->reply_to)
                    <div>
                        <p class="text-gray-500">Reply To</p>
                        <p class="font-medium">{{ $campaign->reply_to }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Tracking -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-4">Tracking</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Open Tracking</span>
                        <span class="font-medium">{{ $campaign->track_opens ? '✓ Enabled' : '✗ Disabled' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Click Tracking</span>
                        <span class="font-medium">{{ $campaign->track_clicks ? '✓ Enabled' : '✗ Disabled' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
