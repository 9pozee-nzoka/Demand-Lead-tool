@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Create Alert Rule</h1>
                <p class="text-sm text-gray-600 mt-1">Set up automatic notifications when conditions are met</p>
            </div>

            <!-- Form -->
            <form action="{{ route('alerts.rules.store') }}" method="POST" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                @csrf

                <!-- Name -->
                <div class="mb-6">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Rule Name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500"
                           placeholder="e.g., Spike Alert - High Priority">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div class="mb-6">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500"
                              placeholder="Optional: Describe when this rule should trigger">{{ old('description') }}</textarea>
                </div>

                <!-- Project -->
                <div class="mb-6">
                    <label for="project_id" class="block text-sm font-medium text-gray-700 mb-2">Project (Optional)</label>
                    <select name="project_id" id="project_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500">
                        <option value="">All Projects</option>
                        @foreach($projects as $project)
                        <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
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
                        <option value="spike">Spike - Sudden traffic increase (100%+ growth)</option>
                        <option value="rising_trend">Rising Trend - Consistent growth (30%+ growth)</option>
                        <option value="threshold">Threshold - Custom metric thresholds</option>
                        <option value="opportunity_detected">Opportunity Detected - High-score opportunity</option>
                    </select>
                </div>

                <!-- Trigger Condition (JSON) -->
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
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="channels[]" value="email" checked
                                   class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-700">Email</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="channels[]" value="sms"
                                   class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-700">SMS</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="channels[]" value="in_app" checked
                                   class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-700">In-App Notification</span>
                        </label>
                    </div>
                </div>

                <!-- Recipients -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Recipients (Optional)</label>
                    <p class="text-xs text-gray-500 mb-2">If not specified, alerts will be sent to organization admins</p>
                    <select name="recipient_user_ids[]" multiple
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500" size="5">
                        @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Hold Ctrl/Cmd to select multiple users</p>
                </div>

                <!-- Status -->
                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" checked
                               class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                        <span class="ml-2 text-sm text-gray-700">Enable this rule immediately</span>
                    </label>
                </div>

                <!-- Actions -->
                <div class="flex gap-3">
                    <button type="submit"
                            class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                        Create Alert Rule
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
