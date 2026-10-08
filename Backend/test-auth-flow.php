<?php

/**
 * Test Authentication Flow
 * Run: php test-auth-flow.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "========================================\n";
echo "Testing Authentication Flow\n";
echo "========================================\n\n";

// 1. Check if users exist
$user = App\Models\User::first();
if (!$user) {
    echo "❌ No users found in database\n";
    exit(1);
}

echo "✅ Found user: {$user->email}\n";
echo "   Organization: {$user->organization_id}\n";
echo "   Email verified: " . ($user->email_verified_at ? 'YES' : 'NO') . "\n";
echo "   Has verified email: " . ($user->hasVerifiedEmail() ? 'YES' : 'NO') . "\n\n";

// 2. Check dashboard route middleware
echo "Dashboard Route Configuration:\n";
$routes = Route::getRoutes();
$dashboardRoute = $routes->getByName('dashboard');

if ($dashboardRoute) {
    $middleware = $dashboardRoute->middleware();
    echo "   Middleware: " . implode(', ', $middleware) . "\n";
    
    // Check if verified middleware is present
    if (in_array('verified', $middleware)) {
        echo "   ⚠️  'verified' middleware IS required\n";
    } else {
        echo "   ✅ 'verified' middleware NOT required\n";
    }
} else {
    echo "   ❌ Dashboard route not found!\n";
}
echo "\n";

// 3. Check session configuration
echo "Session Configuration:\n";
echo "   Driver: " . config('session.driver') . "\n";
echo "   Encrypt: " . (config('session.encrypt') ? 'true' : 'false') . "\n";
echo "   Secure: " . (config('session.secure') ? 'true' : 'false') . "\n";
echo "   SameSite: " . config('session.same_site') . "\n";
echo "   Domain: " . (config('session.domain') ?: 'null') . "\n\n";

// 4. Test session write/read
echo "Session Test:\n";
try {
    session()->put('test_key', 'test_value');
    session()->save();
    $value = session()->get('test_key');
    
    if ($value === 'test_value') {
        echo "   ✅ Session write/read: OK\n";
    } else {
        echo "   ❌ Session write/read: FAILED (got: " . var_export($value, true) . ")\n";
    }
} catch (Exception $e) {
    echo "   ❌ Session error: " . $e->getMessage() . "\n";
}
echo "\n";

// 5. Simulate login
echo "Simulating Login:\n";
try {
    Auth::login($user);
    echo "   ✅ Auth::login() successful\n";
    echo "   Authenticated: " . (Auth::check() ? 'YES' : 'NO') . "\n";
    echo "   User ID: " . (Auth::id() ?: 'NULL') . "\n";
    
    // Check if user passes verified middleware
    if ($user->hasVerifiedEmail()) {
        echo "   ✅ User would pass 'verified' middleware\n";
    } else {
        echo "   ❌ User would FAIL 'verified' middleware\n";
    }
} catch (Exception $e) {
    echo "   ❌ Login failed: " . $e->getMessage() . "\n";
}
echo "\n";

echo "========================================\n";
echo "Diagnosis:\n";
echo "========================================\n";

if (!$user->hasVerifiedEmail()) {
    echo "❌ ISSUE: User email not verified\n";
    echo "   FIX: Run in production:\n";
    echo "   php artisan tinker --execute=\"App\\Models\\User::whereNull('email_verified_at')->update(['email_verified_at' => now()]);\"\n\n";
}

if (config('session.encrypt') === true) {
    echo "⚠️  WARNING: SESSION_ENCRYPT=true can cause issues on HTTPS\n";
    echo "   FIX: Set SESSION_ENCRYPT=false in .env\n\n";
}

if (config('session.driver') === 'file') {
    $sessionPath = storage_path('framework/sessions');
    if (!is_writable($sessionPath)) {
        echo "❌ ISSUE: Session directory not writable\n";
        echo "   FIX: chmod -R 775 {$sessionPath}\n\n";
    }
}

if (config('session.secure') === false && app()->environment('production')) {
    echo "⚠️  WARNING: SESSION_SECURE_COOKIE should be true for HTTPS\n";
    echo "   FIX: Set SESSION_SECURE_COOKIE=true in .env\n\n";
}

echo "✅ Test complete\n";
