# Batangan Driver 6-1 Cycle Pattern Implementation

## Overview
This document describes the refined batangan driver scheduling pattern that follows a strict **6 consecutive working days, then 1 day off** cycle.

## Pattern Structure

### The 6-1 Cycle
```
v v v v v v x
```

Where:
- `v` = Working day (driver assigned to shift)
- `x` = Off day (driver rests, cadangan driver covers)

### Key Rules

1. **Strict 6-Day Work Period**
   - Each batangan driver MUST work exactly 6 consecutive days
   - Cannot be more than 6 days
   - Cannot be less than 6 days
   - This ensures consistent rest periods

2. **Mandatory 7th Day Off**
   - After 6 consecutive working days, the 7th day is OFF
   - Driver does NOT work on this day
   - Cadangan driver covers the shift

3. **Cycle Repeats**
   - After the off day, the cycle starts again: 6 work + 1 off
   - Pattern continues throughout the month
   - Example for 28 days: `v v v v v v x | v v v v v v x | v v v v v v x | v v v v v v x`

## Implementation Details

### Database Changes

#### Migration: `2025_10_18_000001_add_cycle_tracking_to_schedules_table.php`

Added two new fields to the `schedules` table:

1. **`cycle_day`** (tinyint, nullable)
   - Tracks position in the 7-day cycle (1-7)
   - Days 1-6: Working days
   - Day 7: Off/rest day
   - Used to determine when a driver should be off

2. **`schedule_type`** (enum: 'regular', 'off', 'cadangan_cover')
   - `regular`: Normal batangan driver shift
   - `off`: Marked as off day (for tracking)
   - `cadangan_cover`: Shift covered by cadangan driver

### Service Updates

#### `ScheduleGeneratorService.php`

**New Constants:**
```php
const BATANGAN_WORK_DAYS = 6;      // 6 consecutive working days
const BATANGAN_CYCLE_LENGTH = 7;   // 6 work + 1 off = 7 days total
```

**New Methods:**

1. **`getBatanganCycleDay()`**
   - Calculates the current position in the 7-day cycle for a driver
   - Returns: `['cycle_day' => int (1-7), 'consecutive_days' => int, 'last_schedule_date' => string]`
   - Logic:
     - If no previous schedule: starts at Day 1
     - If previous schedule exists: calculates next day based on last cycle_day
     - Handles gaps in schedule (interruptions, weekends, etc.)

2. **`countConsecutiveWorkingDays()`**
   - Fallback method to count consecutive working days
   - Looks back up to 7 days
   - Used when cycle_day is not stored in database (legacy schedules)

**Updated Methods:**

1. **`assignBatanganSimple()`** → **`assignBatangan6-1Pattern()`**
   - Implements the strict 6-1 cycle
   - For each batangan driver:
     - Gets current cycle day (1-7)
     - If cycle day = 7: SKIP (off day)
     - If cycle day = 1-6: ASSIGN shift
   - Stores cycle_day and schedule_type in database
   - Logs cycle position for debugging

2. **`fillWithCadanganDrivers()`**
   - Updated to cover batangan off days
   - Assigns cadangan drivers to empty shifts
   - Marks assignments as 'cadangan_cover' type
   - No cycle tracking for cadangan (they work as needed)

**Removed Methods:**
- `shouldBatanganSkipDay()` - No longer needed (cycle handles skip logic)
- `getDriverWeekendOffDay()` - Weekend logic replaced by cycle

## Schedule Generation Flow

### Phase 1: Batangan Assignment (6-1 Cycle)
```
For each date in range:
  For each batangan driver:
    1. Get cycle position (1-7)
    2. If cycle_day == 7:
         → Skip (off day)
    3. If cycle_day == 1-6:
         → Check constraints (monthly limit, conflicts)
         → Assign to designated shift (pagi/siang)
         → Store cycle_day in database
    4. Log cycle information
```

### Phase 2: Cadangan Coverage
```
For each date in range:
  If shifts < 2 (incomplete coverage):
    For each empty shift:
      1. Find available cadangan driver
      2. Check constraints (monthly limit, conflicts)
      3. Assign cadangan to shift
      4. Mark as 'cadangan_cover' type
      5. No cycle_day (cadangan works as needed)
```

### Phase 3: Completion Service (Existing)
- Fills any remaining gaps
- Handles edge cases
- Ensures full coverage

## Example Scenarios

### Scenario 1: Full Month (30 days)
**Driver A (Pagi shift):**
```
Day:  1  2  3  4  5  6  7  8  9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30
Cycle:1  2  3  4  5  6  7  1  2  3  4  5  6  7  1  2  3  4  5  6  7  1  2  3  4  5  6  7  1  2
Work: v  v  v  v  v  v  x  v  v  v  v  v  v  x  v  v  v  v  v  v  x  v  v  v  v  v  v  x  v  v
```
- Works: Days 1-6, 8-13, 15-20, 22-27, 29-30
- Off: Days 7, 14, 21, 28
- Total working days: 26 days (out of 30)

**Cadangan covers:** Days 7, 14, 21, 28 (4 days)

### Scenario 2: Mid-Month Start
If generating schedule starting Day 10 and driver was working Days 8-9:
```
Day:  10 11 12 13 14 15 16 17 18 19 20
Cycle: 3  4  5  6  7  1  2  3  4  5  6
Work:  v  v  v  v  x  v  v  v  v  v  v
```
- Continues from previous cycle position
- Off day occurs on Day 14 (7th day of cycle)

## Benefits

### 1. **Predictability**
- Drivers know exact schedule pattern
- Easy to plan personal time
- Fair and consistent rotation

### 2. **Health & Safety**
- Mandatory rest every 7 days
- Prevents driver fatigue
- Reduces accident risk

### 3. **Easy Calculation**
- Simple 6-1 pattern is easy to understand
- No complex patterns or exceptions
- Clear cycle tracking in database

### 4. **Fair Distribution**
- All batangan drivers follow same pattern
- Equal workload distribution
- Cadangan drivers provide consistent backup

### 5. **Regulatory Compliance**
- Ensures drivers get regular rest
- Meets labor law requirements
- Documented rest periods

## Testing

### Test Cases

1. **Test Normal 6-1 Cycle**
   - Generate 14-day schedule
   - Verify driver works days 1-6, off day 7
   - Verify cycle repeats days 8-13, off day 14

2. **Test Cadangan Coverage**
   - Verify cadangan covers day 7, 14, etc.
   - Verify cadangan marked as 'cadangan_cover' type

3. **Test Monthly Limit**
   - Verify driver stops at 12 days/month (max)
   - Even if cycle allows more

4. **Test Cycle Continuity**
   - Generate schedule with gaps
   - Verify cycle continues correctly after interruption

5. **Test Multiple Units**
   - Each driver has independent cycle per unit
   - Verify cycle tracking is unit-specific

## Migration Steps

### 1. Run Migration
```bash
php artisan migrate
```

This adds `cycle_day` and `schedule_type` fields to schedules table.

### 2. Clear Existing Schedules (Optional)
If you want to regenerate with new pattern:
```php
// Via controller or service
$service->clearExistingSchedules($routeId, $unitId, $startDate, $endDate);
```

### 3. Generate New Schedules
```php
$service->generateSchedules($routeId, $unitId, $startDate, $endDate);
```

### 4. Verify Results
Check logs for:
- Cycle day tracking: `Cycle Day X/7`
- Off day skips: `🚫 Driver ... is on OFF day (Day 7 of cycle)`
- Cadangan coverage: `✓ Cadangan cover: Driver ... (Covering batangan off day)`

## Configuration

### Adjusting the Cycle

If you need to change the pattern in the future:

**Current:** 6 work + 1 off = 7-day cycle

**To change to 5 work + 2 off:**
```php
const BATANGAN_WORK_DAYS = 5;
const BATANGAN_CYCLE_LENGTH = 7;  // 5 work + 2 off
```

Update `getBatanganCycleDay()` logic to handle Days 6-7 as off days.

**To change to 12 work + 2 off:**
```php
const BATANGAN_WORK_DAYS = 12;
const BATANGAN_CYCLE_LENGTH = 14;  // 12 work + 2 off
```

## Monitoring & Logging

### Log Messages to Watch

**Cycle Tracking:**
```
Driver X (ID) cycle position: Day Y/7
```

**Off Day Detection:**
```
🚫 Driver X (ID) is on OFF day (Day 7 of cycle) - 2025-10-25
```

**Successful Assignment:**
```
✓ Batangan PAGI: Driver X (ID) assigned on 2025-10-25 (Cycle Day 3/7, Monthly: 8/12)
```

**Cadangan Coverage:**
```
✓ Cadangan cover: Driver Y (ID) assigned pagi shift on 2025-10-25 (Covering batangan off day, Monthly: 5/12)
```

## Future Enhancements

### Possible Improvements

1. **Cycle Visualization**
   - Dashboard showing each driver's cycle position
   - Calendar view with color-coded days (work/off)

2. **Cycle Reset Function**
   - Admin tool to reset a driver's cycle
   - Useful for special circumstances

3. **Automatic Notifications**
   - Notify drivers of upcoming off days
   - Alert managers of coverage needs

4. **Cycle Reports**
   - Report showing cycle compliance
   - Track deviations from 6-1 pattern

5. **Flexible Cycle Start**
   - Allow different starting positions per driver
   - Stagger cycles to avoid multiple off days same day

## Summary

The 6-1 cycle pattern provides:
- ✅ **Strict 6-day work requirement** - Exactly 6 consecutive days, no more, no less
- ✅ **Mandatory 7th day off** - Ensures regular rest for driver health
- ✅ **Predictable schedule** - Easy for drivers to plan ahead
- ✅ **Database tracking** - cycle_day field tracks position in cycle
- ✅ **Cadangan integration** - Seamless coverage for off days
- ✅ **Simple & maintainable** - Clear logic, easy to understand and modify

This implementation ensures batangan drivers have a consistent, healthy work pattern while maintaining full operational coverage through cadangan drivers.
