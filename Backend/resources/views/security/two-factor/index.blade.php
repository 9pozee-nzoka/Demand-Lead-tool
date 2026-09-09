@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Header -->
            <div class="d-flex align-items-center mb-4">
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary me-3">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                <div>
                    <h1 class="h3 mb-0">
                        <i class="bi bi-shield-lock"></i> Two-Factor Authentication
                    </h1>
                    <p class="text-muted mb-0">Add an extra layer of security to your account</p>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>{{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- 2FA Status Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="card-title mb-2">
                                <i class="bi bi-{{ $is2FAEnabled ? 'shield-check text-success' : 'shield-exclamation text-warning' }}"></i>
                                Two-Factor Authentication
                            </h5>
                            <p class="card-text text-muted mb-0">
                                @if($is2FAEnabled)
                                    ✅ <strong>Enabled</strong> - Your account is protected with 2FA
                                @else
                                    ⚠️ <strong>Disabled</strong> - Enable 2FA to secure your account
                                @endif
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            @if($is2FAEnabled)
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#disable2FAModal">
                                    <i class="bi bi-shield-slash"></i> Disable
                                </button>
                            @else
                                <a href="{{ route('2fa.enable') }}" class="btn btn-success btn-lg">
                                    <i class="bi bi-shield-plus"></i> Enable 2FA
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if($is2FAEnabled)
                <!-- Recovery Codes Section -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-3">
                            <i class="bi bi-key"></i> Recovery Codes
                        </h5>
                        <p class="text-muted">
                            Recovery codes can be used to access your account if you lose access to your authentication device.
                        </p>
                        
                        @if($hasRecoveryCodes)
                            <div class="d-flex gap-2 mt-3">
                                <a href="{{ route('2fa.recovery-codes') }}" class="btn btn-outline-primary">
                                    <i class="bi bi-eye"></i> View Codes
                                </a>
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#regenerateCodesModal">
                                    <i class="bi bi-arrow-repeat"></i> Regenerate Codes
                                </button>
                            </div>
                            
                            <div class="alert alert-info mt-3 mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Remaining codes:</strong> {{ auth()->user()->getRemainingRecoveryCodesCount() }} of 8
                            </div>
                        @else
                            <div class="alert alert-warning mt-3">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                No recovery codes generated. Please regenerate them.
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Information Section -->
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3">
                        <i class="bi bi-info-circle"></i> How It Works
                    </h5>
                    
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-3 d-inline-block mb-3">
                                    <i class="bi bi-phone text-primary" style="font-size: 2rem;"></i>
                                </div>
                                <h6>1. Install App</h6>
                                <p class="small text-muted">Download Google Authenticator or any TOTP app</p>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="bg-success bg-opacity-10 rounded-circle p-3 d-inline-block mb-3">
                                    <i class="bi bi-qr-code text-success" style="font-size: 2rem;"></i>
                                </div>
                                <h6>2. Scan QR Code</h6>
                                <p class="small text-muted">Scan the QR code with your authenticator app</p>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="bg-info bg-opacity-10 rounded-circle p-3 d-inline-block mb-3">
                                    <i class="bi bi-shield-check text-info" style="font-size: 2rem;"></i>
                                </div>
                                <h6>3. Enter Code</h6>
                                <p class="small text-muted">Use the 6-digit code to verify your identity</p>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h6 class="mb-3">Recommended Authenticator Apps:</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            <strong>Google Authenticator</strong> - iOS & Android
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            <strong>Microsoft Authenticator</strong> - iOS & Android
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            <strong>Authy</strong> - iOS, Android & Desktop
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Disable 2FA Modal -->
<div class="modal fade" id="disable2FAModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('2fa.disable') }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header border-0 bg-danger bg-opacity-10">
                    <h5 class="modal-title text-danger">
                        <i class="bi bi-shield-slash me-2"></i>Disable Two-Factor Authentication
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> Disabling 2FA will make your account less secure.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">2FA Code</label>
                        <input type="text" name="code" class="form-control" placeholder="000000" maxlength="6" required>
                        <small class="text-muted">Enter the 6-digit code from your authenticator app</small>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-shield-slash me-1"></i>Disable 2FA
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Regenerate Codes Modal -->
<div class="modal fade" id="regenerateCodesModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('2fa.regenerate-codes') }}" method="POST">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title">
                        <i class="bi bi-arrow-repeat me-2"></i>Regenerate Recovery Codes
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        This will invalidate your current recovery codes and generate new ones.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-arrow-repeat me-1"></i>Regenerate Codes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
