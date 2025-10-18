# Testing the 6-1 Cycle Pattern

## Quick Test Steps

### 1. Run Migration
```bash
php artisan migrate
```

Expected output:
```
Migrating: 2025_10_18_000001_add_cycle_tracking_to_schedules_table
Migrated:  2025_10_18_000001_add_cycle_tracking_to_schedules_table
```

### 2. Clear Existing Test Data (Optional)
```bash
# Connect to your database and clear schedules for testing
php artisan tinker
```

Then in tinker:
```php
use App\Models\Schedule;
use Carbon\Carbon;

// Clear schedules for November 2025 (example)
Schedule::whereBetween('schedule_date', ['2025-11-01', '2025-11-30'])->delete();
```

### 3. Generate Test Schedule

Via Tinker:
```php
use App\Services\ScheduleGeneratorService;
use Carbon\Carbon;

$service = new ScheduleGeneratorService();

// Generate for 14 days (2 complete cycles)
$result = $service->generateSchedules(
    routeId: 1,              // Your route ID
    unitId: 1,               // Your unit ID
    startDate: '2025-11-01',
    endDate: '2025-11-14'
);

// Check result
print_r($result);
```

### 4. Verify Results

Check the schedules table:
```sql
SELECT 
    s.schedule_date,
    d.name as driver_name,
    d.type as driver_type,
    s.shift,
    s.cycle_day,
    s.schedule_type
FROM schedules s
JOIN drivers d ON s.driver_id = d.id
WHERE s.unit_id = 1 
  AND s.schedule_date BETWEEN '2025-11-01' AND '2025-11-14'
ORDER BY s.schedule_date, s.shift;
```

### Expected Results for 14 Days (2 Cycles)

**Batangan Driver (Pagi):**
```
Date       | Shift | Cycle Day | Type           | Notes
-----------|-------|-----------|----------------|------------------
2025-11-01 | pagi  | 1         | regular        | Work day 1
2025-11-02 | pagi  | 2         | regular        | Work day 2
2025-11-03 | pagi  | 3         | regular        | Work day 3
2025-11-04 | pagi  | 4         | regular        | Work day 4
2025-11-05 | pagi  | 5         | regular        | Work day 5
2025-11-06 | pagi  | 6         | regular        | Work day 6
2025-11-07 | NULL  | NULL      | NULL           | OFF DAY (Cadangan covers)
2025-11-08 | pagi  | 1         | regular        | Cycle restarts
2025-11-09 | pagi  | 2         | regular        | Work day 2
2025-11-10 | pagi  | 3         | regular        | Work day 3
2025-11-11 | pagi  | 4         | regular        | Work day 4
2025-11-12 | pagi  | 5         | regular        | Work day 5
2025-11-13 | pagi  | 6         | regular        | Work day 6
2025-11-14 | NULL  | NULL      | NULL           | OFF DAY (Cadangan covers)
```

**Batangan Driver (Siang):**
- Same pattern but for siang shift

**Cadangan Drivers:**
```
Date       | Shift | Cycle Day | Type            | Notes
-----------|-------|-----------|-----------------|------------------
2025-11-07 | pagi  | NULL      | cadangan_cover  | Covers batangan off
2025-11-07 | siang | NULL      | cadangan_cover  | Covers batangan off
2025-11-14 | pagi  | NULL      | cadangan_cover  | Covers batangan off
2025-11-14 | siang | NULL      | cadangan_cover  | Covers batangan off
```

### 5. Check Logs

Review the Laravel log file:
```bash
tail -f storage/logs/laravel.log
```

Look for:
1. **Cycle tracking:**
   ```
   Driver John Doe (12) cycle position: Day 1/7
   Driver John Doe (12) cycle position: Day 2/7
   ...
   ```

2. **Off day detection:**
   ```
   🚫 Driver John Doe (12) is on OFF day (Day 7 of cycle) - 2025-11-07
   ```

3. **Cadangan coverage:**
   ```
   ✓ Cadangan cover: Driver Jane Smith (34) assigned pagi shift on 2025-11-07 (Covering batangan off day, Monthly: 2/12)
   ```

### 6. Validate Pattern

Create a simple validation script:

```php
// test-cycle-pattern.php
use App\Models\Schedule;
use App\Models\Driver;
use Carbon\Carbon;

$unitId = 1;
$startDate = '2025-11-01';
$endDate = '2025-11-14';

// Get batangan drivers
$batanganDrivers = Driver::where('type', 'batangan')
    ->whereHas('units', function($q) use ($unitId) {
        $q->where('units.id', $unitId);
    })
    ->get();

foreach ($batanganDrivers as $driver) {
    echo "\n=== Driver: {$driver->name} ({$driver->type}) ===\n";
    
    $schedules = Schedule::where('driver_id', $driver->id)
        ->where('unit_id', $unitId)
        ->whereBetween('schedule_date', [$startDate, $endDate])
        ->orderBy('schedule_date')
        ->get();
    
    $consecutiveDays = 0;
    $lastDate = null;
    
    foreach ($schedules as $schedule) {
        $date = Carbon::parse($schedule->schedule_date);
        
        // Check if consecutive
        if ($lastDate && $lastDate->copy()->addDay()->eq($date)) {
            $consecutiveDays++;
        } else {
            if ($consecutiveDays > 0) {
                echo "✓ Worked {$consecutiveDays} consecutive days\n";
            }
            $consecutiveDays = 1;
        }
        
        echo "  {$schedule->schedule_date} - {$schedule->shift} - Cycle Day: {$schedule->cycle_day}\n";
        $lastDate = $date;
    }
    
    if ($consecutiveDays > 0) {
        echo "✓ Worked {$consecutiveDays} consecutive days\n";
    }
    
    // Verify max 6 consecutive days
    if ($consecutiveDays > 6) {
        echo "❌ ERROR: More than 6 consecutive days detected!\n";
    } else {
        echo "✅ Pattern valid: Max {$consecutiveDays} consecutive days\n";
    }
}
```

Run:
```bash
php test-cycle-pattern.php
```

## Common Issues & Troubleshooting

### Issue 1: Driver Works More Than 6 Consecutive Days
**Symptom:** cycle_day shows 8, 9, etc.
**Cause:** Logic error in cycle calculation
**Fix:** Check `getBatanganCycleDay()` method

### Issue 2: No Cadangan Coverage on Off Days
**Symptom:** Days 7, 14, etc. have empty shifts
**Cause:** Insufficient cadangan drivers or they hit monthly limit
**Fix:** 
- Add more cadangan drivers
- Adjust CADANGAN_BASE_MAX_SHIFTS limit

### Issue 3: Cycle Doesn't Reset After Gap
**Symptom:** Driver skips multiple days but cycle continues from wrong position
**Cause:** Gap handling logic in `getBatanganCycleDay()`
**Fix:** Check gap handling (daysDiff > 7 should reset)

### Issue 4: cycle_day is NULL
**Symptom:** cycle_day field is always NULL
**Cause:** Migration not run or fields not being set in create()
**Fix:** 
- Ensure migration ran successfully
- Check that `cycle_day` is being set in Schedule::create()

## Performance Testing

Test with larger datasets:

```php
// Generate full month
$result = $service->generateSchedules(
    routeId: 1,
    unitId: 1,
    startDate: '2025-11-01',
    endDate: '2025-11-30'  // Full month
);

// Should see approximately:
// - Batangan works ~26 days (6+6+6+6+2)
// - Cadangan covers ~4 days (7, 14, 21, 28)
```

## Success Criteria

✅ **Pattern Compliance**
- Each batangan driver works EXACTLY 6 consecutive days
- Followed by 1 off day
- Pattern repeats throughout period

✅ **Database Tracking**
- cycle_day field populated (1-7)
- schedule_type correctly set ('regular' or 'cadangan_cover')

✅ **Full Coverage**
- Every day has 2 shifts (pagi + siang)
- Cadangan covers all batangan off days

✅ **No Conflicts**
- No driver has multiple shifts same day
- No duplicate shift assignments
- Monthly limits respected

✅ **Logging**
- Clear cycle position logging
- Off day detection logged
- Cadangan coverage logged

## Next Steps

After successful testing:

1. **Deploy to staging** - Test with real data
2. **Monitor for 1-2 weeks** - Ensure pattern holds
3. **Train staff** - Explain new 6-1 pattern
4. **Deploy to production** - Roll out gradually
5. **Monitor & adjust** - Fine-tune as needed

## Support

If you encounter issues:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify database fields: `cycle_day`, `schedule_type`
3. Review this documentation
4. Check the main documentation: `docs/Batangan6-1CyclePattern.md`
