<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthController extends Controller
{
    protected $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Show 2FA settings page
     */
    public function index(Request $request)
    {
        $user = $request->user();

        return view('security.two-factor.index', [
            'is2FAEnabled' => $user->google2fa_enabled,
            'hasRecoveryCodes' => !empty($user->two_factor_recovery_codes),
        ]);
    }

    /**
     * Enable 2FA - Step 1: Generate secret and show QR code
     */
    public function enable(Request $request)
    {
        $user = $request->user();

        if ($user->google2fa_enabled) {
            return redirect()->route('2fa.index')
                ->with('error', '2FA is already enabled');
        }

        // Generate a new secret
        $secret = $this->google2fa->generateSecretKey();

        // Store temporarily in session for verification
        $request->session()->put('2fa_secret', $secret);

        // Generate QR code URL
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        // Generate QR code using inline SVG
        $qrCodeImage = $this->generateQRCode($qrCodeUrl);

        return view('security.two-factor.enable', [
            'secret' => $secret,
            'qrCodeImage' => $qrCodeImage,
            'qrCodeUrl' => $qrCodeUrl,
        ]);
    }

    /**
     * Confirm 2FA setup by verifying code
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'code' => 'required|numeric|digits:6',
        ]);

        $user = $request->user();
        $secret = $request->session()->get('2fa_secret');

        if (!$secret) {
            return back()->with('error', 'Session expired. Please start the setup again.');
        }

        // Verify the code
        $valid = $this->google2fa->verifyKey($secret, $request->code);

        if (!$valid) {
            return back()
                ->withInput()
                ->with('error', 'Invalid verification code. Please try again.');
        }

        // Save the secret and enable 2FA
        $user->google2fa_secret = Crypt::encryptString($secret);
        $user->google2fa_enabled = true;
        $user->two_factor_enabled_at = now();

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();
        $user->two_factor_recovery_codes = Crypt::encryptString(json_encode($recoveryCodes));

        $user->save();

        // Clear session
        $request->session()->forget('2fa_secret');

        // Audit log
        AuditLog::record(
            $user->organization_id,
            $user->id,
            'security',
            'two_factor_enabled',
            null,
            ['user_id' => $user->id]
        );

        return view('security.two-factor.recovery-codes', [
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    /**
     * Show recovery codes
     */
    public function showRecoveryCodes(Request $request)
    {
        $user = $request->user();

        if (!$user->google2fa_enabled || !$user->two_factor_recovery_codes) {
            return redirect()->route('2fa.index')
                ->with('error', '2FA is not enabled');
        }

        // Verify password before showing codes
        if (!$request->session()->has('2fa_codes_verified')) {
            return view('security.two-factor.verify-password');
        }

        $recoveryCodes = json_decode(Crypt::decryptString($user->two_factor_recovery_codes), true);

        return view('security.two-factor.recovery-codes', [
            'recoveryCodes' => $recoveryCodes,
            'showOnly' => true,
        ]);
    }

    /**
     * Verify password before showing recovery codes
     */
    public function verifyPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return back()->with('error', 'Invalid password');
        }

        $request->session()->put('2fa_codes_verified', true);

        return redirect()->route('2fa.recovery-codes');
    }

    /**
     * Regenerate recovery codes
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $user = $request->user();

        if (!$user->google2fa_enabled) {
            return redirect()->route('2fa.index')
                ->with('error', '2FA is not enabled');
        }

        // Verify password
        $request->validate([
            'password' => 'required|string',
        ]);

        if (!Hash::check($request->password, $user->password)) {
            return back()->with('error', 'Invalid password');
        }

        // Generate new recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();
        $user->two_factor_recovery_codes = Crypt::encryptString(json_encode($recoveryCodes));
        $user->save();

        // Audit log
        AuditLog::record(
            $user->organization_id,
            $user->id,
            'security',
            'two_factor_recovery_codes_regenerated',
            null,
            ['user_id' => $user->id]
        );

        return view('security.two-factor.recovery-codes', [
            'recoveryCodes' => $recoveryCodes,
            'regenerated' => true,
        ]);
    }

    /**
     * Disable 2FA
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'code' => 'required|numeric|digits:6',
        ]);

        $user = $request->user();

        if (!$user->google2fa_enabled) {
            return redirect()->route('2fa.index')
                ->with('error', '2FA is not enabled');
        }

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return back()->with('error', 'Invalid password');
        }

        // Verify 2FA code
        $secret = Crypt::decryptString($user->google2fa_secret);
        $valid = $this->google2fa->verifyKey($secret, $request->code);

        if (!$valid) {
            return back()->with('error', 'Invalid verification code');
        }

        // Disable 2FA
        $user->google2fa_secret = null;
        $user->google2fa_enabled = false;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_enabled_at = null;
        $user->save();

        // Audit log
        AuditLog::record(
            $user->organization_id,
            $user->id,
            'security',
            'two_factor_disabled',
            null,
            ['user_id' => $user->id]
        );

        return redirect()->route('2fa.index')
            ->with('success', 'Two-factor authentication has been disabled');
    }

    /**
     * Verify 2FA code during login
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|numeric|digits:6',
        ]);

        $userId = $request->session()->get('2fa_user_id');

        if (!$userId) {
            return redirect()->route('login')
                ->with('error', 'Session expired. Please login again.');
        }

        $user = \App\Models\User::find($userId);

        if (!$user || !$user->google2fa_enabled) {
            return redirect()->route('login')
                ->with('error', 'Invalid session');
        }

        // Check if it's a recovery code
        if (strlen($request->code) > 6) {
            return $this->verifyRecoveryCode($request, $user);
        }

        // Verify the 2FA code
        $secret = Crypt::decryptString($user->google2fa_secret);
        $valid = $this->google2fa->verifyKey($secret, $request->code);

        if (!$valid) {
            return back()
                ->withInput()
                ->with('error', 'Invalid verification code. Please try again.');
        }

        // Login successful
        auth()->login($user, $request->session()->get('2fa_remember'));
        $request->session()->forget(['2fa_user_id', '2fa_remember']);

        return redirect()->intended('/dashboard');
    }

    /**
     * Verify recovery code
     */
    protected function verifyRecoveryCode(Request $request, $user)
    {
        $recoveryCodes = json_decode(Crypt::decryptString($user->two_factor_recovery_codes), true);

        if (!in_array($request->code, $recoveryCodes)) {
            return back()->with('error', 'Invalid recovery code');
        }

        // Remove used recovery code
        $recoveryCodes = array_diff($recoveryCodes, [$request->code]);
        $user->two_factor_recovery_codes = Crypt::encryptString(json_encode(array_values($recoveryCodes)));
        $user->save();

        // Audit log
        AuditLog::record(
            $user->organization_id,
            $user->id,
            'security',
            'two_factor_recovery_code_used',
            null,
            ['user_id' => $user->id, 'remaining_codes' => count($recoveryCodes)]
        );

        // Login successful
        auth()->login($user, $request->session()->get('2fa_remember'));
        $request->session()->forget(['2fa_user_id', '2fa_remember']);

        return redirect()->intended('/dashboard')
            ->with('warning', 'You used a recovery code. ' . count($recoveryCodes) . ' codes remaining. Please regenerate new codes.');
    }

    /**
     * Generate recovery codes
     */
    protected function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(Str::random(10));
        }
        return $codes;
    }

    /**
     * Generate QR code SVG (simple implementation)
     */
    protected function generateQRCode($url): string
    {
        // Using a simple QR code generation via Google Charts API (works without additional dependencies)
        // For production, consider using a proper QR library
        return sprintf(
            'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=%s',
            urlencode($url)
        );
    }
}
