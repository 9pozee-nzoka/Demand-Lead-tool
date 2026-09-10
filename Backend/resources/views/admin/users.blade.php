@extends('layouts.app')

@section('title', 'Users Management')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">All Users</h1>
            <p class="mt-2 text-gray-600">Manage all users across all organizations</p>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Search users..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <select name="role" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">All Roles</option>
                        <option value="owner" {{ request('role') === 'owner' ? 'selected' : '' }}>Owner</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="analyst" {{ request('role') === 'analyst' ? 'selected' : '' }}>Analyst</option>
                        <option value="marketing" {{ request('role') === 'marketing' ? 'selected' : '' }}>Marketing</option>
                        <option value="sales" {{ request('role') === 'sales' ? 'selected' : '' }}>Sales</option>
                        <option value="viewer" {{ request('role') === 'viewer' ? 'selected' : '' }}>Viewer</option>
                    </select>
                </div>
                <div>
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div>
                    <select name="admin_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">All Types</option>
                        <option value="super_admin" {{ request('admin_type') === 'super_admin' ? 'selected' : '' }}>Super Admins</option>
                        <option value="org_admin" {{ request('admin_type') === 'org_admin' ? 'selected' : '' }}>Org Admins</option>
                    </select>
                </div>
                <div class="flex space-x-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                        Filter
                    </button>
                    <a href="{{ route('admin.users') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">User</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Organization</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Joined</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($users as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center text-white font-semibold">
                                            {{ substr($user->name, 0, 1) }}
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-semibold text-gray-900">
                                            {{ $user->name }}
                                            @if($user->is_super_admin)
                                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                    Super Admin
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ $user->organization->name ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full
                                    @if($user->role === 'owner') bg-purple-100 text-purple-800
                                    @elseif($user->role === 'admin') bg-blue-100 text-blue-800
                                    @elseif($user->role === 'analyst') bg-green-100 text-green-800
                                    @elseif($user->role === 'marketing') bg-yellow-100 text-yellow-800
                                    @elseif($user->role === 'sales') bg-orange-100 text-orange-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full
                                    {{ $user->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ ucfirst($user->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $user->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center space-x-2">
                                    @if(!$user->is_super_admin && $user->id !== Auth::id())
                                        <form action="{{ route('admin.impersonate', $user) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                    class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition group relative" 
                                                    title="Impersonate User">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 bg-gray-900 text-white text-xs rounded py-1 px-2 mb-2 opacity-0 group-hover:opacity-100 transition whitespace-nowrap">
                                                    Impersonate
                                                </span>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-gray-400 text-xs italic">N/A</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                    <p class="mt-4 text-sm font-medium">No users found</p>
                                    <p class="mt-1 text-xs text-gray-400">Try adjusting your filters</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($users->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                {{ $users->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
