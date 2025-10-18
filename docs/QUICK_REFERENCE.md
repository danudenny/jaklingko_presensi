# 6-1 Cycle Pattern - Quick Reference Card

## Pattern
```
v v v v v v x
```
**6 consecutive working days, then 1 off day**

## Key Constants
```php
BATANGAN_WORK_DAYS = 6
BATANGAN_CYCLE_LENGTH = 7
BATANGAN_BASE_MAX_SHIFTS = 12
```

## Cycle Days
```
Day 1-6: WORK (driver assigned)
Day 7:   OFF  (cadangan covers)
```

## Database Fields
```
cycle_day       → 1-7 (tracks position)
schedule_type   → 'regular' or 'cadangan_cover'
```

## Migration
```bash
php artisan migrate
```

## Test
```php
$service = new ScheduleGeneratorService();
$result = $service->generateSchedules(
    routeId: 1,
    unitId: 1,
    startDate: '2025-11-01',
    endDate: '2025-11-14'
);
```

## Verify
```sql
SELECT schedule_date, shift, cycle_day, schedule_type
FROM schedules 
WHERE unit_id = 1 
  AND schedule_date BETWEEN '2025-11-01' AND '2025-11-14'
ORDER BY schedule_date, shift;
```

## Expected Pattern (14 days)
```
Days 1-6:   Batangan works (cycle 1-6)
Day 7:      Cadangan covers (cycle NULL)
Days 8-13:  Batangan works (cycle 1-6)
Day 14:     Cadangan covers (cycle NULL)
```

## Log Messages
```
✓ Success:
  Driver X cycle position: Day Y/7
  ✓ Batangan PAGI: assigned (Cycle Day Y/7)
  
🚫 Off Day:
  Driver X is on OFF day (Day 7 of cycle)
  
✓ Cadangan:
  Cadangan cover: Driver Y (Covering batangan off day)
```

## Monthly Stats (30 days)
```
Batangan:   26 work days, 4 off days
Cadangan:   4 coverage days
Cycles:     4 complete + 2 partial days
```

## Files Modified
```
✓ Migration:  2025_10_18_000001_add_cycle_tracking...
✓ Service:    ScheduleGeneratorService.php
✓ Docs:       Batangan6-1CyclePattern.md
              Testing6-1CyclePattern.md
              IMPLEMENTATION_SUMMARY.md
              VISUAL_GUIDE.md
```

## Support
- Logs: `storage/logs/laravel.log`
- Docs: `docs/Batangan6-1CyclePattern.md`
- Tests: `docs/Testing6-1CyclePattern.md`

## Rules
✓ Exactly 6 consecutive work days (no more, no less)
✓ Mandatory 7th day off
✓ Cadangan covers all off days
✓ Cycle tracked in database
✓ Monthly limit: 12 shifts max

---
**Pattern:** v v v v v v x (repeat)  
**Status:** ✅ Ready for Testing  
**Date:** October 18, 2025
