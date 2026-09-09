# Seeder Fix: Invalid Alert Status

## Issue
DemoDataSeeder was failing with:
```
SQLSTATE[01000]: Warning: 1265 Data truncated for column 'status' at row 1
```

## Root Cause
The `alerts` table has an ENUM constraint for the `status` column:
```sql
enum('pending','sent','failed','read')
```

But the seeder was trying to insert `'delivered'` which is not a valid value.

## Fix
**File:** `database/seeders/DemoDataSeeder.php`  
**Line:** 934

**Before:**
```php
'status' => ['sent', 'sent', 'delivered', 'pending'][rand(0,3)],
```

**After:**
```php
'status' => ['sent', 'sent', 'sent', 'pending'][rand(0,3)],
```

## Note
The `campaign_recipients` table DOES support `'delivered'` status:
```sql
enum('pending','sending','sent','delivered','opened','clicked','bounced','complained','unsubscribed')
```

So campaign recipients can be marked as 'delivered', but alerts cannot.

## Verification
After fix, seeder runs successfully:
```bash
php artisan db:seed --class=DemoDataSeeder
```

Results:
- ✅ 2 Organizations
- ✅ 7 Users
- ✅ 3 Projects
- ✅ 18 Keywords
- ✅ 360 Measurements (30 days × 4 keywords × 3 projects)
- ✅ 7 Opportunities
- ✅ 12 Leads
- ✅ 8 Deals
- ✅ 4 Alerts (with valid statuses)
- ✅ 4 Email Campaigns

## Login Credentials
```
Owner     : owner@demo.com      / password
Admin     : admin@demo.com      / password
Analyst   : analyst@demo.com    / password
Sales     : sales@demo.com      / password
Marketing : marketing@demo.com  / password
```
