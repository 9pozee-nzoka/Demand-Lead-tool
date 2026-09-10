@if(isset($isImpersonating) && $isImpersonating)
<div class="fixed top-0 left-0 right-0 z-50 bg-gradient-to-r from-yellow-500 to-orange-500 text-white shadow-lg">
    <div class="container mx-auto px-4 py-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <div>
                    <div class="font-semibold">
                        You are impersonating <span class="font-bold">{{ Auth::user()->name }}</span>
                    </div>
                    <div class="text-xs opacity-90">
                        {{ Auth::user()->email }} • Organization: {{ Auth::user()->organization->name ?? 'N/A' }}
                    </div>
                </div>
            </div>
            <form action="{{ route('impersonate.stop') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="bg-white text-orange-600 hover:bg-gray-100 px-6 py-2 rounded-lg font-semibold transition shadow-md hover:shadow-lg flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Stop Impersonating</span>
                </button>
            </form>
        </div>
    </div>
</div>
<!-- Spacer to push content down -->
<div class="h-16"></div>
@endif
