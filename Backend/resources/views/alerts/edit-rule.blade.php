@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Edit Alert Rule</h1>
                <p class="text-sm text-gray-600 mt-1">Update notification conditions</p>
            </div>

            <!-- Form -->
            <form action="{{ route('alerts.rules.update', $alertRule) }}" method="POST" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                @csrf
                @method('PATCH')

                <!-- Name -->
                <div class="mb-6">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Rule Name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $alertRule->name) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div class="mb-6">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500">{{ old('description') }}</textarea>
                </div>

                <!-- Project -->
                <div class="mb-6">
                    <label for="project_id" class="block text-sm font-medium text-gray-700 mb-2">Project (Optional)</label>
                    <select name="project_id" id="project_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500">
                        <option value="">All Projects</option>
                        @foreach($projects as $project)
                        <option value="{{ $project->id }}" {{ old('project_id', $alertRule->project_id) == $project->id ? 'selected' : '' }}>
                            {{ $project->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Alert Type -->
                <div class="mb-6">
                    <label for="alert_type" class="block text-sm font-medium text-gray-700 mb-2">Alert Type *</label>
                    <select name="alert_type" id="alert_type" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500">
                        <option value="spike" {{ old('alert_type') === 'spike' ? 'selected' : '' }}>Spike - Sudden traffic increase</option>
                        <option value="rising_trend" {{ old('alert_type') === 'rising_trend' ? 'selected' : '' }}>Rising Trend - Consistent growth</option>
                        <option value="threshold" {{ old('alert_type') === 'threshold' ? 'selected' : '' }}>Threshold - Custom metrics</option>
                        <option value="opportunity_detected" {{ old('alert_type') === 'opportunity_detected' ? 'selected' : '' }}>Opportunity Detected</option>
                    </select>
                </div>

                <!-- Trigger Condition -->
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Trigger Conditions</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Minimum Growth (%)</label>
                            <input type="number" name="trigger_condition[min_growth]" value="{{ old('trigger_condition.min_growth', 50) }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md">
                        </div>
                        
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Minimum Score</label>
                            <input type="number" name="trigger_condition[min_score]" value="{{ old('trigger_condition.min_score', 60) }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>
                </div>

                <!-- Channels -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notification Channels *</label>
                    @php
                        $selectedChannels = old('channels', $alertRule->channels ?? ['email', 'in_app']);
                    @endphp
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="channels[]" value="email" {{ in_array('email', $selectedChannels) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-700">Email</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="channels[]" value="sms" {{ in_array('sms', $selectedChannels) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-700">SMS</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="channels[]" value="in_app" {{ in_array('in_app', $selectedChannels) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-700">In-App Notification</span>
                        </label>
                    </div>
                </div>

                <!-- Recipients -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Recipients (Optional)</label>
                    <select name="recipient_user_ids[]" multiple
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500" size="5">
                        @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status -->
                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" {{ old('is_active', $alertRule->status === 'active') ? 'checked' : '' }}
                               class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                        <span class="ml-2 text-sm text-gray-700">Enable this rule</span>
                    </label>
                </div>

                <!-- Actions -->
                <div class="flex gap-3">
                    <button type="submit"
                            class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                        Update Alert Rule
                    </button>
                    <a href="{{ route('alerts.rules') }}"
                       class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
