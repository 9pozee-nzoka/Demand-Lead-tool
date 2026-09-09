@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex items-center text-sm text-gray-600 mb-4">
            <a href="{{ route('keywords.index') }}" class="hover:text-gray-900">Keywords</a>
            <svg class="h-4 w-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <a href="{{ route('keywords.show', $keyword) }}" class="hover:text-gray-900">{{ $keyword->term }}</a>
            <svg class="h-4 w-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-gray-900">Edit</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Edit Keyword</h1>
        <p class="text-gray-600 mt-1">Update keyword tracking settings</p>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <form action="{{ route('keywords.update', $keyword) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Keyword Term -->
            <div>
                <label for="term" class="block text-sm font-medium text-gray-700 mb-2">Keyword *</label>
                <input type="text" name="term" id="term" value="{{ old('term', $keyword->term) }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                    placeholder="e.g., cloud hosting services">
                @error('term')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" id="status"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    <option value="active" {{ old('status', $keyword->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="paused" {{ old('status', $keyword->status) === 'paused' ? 'selected' : '' }}>Paused</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Match Type -->
            <div>
                <label for="match_type" class="block text-sm font-medium text-gray-700 mb-2">Match Type</label>
                <select name="match_type" id="match_type"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    <option value="exact" {{ old('match_type', $keyword->match_type) === 'exact' ? 'selected' : '' }}>Exact</option>
                    <option value="phrase" {{ old('match_type', $keyword->match_type) === 'phrase' ? 'selected' : '' }}>Phrase</option>
                    <option value="broad" {{ old('match_type', $keyword->match_type) === 'broad' ? 'selected' : '' }}>Broad</option>
                </select>
                @error('match_type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Priority -->
            <div>
                <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                <select name="priority" id="priority"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    <option value="low" {{ old('priority', $keyword->priority) === 'low' ? 'selected' : '' }}>Low</option>
                    <option value="medium" {{ old('priority', $keyword->priority) === 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="high" {{ old('priority', $keyword->priority) === 'high' ? 'selected' : '' }}>High</option>
                </select>
                @error('priority')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Notes -->
            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                <textarea name="notes" id="notes" rows="3"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                    placeholder="Add any notes about this keyword...">{{ old('notes', $keyword->notes) }}</textarea>
                @error('notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                <form action="{{ route('keywords.destroy', $keyword) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this keyword? This action cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 text-red-600 hover:text-red-700 font-medium">
                        Delete Keyword
                    </button>
                </form>
                
                <div class="flex items-center space-x-3">
                    <a href="{{ route('keywords.show', $keyword) }}" class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                        Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
