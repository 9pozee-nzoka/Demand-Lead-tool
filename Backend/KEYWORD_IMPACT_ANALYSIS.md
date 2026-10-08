# Keyword Schema Changes - Impact Analysis

## Why Keyword Changes Affect Production

### The Schema Changes Made:

**Migration:** `2026_10_07_115216_add_missing_columns_to_keywords_table.php`

Added columns:
- `term` (string, nullable)
- `match_type` (enum: exact, phrase, broad)
- `notes` (text, nullable)

### Impact on Production

#### ✅ When Migrations ARE Run (Current State)
Production works because:
1. All columns exist in the database
2. Controllers can insert/update keywords successfully
3. Dashboard queries work (don't rely on new columns)
4. Existing keywords have NULL for new columns (which is fine)

#### ❌ If Migrations Were NOT Run

Production would break in these scenarios:

### 1. **Creating New Keywords**
**Location:** `app/Http/Controllers/Web/KeywordController.php:59`

```php
$keyword = Keyword::create([
    'project_id' => $validated['project_id'],
    'keyword' => $validated['term'],
    'term' => $validated['term'],           // ← Would fail: column doesn't exist
    'match_type' => $validated['match_type'], // ← Would fail: column doesn't exist
    'notes' => $validated['notes'] ?? null,   // ← Would fail: column doesn't exist
    'status' => 'active',
]);
```

**Error:** `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'term'`

### 2. **API Keyword Creation**
**Location:** `app/Http/Controllers/Api/KeywordController.php`

Similar issue if API routes are used.

### 3. **Keyword Forms**
**Location:** `resources/views/keywords/create.blade.php`, `resources/views/keywords/edit.blade.php`

Forms expect these fields - submissions would fail.

---

## Why Production Worked BEFORE Migration

The app worked before because:

1. **Dashboard doesn't SELECT new columns**
   - Query: `Keyword::where('status', 'active')->count()`
   - This only counts, doesn't try to access `term`, `match_type`, or `notes`

2. **No one tried to CREATE keywords**
   - Reading existing keywords works fine
   - Only INSERT/UPDATE operations would fail

3. **Root route (`/`) doesn't touch keywords**
   - Just checks auth and redirects
   - Keyword table not involved

---

## What Happens in Each Scenario

### Scenario A: Migration Run ✅
```bash
# Schema has all columns
php artisan migrate --force

# Result: Everything works
- Dashboard loads ✅
- Can create keywords ✅
- Can update keywords ✅
- Forms work ✅
```

### Scenario B: Migration NOT Run ❌
```bash
# Schema missing: term, match_type, notes

# Result: Mixed behavior
- Dashboard loads ✅ (just counts, doesn't use new columns)
- Login works ✅ (doesn't touch keywords)
- Home page works ✅ (doesn't touch keywords)
- Creating keywords FAILS ❌ (tries to insert into non-existent columns)
- Keyword forms FAIL ❌ (tries to access non-existent columns)
```

---

## Affected Routes

### ✅ Safe Routes (work without migration)
- `GET /` - Home page
- `GET /login` - Login page
- `GET /dashboard` - Dashboard (only counts)
- `GET /projects` - Projects listing

### ❌ Breaking Routes (require migration)
- `POST /keywords` - Create keyword
- `PUT /keywords/{id}` - Update keyword
- `GET /keywords/create` - Create form (if it references new fields)
- `GET /keywords/{id}/edit` - Edit form (if it references new fields)

---

## Why Database Credentials Were the Real Issue

The 500 error you saw was **NOT** from missing columns. It was from:

1. **Wrong database credentials cached**
   - Could not connect to database at all
   - `auth()->check()` in root route failed
   - Result: 500 error on EVERY page

2. **Once credentials fixed:**
   - Database connection works
   - Dashboard works (doesn't need new columns)
   - Home/login work (don't touch keywords)
   - **Only keyword creation would fail** (needs new columns)

---

## Current Production State

✅ **All migrations run**  
✅ **Database credentials correct**  
✅ **Schema up to date**  
✅ **All features working**

---

## Verification Commands

### Check if migration was run:
```bash
php artisan migrate:status | grep add_missing_columns_to_keywords
```

### Check if columns exist:
```bash
php artisan tinker --execute="
echo 'term: ' . (Schema::hasColumn('keywords', 'term') ? 'YES' : 'NO') . PHP_EOL;
echo 'match_type: ' . (Schema::hasColumn('keywords', 'match_type') ? 'YES' : 'NO') . PHP_EOL;
echo 'notes: ' . (Schema::hasColumn('keywords', 'notes') ? 'YES' : 'NO') . PHP_EOL;
"
```

### Test keyword creation:
```bash
php artisan tinker --execute="
\$project = App\Models\Project::first();
if (\$project) {
    try {
        \$keyword = App\Models\Keyword::create([
            'project_id' => \$project->id,
            'keyword' => 'test',
            'term' => 'test',
            'normalized_keyword' => 'test',
            'match_type' => 'phrase',
            'priority' => 'medium',
            'status' => 'active',
        ]);
        echo 'SUCCESS: Keyword created ID ' . \$keyword->id . PHP_EOL;
    } catch (Exception \$e) {
        echo 'ERROR: ' . \$e->getMessage() . PHP_EOL;
    }
} else {
    echo 'No projects found' . PHP_EOL;
}
"
```

---

## Summary

**Why changes affected production:**
- Web controller tries to INSERT `term`, `match_type`, `notes`
- If columns don't exist → SQL error → 500 on keyword creation

**Why dashboard still worked:**
- Dashboard only COUNTs keywords
- Doesn't try to access the new columns
- Only reads `status` field

**Why 500 error was everywhere:**
- Database credentials were wrong
- Had nothing to do with keyword schema
- Once credentials fixed, most pages worked
- Only keyword forms would break without migration

**Current status:**
- ✅ Everything fixed and working
