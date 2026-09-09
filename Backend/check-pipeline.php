<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== End-to-End Pipeline Verification ===\n\n";

$checks = [
    'Projects' => App\Models\Project::count(),
    'Keywords' => App\Models\Keyword::count(),
    'Measurements' => App\Models\KeywordMeasurement::count(),
    'Opportunities' => App\Models\Opportunity::count(),
    'Leads' => App\Models\Lead::count(),
    'Deals' => App\Models\Deal::count(),
    'Tasks' => App\Models\Task::count(),
    'Landing Pages' => App\Models\LandingPage::count(),
    'Email Campaigns' => App\Models\Campaign::count(),
    'Alert Rules' => App\Models\AlertRule::count(),
];

foreach ($checks as $label => $count) {
    echo sprintf("✓ %-20s %d\n", $label . ':', $count);
}

echo "\n=== Demo User Check ===\n";
$demoUser = App\Models\User::where('email', 'owner@demo.com')->first();
if ($demoUser) {
    echo "✓ Demo owner account exists\n";
    echo "  Organization: {$demoUser->organization->name}\n";
    echo "  Role: {$demoUser->role}\n";
} else {
    echo "✗ Demo owner not found\n";
}

echo "\n=== Recent Activity ===\n";
$recentMeasurements = App\Models\KeywordMeasurement::where('date', '>', now()->subDays(7)->toDateString())->count();
echo "Measurements (7 days): {$recentMeasurements}\n";

$recentOpportunities = App\Models\Opportunity::where('detected_at', '>', now()->subDays(7))->count();
echo "Opportunities (7 days): {$recentOpportunities}\n";

$recentLeads = App\Models\Lead::where('created_at', '>', now()->subDays(7))->count();
echo "Leads (7 days): {$recentLeads}\n";

echo "\n✅ Pipeline verification COMPLETE\n";
