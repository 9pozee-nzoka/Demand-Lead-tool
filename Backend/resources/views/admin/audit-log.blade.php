@extends('components.admin-layout')

@section('content')
<div class="container-fluid px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2">📋 Audit Log</h1>
                <p class="text-red-200">System-wide activity and security audit trail</p>
            </div>
            <button onclick="exportLogs()" 
                    class="px-4 py-2 bg-white text-red-600 rounded-lg font-medium hover:bg-red-50 transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export Logs
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('admin.audit-log') }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}"
                       placeholder="Search by user, organization, action..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
            </div>
            
            <select name="action_type" 
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                <option value="">All Actions</option>
                <option value="auth" {{ request('action_type') === 'auth' ? 'selected' : '' }}>Authentication</option>
                <option value="admin" {{ request('action_type') === 'admin' ? 'selected' : '' }}>Admin Actions</option>
                <option value="organization" {{ request('action_type') === 'organization' ? 'selected' : '' }}>Organizations</option>
                <option value="user" {{ request('action_type') === 'user' ? 'selected' : '' }}>User Management</option>
                <option value="data" {{ request('action_type') === 'data' ? 'selected' : '' }}>Data Changes</option>
                <option value="security" {{ request('action_type') === 'security' ? 'selected' : '' }}>Security Events</option>
            </select>

            <input type="date" 
                   name="date_from" 
                   value="{{ request('date_from') }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">

            <input type="date" 
                   name="date_to" 
                   value="{{ request('date_to') }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">

            <button type="submit" 
                    class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium">
                Filter
            </button>

            @if(request()->hasAny(['search', 'action_type', 'date_from', 'date_to']))
            <a href="{{ route('admin.audit-log') }}" 
               class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                Clear
            </a>
            @endif
        </form>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4">
            <div class="text-center">
                <div class="text-3xl font-bold text-blue-700 mb-1">{{ $logs->total() }}</div>
                <div class="text-sm text-blue-600 font-medium">Total Events</div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4">
            <div class="text-center">
                <div class="text-3xl font-bold text-green-700 mb-1">{{ $logs->where('action', 'like', 'auth.%')->count() }}</div>
                <div class="text-sm text-green-600 font-medium">Auth Events</div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4">
            <div class="text-center">
                <div class="text-3xl font-bold text-purple-700 mb-1">{{ $logs->where('action', 'like', 'admin.%')->count() }}</div>
                <div class="text-sm text-purple-600 font-medium">Admin Actions</div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-4">
            <div class="text-center">
                <div class="text-3xl font-bold text-orange-700 mb-1">{{ $logs->where('action', 'like', 'security.%')->count() }}</div>
                <div class="text-sm text-orange-600 font-medium">Security Events</div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-4">
            <div class="text-center">
                <div class="text-3xl font-bold text-red-700 mb-1">{{ $logs->where('action', 'like', '%.failed')->count() }}</div>
                <div class="text-sm text-red-600 font-medium">Failed Actions</div>
            </div>
        </div>
    </div>

    <!-- Audit Log Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Timestamp
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            User
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Organization
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Action
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Details
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            IP Address
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $log->created_at->format('M d, Y') }}</div>
                            <div class="text-xs text-gray-500">{{ $log->created_at->format('H:i:s') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($log->user)
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8">
                                    <div class="h-8 w-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold">
                                        {{ strtoupper(substr($log->user->name, 0, 2)) }}
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">{{ $log->user->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $log->user->email }}</div>
                                </div>
                            </div>
                            @else
                            <span class="text-sm text-gray-400">System</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($log->organization)
                            <div class="text-sm text-gray-900">{{ $log->organization->name }}</div>
                            @else
                            <span class="text-sm text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $actionColors = [
                                    'auth' => 'bg-blue-100 text-blue-800',
                                    'admin' => 'bg-purple-100 text-purple-800',
                                    'organization' => 'bg-green-100 text-green-800',
                                    'user' => 'bg-yellow-100 text-yellow-800',
                                    'security' => 'bg-red-100 text-red-800',
                                    'data' => 'bg-gray-100 text-gray-800',
                                ];
                                $prefix = explode('.', $log->action)[0];
                                $color = $actionColors[$prefix] ?? 'bg-gray-100 text-gray-800';
                                
                                $icons = [
                                    'auth.login' => '🔐',
                                    'auth.logout' => '🚪',
                                    'auth.failed' => '⛔',
                                    'admin.impersonate' => '👤',
                                    'organization.created' => '🏢',
                                    'organization.updated' => '✏️',
                                    'organization.suspended' => '⏸️',
                                    'user.created' => '👤',
                                    'user.updated' => '✏️',
                                    'user.deleted' => '🗑️',
                                    'security.2fa.enabled' => '🔐',
                                    'security.2fa.disabled' => '🔓',
                                    'data.export' => '📤',
                                ];
                                $icon = $icons[$log->action] ?? '📝';
                            @endphp
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $color }}">
                                {{ $icon }} {{ $log->action }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 max-w-xs truncate">
                                {{ $log->description ?? '—' }}
                            </div>
                            @if($log->metadata)
                            <button onclick="showDetails({{ $log->id }})" class="text-xs text-indigo-600 hover:text-indigo-900">
                                View metadata
                            </button>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $log->ip_address ?? '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <div class="text-4xl mb-2">📋</div>
                            <p>No audit log entries found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $logs->links() }}
        </div>
    </div>
</div>

<script>
function showDetails(logId) {
    alert('Metadata details modal - to be implemented');
}

function exportLogs() {
    window.location.href = '/super-admin/audit-log/export';
}
</script>
@endsection
