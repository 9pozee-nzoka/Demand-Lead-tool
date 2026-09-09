# Analytics Controller Fix

## Issue
Analytics dashboard was showing "Internal Server Error" due to missing `score_label` column in the `opportunities` table.

**Error:**
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'score_label' in 'where clause'
```

## Root Cause
The `AnalyticsController` was trying to query `score_label` column on `opportunities` table, but this column doesn't exist. The table only has `opportunity_score` (numeric 0-100).

## Database Schema

### Opportunities Table
- ✅ `opportunity_score` (decimal 0-100)
- ❌ `score_label` (does NOT exist)

### Leads Table  
- ✅ `lead_score` (decimal 0-100)
- ✅ `score_label` (enum: 'hot', 'warm', 'potential', 'low')

## Fix Applied

### 1. High Opportunities Count
**Before:**
```php
'high_opportunities' => Opportunity::where('organization_id', $organizationId)
    ->whereBetween('created_at', [$startDate, $endDate])
    ->where('score_label', 'very_high')
    ->orWhere('score_label', 'high')
    ->count(),
```

**After:**
```php
'high_opportunities' => Opportunity::where('organization_id', $organizationId)
    ->whereBetween('created_at', [$startDate, $endDate])
    ->where(function($q) {
        $q->where('opportunity_score', '>=', 80) // VERY HIGH (80-100)
          ->orWhereBetween('opportunity_score', [60, 79]); // HIGH (60-79)
    })
    ->count(),
```

### 2. Opportunity Scores Distribution Chart
**Before:**
```php
$data = Opportunity::where('organization_id', $organizationId)
    ->whereBetween('created_at', [$startDate, $endDate])
    ->selectRaw('score_label, COUNT(*) as count')
    ->groupBy('score_label')
    ->get();
```

**After:**
```php
$data = Opportunity::where('organization_id', $organizationId)
    ->whereBetween('created_at', [$startDate, $endDate])
    ->selectRaw('
        CASE 
            WHEN opportunity_score >= 80 THEN "very_high"
            WHEN opportunity_score >= 60 THEN "high"
            WHEN opportunity_score >= 40 THEN "moderate"
            ELSE "low"
        END as score_label,
        COUNT(*) as count
    ')
    ->groupBy('score_label')
    ->get();
```

## Score Thresholds

As per the architecture document:

| Score Range | Label | Description |
|-------------|-------|-------------|
| 80-100 | VERY HIGH | Top priority opportunities |
| 60-79 | HIGH | Strong opportunities |
| 40-59 | MODERATE | Worth monitoring |
| 0-39 | LOW | Low priority |

## Testing

Visit `/analytics` - should now work without errors.

## Files Modified
- `app/Http/Controllers/Web/AnalyticsController.php`

## Note
The `leads` table correctly has both `lead_score` and `score_label` columns, so lead-related analytics queries work fine.
