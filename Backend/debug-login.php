<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Auth;
use App\Models\User;

// Find the user
$user = User::where('email', 'pauljohns730@gmail.com')->first();

if (!$user) {
    echo "❌ User not found\n";
    exit(1);
}

echo "User details:\n";
echo "  ID: {$user->id}\n";
echo "  Email: {$user->email}\n";
echo "  Name: {$user->name}\n";
echo "  is_super_admin (raw): " . var_export($user->is_super_admin, true) . "\n";
echo "  is_super_admin (bool): " . ($user->is_super_admin ? 'TRUE' : 'FALSE') . "\n";
echo "  is_super_admin (int): " . (int)$user->is_super_admin . "\n";

// Check if isSuperAdmin() method exists
if (method_exists($user, 'isSuperAdmin')) {
    echo "  isSuperAdmin() method: " . ($user->isSuperAdmin() ? 'TRUE' : 'FALSE') . "\n";
} else {
    echo "  ❌ isSuperAdmin() method NOT FOUND\n";
}

echo "\nDatabase check:\n";
$raw = \DB::table('users')->where('email', 'pauljohns730@gmail.com')->first(['is_super_admin']);
echo "  Raw DB value: " . var_export($raw->is_super_admin, true) . "\n";
