# Schema Fix - Missing Columns in Keywords Table

## Issue Found

```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'match_type' in 'field list'
```

The `keywords` table was missing several columns that the controllers expected:
- `term` (controller uses this, but table only had `keyword`)
- `match_type` (enum: exact, phrase, broad)
- `notes` (text field)

## What Was Fixed

### 1. Created Migration
**File:** `database/migrations/2026_10_07_115216_add_missing_columns_to_keywords_table.php`

Adds:
- `term` column (string, nullable, after `keyword`)
- `match_type` enum column (default: 'phrase')
- `notes` text column
- Copies existing `keyword` values to `term` for backward compatibility

### 2. Fixed Web Controller
**File:** `app/Http/Controllers/Web/KeywordController.php`

Changed `keyword_locations` creation from:
```php
'location' => strtoupper($location),  // ❌ Wrong field
```

To:
```php
'country' => strtoupper($location),   // ✅ Correct field
'type' => 'country',
```

## Production Deployment

Run these commands on production:

```bash
cd ~/soarcorp/Demand-Lead-tool/Backend

# 1. Pull latest code
git pull origin main

# 2. Run migration
php artisan migrate --force

# 3. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan config:cache

# 4. Restart PHP
sudo systemctl restart php-fpm
```

## Verification

After deployment, verify the columns exist:

```bash
php artisan tinker
>>> Schema::hasColumn('keywords', 'term')       # Should be: true
>>> Schema::hasColumn('keywords', 'match_type') # Should be: true
>>> Schema::hasColumn('keywords', 'notes')      # Should be: true
>>> exit
```

Or via MySQL:

```sql
SHOW COLUMNS FROM keywords;
```

## Root Cause

The initial migration (`2024_01_01_000003_create_projects_and_keywords_tables.php`) created the table with only `keyword`, but:
- Web controllers expected `term` field
- Forms and validation rules expected `match_type` field
- Forms expected `notes` field

This mismatch occurred because:
1. The API controllers use `keyword` (RESTful approach)
2. The Web controllers use `term` (form-based approach)
3. Migration was created for API-first, but web routes were added later

## Future Prevention

- Always check both API and Web controllers when creating migrations
- Keep model `$fillable` array in sync with actual table columns
- Run `php artisan migrate:fresh --seed` regularly in dev to catch issues early
