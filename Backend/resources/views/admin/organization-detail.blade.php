@extends('components.admin-layout')

@section('content')
<div class="container-fluid px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center text-sm text-red-200 mb-4">
            <a href="{{ route('admin.organizations') }}" class="hover:text-white">Organizations</a>
            <svg class="h-4 w-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-white">{{ $organization->name }}</span>
        </div>
        
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2">{{ $organization->name }}</h1>
                <p class="text-red-200">Organization Details & Activity</p>
            </div>
            <div class="flex gap-2">
                <button class="px-4 py-2 bg-white text-red-600 rounded-lg font-medium hover:bg-red-50 transition-colors">
                    Edit Organization
                </button>
                @if($organization->status === 'active')
                <button class="px-4 py-2 bg-red-700 text-white rounded-lg font-medium hover:bg-red-800 transition-colors">
                    Suspend
                </button>
                @else
                <button class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition-colors">
                    Activate
                </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-gradient-to-br from-white to-gray-50 rounded-lg shadow-lg p-5">
            <div class="text-center">
                <div class="text-3xl font-bold text-gray-900 mb-1">{{ $organization->users->count() }}</div>
                <div class="text-sm text-gray-600 font-medium">Users</div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-white to-gray-50 rounded-lg shadow-lg p-5">
            <div class="text-center">
                <div class="text-3xl font-bold text-gray-900 mb-1">{{ $stats['keywords'] }}</div>
                <div class="text-sm text-gray-600 font-medium">Keywords</div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-white to-gray-50 rounded-lg shadow-lg p-5">
            <div class="text-center">
                <div class="text-3xl font-bold text-gray-900 mb-1">{{ $stats['opportunities'] }}</div>
                <div class="text-sm text-gray-600 font-medium">Opportunities</div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-white to-gray-50 rounded-lg shadow-lg p-5">
            <div class="text-center">
                <div class="text-3xl font-bold text-gray-900 mb-1">{{ $stats['leads'] }}</div>
                <div class="text-sm text-gray-600 font-medium">Leads</div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-white to-gray-50 rounded-lg shadow-lg p-5">
            <div class="text-center">
                <div class="text-3xl font-bold text-green-700 mb-1">${{ number_format($stats['revenue']) }}</div>
                <div class="text-sm text-gray-600 font-medium">Revenue</div>
            </div>
        </div>
    </div>

    <!-- Organization Info -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Organization Details</h2>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-600">Status</dt>
                    <dd>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $organization->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ ucfirst($organization->status) }}
                        </span>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-600">Plan</dt>
                    <dd class="text-sm text-gray-900 font-medium">{{ $organization->plan->name ?? 'No Plan' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-600">Industry</dt>
                    <dd class="text-sm text-gray-900">{{ $organization->industry ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-600">Country</dt>
                    <dd class="text-sm text-gray-900">{{ $organization->country ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-600">Timezone</dt>
                    <dd class="text-sm text-gray-900">{{ $organization->timezone ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-600">Created</dt>
                    <dd class="text-sm text-gray-900">{{ $organization->created_at->format('M d, Y') }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Team Members</h2>
            <div class="space-y-3">
                @foreach($organization->users->take(5) as $user)
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                            <div class="text-xs text-gray-500">{{ $user->email }}</div>
                        </div>
                    </div>
                    <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">
                        {{ ucfirst($user->role) }}
                    </span>
                </div>
                @endforeach
                @if($organization->users->count() > 5)
                <div class="pt-2 text-sm text-gray-500 text-center">
                    + {{ $organization->users->count() - 5 }} more
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Audit Log -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">Recent Activity</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($auditLog as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $log->created_at->diffForHumans() }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($log->user)
                            <div class="text-sm font-medium text-gray-900">{{ $log->user->name }}</div>
                            <div class="text-xs text-gray-500">{{ $log->user->email }}</div>
                            @else
                            <span class="text-sm text-gray-400">System</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            {{ $log->description ?? '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                            No activity recorded
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
