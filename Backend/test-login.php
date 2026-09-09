<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Hash;

$user = App\Models\User::where('email', 'pauljohns730@gmail.com')->first();

if (!$user) {
    echo "❌ User not found\n";
    exit(1);
}

$password = 'Pozee@5268';
$passwordMatches = Hash::check($password, $user->password);

echo "Password check: " . ($passwordMatches ? '✅ CORRECT' : '❌ WRONG') . "\n";
echo "is_super_admin: " . ($user->is_super_admin ? '✅ true' : '❌ false') . "\n";

if ($passwordMatches && $user->is_super_admin) {
    echo "\n✅ Login credentials work! You can now:\n";
    echo "   1. Visit http://127.0.0.1:8000/login\n";
    echo "   2. Email: pauljohns730@gmail.com\n";
    echo "   3. Password: Pozee@5268\n";
    echo "   4. After login, dashboard is at /dashboard\n";
}
