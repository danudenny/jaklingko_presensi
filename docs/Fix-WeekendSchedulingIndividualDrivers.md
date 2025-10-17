# Fix: Weekend Scheduling - Ensure At Least One Driver Works Each Weekend Day

## Issue Reported
Some drivers were not getting scheduled on **both** Saturday AND Sunday, leaving them with **2 empty weekend days**. This violates the requirement that each driver should work **at least one weekend day**.

## Root Cause

### The Bug
The `shouldBatanganSkipDay()` method was checking if **either** driver had that day off and would skip **both drivers** if true:

```php
// ❌ WRONG LOGIC:
if ($dayOfWeek === $driverPagiOffDay || $dayOfWeek === $driverSiangOffDay) {
    return true; // Skip BOTH drivers
}
```

### What Happened
```
Driver A: Saturday OFF (day 6)
Driver B: Sunday OFF (day 0)

Saturday check:
- Driver A has Saturday off → returns TRUE
- Result: BOTH drivers skip Saturday ❌

Sunday check:  
- Driver B has Sunday off → returns TRUE
- Result: BOTH drivers skip Sunday ❌

Net effect: No one works EITHER weekend day! ❌❌
```

## The Fix

### Updated Logic

**1. `shouldBatanganSkipDay()` - Only returns true if BOTH drivers are off:**
```php
// ✅ CORRECT LOGIC:
if ($dayOfWeek === $driverPagiOffDay && $dayOfWeek === $driverSiangOffDay) {
    return true; // Skip only if BOTH off (rare/never)
}
return false; // At least one driver works
```

**2. `assignBatanganSimple()` - Check each driver individually:**
```php
// Get each driver's off day
$driverPagiOffDay = $isWeekend ? $this->getDriverWeekendOffDay($driverPagi->id, $unitId) : -1;
$driverSiangOffDay = $isWeekend ? $this->getDriverWeekendOffDay($driverSiang->id, $unitId) : -1;

// PAGI driver assignment
if ($isWeekend && $dayOfWeek === $driverPagiOffDay) {
    Log::info("⏭️ Driver skips - weekend rest day");
    // Skip ONLY this driver, continue to SIANG
} elseif (...) {
    // Assign PAGI shift
}

// SIANG driver assignment  
if ($isWeekend && $dayOfWeek === $driverSiangOffDay) {
    Log::info("⏭️ Driver skips - weekend rest day");
    // Skip ONLY this driver
} elseif (...) {
    // Assign SIANG shift
}
```

## New Behavior

### Scenario 1: Different Weekend Days Off
```
Driver A (PAGI): Saturday OFF
Driver B (SIANG): Sunday OFF

Saturday:
✓ Driver A: SKIPS (their off day)
✓ Driver B: WORKS SIANG (not their off day)
✓ Result: Cadangan fills PAGI shift

Sunday:
✓ Driver A: WORKS PAGI (not their off day)
✓ Driver B: SKIPS (their off day)
✓ Result: Cadangan fills SIANG shift
```

### Scenario 2: Same Weekend Day Off (rare, but possible)
```
Driver A (PAGI): Saturday OFF
Driver B (SIANG): Saturday OFF

Saturday:
✓ Both drivers SKIP (both have off day)
✓ Result: Cadangan fills BOTH shifts

Sunday:
✓ Driver A: WORKS PAGI
✓ Driver B: WORKS SIANG
✓ Result: Full batangan coverage
```

## Code Changes

### File: `app/Services/ScheduleGeneratorService.php`

#### Change 1: Updated `shouldBatanganSkipDay()`
```php
// Before:
if ($dayOfWeek === $driverPagiOffDay || $dayOfWeek === $driverSiangOffDay) {
    return true;
}

// After:
if ($dayOfWeek === $driverPagiOffDay && $dayOfWeek === $driverSiangOffDay) {
    return true; // Both off - skip day (rare)
}
return false; // At least one driver works
```

#### Change 2: Updated `assignBatanganSimple()` 
Added individual driver weekend checks:

```php
// Get weekend off days for individual driver checks
$dayOfWeek = $date->dayOfWeek;
$isWeekend = ($dayOfWeek === 0 || $dayOfWeek === 6);
$driverPagiOffDay = $isWeekend ? $this->getDriverWeekendOffDay($driverPagi->id, $unitId) : -1;
$driverSiangOffDay = $isWeekend ? $this->getDriverWeekendOffDay($driverSiang->id, $unitId) : -1;

// Check PAGI driver individually
if ($isWeekend && $dayOfWeek === $driverPagiOffDay) {
    // Skip only PAGI driver
} elseif (...) {
    // Assign PAGI
}

// Check SIANG driver individually  
if ($isWeekend && $dayOfWeek === $driverSiangOffDay) {
    // Skip only SIANG driver
} elseif (...) {
    // Assign SIANG
}
```

## Expected Results

### Weekly Schedule Example
```
Week View:
        Mon  Tue  Wed  Thu  Fri  Sat  Sun
───────────────────────────────────────────
Driver A 
(PAGI)   ✓    ✓    ✓    ✓    ✓    -    ✓
         Works Works Works Works Works OFF  Works

Driver B
(SIANG)  ✓    ✓    ✓    ✓    ✓    ✓    -
         Works Works Works Works Works Works OFF

Coverage:
Sat:  1 shift from batangan (SIANG), PAGI filled by cadangan ✓
Sun:  1 shift from batangan (PAGI), SIANG filled by cadangan ✓
```

### Monthly Statistics
```
30-day month:

Driver A (PAGI, Sat OFF):
- Weekdays: 20 days (Mon-Fri × 4 weeks)
- Saturdays: 0 days (all off)
- Sundays: 4 days (all work)
- Max: 12 days (hits limit ~Day 14)
- Actual: 12 days ✓

Driver B (SIANG, Sun OFF):
- Weekdays: 20 days (Mon-Fri × 4 weeks)
- Saturdays: 4 days (all work)
- Sundays: 0 days (all off)
- Max: 12 days (hits limit ~Day 14)
- Actual: 12 days ✓

Weekend Coverage:
- Saturdays (4 days): 4 SIANG shifts (batangan) + 4 PAGI shifts (cadangan) = 8 shifts ✓
- Sundays (4 days): 4 PAGI shifts (batangan) + 4 SIANG shifts (cadangan) = 8 shifts ✓
```

## Testing

### Test Case 1: Verify Each Driver Works At Least One Weekend Day
```sql
-- Check that every batangan driver has at least one weekend shift
SELECT 
    d.id,
    d.name,
    d.type,
    SUM(CASE WHEN DAYOFWEEK(s.schedule_date) = 7 THEN 1 ELSE 0 END) as saturday_shifts,
    SUM(CASE WHEN DAYOFWEEK(s.schedule_date) = 1 THEN 1 ELSE 0 END) as sunday_shifts,
    SUM(CASE WHEN DAYOFWEEK(s.schedule_date) IN (1,7) THEN 1 ELSE 0 END) as total_weekend_shifts
FROM drivers d
LEFT JOIN schedules s ON d.id = s.driver_id
    AND s.schedule_date BETWEEN '2024-11-01' AND '2024-11-30'
WHERE d.type = 'batangan'
  AND d.status = 'aktif'
GROUP BY d.id, d.name, d.type
HAVING total_weekend_shifts = 0;

-- Expected Result: 0 rows (all batangan drivers have weekend shifts)
```

### Test Case 2: Verify Weekend Coverage
```sql
-- Check that all weekend days have at least 1 shift covered
SELECT 
    schedule_date,
    DAYNAME(schedule_date) as day_name,
    COUNT(*) as total_shifts,
    SUM(CASE WHEN d.type = 'batangan' THEN 1 ELSE 0 END) as batangan_shifts,
    SUM(CASE WHEN d.type = 'cadangan' THEN 1 ELSE 0 END) as cadangan_shifts
FROM schedules s
JOIN drivers d ON s.driver_id = d.id
WHERE s.schedule_date BETWEEN '2024-11-01' AND '2024-11-30'
  AND DAYOFWEEK(s.schedule_date) IN (1, 7)  -- Sunday = 1, Saturday = 7
GROUP BY s.schedule_date
HAVING total_shifts < 1;

-- Expected Result: 0 rows (all weekend days have coverage)
```

### Test Case 3: Verify Individual Driver Weekend Patterns
```sql
-- Verify each driver has consistent weekend off day
SELECT 
    d.id,
    d.name,
    GROUP_CONCAT(DISTINCT 
        CASE 
            WHEN DAYOFWEEK(s.schedule_date) = 7 THEN 'Works Saturday'
            WHEN DAYOFWEEK(s.schedule_date) = 1 THEN 'Works Sunday'
        END
    ) as weekend_pattern
FROM drivers d
LEFT JOIN schedules s ON d.id = s.driver_id
    AND s.schedule_date BETWEEN '2024-11-01' AND '2024-11-30'
    AND DAYOFWEEK(s.schedule_date) IN (1, 7)
WHERE d.type = 'batangan'
  AND d.status = 'aktif'
GROUP BY d.id, d.name;

-- Expected: Each driver has either "Works Saturday" OR "Works Sunday" (not both)
```

## Log Messages

### Before Fix (Wrong)
```
⏭️ Batangan drivers skip 2024-11-09 (Saturday) - weekend rest day
⏭️ Batangan drivers skip 2024-11-10 (Sunday) - weekend rest day
```
Result: NO coverage on either day ❌

### After Fix (Correct)
```
⏭️ Driver Alice (1) skips 2024-11-09 (Saturday) - weekend rest day
✓ Batangan SIANG: Driver Bob (2) assigned on 2024-11-09 (Monthly: 5/12)

✓ Batangan PAGI: Driver Alice (1) assigned on 2024-11-10 (Monthly: 6/12)
⏭️ Driver Bob (2) skips 2024-11-10 (Sunday) - weekend rest day
```
Result: Each weekend day has 1 batangan + cadangan fills other shift ✓

## Summary

### Problem
- Both drivers were being skipped on weekends when only one should skip
- Led to completely empty weekend days for some drivers

### Solution  
- Check drivers **individually** for their weekend off day
- Only skip the **specific driver** who has that day off
- Allow the other driver to work their shift

### Result
- ✅ Each driver works **at least one weekend day** (either Sat or Sun)
- ✅ Each driver gets **one weekend day off** (6-day work week)
- ✅ Weekend coverage maintained (batangan + cadangan)
- ✅ Fair and balanced scheduling

### Impact
- **No database changes** required
- **Backward compatible** with existing schedules
- **Immediate effect** on new schedule generation
- **Self-correcting** on schedule regeneration
