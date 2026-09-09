<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'DemandLead') }} - AI Demand Intelligence Platform</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
        }
        @keyframes float-delayed {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(-40px, 30px) scale(0.95); }
            66% { transform: translate(20px, -20px) scale(1.05); }
        }
        @keyframes gradient-shift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        .blob-1 {
            animation: float 20s ease-in-out infinite;
        }
        .blob-2 {
            animation: float-delayed 25s ease-in-out infinite;
        }
        .gradient-bg {
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #4facfe);
            background-size: 400% 400%;
            animation: gradient-shift 15s ease infinite;
        }
        .hover-lift {
            transition: all 0.3s ease;
        }
        .hover-lift:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body class="antialiased bg-white overflow-x-hidden">
    <!-- Navigation -->
    <nav class="bg-white/90 backdrop-blur-md border-b border-gray-200 fixed w-full z-50 top-0 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20">
                <div class="flex items-center">
                    <img src="{{ asset('images/logo/soarlogo.png') }}" alt="DemandLead" class="h-8 w-auto">
                    <span class="ml-2 text-xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">DemandLead</span>
                </div>
                <div class="flex items-center space-x-4">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-gray-700 hover:text-gray-900 font-medium transition">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-gray-700 hover:text-gray-900 font-medium transition">Log in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition transform hover:scale-105">
                                    Get Started
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section with Animated Background -->
    <section class="relative pt-32 pb-24 px-4 sm:px-6 lg:px-8 overflow-hidden">
        <!-- Animated Gradient Blobs -->
        <div class="absolute inset-0 -z-10">
            <div class="absolute top-0 -left-40 w-96 h-96 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 blob-1"></div>
            <div class="absolute top-40 -right-40 w-96 h-96 bg-blue-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 blob-2"></div>
            <div class="absolute -bottom-40 left-40 w-96 h-96 bg-pink-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 blob-1"></div>
        </div>

        <div class="max-w-7xl mx-auto text-center relative z-10">
            <!-- Badge -->
            <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-200 rounded-full mb-6">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                <span class="text-sm font-semibold text-gray-700">AI-Powered Demand Intelligence</span>
            </div>

            <!-- Headline -->
            <h1 class="text-6xl md:text-7xl font-extrabold text-gray-900 mb-6 leading-tight">
                Know What Your Market<br>
                <span class="bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                    Wants Before Competitors
                </span>
            </h1>
            
            <!-- Subheadline -->
            <p class="text-xl md:text-2xl text-gray-600 mb-10 max-w-4xl mx-auto leading-relaxed">
                Detect rising demand, score opportunities with AI, capture qualified leads automatically, and measure revenue from discovery to close.
            </p>

            <!-- CTA Buttons -->
            <div class="flex items-center justify-center gap-4 mb-12">
                @auth
                    <a href="{{ url('/dashboard') }}" class="px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-2xl hover:shadow-purple-500/50 transition transform hover:scale-105 text-lg">
                        Go to Dashboard ΓåÆ
                    </a>
                @else
                    <a href="{{ route('register') }}" class="px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-2xl hover:shadow-purple-500/50 transition transform hover:scale-105 text-lg">
                        Start Free Trial ΓåÆ
                    </a>
                    <a href="{{ route('login') }}" class="px-8 py-4 border-2 border-gray-300 hover:border-gray-400 bg-white text-gray-700 font-bold rounded-xl shadow-lg hover:shadow-xl transition transform hover:scale-105 text-lg">
                        Sign In
                    </a>
                @endauth
            </div>

            <!-- Trust Indicators -->
            <div class="flex items-center justify-center gap-8 text-sm text-gray-600">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="font-medium">No credit card required</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="font-medium">14-day free trial</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="font-medium">Cancel anytime</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Flow with Numbers -->
    <section class="py-24 bg-gradient-to-br from-gray-50 to-blue-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                    The Complete Intelligence Loop
                </h2>
                <p class="text-xl text-gray-600">From market signals to closed revenue in four steps</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Step 1 -->
                <div class="relative bg-white p-8 rounded-2xl shadow-lg hover-lift border-2 border-transparent hover:border-blue-200">
                    <div class="absolute -top-4 -left-4 w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-full flex items-center justify-center font-bold text-xl shadow-lg">
                        1
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-100 to-blue-200 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3 text-center">Detect Demand</h3>
                    <p class="text-gray-600 text-center">Monitor search trends and market signals across your keywords in real-time</p>
                </div>

                <!-- Step 2 -->
                <div class="relative bg-white p-8 rounded-2xl shadow-lg hover-lift border-2 border-transparent hover:border-green-200">
                    <div class="absolute -top-4 -left-4 w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 text-white rounded-full flex items-center justify-center font-bold text-xl shadow-lg">
                        2
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-green-100 to-green-200 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3 text-center">Score Opportunities</h3>
                    <p class="text-gray-600 text-center">AI ranks each opportunity 0-100 by growth, intent, volume, and relevance</p>
                </div>

                <!-- Step 3 -->
                <div class="relative bg-white p-8 rounded-2xl shadow-lg hover-lift border-2 border-transparent hover:border-purple-200">
                    <div class="absolute -top-4 -left-4 w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-full flex items-center justify-center font-bold text-xl shadow-lg">
                        3
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-100 to-purple-200 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3 text-center">Generate Leads</h3>
                    <p class="text-gray-600 text-center">Convert demand into qualified leads via landing pages and WhatsApp</p>
                </div>

                <!-- Step 4 -->
                <div class="relative bg-white p-8 rounded-2xl shadow-lg hover-lift border-2 border-transparent hover:border-orange-200">
                    <div class="absolute -top-4 -left-4 w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 text-white rounded-full flex items-center justify-center font-bold text-xl shadow-lg">
                        4
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-orange-100 to-orange-200 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <svg class="h-8 w-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3 text-center">Measure Revenue</h3>
                    <p class="text-gray-600 text-center">Track complete ROI from opportunity detection to closed deal</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Grid -->
    <section class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                    Powerful Features
                </h2>
                <p class="text-xl text-gray-600">Everything you need to turn market demand into revenue</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="bg-gradient-to-br from-blue-50 to-white p-8 rounded-2xl shadow-lg hover-lift border border-blue-100">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mb-6 shadow-lg">
                        <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Opportunity Engine</h3>
                    <p class="text-gray-600 leading-relaxed">Explainable 0-100 scores with AI-recommended actions for every opportunity.</p>
                </div>

                <div class="bg-gradient-to-br from-green-50 to-white p-8 rounded-2xl shadow-lg hover-lift border border-green-100">
                    <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center mb-6 shadow-lg">
                        <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Smart Alerts</h3>
                    <p class="text-gray-600 leading-relaxed">Get notified via SMS, email, or WhatsApp when high-value opportunities emerge.</p>
                </div>

                <div class="bg-gradient-to-br from-purple-50 to-white p-8 rounded-2xl shadow-lg hover-lift border border-purple-100">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mb-6 shadow-lg">
                        <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Landing Pages</h3>
                    <p class="text-gray-600 leading-relaxed">AI-generated landing pages tied directly to market opportunities.</p>
                </div>

                <div class="bg-gradient-to-br from-orange-50 to-white p-8 rounded-2xl shadow-lg hover-lift border border-orange-100">
                    <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center mb-6 shadow-lg">
                        <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">WhatsApp Capture</h3>
                    <p class="text-gray-600 leading-relaxed">Capture and qualify leads through WhatsApp with AI conversation.</p>
                </div>

                <div class="bg-gradient-to-br from-red-50 to-white p-8 rounded-2xl shadow-lg hover-lift border border-red-100">
                    <div class="w-14 h-14 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center mb-6 shadow-lg">
                        <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Intent Detection</h3>
                    <p class="text-gray-600 leading-relaxed">Prioritize commercial and transactional demand over informational queries.</p>
                </div>

                <div class="bg-gradient-to-br from-indigo-50 to-white p-8 rounded-2xl shadow-lg hover-lift border border-indigo-100">
                    <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl flex items-center justify-center mb-6 shadow-lg">
                        <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Built-in CRM</h3>
                    <p class="text-gray-600 leading-relaxed">Track leads, deals, tasks, and pipeline stages without external tools.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="relative py-24 overflow-hidden">
        <!-- Gradient Background -->
        <div class="absolute inset-0 gradient-bg"></div>
        
        <div class="relative max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8 z-10">
            <h2 class="text-4xl md:text-5xl font-extrabold text-white mb-6">
                Ready to Capture Market Demand?
            </h2>
            <p class="text-xl md:text-2xl text-white/90 mb-10">
                Start detecting opportunities and generating leads today
            </p>
            @auth
                <a href="{{ url('/dashboard') }}" class="inline-block px-10 py-4 bg-white hover:bg-gray-100 text-purple-600 font-bold rounded-xl shadow-2xl hover:shadow-white/50 transition transform hover:scale-105 text-lg">
                    Go to Dashboard ΓåÆ
                </a>
            @else
                <a href="{{ route('register') }}" class="inline-block px-10 py-4 bg-white hover:bg-gray-100 text-purple-600 font-bold rounded-xl shadow-2xl hover:shadow-white/50 transition transform hover:scale-105 text-lg">
                    Start Free Trial ΓåÆ
                </a>
            @endauth
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center">
                    <img src="{{ asset('images/logo/soarlogo.png') }}" alt="DemandLead" class="h-8 w-auto">
                    <span class="ml-2 text-lg font-bold text-white">DemandLead</span>
                </div>
                <p class="text-sm">┬⌐ {{ date('Y') }} DemandLead. AI Demand Intelligence Platform.</p>
            </div>
        </div>
    </footer>
</body>
</html>
