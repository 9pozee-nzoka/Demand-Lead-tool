@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">👥 Team</h1>
            <p class="text-gray-600 mt-1">Manage team members, roles, and permissions</p>
        </div>
        @if(auth()->user()->isAdmin())
        <button onclick="document.getElementById('inviteModal').classList.remove('hidden')" 
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Invite Member
        </button>
        @endif
    </div>

    <!-- Team Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-blue-600 font-medium">Total Members</p>
                    <p class="text-2xl font-bold text-blue-700">{{ $users->total() }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">👥</span>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-green-600 font-medium">Active</p>
                    <p class="text-2xl font-bold text-green-700">{{ $stats['active'] }}</p>
                </div>
                <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">✅</span>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-yellow-600 font-medium">Admins</p>
                    <p class="text-2xl font-bold text-yellow-700">{{ $stats['admins'] }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-500 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">👑</span>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-purple-600 font-medium">Invites Sent</p>
                    <p class="text-2xl font-bold text-purple-700">{{ $stats['pending'] }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">✉️</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('team.index') }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}"
                       placeholder="Search by name or email..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            
            <select name="role" 
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option value="">All Roles</option>
                <option value="owner" {{ request('role') === 'owner' ? 'selected' : '' }}>Owner</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="analyst" {{ request('role') === 'analyst' ? 'selected' : '' }}>Analyst</option>
                <option value="marketing" {{ request('role') === 'marketing' ? 'selected' : '' }}>Marketing</option>
                <option value="sales" {{ request('role') === 'sales' ? 'selected' : '' }}>Sales</option>
                <option value="viewer" {{ request('role') === 'viewer' ? 'selected' : '' }}>Viewer</option>
            </select>

            <select name="status" 
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option value="">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            </select>

            <button type="submit" 
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                Filter
            </button>

            @if(request()->hasAny(['search', 'role', 'status']))
            <a href="{{ route('team.index') }}" 
               class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                Clear
            </a>
            @endif
        </form>
    </div>

    <!-- Team Members Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Member
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Role
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Joined
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($users as $user)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900 flex items-center gap-2">
                                    {{ $user->name }}
                                    @if($user->id === auth()->id())
                                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">You</span>
                                    @endif
                                    @if($user->google2fa_enabled)
                                        <span class="text-xs" title="2FA Enabled">🔐</span>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                @if($user->phone)
                                <div class="text-xs text-gray-400">{{ $user->phone }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $roleColors = [
                                'owner' => 'bg-red-100 text-red-800',
                                'admin' => 'bg-purple-100 text-purple-800',
                                'analyst' => 'bg-blue-100 text-blue-800',
                                'marketing' => 'bg-green-100 text-green-800',
                                'sales' => 'bg-yellow-100 text-yellow-800',
                                'viewer' => 'bg-gray-100 text-gray-800',
                            ];
                            $roleIcons = [
                                'owner' => '👑',
                                'admin' => '⚡',
                                'analyst' => '📊',
                                'marketing' => '📢',
                                'sales' => '💼',
                                'viewer' => '👁️',
                            ];
                        @endphp
                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $roleColors[$user->role] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $roleIcons[$user->role] ?? '' }} {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($user->status === 'active')
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                ✓ Active
                            </span>
                        @elseif($user->status === 'pending')
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                ⏳ Pending
                            </span>
                        @else
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                ○ Inactive
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $user->created_at->format('M d, Y') }}
                        <div class="text-xs text-gray-400">{{ $user->created_at->diffForHumans() }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center gap-2">
                            @if(auth()->user()->isAdmin() && $user->id !== auth()->id())
                                <button onclick="editUser({{ $user->id }})" 
                                        class="text-indigo-600 hover:text-indigo-900" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                
                                @if($user->status === 'active')
                                <button onclick="toggleStatus({{ $user->id }}, 'inactive')" 
                                        class="text-yellow-600 hover:text-yellow-900" title="Deactivate">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                    </svg>
                                </button>
                                @else
                                <button onclick="toggleStatus({{ $user->id }}, 'active')" 
                                        class="text-green-600 hover:text-green-900" title="Activate">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </button>
                                @endif

                                @if($user->role !== 'owner')
                                <button onclick="confirmDelete({{ $user->id }}, '{{ $user->name }}')" 
                                        class="text-red-600 hover:text-red-900" title="Remove">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                        <div class="text-4xl mb-2">👥</div>
                        <p>No team members found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $users->links() }}
        </div>
    </div>
</div>

<!-- Invite Modal -->
<div id="inviteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-900">✉️ Invite Team Member</h3>
            <button onclick="document.getElementById('inviteModal').classList.add('hidden')" 
                    class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form action="{{ route('team.invite') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" required 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" required 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone (optional)</label>
                <input type="tel" name="phone" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <option value="viewer">👁️ Viewer - Read-only access</option>
                    <option value="analyst">📊 Analyst - View trends and insights</option>
                    <option value="marketing">📢 Marketing - Manage campaigns and pages</option>
                    <option value="sales" selected>💼 Sales - Manage leads and deals</option>
                    <option value="admin">⚡ Admin - Full access (except billing)</option>
                </select>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-800">
                <p class="font-medium mb-1">📧 Invitation will be sent</p>
                <p class="text-xs">An email with login instructions will be sent to this address.</p>
            </div>

            <div class="flex gap-3">
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                    Send Invite
                </button>
                <button type="button" 
                        onclick="document.getElementById('inviteModal').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(userId) {
    // TODO: Implement edit modal
    alert('Edit user functionality coming in next update');
}

function toggleStatus(userId, newStatus) {
    if(confirm(`Are you sure you want to ${newStatus === 'active' ? 'activate' : 'deactivate'} this user?`)) {
        fetch(`/team/${userId}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ status: newStatus })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                alert('Error updating status');
            }
        });
    }
}

function confirmDelete(userId, userName) {
    if(confirm(`Are you sure you want to remove ${userName} from your team? This action cannot be undone.`)) {
        fetch(`/team/${userId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                alert('Error removing user');
            }
        });
    }
}
</script>
@endsection
