@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-whatsapp text-success"></i> WhatsApp Integration
            </h1>
            <p class="text-muted">Capture leads automatically via WhatsApp Business</p>
        </div>
        <div>
            @if($isConfigured)
                <a href="{{ route('whatsapp.configure') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-gear"></i> Settings
                </a>
                <a href="{{ route('whatsapp.statistics') }}" class="btn btn-outline-info">
                    <i class="bi bi-graph-up"></i> Statistics
                </a>
            @else
                <a href="{{ route('whatsapp.configure') }}" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Configure WhatsApp
                </a>
            @endif
        </div>
    </div>

    @if(!$isConfigured)
        <!-- Setup Required -->
        <div class="alert alert-warning">
            <h5 class="alert-heading">
                <i class="bi bi-exclamation-triangle"></i> Setup Required
            </h5>
            <p class="mb-3">WhatsApp integration is not configured yet. Connect your WhatsApp Business account to start capturing leads automatically.</p>
            <a href="{{ route('whatsapp.configure') }}" class="btn btn-warning">
                <i class="bi bi-gear"></i> Configure Now
            </a>
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Total Conversations</p>
                            <h3 class="mb-0">{{ number_format($stats['total_conversations']) }}</h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="bi bi-chat-dots text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Active Today</p>
                            <h3 class="mb-0">{{ number_format($stats['active_today']) }}</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="bi bi-person-check text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Qualified Leads</p>
                            <h3 class="mb-0">{{ number_format($stats['qualified_leads']) }}</h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded">
                            <i class="bi bi-star text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Response Rate</p>
                            <h3 class="mb-0">{{ $stats['response_rate'] }}%</h3>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded">
                            <i class="bi bi-reply text-info fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Conversations -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Recent Conversations</h5>
            @if($leads->count() > 0)
                <span class="badge bg-success">{{ $leads->total() }} total</span>
            @endif
        </div>
        <div class="card-body">
            @if($leads->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Contact</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Quality</th>
                                <th>Last Contact</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($leads as $lead)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2" 
                                                 style="width: 40px; height: 40px;">
                                                <i class="bi bi-whatsapp"></i>
                                            </div>
                                            <div>
                                                <strong>{{ $lead->name }}</strong>
                                                @if($lead->company)
                                                    <div class="small text-muted">{{ $lead->company }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <code>{{ $lead->phone }}</code>
                                    </td>
                                    <td>
                                        @php
                                            $statusClass = match($lead->status) {
                                                'new' => 'primary',
                                                'qualified' => 'success',
                                                'contacted' => 'info',
                                                'disqualified' => 'danger',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }}">
                                            {{ ucfirst($lead->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $qualityClass = match($lead->quality) {
                                                'hot' => 'danger',
                                                'warm' => 'warning',
                                                'potential' => 'info',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $qualityClass }}">
                                            {{ ucfirst($lead->quality) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($lead->last_contact_at)
                                            <span title="{{ $lead->last_contact_at }}">
                                                {{ $lead->last_contact_at->diffForHumans() }}
                                            </span>
                                        @else
                                            <span class="text-muted">Never</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('whatsapp.conversation', $lead) }}" 
                                               class="btn btn-outline-success"
                                               title="View Conversation">
                                                <i class="bi bi-chat-dots"></i>
                                            </a>
                                            <a href="{{ route('leads.show', $lead) }}" 
                                               class="btn btn-outline-secondary"
                                               title="Lead Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-3">
                    {{ $leads->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-chat-dots text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 text-muted">No Conversations Yet</h5>
                    <p class="text-muted">
                        @if($isConfigured)
                            Leads will appear here when contacts message your WhatsApp Business number
                        @else
                            Configure WhatsApp integration to start capturing leads
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- How It Works -->
    @if($isConfigured)
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="bi bi-info-circle text-primary"></i> How WhatsApp Integration Works
                </h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="d-flex mb-3">
                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                 style="width: 40px; height: 40px; flex-shrink: 0;">
                                <strong>1</strong>
                            </div>
                            <div>
                                <h6>Automatic Capture</h6>
                                <p class="small text-muted mb-0">
                                    When someone messages your WhatsApp Business number, they're automatically added as a lead
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex mb-3">
                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                 style="width: 40px; height: 40px; flex-shrink: 0;">
                                <strong>2</strong>
                            </div>
                            <div>
                                <h6>Smart Responses</h6>
                                <p class="small text-muted mb-0">
                                    AI responds automatically to common questions about pricing, features, and scheduling demos
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex mb-3">
                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                 style="width: 40px; height: 40px; flex-shrink: 0;">
                                <strong>3</strong>
                            </div>
                            <div>
                                <h6>Lead Qualification</h6>
                                <p class="small text-muted mb-0">
                                    Qualified leads are routed to your team with full conversation history for follow-up
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
