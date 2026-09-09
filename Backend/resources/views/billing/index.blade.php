@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Billing</h1>
        <p class="text-gray-600 mt-1">Subscription plans, usage tracking, and invoices</p>
    </div>

    <!-- Coming Soon Card -->
    <div class="bg-white rounded-lg shadow-md p-12 text-center">
        <div class="max-w-md mx-auto">
            <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="h-10 w-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-3">Billing Coming Soon</h2>
            <p class="text-gray-600 mb-6">
                Subscription plans, usage tracking, and invoices
            </p>
            <div class="flex items-center justify-center gap-2 text-sm text-gray-500">
                <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>Sprint 16</span>
            </div>
        </div>
    </div>
</div>
@endsection