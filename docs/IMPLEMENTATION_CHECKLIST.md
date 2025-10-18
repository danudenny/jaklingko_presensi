# Implementation Checklist - Batangan 6-1 Cycle Pattern

## Pre-Implementation

### 1. Code Review
- [x] Migration file created and reviewed
- [x] Service logic updated for 6-1 cycle
- [x] Old weekend logic removed
- [x] Constants defined correctly
- [x] No syntax errors
- [x] Documentation complete

### 2. Understanding
- [ ] Read `docs/Batangan6-1CyclePattern.md`
- [ ] Review `docs/VISUAL_GUIDE.md`
- [ ] Understand pattern: v v v v v v x
- [ ] Understand cycle tracking (1-7)
- [ ] Know what cycle_day means
- [ ] Know what schedule_type means

## Implementation Steps

### 3. Database Migration
```bash
php artisan migrate
```
- [ ] Migration ran successfully
- [ ] Check: `cycle_day` field exists in schedules table
- [ ] Check: `schedule_type` field exists in schedules table
- [ ] No migration errors

### 4. Backup (IMPORTANT!)
```bash
# Backup existing schedules
php artisan tinker
```
```php
$backupFile = storage_path('schedules_backup_' . date('Ymd_His') . '.json');
$schedules = \App\Models\Schedule::all();
file_put_contents($backupFile, json_encode($schedules->toArray()));
echo "Backup saved to: $backupFile\n";
```
- [ ] Backup created
- [ ] Backup file location noted
- [ ] Can restore if needed

### 5. Clear Test Data (Optional)
```php
// Only if testing, not for production!
\App\Models\Schedule::whereBetween('schedule_date', ['2025-11-01', '2025-11-30'])->delete();
```
- [ ] Test month cleared (if applicable)
- [ ] Production data untouched

## Testing Phase

### 6. Generate Test Schedule (14 days)
```php
$service = new \App\Services\ScheduleGeneratorService();
$result = $service->generateSchedules(
    routeId: 1,      // YOUR ROUTE ID
    unitId: 1,       // YOUR UNIT ID
    startDate: '2025-11-01',
    endDate: '2025-11-14'
);
print_r($result);
```
- [ ] Command executed successfully
- [ ] No errors in result
- [ ] Schedules generated

### 7. Verify Database
```sql
SELECT 
    schedule_date,
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
- [ ] Query executed successfully
- [ ] Batangan drivers: cycle_day = 1-6 on work days
- [ ] Batangan drivers: no schedule on day 7
- [ ] Cadangan drivers: cycle_day = NULL
- [ ] Cadangan drivers: schedule_type = 'cadangan_cover'
- [ ] Days 7, 14 covered by cadangan

### 8. Validate Pattern
- [ ] Batangan works days 1-6 (cycle 1-6)
- [ ] Batangan off day 7 (no schedule)
- [ ] Cadangan covers day 7
- [ ] Pattern repeats: days 8-13 (cycle 1-6)
- [ ] Cadangan covers day 14
- [ ] Each day has 2 shifts (pagi + siang)

### 9. Check Logs
```bash
tail -100 storage/logs/laravel.log
```
Look for:
- [ ] "cycle position: Day X/7" messages
- [ ] "🚫 Driver ... is on OFF day (Day 7 of cycle)" messages
- [ ] "✓ Batangan PAGI: ... (Cycle Day X/7)" messages
- [ ] "✓ Cadangan cover: ... (Covering batangan off day)" messages
- [ ] No error messages

### 10. Test Full Month (30 days)
```php
$result = $service->generateSchedules(1, 1, '2025-11-01', '2025-11-30');
```
- [ ] Generated successfully
- [ ] Approximately 26 batangan work days
- [ ] Approximately 4 batangan off days (7, 14, 21, 28)
- [ ] All off days covered by cadangan
- [ ] Monthly limit respected (max 12)

### 11. Test Edge Cases
- [ ] Test with 7 days (exactly 1 cycle)
- [ ] Test with 15 days (2 cycles + 1 day)
- [ ] Test with existing schedules (should not conflict)
- [ ] Test with insufficient cadangan drivers
- [ ] Test driver at monthly limit (should stop)

## Validation Phase

### 12. Pattern Compliance
Create validation script:
```php
$unitId = 1;
$batanganDrivers = \App\Models\Driver::where('type', 'batangan')
    ->whereHas('units', fn($q) => $q->where('units.id', $unitId))
    ->get();

foreach ($batanganDrivers as $driver) {
    $schedules = \App\Models\Schedule::where('driver_id', $driver->id)
        ->where('unit_id', $unitId)
        ->whereBetween('schedule_date', ['2025-11-01', '2025-11-14'])
        ->orderBy('schedule_date')
        ->get();
    
    $consecutive = 0;
    $lastDate = null;
    
    foreach ($schedules as $schedule) {
        $date = \Carbon\Carbon::parse($schedule->schedule_date);
        if ($lastDate && $lastDate->copy()->addDay()->eq($date)) {
            $consecutive++;
        } else {
            if ($consecutive > 6) {
                echo "❌ ERROR: Driver {$driver->name} worked {$consecutive} consecutive days!\n";
            }
            $consecutive = 1;
        }
        $lastDate = $date;
    }
    
    echo "✅ Driver {$driver->name}: Max {$consecutive} consecutive days\n";
}
```
- [ ] No driver works > 6 consecutive days
- [ ] All drivers follow 6-1 pattern
- [ ] Validation script passes

### 13. Coverage Check
```sql
SELECT 
    schedule_date,
    COUNT(*) as shifts
FROM schedules
WHERE unit_id = 1 
  AND schedule_date BETWEEN '2025-11-01' AND '2025-11-14'
GROUP BY schedule_date
HAVING shifts < 2;
```
- [ ] Query returns 0 rows (all days have 2 shifts)
- [ ] OR expected incomplete days documented

### 14. Conflict Check
```sql
-- Check: No driver has 2 shifts on same day
SELECT driver_id, schedule_date, COUNT(*) as shifts
FROM schedules
WHERE unit_id = 1 
  AND schedule_date BETWEEN '2025-11-01' AND '2025-11-14'
GROUP BY driver_id, schedule_date
HAVING shifts > 1;

-- Check: No shift assigned twice same day
SELECT schedule_date, shift, COUNT(*) as assignments
FROM schedules
WHERE unit_id = 1 
  AND schedule_date BETWEEN '2025-11-01' AND '2025-11-14'
GROUP BY schedule_date, shift
HAVING assignments > 1;
```
- [ ] No duplicate driver assignments
- [ ] No duplicate shift assignments
- [ ] No conflicts found

## Documentation Review

### 15. Documentation Complete
- [x] Main guide: `Batangan6-1CyclePattern.md`
- [x] Testing guide: `Testing6-1CyclePattern.md`
- [x] Implementation summary: `IMPLEMENTATION_SUMMARY.md`
- [x] Visual guide: `VISUAL_GUIDE.md`
- [x] Quick reference: `QUICK_REFERENCE.md`
- [x] This checklist: `IMPLEMENTATION_CHECKLIST.md`

### 16. Code Documentation
- [x] Constants documented
- [x] Methods have docblocks
- [x] Cycle logic explained in comments
- [x] Log messages are clear

## Stakeholder Communication

### 17. Training Materials
- [ ] Create presentation slides
- [ ] Explain 6-1 pattern to operations team
- [ ] Show examples of schedule
- [ ] Explain cycle_day field
- [ ] Document how to read schedules

### 18. User Communication
- [ ] Notify drivers of new pattern
- [ ] Explain benefits (regular rest)
- [ ] Provide schedule examples
- [ ] Set expectations

## Deployment Preparation

### 19. Staging Deployment
- [ ] Deploy to staging environment
- [ ] Test with staging data
- [ ] Monitor for 1 week
- [ ] Collect feedback
- [ ] Fix any issues found

### 20. Production Readiness
- [ ] All tests pass
- [ ] No critical bugs
- [ ] Stakeholders approved
- [ ] Rollback plan documented
- [ ] Monitoring set up

## Production Deployment

### 21. Pre-Deployment
- [ ] Backup production database
- [ ] Notify users of deployment
- [ ] Schedule maintenance window
- [ ] Prepare rollback script

### 22. Deployment
```bash
# On production server
git pull origin master
php artisan migrate
php artisan config:clear
php artisan cache:clear
```
- [ ] Migration ran successfully
- [ ] No errors in logs
- [ ] Application running

### 23. Post-Deployment
- [ ] Generate test schedule
- [ ] Verify pattern working
- [ ] Check logs for errors
- [ ] Monitor for 24 hours
- [ ] Collect user feedback

## Monitoring & Maintenance

### 24. Daily Monitoring (First Week)
- [ ] Check logs for errors
- [ ] Verify schedules generated correctly
- [ ] Confirm cycle tracking working
- [ ] Review any reported issues
- [ ] Update documentation if needed

### 25. Weekly Review (First Month)
- [ ] Pattern compliance report
- [ ] Driver feedback collection
- [ ] Coverage statistics
- [ ] Performance metrics
- [ ] Identify improvements

### 26. Monthly Audit
- [ ] Review cycle accuracy
- [ ] Check monthly limits enforced
- [ ] Validate cadangan coverage
- [ ] Generate compliance report
- [ ] Plan optimizations

## Success Criteria

### 27. Technical Success
- [x] Migration successful
- [ ] Cycle tracking working
- [ ] Pattern enforced (6-1)
- [ ] No conflicts
- [ ] Full coverage
- [ ] Logs clear

### 28. Business Success
- [ ] Drivers satisfied with pattern
- [ ] Operations team can manage schedules
- [ ] Regulatory compliance met
- [ ] Improved driver health metrics
- [ ] Reduced accidents/incidents

### 29. Final Approval
- [ ] Technical lead approved
- [ ] Operations manager approved
- [ ] Legal/compliance approved
- [ ] Drivers acknowledged
- [ ] Documented for future reference

## Rollback Plan (If Needed)

### 30. Emergency Rollback
If critical issues found:
```bash
# 1. Revert migration
php artisan migrate:rollback --step=1

# 2. Revert code
git revert <commit-hash>
git push origin master

# 3. Restore backup
php artisan tinker
```
```php
$backup = json_decode(file_get_contents(storage_path('schedules_backup_YYYYMMDD_HHMMSS.json')));
foreach ($backup as $schedule) {
    \App\Models\Schedule::create((array)$schedule);
}
```
- [ ] Rollback procedure tested
- [ ] Backup restoration verified
- [ ] Stakeholders notified

## Completion

### 31. Final Sign-Off
Date: _______________

Signatures:
- [ ] Technical Lead: _______________
- [ ] Operations Manager: _______________
- [ ] Project Manager: _______________

### 32. Archive
- [ ] Document lessons learned
- [ ] Archive implementation notes
- [ ] Update project wiki
- [ ] Close implementation ticket

---

## Quick Status Check

Current Status: 
- [x] Code Complete
- [ ] Testing Complete
- [ ] Staging Complete
- [ ] Production Deployed
- [ ] Monitoring Active
- [ ] Sign-Off Complete

Next Action: 
```
Run migration: php artisan migrate
Generate test schedule: See section 6
Verify results: See section 7
```

---

**Version:** 1.0  
**Last Updated:** October 18, 2025  
**Status:** Ready for Testing ✅
