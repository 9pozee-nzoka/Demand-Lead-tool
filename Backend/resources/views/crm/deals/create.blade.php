@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Header -->
            <div class="mb-4">
                <h1 class="h3 mb-0">Create New Deal</h1>
                <p class="text-muted">Add a new deal to your sales pipeline</p>
            </div>

            <!-- Form -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('deals.store') }}" method="POST">
                        @csrf

                        <!-- Deal Title -->
                        <div class="mb-4">
                            <label for="title" class="form-label fw-bold">Deal Title *</label>
                            <input type="text" 
                                   class="form-control @error('title') is-invalid @enderror" 
                                   id="title" 
                                   name="title" 
                                   value="{{ old('title') }}"
                                   placeholder="e.g., Website Redesign for Acme Corp"
                                   required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <!-- Deal Value -->
                            <div class="col-md-6 mb-4">
                                <label for="value" class="form-label fw-bold">Deal Value *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" 
                                           class="form-control @error('value') is-invalid @enderror" 
                                           id="value" 
                                           name="value" 
                                           value="{{ old('value') }}"
                                           step="0.01"
                                           min="0"
                                           placeholder="10000.00"
                                           required>
                                    @error('value')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Currency -->
                            <div class="col-md-6 mb-4">
                                <label for="currency" class="form-label fw-bold">Currency</label>
                                <select name="currency" id="currency" class="form-select">
                                    <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                    <option value="EUR" {{ old('currency') === 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                                    <option value="GBP" {{ old('currency') === 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                                    <option value="KES" {{ old('currency') === 'KES' ? 'selected' : '' }}>KES - Kenyan Shilling</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Lead -->
                            <div class="col-md-6 mb-4">
                                <label for="lead_id" class="form-label fw-bold">Associated Lead</label>
                                <select name="lead_id" id="lead_id" class="form-select">
                                    <option value="">-- Select Lead --</option>
                                    @foreach($leads as $lead)
                                        <option value="{{ $lead->id }}" {{ old('lead_id') == $lead->id ? 'selected' : '' }}>
                                            {{ $lead->name }} - {{ $lead->email }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Optional: Link this deal to an existing lead</small>
                            </div>

                            <!-- Contact -->
                            <div class="col-md-6 mb-4">
                                <label for="contact_id" class="form-label fw-bold">Contact</label>
                                <select name="contact_id" id="contact_id" class="form-select">
                                    <option value="">-- Select Contact --</option>
                                    @foreach($contacts as $contact)
                                        <option value="{{ $contact->id }}" {{ old('contact_id') == $contact->id ? 'selected' : '' }}>
                                            {{ $contact->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Stage -->
                            <div class="col-md-6 mb-4">
                                <label for="stage" class="form-label fw-bold">Pipeline Stage *</label>
                                <select name="stage" id="stage" class="form-select @error('stage') is-invalid @enderror" required>
                                    <option value="new" {{ old('stage') === 'new' ? 'selected' : '' }}>New</option>
                                    <option value="qualified" {{ old('stage') === 'qualified' ? 'selected' : '' }}>Qualified</option>
                                    <option value="proposal" {{ old('stage') === 'proposal' ? 'selected' : '' }}>Proposal</option>
                                    <option value="negotiation" {{ old('stage') === 'negotiation' ? 'selected' : '' }}>Negotiation</option>
                                    <option value="closed_won" {{ old('stage') === 'closed_won' ? 'selected' : '' }}>Closed Won</option>
                                    <option value="closed_lost" {{ old('stage') === 'closed_lost' ? 'selected' : '' }}>Closed Lost</option>
                                </select>
                                @error('stage')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Assigned To -->
                            <div class="col-md-6 mb-4">
                                <label for="assigned_to" class="form-label fw-bold">Assign To</label>
                                <select name="assigned_to" id="assigned_to" class="form-select">
                                    <option value="">-- Unassigned --</option>
                                    @foreach($teamMembers as $member)
                                        <option value="{{ $member->id }}" {{ old('assigned_to') == $member->id ? 'selected' : '' }}>
                                            {{ $member->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Expected Close Date -->
                        <div class="mb-4">
                            <label for="expected_close_at" class="form-label fw-bold">Expected Close Date</label>
                            <input type="date" 
                                   class="form-control" 
                                   id="expected_close_at" 
                                   name="expected_close_at" 
                                   value="{{ old('expected_close_at') }}"
                                   min="{{ now()->format('Y-m-d') }}">
                            <small class="text-muted">When do you expect to close this deal?</small>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('deals.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Create Deal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
