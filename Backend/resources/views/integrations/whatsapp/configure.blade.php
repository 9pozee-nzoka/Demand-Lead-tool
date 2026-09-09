@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Header -->
            <div class="mb-4">
                <h1 class="h3 mb-0">
                    <i class="bi bi-whatsapp text-success"></i> Configure WhatsApp Integration
                </h1>
                <p class="text-muted">Connect your WhatsApp Business account</p>
            </div>

            <!-- Configuration Form -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('whatsapp.store') }}" method="POST" id="whatsappForm">
                        @csrf

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Prerequisites:</strong>
                            <ul class="mb-0 mt-2 small">
                                <li>WhatsApp Business Platform account</li>
                                <li>Verified Business Account</li>
                                <li>Phone number registered with WhatsApp Business API</li>
                            </ul>
                        </div>

                        <!-- Access Token -->
                        <div class="mb-4">
                            <label for="access_token" class="form-label fw-bold">
                                Access Token *
                                <i class="bi bi-question-circle text-muted" 
                                   title="Permanent access token from Meta Business Suite"></i>
                            </label>
                            <input type="text" 
                                   class="form-control @error('access_token') is-invalid @enderror" 
                                   id="access_token" 
                                   name="access_token" 
                                   value="{{ old('access_token', $dataSource ? '••••••••••••' : '') }}"
                                   placeholder="EAAxxxxxxxxxxxxxxxxxx"
                                   required>
                            <small class="text-muted">
                                Get from Meta Business Suite → System Users → Generate Token
                            </small>
                            @error('access_token')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Phone Number ID -->
                        <div class="mb-4">
                            <label for="phone_number_id" class="form-label fw-bold">
                                Phone Number ID *
                            </label>
                            <input type="text" 
                                   class="form-control @error('phone_number_id') is-invalid @enderror" 
                                   id="phone_number_id" 
                                   name="phone_number_id" 
                                   value="{{ old('phone_number_id', $dataSource ? json_decode(decrypt($dataSource->credentials), true)['phone_number_id'] ?? '' : '') }}"
                                   placeholder="1234567890123456"
                                   required>
                            <small class="text-muted">
                                Found in WhatsApp Business API → Phone Numbers
                            </small>
                            @error('phone_number_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Verify Token -->
                        <div class="mb-4">
                            <label for="verify_token" class="form-label fw-bold">
                                Webhook Verify Token *
                            </label>
                            <input type="text" 
                                   class="form-control @error('verify_token') is-invalid @enderror" 
                                   id="verify_token" 
                                   name="verify_token" 
                                   value="{{ old('verify_token', $dataSource ? '••••••••••••' : '') }}"
                                   placeholder="your_secret_verify_token"
                                   required>
                            <small class="text-muted">
                                Create a random string (min 8 characters). Use this when setting up webhook in Meta.
                            </small>
                            @error('verify_token')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Business Account ID -->
                        <div class="mb-4">
                            <label for="business_account_id" class="form-label fw-bold">
                                Business Account ID (Optional)
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="business_account_id" 
                                   name="business_account_id" 
                                   value="{{ old('business_account_id', $dataSource ? json_decode(decrypt($dataSource->credentials), true)['business_account_id'] ?? '' : '') }}"
                                   placeholder="1234567890123456">
                            <small class="text-muted">
                                WhatsApp Business Account ID from Meta Business Suite
                            </small>
                        </div>

                        <!-- Webhook URL Info -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Webhook URL</label>
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control" 
                                       value="{{ $webhookUrl }}" 
                                       readonly 
                                       id="webhookUrl">
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        onclick="copyWebhookUrl()">
                                    <i class="bi bi-clipboard"></i> Copy
                                </button>
                            </div>
                            <small class="text-muted">
                                Use this URL when configuring webhooks in Meta Developer Console
                            </small>
                        </div>

                        <!-- Webhook Setup Instructions -->
                        <div class="alert alert-warning">
                            <h6 class="alert-heading">
                                <i class="bi bi-exclamation-triangle"></i> Webhook Setup Required
                            </h6>
                            <p class="small mb-2">After saving, configure webhooks in Meta:</p>
                            <ol class="small mb-0">
                                <li>Go to Meta Developers → Your App → WhatsApp → Configuration</li>
                                <li>Click "Edit" next to Webhooks</li>
                                <li>Enter Callback URL: <code>{{ $webhookUrl }}</code></li>
                                <li>Enter Verify Token (same as above)</li>
                                <li>Click "Verify and Save"</li>
                                <li>Subscribe to webhook fields: <strong>messages</strong></li>
                            </ol>
                        </div>

                        <!-- Test Connection -->
                        @if($dataSource)
                            <div class="mb-4">
                                <button type="button" 
                                        class="btn btn-outline-info w-100" 
                                        onclick="testConnection()">
                                    <i class="bi bi-wifi"></i> Test Connection
                                </button>
                                <div id="testResult" class="mt-2"></div>
                            </div>
                        @endif

                        <!-- Actions -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('whatsapp.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Back
                            </a>
                            <div>
                                @if($dataSource)
                                    <button type="button" 
                                            class="btn btn-outline-danger me-2" 
                                            onclick="if(confirm('Disconnect WhatsApp integration?')) { document.getElementById('disconnectForm').submit(); }">
                                        <i class="bi bi-x-circle"></i> Disconnect
                                    </button>
                                @endif
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> {{ $dataSource ? 'Update Configuration' : 'Connect WhatsApp' }}
                                </button>
                            </div>
                        </div>
                    </form>

                    @if($dataSource)
                        <form id="disconnectForm" action="{{ route('whatsapp.disconnect') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    @endif
                </div>
            </div>

            <!-- Setup Guide -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="bi bi-book text-primary"></i> Setup Guide
                    </h5>
                    
                    <div class="accordion" id="setupAccordion">
                        <!-- Step 1 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#step1">
                                    <strong>Step 1:</strong> Create WhatsApp Business Platform Account
                                </button>
                            </h2>
                            <div id="step1" class="accordion-collapse collapse show" data-bs-parent="#setupAccordion">
                                <div class="accordion-body small">
                                    <ol class="mb-0">
                                        <li>Go to <a href="https://developers.facebook.com" target="_blank">Meta for Developers</a></li>
                                        <li>Create a new app and select "Business" type</li>
                                        <li>Add "WhatsApp" product to your app</li>
                                        <li>Complete business verification</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step2">
                                    <strong>Step 2:</strong> Get Access Token
                                </button>
                            </h2>
                            <div id="step2" class="accordion-collapse collapse" data-bs-parent="#setupAccordion">
                                <div class="accordion-body small">
                                    <ol class="mb-0">
                                        <li>In Meta Business Suite, go to System Users</li>
                                        <li>Create a new system user or select existing</li>
                                        <li>Generate a permanent access token</li>
                                        <li>Grant permissions: <code>whatsapp_business_management</code>, <code>whatsapp_business_messaging</code></li>
                                        <li>Copy the token (starts with "EAA")</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step3">
                                    <strong>Step 3:</strong> Configure Phone Number
                                </button>
                            </h2>
                            <div id="step3" class="accordion-collapse collapse" data-bs-parent="#setupAccordion">
                                <div class="accordion-body small">
                                    <ol class="mb-0">
                                        <li>In WhatsApp Manager, register your phone number</li>
                                        <li>Verify your phone number via SMS/call</li>
                                        <li>Copy the Phone Number ID from API Setup page</li>
                                        <li>Complete business profile setup</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyWebhookUrl() {
    const input = document.getElementById('webhookUrl');
    input.select();
    document.execCommand('copy');
    
    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check"></i> Copied!';
    btn.classList.add('btn-success');
    btn.classList.remove('btn-outline-secondary');
    
    setTimeout(() => {
        btn.innerHTML = originalHtml;
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-secondary');
    }, 2000);
}

async function testConnection() {
    const btn = event.target;
    const resultDiv = document.getElementById('testResult');
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Testing...';
    resultDiv.innerHTML = '';
    
    try {
        const response = await fetch('{{ route("whatsapp.test") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success mb-0">
                    <i class="bi bi-check-circle"></i> Connection successful!
                    <div class="small mt-2">
                        Business: ${data.data.verified_name || 'N/A'}<br>
                        Phone: ${data.data.display_phone_number || 'N/A'}<br>
                        Quality: ${data.data.quality_rating || 'N/A'}
                    </div>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-danger mb-0">
                    <i class="bi bi-x-circle"></i> Connection failed: ${data.message}
                </div>
            `;
        }
    } catch (error) {
        resultDiv.innerHTML = `
            <div class="alert alert-danger mb-0">
                <i class="bi bi-x-circle"></i> Error: ${error.message}
            </div>
        `;
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-wifi"></i> Test Connection';
    }
}
</script>
@endsection
