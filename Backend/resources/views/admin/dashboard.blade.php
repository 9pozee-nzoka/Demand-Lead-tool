<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white">
                    🛡️ Super Admin Dashboard
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    System-wide administration and monitoring
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                    ← Back to App
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- System Health -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">System Health</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Database -->
                    <div class="p-4 rounded-lg {{ $health['database']['status'] === 'healthy' ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Database</p>
                                <p class="mt-1 text-xs {{ $health['database']['status'] === 'healthy' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $health['database']['message'] }}
                                </p>
                            </div>
                            <span class="text-2xl">
                                @if($health['database']['status'] === 'healthy')
                                    ✅
                                @else
                                    ❌
                                @endif
                            </span>
                        </div>
                    </div>

                    <!-- Queue -->
                    <div class="p-4 rounded-lg {{ $health['queue']['status'] === 'healthy' ? 'bg-green-50 dark:bg-green-900/20' : 'bg-yellow-50 dark:bg-yellow-900/20' }}">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Queue</p>
                                <p class="mt-1 text-xs {{ $health['queue']['status'] === 'healthy' ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }}">
                                    {{ $health['queue']['message'] }}
                                </p>
                            </div>
                            <span class="text-2xl">
                                @if($health['queue']['status'] === 'healthy')
                                    ✅
                                @else
                                    ⚠️
                                @endif
                            </span>
                        </div>
                    </div>

                    <!-- Failed Jobs -->
                    <div class="p-4 rounded-lg {{ $health['failed_jobs'] === 0 ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Failed Jobs</p>
                                <p class="mt-1 text-xs {{ $health['failed_jobs'] === 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $health['failed_jobs'] }} failed
                                </p>
                            </div>
                            <span class="text-2xl">
                                @if($health['failed_jobs'] === 0)
                                    ✅
                                @else
                                    ❌
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Metrics Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Organizations -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Organizations</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                                {{ number_format($metrics['totals']['organizations']) }}
                            </p>
                            <p class="mt-1 text-xs text-green-600 dark:text-green-400">
                                +{{ number_format($metrics['this_month']['new_orgs']) }} this month
                            </p>
                        </div>
                        <div class="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
                            <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Users -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Users</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                                {{ number_format($metrics['totals']['users']) }}
                            </p>
                            <p class="mt-1 text-xs text-green-600 dark:text-green-400">
                                +{{ number_format($metrics['this_month']['new_users']) }} this month
                            </p>
                        </div>
                        <div class="p-3 bg-purple-100 dark:bg-purple-900/20 rounded-lg">
                            <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Leads -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Leads</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                                {{ number_format($metrics['totals']['leads']) }}
                            </p>
                            <p class="mt-1 text-xs text-green-600 dark:text-green-400">
                                +{{ number_format($metrics['this_month']['new_leads']) }} this month
                            </p>
                        </div>
                        <div class="p-3 bg-green-100 dark:bg-green-900/20 rounded-lg">
                            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Revenue -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Revenue</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                                ${{ number_format($metrics['totals']['revenue'], 0) }}
                            </p>
                            <p class="mt-1 text-xs text-green-600 dark:text-green-400">
                                ${{ number_format($metrics['this_month']['revenue'], 0) }} this month
                            </p>
                        </div>
                        <div class="p-3 bg-emerald-100 dark:bg-emerald-900/20 rounded-lg">
                            <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Secondary Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Keywords Tracked</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($metrics['totals']['keywords']) }}
                    </p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Opportunities</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($metrics['totals']['opportunities']) }}
                    </p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Orgs</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($metrics['totals']['active_orgs']) }}
                    </p>
                </div>
            </div>

            <!-- Recent Organizations -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Signups</h3>
                    <a href="{{ route('admin.organizations') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                        View all →
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Organization</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Plan</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Owner</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Created</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($recentOrgs as $org)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $org->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $org->slug }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                                        {{ $org->plan->name ?? 'Free' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    {{ $org->users->first()->email ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $org->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' }}">
                                        {{ ucfirst($org->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    {{ $org->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    <a href="{{ route('admin.organizations.show', $org) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                        View
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No organizations found
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <a href="{{ route('admin.organizations') }}" class="block p-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Manage Organizations</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">View and manage all organizations on the platform</p>
                </a>

                <a href="{{ route('admin.users') }}" class="block p-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">User Management</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">View all users across all organizations</p>
                </a>

                <a href="{{ route('admin.audit-log') }}" class="block p-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Audit Log</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Review system-wide activity and admin actions</p>
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
