<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - Super Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased">
    <div class="min-h-full">
        <!-- Super Admin Navigation -->
        <nav class="bg-gradient-to-r from-red-600 to-red-700 border-b border-red-800">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <span class="text-2xl font-bold text-white">🛡️</span>
                        </div>
                        <div class="ml-4">
                            <h1 class="text-lg font-bold text-white">Super Admin Panel</h1>
                            <p class="text-xs text-red-100">System Administration</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}" 
                           class="text-sm font-medium text-white hover:text-red-100 transition">
                            ← Back to App
                        </a>
                        
                        <div class="relative">
                            <button class="flex items-center gap-2 rounded-lg bg-red-700 px-3 py-2 text-sm font-medium text-white hover:bg-red-800 transition">
                                <span>{{ Auth::user()->name }}</span>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <div class="flex h-[calc(100vh-4rem)]">
            <!-- Super Admin Sidebar -->
            <aside class="w-64 bg-white border-r border-gray-200 overflow-y-auto">
                <nav class="p-4 space-y-1">
                    <!-- Dashboard -->
                    <a href="{{ route('admin.dashboard') }}" 
                       class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition {{ request()->routeIs('admin.dashboard') ? 'bg-red-50 text-red-700' : 'text-gray-700 hover:bg-gray-50' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Dashboard
                    </a>

                    <!-- Organizations -->
                    <a href="{{ route('admin.organizations') }}" 
                       class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition {{ request()->routeIs('admin.organizations*') ? 'bg-red-50 text-red-700' : 'text-gray-700 hover:bg-gray-50' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        Organizations
                    </a>

                    <!-- Users -->
                    <a href="{{ route('admin.users') }}" 
                       class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition {{ request()->routeIs('admin.users') ? 'bg-red-50 text-red-700' : 'text-gray-700 hover:bg-gray-50' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        All Users
                    </a>

                    <!-- Audit Log -->
                    <a href="{{ route('admin.audit-log') }}" 
                       class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition {{ request()->routeIs('admin.audit-log') ? 'bg-red-50 text-red-700' : 'text-gray-700 hover:bg-gray-50' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Audit Log
                    </a>

                    <div class="pt-4 mt-4 border-t border-gray-200">
                        <!-- Settings -->
                        <a href="{{ route('admin.settings') }}" 
                           class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition {{ request()->routeIs('admin.settings') ? 'bg-red-50 text-red-700' : 'text-gray-700 hover:bg-gray-50' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Settings
                        </a>
                    </div>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto bg-gray-50">
                <!-- Page Header -->
                @if (isset($header))
                    <header class="bg-white shadow-sm border-b border-gray-200">
                        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <!-- Page Content -->
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
</body>
</html>
