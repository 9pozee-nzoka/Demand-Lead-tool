@extends('layouts.guest')

@section('content')
<div class="card shadow border-0">
    <div class="card-body p-5">
        <div class="text-center mb-4">
            <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                 style="width: 80px; height: 80px;">
                <i class="bi bi-shield-check text-primary" style="font-size: 2.5rem;"></i>
            </div>
            <h4 class="mb-2">Two-Factor Authentication</h4>
            <p class="text-muted">Enter the 6-digit code from your authenticator app</p>
        </div>

        @if(session('error'))
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            </div>
        @endif

        <form action="{{ route('2fa.verify') }}" method="POST">
            @csrf
            
            <div class="mb-4">
                <label for="code" class="form-label">Verification Code</label>
                <input type="text" 
                       id="code"
                       name="code" 
                       class="form-control form-control-lg text-center @error('code') is-invalid @enderror" 
                       placeholder="000000"
                       maxlength="6"
                       pattern="[0-9]{6}"
                       autofocus
                       required>
                @error('code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-circle me-2"></i>Verify
                </button>
            </div>
        </form>

        <hr class="my-4">

        <div class="text-center">
            <button type="button" class="btn btn-link text-decoration-none" data-bs-toggle="collapse" data-bs-target="#recoveryCodeForm">
                <i class="bi bi-key me-1"></i>Use Recovery Code Instead
            </button>
        </div>

        <div class="collapse mt-3" id="recoveryCodeForm">
            <div class="alert alert-info small">
                <i class="bi bi-info-circle me-1"></i>
                Lost access to your authenticator? Enter one of your recovery codes below.
            </div>
            
            <form action="{{ route('2fa.verify') }}" method="POST">
                @csrf
                
                <div class="mb-3">
                    <label for="recovery_code" class="form-label">Recovery Code</label>
                    <input type="text" 
                           id="recovery_code"
                           name="code" 
                           class="form-control text-uppercase" 
                           placeholder="XXXXXXXXXX"
                           maxlength="10">
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-key me-2"></i>Use Recovery Code
                    </button>
                </div>
            </form>
        </div>

        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="text-muted text-decoration-none small">
                <i class="bi bi-arrow-left me-1"></i>Back to Login
            </a>
        </div>
    </div>
</div>

<script>
// Auto-format the code input
document.getElementById('code').addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '').slice(0, 6);
});

// Auto-submit when 6 digits entered
document.getElementById('code').addEventListener('input', function(e) {
    if (this.value.length === 6 && /^\d{6}$/.test(this.value)) {
        setTimeout(() => this.form.submit(), 300);
    }
});

// Format recovery code input
if (document.getElementById('recovery_code')) {
    document.getElementById('recovery_code').addEventListener('input', function(e) {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 10);
    });
}
</script>
@endsection
