#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Route;
use App\Models\User;

// Login as demo user
$user = User::where('email', 'owner@demo.com')->first();
if (!$user) {
    die("Demo user not found\n");
}

// Auth the user
auth()->login($user);

// Get all web routes
$routes = collect(Route::getRoutes())->filter(function ($route) {
    return in_array('GET', $route->methods()) && 
           in_array('web', $route->gatherMiddleware()) &&
           !str_contains($route->uri(), 'api/') &&
           !str_contains($route->uri(), 'horizon/') &&
           !str_contains($route->uri(), 'sanctum/') &&
           !str_contains($route->uri(), '{');
});

echo "Testing " . $routes->count() . " routes...\n\n";

$passed = 0;
$failed = 0;
$errors = [];

foreach ($routes as $route) {
    $uri = '/' . $route->uri();
    
    try {
        $response = app()->handle(
            \Illuminate\Http\Request::create($uri, 'GET')
        );
        
        $status = $response->getStatusCode();
        
        if ($status >= 200 && $status < 300) {
            $passed++;
            echo "✓ {$uri} ({$status})\n";
        } elseif ($status >= 300 && $status < 400) {
            // Redirects are OK
            $passed++;
            echo "→ {$uri} ({$status})\n";
        } else {
            $failed++;
            echo "✗ {$uri} ({$status})\n";
            $errors[] = "{$uri} → {$status}";
        }
    } catch (\Exception $e) {
        $failed++;
        echo "✗ {$uri} - ERROR: " . get_class($e) . ": " . $e->getMessage() . "\n";
        $errors[] = "{$uri} → " . get_class($e) . ": " . substr($e->getMessage(), 0, 100);
    }
}

echo "\n--- SUMMARY ---\n";
echo "✅ Passed: $passed\n";
echo "❌ Failed: $failed\n";

if (count($errors) > 0) {
    echo "\n--- ERRORS ---\n";
    foreach ($errors as $error) {
        echo "  $error\n";
    }
}
