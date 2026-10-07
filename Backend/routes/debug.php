<?php

use Illuminate\Support\Facades\Route;

/**
 * Debug routes - REMOVE THESE IN PRODUCTION AFTER FIXING
 */

// Check session configuration
Route::get('/debug/session-config', function () {
    if (!app()->environment('local', 'staging')) {
        abort(404);
    }
    
    return response()->json([
        'session_driver' => config('session.driver'),
        'session_lifetime' => config('session.lifetime'),
        'session_encrypt' => config('session.encrypt'),
        'session_domain' => config('session.domain'),
        'session_secure' => config('session.secure'),
        'session_same_site' => config('session.same_site'),
        'session_http_only' => config('session.http_only'),
        'app_env' => config('app.env'),
        'app_url' => config('app.url'),
        'app_key_set' => !empty(config('app.key')),
    ]);
});

// Test database connection
Route::get('/debug/db-test', function () {
    if (!app()->environment('local', 'staging')) {
        abort(404);
    }
    
    try {
        \DB::connection()->getPdo();
        $canConnect = true;
        
        // Check if sessions table exists
        $tablesExist = \Schema::hasTable('sessions');
        
        $sessionCount = $tablesExist ? \DB::table('sessions')->count() : 0;
        
    } catch (\Exception $e) {
        $canConnect = false;
        $tablesExist = false;
        $sessionCount = 0;
    }
    
    return response()->json([
        'database_connected' => $canConnect,
        'sessions_table_exists' => $tablesExist,
        'active_sessions' => $sessionCount,
    ]);
});

// Test session write
Route::get('/debug/session-write', function () {
    if (!app()->environment('local', 'staging')) {
        abort(404);
    }
    
    session(['test_key' => 'test_value_' . time()]);
    
    return response()->json([
        'session_id' => session()->getId(),
        'test_value' => session('test_key'),
        'all_session_data' => session()->all(),
    ]);
});

// Test auth
Route::get('/debug/auth-test', function () {
    if (!app()->environment('local', 'staging')) {
        abort(404);
    }
    
    return response()->json([
        'authenticated' => auth()->check(),
        'user_id' => auth()->id(),
        'user_email' => auth()->user()?->email,
        'session_id' => session()->getId(),
    ]);
})->middleware('auth');
