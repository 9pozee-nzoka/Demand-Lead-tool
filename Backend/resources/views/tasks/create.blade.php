@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Header -->
            <div class="mb-4">
                <h1 class="h3 mb-0">Create New Task</h1>
                <p class="text-muted">Add a new task or activity</p>
            </div>

            <!-- Form -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('tasks.store') }}" method="POST">
                        @csrf

                        <!-- Task Title -->
                        <div class="mb-4">
                            <label for="title" class="form-label fw-bold">Task Title *</label>
                            <input type="text" 
                                   class="form-control @error('title') is-invalid @enderror" 
                                   id="title" 
                                   name="title" 
                                   value="{{ old('title') }}"
                                   placeholder="e.g., Follow up with John about proposal"
                                   required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold">Description</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="4"
                                      placeholder="Add details about this task...">{{ old('description') }}</textarea>
                        </div>

                        <div class="row">
                            <!-- Type -->
                            <div class="col-md-6 mb-4">
                                <label for="type" class="form-label fw-bold">Task Type *</label>
                                <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="">-- Select Type --</option>
                                    <option value="call" {{ old('type') === 'call' ? 'selected' : '' }}>
                                        <i class="bi bi-telephone"></i> Call
                                    </option>
                                    <option value="email" {{ old('type') === 'email' ? 'selected' : '' }}>Email</option>
                                    <option value="meeting" {{ old('type') === 'meeting' ? 'selected' : '' }}>Meeting</option>
                                    <option value="follow_up" {{ old('type') === 'follow_up' ? 'selected' : '' }}>Follow Up</option>
                                    <option value="research" {{ old('type') === 'research' ? 'selected' : '' }}>Research</option>
                                    <option value="other" {{ old('type') === 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Due Date -->
                            <div class="col-md-6 mb-4">
                                <label for="due_at" class="form-label fw-bold">Due Date *</label>
                                <input type="datetime-local" 
                                       class="form-control @error('due_at') is-invalid @enderror" 
                                       id="due_at" 
                                       name="due_at" 
                                       value="{{ old('due_at') }}"
                                       required>
                                @error('due_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Assigned To -->
                        <div class="mb-4">
                            <label for="assigned_user_id" class="form-label fw-bold">Assign To *</label>
                            <select name="assigned_user_id" id="assigned_user_id" class="form-select @error('assigned_user_id') is-invalid @enderror" required>
                                <option value="">-- Select Team Member --</option>
                                @foreach($teamMembers as $member)
                                    <option value="{{ $member->id }}" {{ old('assigned_user_id') == $member->id ? 'selected' : '' }}>
                                        {{ $member->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('assigned_user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <!-- Related Lead -->
                            <div class="col-md-6 mb-4">
                                <label for="lead_id" class="form-label fw-bold">Related Lead</label>
                                <select name="lead_id" id="lead_id" class="form-select">
                                    <option value="">-- None --</option>
                                    @foreach($leads as $lead)
                                        <option value="{{ $lead->id }}" {{ old('lead_id') == $lead->id ? 'selected' : '' }}>
                                            {{ $lead->name }} - {{ $lead->email }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Optional: Link to a lead</small>
                            </div>

                            <!-- Related Deal -->
                            <div class="col-md-6 mb-4">
                                <label for="deal_id" class="form-label fw-bold">Related Deal</label>
                                <select name="deal_id" id="deal_id" class="form-select">
                                    <option value="">-- None --</option>
                                    @foreach($deals as $deal)
                                        <option value="{{ $deal->id }}" {{ old('deal_id') == $deal->id ? 'selected' : '' }}>
                                            {{ $deal->title }} (${{ number_format($deal->value) }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Optional: Link to a deal</small>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Create Task
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
