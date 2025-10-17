# Fix: Batangan Driver 12-Day Limit Enforcement

## Issue Reported
Batangan drivers were getting scheduled for 13, 14, 15, or even 16 days instead of the maximum 12 days as required.

## Root Causes Identified

### 1. **ScheduleCompletionService had higher limits**
```php
// BEFORE (WRONG):
const BATANGAN_BASE_MAX_SHIFTS = 14;  // ❌ Allowed 14 days
const CADANGAN_BASE_MAX_SHIFTS = 13;  // ❌ Allowed 13 days

// AFTER (CORRECT):
const BATANGAN_BASE_MAX_SHIFTS = 12;  // ✅ Maximum 12 days
const CADANGAN_BASE_MAX_SHIFTS = 12;  // ✅ Maximum 12 days
```

### 2. **ScheduleCompletionService had "Relaxed Constraints" Phase**
The completion service had a **Phase 3** that bypassed monthly limits to force schedule completion:

```php
// Phase 3: If still no driver, try with relaxed constraints
if (!$assignedDriver) {
    Log::info("🆘 Trying with relaxed constraints...");
    // This was BYPASSING the 12-day limit! ❌
    if ($this->canDriverTakeShiftRelaxed(...)) {
        // Force assignment even if over limit
    }
}
```

### 3. **Batangan drivers were being used to fill gaps**
The completion service was assigning batangan drivers to **any shift** (pagi or siang) to fill gaps, instead of only using cadangan drivers for completion.

## Fixes Applied

### Fix 1: Updated Max Shift Constants ✅
**File**: `app/Services/ScheduleCompletionService.php`

Changed both batangan and cadangan max shifts to 12 days.

### Fix 2: Removed Relaxed Constraints Phase ✅
**File**: `app/Services/ScheduleCompletionService.php`

**Before** - 3 Phases:
1. Try cadangan drivers
2. Try batangan drivers (any shift)
3. **Try with relaxed constraints (bypass limits)** ❌

**After** - 1 Phase:
1. Try cadangan drivers ONLY ✅
2. If no cadangan available, leave shift empty (don't force) ✅

**Removed**:
- Phase 2: Batangan driver fallback
- Phase 3: Relaxed constraints
- Method: `canDriverTakeShiftRelaxed()`

### Fix 3: Completion Service Only Uses Cadangan ✅

```php
// NOW: Only cadangan drivers fill gaps
$cadanganDrivers = $availableDrivers->where('type', self::DRIVER_TYPE_CADANGAN);

// Try to fill each missing shift with CADANGAN drivers ONLY
// Batangan drivers should already be assigned to their designated shifts
foreach ($missingShifts as $shift) {
    foreach ($sortedCadanganDrivers as $driver) {
        if ($this->canDriverTakeShift($driver, ...)) {
            // Assign cadangan driver
        }
    }
    
    if (!$assignedDriver) {
        // Log warning but DON'T force assignment ✅
        Log::warning("No cadangan driver available - shift remains empty");
    }
}
```

## New Schedule Generation Flow

```
┌─────────────────────────────────────────────┐
│  PHASE 1: Batangan Simple Assignment        │
│  ──────────────────────────────────────     │
│  • Driver A → PAGI only (max 12 days)       │
│  • Driver B → SIANG only (max 12 days)      │
│  • STOPS at 12 days ✅                       │
└─────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────┐
│  PHASE 2: Cadangan Fill (In Generator)      │
│  ──────────────────────────────────────     │
│  • Fills empty slots when batangan at limit │
│  • Max 12 days per cadangan driver ✅        │
└─────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────┐
│  PHASE 3: Completion Service                │
│  ──────────────────────────────────────     │
│  • ONLY uses cadangan drivers ✅             │
│  • Respects 12-day limit ✅                  │
│  • NO relaxed constraints ✅                 │
│  • Leaves gaps if no cadangan available ✅   │
└─────────────────────────────────────────────┘
```

## Expected Behavior After Fix

### Batangan Drivers:
- ✅ **Maximum 12 shifts per month** (strictly enforced)
- ✅ Each driver assigned to **one shift type** only (pagi OR siang)
- ✅ Will NOT be used in completion phase
- ✅ Will NOT exceed limit even if days remain unscheduled

### Cadangan Drivers:
- ✅ **Maximum 12 shifts per month** (strictly enforced)
- ✅ Fill gaps when batangan drivers reach their limit
- ✅ Can work either pagi or siang shift as needed
- ✅ Used by completion service to fill incomplete days

### Incomplete Days:
- ✅ If **no cadangan drivers available** at limit, days may remain incomplete
- ✅ **This is correct behavior** - better to have incomplete days than violate driver limits
- ⚠️ Logged as warnings for manual review

## Testing Recommendations

### Test Case 1: 30-Day Month Schedule
```
Expected Result:
- Batangan Driver A: 12 PAGI shifts ✅
- Batangan Driver B: 12 SIANG shifts ✅
- Cadangan Drivers: Fill remaining 6 days (up to 12 shifts each) ✅
- Some days may be incomplete if not enough cadangan drivers ✅
```

### Test Case 2: Verify No Overages
```sql
-- Query to check for drivers exceeding 12 shifts
SELECT 
    d.id,
    d.name,
    d.type,
    COUNT(*) as total_shifts
FROM schedules s
JOIN drivers d ON s.driver_id = d.id
WHERE s.schedule_date BETWEEN '2024-11-01' AND '2024-11-30'
GROUP BY d.id, d.name, d.type
HAVING total_shifts > 12
ORDER BY total_shifts DESC;

-- Expected Result: 0 rows ✅
```

### Test Case 3: Completion Service Logs
```
Check logs for:
✅ "COMPLETION: Cadangan driver assigned..." (should see this)
❌ "COMPLETION: Batangan driver assigned..." (should NOT see this)
❌ "COMPLETION (RELAXED): Driver assigned..." (should NOT see this)
✅ "No cadangan driver available - shift remains empty" (OK to see this)
```

## Files Modified

1. **app/Services/ScheduleCompletionService.php**
   - Changed `BATANGAN_BASE_MAX_SHIFTS` from 14 to 12
   - Changed `CADANGAN_BASE_MAX_SHIFTS` from 13 to 12
   - Removed Phase 2 (batangan fallback)
   - Removed Phase 3 (relaxed constraints)
   - Removed `canDriverTakeShiftRelaxed()` method
   - Now only uses cadangan drivers for completion

2. **app/Services/ScheduleGeneratorService.php** (from previous update)
   - Already implements simple batangan assignment (12 days max)
   - Already has cadangan fill logic

## Prevention Measures

To prevent this issue from happening again:

1. ✅ **Single source of truth**: Both services now use same constant value (12)
2. ✅ **No bypass mechanisms**: Removed relaxed constraints
3. ✅ **Clear separation**: Batangan = Phase 1, Cadangan = Phase 2 & Completion
4. ✅ **Strict validation**: `canDriverTakeShift()` enforces limits
5. ✅ **Logging**: Clear logs show which driver type is assigned

## Migration Notes

- **No database migration needed** ✅
- **Existing schedules**: Not affected (historical data)
- **New schedules**: Will respect 12-day limit
- **Backward compatible**: API responses unchanged

## Support

If you still see drivers exceeding 12 days:

1. Check the logs for "RELAXED" messages (should be none)
2. Verify the constants are set to 12 in both services
3. Check if ScheduleCompletionService is being called
4. Run the SQL query above to identify which drivers are over the limit
5. Clear existing schedules and regenerate for testing

## Summary

**The core issue was**: The completion service was bypassing the 12-day limit with "relaxed constraints" and allowing batangan drivers to be reassigned to any shift.

**The fix is**: Strictly enforce 12-day limits in both services, only use cadangan drivers for completion, and remove all bypass mechanisms.

**Result**: Batangan drivers will now be limited to exactly 12 days, and gaps will be filled by cadangan drivers only (up to their 12-day limit).
