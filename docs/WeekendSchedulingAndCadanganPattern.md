# Weekend Scheduling & Cadangan Pattern Implementation

## Overview
Updated the schedule generator to implement:
1. **6-day work week for batangan drivers** (skip one random weekend day)
2. **20-day pattern for cadangan drivers** (work all 7 days following pattern)
3. **Random weekend assignment** (some drivers get Saturday off, others Sunday off)

## Changes Made

### 1. Batangan Drivers: 6-Day Work Week

**Behavior:**
- Each batangan driver works **6 days per week**
- Skips **one weekend day** (either Saturday OR Sunday)
- Assignment is **deterministic** (same driver always gets same day off)
- Assignment appears **random** but is consistent across regenerations

**How it works:**
```php
Driver A (ID: 1, Unit: 5) → Hash determines → Saturday OFF
Driver B (ID: 2, Unit: 5) → Hash determines → Sunday OFF

Week Structure:
Mon Tue Wed Thu Fri Sat Sun
 ✓   ✓   ✓   ✓   ✓   -   ✓   (Driver A - works Sunday, rests Saturday)
 ✓   ✓   ✓   ✓   ✓   ✓   -   (Driver B - works Saturday, rests Sunday)
```

**Implementation:**
```php
// Check if batangan drivers should skip this day
$shouldSkipBatangan = $this->shouldBatanganSkipDay($driverPagi, $driverSiang, $date, $unitId);

if ($shouldSkipBatangan) {
    Log::info("⏭️ Batangan drivers skip {$dateString} - weekend rest day");
    return $schedules; // Don't assign batangan on this day
}
```

### 2. Cadangan Drivers: 20-Day Pattern

**Behavior:**
- Cadangan drivers follow a **20-day rotating pattern**
- Work **all 7 days** of the week (no weekend breaks)
- Pattern includes scheduled rest days within the cycle
- Maximum **12 shifts per month**

**Pattern Structure:**
```
Day Pattern for 20-Day Cycle:
D1  D2  D3  D4  D5  D6  D7  D8  D9  D10 D11 D12 D13 D14 D15 D16 D17 D18 D19 D20
S   S   -   P   P   P   P   P   P   P   P   P   P   P   S   S   -   P   P   P   (Cadangan 1)
P   P   P   S   S   S   S   S   S   S   S   S   S   S   -   P   S   S   S   S   (Cadangan 2)

Legend:
P = Pagi (morning shift)
S = Siang (afternoon shift)
- = Rest day (no shift)
```

**Implementation:**
```php
// Calculate pattern position (cycles every 20 days)
$dayPosition = $monthStart->diffInDays($currentDate) + 1;
$patternPosition = (($dayPosition - 1) % 20) + 1;

// Get pattern for this day
$pattern = $this->getCadanganPatternForDay($patternPosition);

// Assign based on pattern
if ($shift === self::SHIFT_PAGI && $pattern['driver1'] === self::SHIFT_PAGI) {
    $targetDriver = $cadangan1;
}
```

### 3. Random Weekend Assignment Logic

**Deterministic Hash Function:**
```php
private function getDriverWeekendOffDay(int $driverId, int $unitId): int
{
    // Create deterministic "random" assignment using hash
    $hash = crc32("driver_{$driverId}_unit_{$unitId}");
    
    // Returns: 0 = Sunday OFF, 6 = Saturday OFF
    return ($hash % 2 === 0) ? 0 : 6;
}
```

**Key Features:**
- ✅ **Deterministic**: Same driver always gets same day off
- ✅ **Appears random**: Different drivers get different days
- ✅ **Consistent**: Regenerating schedules produces same result
- ✅ **Unit-specific**: Same driver can have different off-days on different units

## Monthly Schedule Example

### Sample Month (30 days, 4 weeks)

**Batangan Drivers:**
- Driver A (PAGI, Saturday OFF): Works 26 days (6 days/week × 4 weeks + 2 extra weekdays)
- Driver B (SIANG, Sunday OFF): Works 26 days (6 days/week × 4 weeks + 2 extra weekdays)

**But wait!** With 12-day limit:
- Driver A: Works 12 days maximum (hits limit mid-month)
- Driver B: Works 12 days maximum (hits limit mid-month)

**Cadangan Drivers Fill the Gap:**
- Cadangan 1 & 2: Follow 20-day pattern
- Work remaining ~18 days (30 - 12 batangan days)
- Each cadangan works max 12 days

## Code Structure

### New Methods Added

#### 1. `shouldBatanganSkipDay()`
```php
/**
 * Determine if batangan drivers should skip this day
 * Returns true on their designated weekend rest day
 */
private function shouldBatanganSkipDay($driverPagi, $driverSiang, Carbon $date, int $unitId): bool
```

#### 2. `getDriverWeekendOffDay()`
```php
/**
 * Get the weekend day (0=Sunday, 6=Saturday) that a driver has off
 * Uses deterministic hash to ensure consistency
 */
private function getDriverWeekendOffDay(int $driverId, int $unitId): int
```

#### 3. `getCadanganPatternForDay()`
```php
/**
 * Get the cadangan pattern for a specific day (1-20)
 * Uses the same 20-day pattern
 */
private function getCadanganPatternForDay(int $day): array
```

### Modified Methods

#### 1. `assignBatanganSimple()`
- Added weekend check before assignment
- Logs when batangan drivers skip a day

#### 2. `fillWithCadanganDrivers()`
- Implemented 20-day pattern logic
- Pattern-based driver selection
- Fallback to any available driver if pattern fails

## Workflow Diagram

```
┌─────────────────────────────────────────────────────┐
│  FOR EACH DAY IN SCHEDULE RANGE                     │
└─────────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│  BATANGAN PHASE                                      │
│  ─────────────────────────────────────────          │
│  1. Check if weekend day                            │
│  2. Check driver's weekend off-day (hash-based)     │
│  3. If match → SKIP (log & return)                  │
│  4. Else → Assign PAGI & SIANG (max 12 days)        │
└─────────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│  CADANGAN PHASE (if gaps exist)                     │
│  ─────────────────────────────────────────          │
│  1. Calculate pattern day (1-20 cycle)              │
│  2. Get pattern for this day                        │
│  3. Assign drivers based on pattern                 │
│  4. Fallback: Any available if pattern fails        │
│  5. Works ALL 7 days (no weekend skip)              │
└─────────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│  COMPLETION SERVICE (if still gaps)                 │
│  ─────────────────────────────────────────          │
│  1. Only uses cadangan drivers                      │
│  2. Fills any remaining incomplete days             │
│  3. Respects 12-day limit                           │
└─────────────────────────────────────────────────────┘
```

## Expected Results

### Week 1 Example (Mon-Sun)
```
        Mon  Tue  Wed  Thu  Fri  Sat  Sun
Batangan A (Pagi):   ✓    ✓    ✓    ✓    ✓    -    ✓   (Sat OFF)
Batangan B (Siang):  ✓    ✓    ✓    ✓    ✓    ✓    -   (Sun OFF)
Saturday Coverage:   P    P    P    P    P    [C]  P   (Cadangan fills Siang)
Sunday Coverage:     S    S    S    S    S    S   [C]  (Cadangan fills Pagi)

[C] = Cadangan driver assigned
```

### Month Summary
```
Total Days: 30

Batangan Assignments:
- Driver A (PAGI): 12 days (limit reached ~Day 14)
- Driver B (SIANG): 12 days (limit reached ~Day 14)
Total: 24 shifts

Cadangan Assignments:
- Days 1-14: Fill weekend gaps (4-6 shifts)
- Days 15-30: Fill all gaps after batangan limit (32 shifts)
- Distributed among cadangan drivers (max 12 each)

If 3 cadangan drivers:
- Cadangan 1: 12 days
- Cadangan 2: 12 days  
- Cadangan 3: 12 days
Total: 36 shifts

Grand Total: 24 (batangan) + 36 (cadangan) = 60 shifts (30 days × 2 shifts/day)
```

## Validation & Testing

### Test Case 1: Weekend Assignment Distribution
```php
// Check that drivers get different weekend days off
$driver1OffDay = getDriverWeekendOffDay(1, 5); // e.g., 6 (Saturday)
$driver2OffDay = getDriverWeekendOffDay(2, 5); // e.g., 0 (Sunday)

// Approximately 50/50 split across many drivers
```

### Test Case 2: 6-Day Work Week Verification
```sql
-- Verify batangan drivers work maximum 6 days per week
SELECT 
    d.name,
    WEEK(s.schedule_date) as week_num,
    COUNT(DISTINCT s.schedule_date) as days_worked
FROM schedules s
JOIN drivers d ON s.driver_id = d.id
WHERE d.type = 'batangan'
  AND s.schedule_date BETWEEN '2024-11-01' AND '2024-11-30'
GROUP BY d.id, d.name, WEEK(s.schedule_date)
HAVING days_worked > 6;

-- Expected: 0 rows
```

### Test Case 3: Cadangan Pattern Verification
```sql
-- Check cadangan drivers follow 20-day pattern with rest days
SELECT 
    d.name,
    s.schedule_date,
    s.shift,
    DATEDIFF(s.schedule_date, '2024-11-01') % 20 + 1 as pattern_day
FROM schedules s
JOIN drivers d ON s.driver_id = d.id
WHERE d.type = 'cadangan'
  AND s.schedule_date BETWEEN '2024-11-01' AND '2024-11-30'
ORDER BY d.id, s.schedule_date;

-- Verify pattern days 3, 15, 17 have rest days for respective drivers
```

### Test Case 4: Total Shifts Per Driver
```sql
-- Ensure no driver exceeds 12 shifts per month
SELECT 
    d.name,
    d.type,
    COUNT(*) as total_shifts
FROM schedules s
JOIN drivers d ON s.driver_id = d.id
WHERE s.schedule_date BETWEEN '2024-11-01' AND '2024-11-30'
GROUP BY d.id, d.name, d.type
HAVING total_shifts > 12;

-- Expected: 0 rows
```

## Migration & Deployment

### No Database Changes Required ✅
- All logic in application layer
- Existing schedules not affected
- Works with current schema

### Backward Compatibility ✅
- Old schedules remain valid
- API response format unchanged
- Logs include new information but compatible

### Regeneration Consistency ✅
- Deterministic hash ensures same results
- Safe to regenerate schedules
- Driver off-days remain consistent

## Configuration

### Constants (unchanged)
```php
const BATANGAN_BASE_MAX_SHIFTS = 12;  // Max 12 days/month
const CADANGAN_BASE_MAX_SHIFTS = 12;  // Max 12 days/month
const SHIFT_PAGI = 'pagi';
const SHIFT_SIANG = 'siang';
```

### Pattern Cycle
```php
// 20-day pattern cycle for cadangan
$patternPosition = (($dayPosition - 1) % 20) + 1;
```

## Logging Examples

### Batangan Skip
```
⏭️ Batangan drivers skip 2024-11-09 (Saturday) - weekend rest day
```

### Cadangan Pattern Assignment
```
📅 Cadangan pattern: Day 7/20 for 2024-11-15
✓ Cadangan pattern: Driver John (123) assigned pagi shift on 2024-11-15 
  (Pattern Day 7/20, Monthly: 5/12)
```

### Weekend Off-Day Assignment
```
Assigned roles: Driver Alice (1) -> PAGI, Driver Bob (2) -> SIANG
Driver Alice weekend off: Saturday (day 6)
Driver Bob weekend off: Sunday (day 0)
```

## Benefits

### For Drivers
- ✅ **Fair rest days**: All batangan drivers get 1 weekend day off
- ✅ **Predictable schedule**: Same day off every week
- ✅ **Work-life balance**: 6-day work week
- ✅ **No burnout**: 12-day monthly limit enforced

### For Operations
- ✅ **Continuous coverage**: Cadangan fill weekend gaps
- ✅ **Flexible patterns**: 20-day cycle adapts to various scenarios
- ✅ **Consistent scheduling**: Deterministic assignments
- ✅ **Fair distribution**: Random but balanced weekend assignments

### For System
- ✅ **Maintainable**: Clear separation of batangan/cadangan logic
- ✅ **Testable**: Deterministic behavior
- ✅ **Scalable**: Works with any number of drivers
- ✅ **Reliable**: Same results on regeneration

## Future Enhancements

### Possible Improvements
1. **Driver preferences**: Allow drivers to choose their preferred day off
2. **Holiday handling**: Special rules for public holidays
3. **Rotation**: Periodic rotation of weekend off-days (e.g., quarterly)
4. **Manual overrides**: Admin can manually set specific rest days
5. **Pattern customization**: Different patterns per unit/route

### Pattern Variations
```php
// Could implement different patterns:
// - 15-day pattern (shorter cycle)
// - 30-day pattern (full month)
// - Custom patterns per unit
```

## Summary

**Key Changes:**
1. ✅ Batangan drivers: **6 days/week** (random weekend day off)
2. ✅ Cadangan drivers: **20-day pattern** (work all 7 days)
3. ✅ Weekend assignment: **Deterministic but appears random**
4. ✅ Limits enforced: **12 days max per month**
5. ✅ Coverage maintained: **Cadangan fill all gaps**

**Result:**
- Better work-life balance for batangan drivers
- Structured, predictable schedules
- Full coverage maintained through cadangan pattern
- System remains fair and maintainable
