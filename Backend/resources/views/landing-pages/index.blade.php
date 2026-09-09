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

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                Landing Pages
            </h1>
            <p class="text-gray-500 mt-1">AI-generated pages optimised for conversions</p>
        </div>
        <a href="{{ route('landing-pages.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-semibold rounded-xl shadow-lg hover:from-indigo-700 hover:to-purple-700 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Generate New Page
        </a>
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">

        {{-- Total Pages --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">Total Pages</p>
                <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($stats['total_pages']) }}</p>
            </div>
        </div>

        {{-- Published --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">Published</p>
                <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($stats['published_pages']) }}</p>
            </div>
        </div>

        {{-- Total Views --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-sky-500 to-blue-500 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">Total Views</p>
                <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($stats['total_views']) }}</p>
            </div>
        </div>

        {{-- Conversions --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">Conversions</p>
                <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($stats['total_conversions']) }}</p>
            </div>
        </div>

    </div>

    {{-- Pages Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

        @if($pages->count() > 0)

            {{-- Table Header --}}
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">
                    All Pages
                    <span class="ml-2 px-2 py-0.5 bg-gray-100 text-gray-500 text-xs rounded-full font-normal normal-case">
                        {{ $pages->total() }} total
                    </span>
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-3 text-left">Page</th>
                            <th class="px-4 py-3 text-left">Keyword</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">SEO</th>
                            <th class="px-4 py-3 text-right">Views</th>
                            <th class="px-4 py-3 text-right">Leads</th>
                            <th class="px-4 py-3 text-right">Conv. Rate</th>
                            <th class="px-4 py-3 text-right pr-6">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($pages as $page)
                        @php
                            $rate = $page->views > 0 ? round(($page->conversions / $page->views) * 100, 1) : 0;
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors group">

                            {{-- Page Title --}}
                            <td class="px-6 py-4 max-w-xs">
                                <div class="flex items-start gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-900 truncate">{{ $page->title }}</p>
                                        <p class="text-xs text-gray-400 truncate mt-0.5 font-mono">
                                            /lp/{{ $page->slug }}
                                        </p>
                                        @if($page->published_at)
                                            <p class="text-xs text-gray-400 mt-0.5">
                                                Published {{ $page->published_at->diffForHumans() }}
                                            </p>
                                        @else
                                            <p class="text-xs text-gray-400 mt-0.5">
                                                Created {{ $page->created_at->diffForHumans() }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Keyword --}}
                            <td class="px-4 py-4">
                                @if($page->keyword)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-medium">
                                        {{ $page->keyword->keyword ?? '—' }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-4 text-center">
                                @if($page->status === 'published')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Published
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 text-xs font-semibold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                        Draft
                                    </span>
                                @endif
                            </td>

                            {{-- SEO Score --}}
                            <td class="px-4 py-4 text-center">
                                @php
                                    $score = $page->seo_score ?? 0;
                                    $seoColor = $score >= 80 ? 'text-emerald-600 bg-emerald-50' : ($score >= 60 ? 'text-amber-600 bg-amber-50' : 'text-red-600 bg-red-50');
                                @endphp
                                <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold {{ $seoColor }}">
                                    {{ $score }}/100
                                </span>
                            </td>

                            {{-- Views --}}
                            <td class="px-4 py-4 text-right">
                                <span class="font-semibold text-gray-800">{{ number_format($page->views) }}</span>
                            </td>

                            {{-- Leads --}}
                            <td class="px-4 py-4 text-right">
                                <span class="font-semibold text-gray-800">{{ number_format($page->leads_count) }}</span>
                            </td>

                            {{-- Conversion Rate --}}
                            <td class="px-4 py-4 text-right">
                                @if($rate >= 5)
                                    <span class="font-bold text-emerald-600">{{ $rate }}%</span>
                                @elseif($rate >= 2)
                                    <span class="font-bold text-amber-600">{{ $rate }}%</span>
                                @else
                                    <span class="font-bold text-gray-500">{{ $rate }}%</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-4 pr-6 text-right">
                                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">

                                    {{-- View / Preview --}}
                                    @if($page->status === 'published')
                                        <a href="{{ url('/lp/' . $page->slug) }}" target="_blank"
                                           title="View live page"
                                           class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                            </svg>
                                        </a>
                                    @else
                                        <a href="{{ route('landing-pages.preview', $page) }}" target="_blank"
                                           title="Preview"
                                           class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-sky-600 hover:bg-sky-50 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                    @endif

                                    {{-- Edit --}}
                                    <a href="{{ route('landing-pages.edit', $page) }}" title="Edit"
                                       class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>

                                    {{-- Analytics --}}
                                    <a href="{{ route('landing-pages.analytics', $page) }}" title="Analytics"
                                       class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-sky-600 hover:bg-sky-50 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                        </svg>
                                    </a>

                                    {{-- Publish / Unpublish toggle --}}
                                    @if($page->status === 'published')
                                        <form action="{{ route('landing-pages.unpublish', $page) }}" method="POST" class="inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" title="Unpublish"
                                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('landing-pages.publish', $page) }}" method="POST" class="inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" title="Publish"
                                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Delete --}}
                                    <form action="{{ route('landing-pages.destroy', $page) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Delete this landing page? This cannot be undone.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Delete"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($pages->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                    {{ $pages->links() }}
                </div>
            @endif

        @else

            {{-- Empty State --}}
            <div class="flex flex-col items-center justify-center py-20 px-8 text-center">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center mb-5">
                    <svg class="w-10 h-10 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No landing pages yet</h3>
                <p class="text-gray-500 text-sm max-w-sm mb-6">
                    Generate your first AI-powered landing page from an opportunity and start capturing leads.
                </p>
                <a href="{{ route('landing-pages.create') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-semibold rounded-xl shadow hover:from-indigo-700 hover:to-purple-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Generate First Page
                </a>
            </div>

        @endif
    </div>

</div>
@endsection
