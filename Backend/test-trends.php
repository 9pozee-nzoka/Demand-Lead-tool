<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\Providers\GoogleTrendsProvider;

echo "=== Google Trends Provider Test ===\n\n";

$provider = new GoogleTrendsProvider();

// Test configuration
echo "1. Configuration check:\n";
$isConfigured = $provider->isConfigured();
echo "   SerpApi key configured: " . ($isConfigured ? "YES" : "NO (using synthetic data)") . "\n\n";

// Test data fetching
echo "2. Fetching trend data for 'digital marketing'...\n";
$data = $provider->getInterestOverTime('digital marketing', 'US', 'today 3-m');

if ($data) {
    echo "   ✓ Data received\n";
    echo "   Timeline points: " . count($data['timeline']) . "\n";
    echo "   Average interest: " . $data['average_interest'] . "\n";
    echo "   Current interest: " . $data['current_interest'] . "\n";
    echo "   Max interest: " . $data['max_interest'] . "\n";
    echo "   Min interest: " . $data['min_interest'] . "\n\n";
    
    // Show recent 5 data points
    echo "   Recent 5 data points:\n";
    $recent = array_slice($data['timeline'], -5);
    foreach ($recent as $point) {
        echo "     {$point['date']}: {$point['value']}\n";
    }
} else {
    echo "   ✗ No data received\n";
}

echo "\n3. Connection test:\n";
$test = $provider->testConnection();
echo "   Status: " . ($test['success'] ? "✓ PASS" : "✗ FAIL") . "\n";
echo "   Message: {$test['message']}\n";
if (isset($test['data_points'])) {
    echo "   Data points: {$test['data_points']}\n";
}

echo "\n✅ Test complete\n";
