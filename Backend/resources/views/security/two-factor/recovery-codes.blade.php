@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-{{ isset($regenerated) ? 'success' : 'warning' }} text-white border-0 p-4">
                    <h4 class="mb-0">
                        <i class="bi bi-key me-2"></i>
                        @if(isset($regenerated))
                            New Recovery Codes Generated
                        @elseif(isset($showOnly))
                            Your Recovery Codes
                        @else
                            Save Your Recovery Codes
                        @endif
                    </h4>
                </div>
                
                <div class="card-body p-4">
                    @if(isset($regenerated))
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            New recovery codes have been generated successfully.
                        </div>
                    @endif

                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Important:</strong> Store these codes in a safe place. Each code can only be used once.
                    </div>

                    <p class="text-muted mb-3">
                        Use these codes to access your account if you lose access to your authenticator device.
                    </p>

                    <div class="bg-light p-4 rounded mb-4">
                        <div class="row g-3">
                            @foreach($recoveryCodes as $index => $code)
                                <div class="col-6">
                                    <div class="bg-white p-3 rounded border text-center">
                                        <code class="fs-6">{{ $code }}</code>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary btn-lg" onclick="downloadCodes()">
                            <i class="bi bi-download me-2"></i>Download Codes
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="printCodes()">
                            <i class="bi bi-printer me-2"></i>Print Codes
                        </button>
                        <button type="button" class="btn btn-outline-info" onclick="copyCodes()">
                            <i class="bi bi-clipboard me-2"></i>Copy to Clipboard
                        </button>
                    </div>

                    <hr class="my-4">

                    <div class="alert alert-info mb-0">
                        <h6><i class="bi bi-info-circle me-2"></i>Best Practices:</h6>
                        <ul class="small mb-0 ps-3">
                            <li>Save these codes in a password manager</li>
                            <li>Print them and store in a secure location</li>
                            <li>Never share these codes with anyone</li>
                            <li>Generate new codes if you suspect they've been compromised</li>
                        </ul>
                    </div>
                </div>

                <div class="card-footer bg-light border-0 p-4">
                    <div class="d-grid">
                        <a href="{{ route('2fa.index') }}" class="btn btn-success btn-lg">
                            <i class="bi bi-check-circle me-2"></i>I've Saved My Codes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const recoveryCodes = @json($recoveryCodes);

function downloadCodes() {
    const text = 'DemandLead Recovery Codes\n' +
                 'Generated: ' + new Date().toLocaleString() + '\n\n' +
                 recoveryCodes.join('\n') + '\n\n' +
                 'Important: Keep these codes safe and secure.\n' +
                 'Each code can only be used once.';
    
    const blob = new Blob([text], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'demandlead-recovery-codes-' + Date.now() + '.txt';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    
    showToast('Recovery codes downloaded successfully', 'success');
}

function printCodes() {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>DemandLead - Recovery Codes</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    padding: 40px;
                }
                h1 {
                    color: #333;
                    border-bottom: 3px solid #667eea;
                    padding-bottom: 10px;
                }
                .code-grid {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 15px;
                    margin: 30px 0;
                }
                .code {
                    padding: 15px;
                    border: 2px solid #ddd;
                    border-radius: 5px;
                    font-family: monospace;
                    font-size: 18px;
                    text-align: center;
                    background: #f8f9fa;
                }
                .warning {
                    background: #fff3cd;
                    border: 2px solid #ffc107;
                    padding: 15px;
                    border-radius: 5px;
                    margin-top: 30px;
                }
                @media print {
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <h1>DemandLead - Recovery Codes</h1>
            <p><strong>Generated:</strong> ${new Date().toLocaleString()}</p>
            <p><strong>Account:</strong> {{ auth()->user()->email }}</p>
            
            <div class="code-grid">
                ${recoveryCodes.map(code => `<div class="code">${code}</div>`).join('')}
            </div>
            
            <div class="warning">
                <strong>⚠️ Important:</strong>
                <ul>
                    <li>Store these codes in a secure location</li>
                    <li>Each code can only be used once</li>
                    <li>Never share these codes with anyone</li>
                </ul>
            </div>
            
            <button class="no-print" onclick="window.print()" style="margin-top: 20px; padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer;">Print</button>
        </body>
        </html>
    `);
    printWindow.document.close();
}

function copyCodes() {
    const text = recoveryCodes.join('\n');
    navigator.clipboard.writeText(text).then(() => {
        showToast('Recovery codes copied to clipboard', 'success');
    }).catch(err => {
        console.error('Failed to copy:', err);
        showToast('Failed to copy codes', 'danger');
    });
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} position-fixed shadow-lg`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${message}
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
</script>
@endsection
