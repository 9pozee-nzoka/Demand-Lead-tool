#!/bin/bash

echo "================================"
echo "Session Configuration Check"
echo "================================"
echo ""

# Check what .env says
echo "1. .env Session Settings:"
grep "SESSION" .env | grep -v "^#"
echo ""

# Check what Laravel is actually using
echo "2. Cached Config (what Laravel uses):"
php artisan tinker --execute="
echo 'SESSION_DRIVER: ' . config('session.driver') . PHP_EOL;
echo 'SESSION_ENCRYPT: ' . (config('session.encrypt') ? 'true' : 'false') . PHP_EOL;
echo 'SESSION_SECURE: ' . (config('session.secure') ? 'true' : 'false') . PHP_EOL;
echo 'SESSION_DOMAIN: ' . config('session.domain') . PHP_EOL;
echo 'SESSION_SAME_SITE: ' . config('session.same_site') . PHP_EOL;
"
echo ""

# Check session storage
SESSION_DRIVER=$(php artisan tinker --execute="echo config('session.driver');" 2>/dev/null)

if [ "$SESSION_DRIVER" = "file" ]; then
    echo "3. File Driver - Checking directory:"
    ls -la storage/framework/sessions/ 2>/dev/null || echo "  Directory doesn't exist!"
    echo ""
elif [ "$SESSION_DRIVER" = "database" ]; then
    echo "3. Database Driver - Checking table:"
    php artisan tinker --execute="
    try {
        echo Schema::hasTable('sessions') ? 'Table exists' : 'Table MISSING!';
    } catch (Exception \$e) {
        echo 'Error: ' . \$e->getMessage();
    }
    " 2>/dev/null
    echo ""
fi

echo "4. Test Session Write:"
php artisan tinker --execute="
try {
    session()->put('test_key', 'test_value');
    session()->save();
    echo 'Session write: OK' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Session write FAILED: ' . \$e->getMessage() . PHP_EOL;
}
"
echo ""

echo "================================"
echo "Recommended Fix:"
echo "================================"
echo "php artisan config:clear"
echo "php artisan cache:clear"
echo "chmod -R 775 storage/framework/sessions"
echo "php artisan config:cache"
echo "sudo systemctl restart php-fpm"
