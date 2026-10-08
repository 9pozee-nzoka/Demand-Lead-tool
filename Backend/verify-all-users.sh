#!/bin/bash

echo "========================================="
echo "Verifying All Users - Production Fix"
echo "========================================="
echo ""

cd ~/soarcorp/Demand-Lead-tool/Backend

# 1. Check how many users are unverified
echo "1. Checking unverified users..."
php artisan tinker --execute="
\$unverified = App\Models\User::whereNull('email_verified_at')->count();
\$total = App\Models\User::count();
echo 'Total users: ' . \$total . PHP_EOL;
echo 'Unverified users: ' . \$unverified . PHP_EOL;
"
echo ""

# 2. Auto-verify all users
echo "2. Auto-verifying all users..."
php artisan tinker --execute="
\$updated = App\Models\User::whereNull('email_verified_at')->update(['email_verified_at' => now()]);
echo 'Updated ' . \$updated . ' users' . PHP_EOL;
"
echo ""

# 3. Verify the fix
echo "3. Verification check..."
php artisan tinker --execute="
\$unverified = App\Models\User::whereNull('email_verified_at')->count();
if (\$unverified === 0) {
    echo '✅ All users are now verified!' . PHP_EOL;
} else {
    echo '⚠️  Still ' . \$unverified . ' unverified users' . PHP_EOL;
}
"
echo ""

echo "========================================="
echo "Done! Users can now log in to dashboard"
echo "========================================="
