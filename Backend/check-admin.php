<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = App\Models\User::where('email', 'pauljohns730@gmail.com')->first();

if (!$user) {
    echo "❌ User NOT FOUND\n";
    exit(1);
}

echo "✅ Super admin found:\n";
echo "   Email: {$user->email}\n";
echo "   Name:  {$user->name}\n";
echo "   is_super_admin: " . ($user->is_super_admin ? 'true' : 'false') . "\n";
echo "   Organization: {$user->organization->name}\n";
