# Database Column Mismatches - Fixed

## Overview
Multiple controllers and services were using incorrect column names that didn't match the actual database schema. This document lists all the fixes applied.

---

## 1. Keywords Table

### Issue
Code was using `term` but database has `keyword`

### Actual Schema
```sql
keyword (varchar) ✅
normalized_keyword (varchar) ✅
term ❌ Does not exist
```

### Files Fixed
- `app/Models/Keyword.php` - Updated fillable array
- `app/Jobs/CollectKeywordData.php` - Changed `$keyword->term` to `$keyword->keyword`

---

## 2. Keyword Locations Table

### Issue
Code was using `location` but database has `country`, `region`, `city`

### Actual Schema
```sql
country (varchar(2)) ✅
region (varchar) ✅
city (varchar) ✅
location ❌ Does not exist
```

### Files Fixed
- `app/Jobs/CollectKeywordData.php` - Changed `$location->location` to `$location->country`

---

## 3. Keyword Measurements Table

### Issue
Code was using `measured_at` but database has `date`

### Actual Schema
```sql
date (date) ✅
measured_at ❌ Does not exist
```

### Files Fixed
- `app/Services/Demand/TrendEngine.php` - All queries changed to use `date`
- `app/Services/Demand/BaselineService.php` - All queries changed to use `date`
- `routes/console.php` - Scheduler queries fixed
- `app/Jobs/CollectKeywordData.php` - Storage queries fixed

---

## 4. Opportunities Table

### Issue
Code was using `score_label` but database only has `opportunity_score` (numeric)

### Actual Schema
```sql
opportunity_score (decimal 0-100) ✅
growth_score (decimal) ✅
intent_score (decimal) ✅
geo_score (decimal) ✅
volume_score (decimal) ✅
competition_score (decimal) ✅
historical_score (decimal) ✅
score_label ❌ Does not exist
```

### Score Label Logic (Calculated Dynamically)
```php
CASE 
    WHEN opportunity_score >= 80 THEN "very_high"  // 80-100
    WHEN opportunity_score >= 60 THEN "high"       // 60-79
    WHEN opportunity_score >= 40 THEN "moderate"   // 40-59
    ELSE "low"                                     // 0-39
END
```

### Files Fixed
- `app/Http/Controllers/Web/AnalyticsController.php`
  - `getAnalyticsData()` method - Changed WHERE clauses to use score ranges
  - `getOpportunityScoresData()` method - Added CASE statement to calculate labels

---

## 5. Deals Table

### Issue
Code was using `closed_at` and stages `closed_won`/`closed_lost`, but database has `won_at`/`lost_at` and stages `won`/`lost`

### Actual Schema
```sql
stage (enum: new, contacted, qualified, quotation, negotiation, won, lost) ✅
won_at (timestamp) ✅
lost_at (timestamp) ✅
expected_close_at (timestamp) ✅
closed_at ❌ Does not exist
closed_won ❌ Stage value doesn't exist (use 'won')
closed_lost ❌ Stage value doesn't exist (use 'lost')
```

### Files Fixed
- `app/Http/Controllers/Web/AnalyticsController.php`
  - `getAnalyticsData()` method:
    - Changed `closed_at` → `won_at`
    - Changed `stage = 'closed_won'` → `stage = 'won'`
    - Changed `stage = 'closed_lost'` → `stage = 'lost'`
  - `getRevenueChartData()` method:
    - Changed all `closed_at` → `won_at`
    - Changed `closed_won` → `won`
  - `exportDealsData()` method:
    - Changed to use `won_at ?? lost_at` for closed date

---

## 6. Alerts Table

### Issue
Seeder was using `delivered` status which doesn't exist

### Actual Schema
```sql
status (enum: pending, sent, failed, read) ✅
delivered ❌ Does not exist
```

### Files Fixed
- `database/seeders/DemoDataSeeder.php` - Changed `'delivered'` to `'sent'`

**Note:** The `campaign_recipients` table DOES support `'delivered'` status, so it's only an issue in the alerts table.

---

## 7. Leads Table ✅

### Status
**No issues found** - This table is correctly implemented

### Schema
```sql
lead_score (decimal) ✅
score_label (enum: hot, warm, potential, low) ✅
status (enum: new, contacted, qualified, nurturing, converted, lost) ✅
```

---

## Pattern Identified

The codebase has inconsistent naming conventions:

| Concept | Some tables use | Other tables use |
|---------|----------------|------------------|
| Timestamp | `*_at` suffix | `date` column |
| Completion | `closed_at` | `won_at`, `lost_at` |
| Status values | `closed_won` | `won` |
| Text field | `term` | `keyword` |
| Location | `location` | `country`, `region`, `city` |
| Score category | `score_label` column | Calculated from `*_score` |

---

## Recommendation

For future development, consider:

1. **Add missing columns** to match code expectations, OR
2. **Update all code** to match actual schema consistently
3. **Add database accessor methods** to models for calculated fields like `score_label`
4. **Document schema** in README with actual column names
5. **Add integration tests** to catch column mismatches early

---

## Quick Reference: Column Name Mapping

| Code Expected | Actual Database Column |
|---------------|----------------------|
| `keywords.term` | `keywords.keyword` |
| `keyword_locations.location` | `keyword_locations.country` |
| `keyword_measurements.measured_at` | `keyword_measurements.date` |
| `opportunities.score_label` | Calculate from `opportunities.opportunity_score` |
| `deals.closed_at` | `deals.won_at` or `deals.lost_at` |
| `deals.stage = 'closed_won'` | `deals.stage = 'won'` |
| `deals.stage = 'closed_lost'` | `deals.stage = 'lost'` |
| `alerts.status = 'delivered'` | `alerts.status = 'sent'` |

---

## All Files Modified

1. ✅ `app/Models/Keyword.php`
2. ✅ `app/Jobs/CollectKeywordData.php`
3. ✅ `app/Services/Demand/TrendEngine.php`
4. ✅ `app/Services/Demand/BaselineService.php`
5. ✅ `app/Http/Controllers/Web/AnalyticsController.php`
6. ✅ `database/seeders/DemoDataSeeder.php`
7. ✅ `routes/console.php`

---

## Testing Checklist

- [x] Keyword collection job runs without errors
- [x] Seeder completes successfully
- [ ] Analytics dashboard loads without errors (test now)
- [ ] Trend detection works correctly
- [ ] Opportunity scoring calculates properly
- [ ] Deal reporting shows correct data

---

**Status:** All known column mismatches have been fixed. Analytics page should now work correctly.
