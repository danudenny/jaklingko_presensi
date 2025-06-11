# Schedule Generator - Conflict Prevention Testing Guide

## Overview
This guide provides comprehensive testing instructions for the enhanced Schedule Generator Service with conflict prevention and validation features.

## Pre-requisites
- Database with sample drivers (batangan and cadangan types)
- At least 2 batangan drivers and 2+ cadangan drivers
- Sample units and routes configured
- Laravel logging configured to capture detailed logs

## Test Cases

### Test Case 1: Basic Conflict Prevention

#### Setup
```sql
-- Ensure you have drivers
INSERT INTO drivers (name, type, status) VALUES 
('Driver A', 'batangan', 'aktif'),
('Driver B', 'batangan', 'aktif'),
('Driver C', 'cadangan', 'aktif'),
('Driver D', 'cadangan', 'aktif');
```

#### Test Steps
1. Generate schedule for a 3-day period
2. Verify no driver has more than 1 shift per day
3. Verify no unit has more than 2 shifts per day
4. Check logs for conflict prevention messages

#### Expected Results
```
✅ No driver should appear twice on same date
✅ Each unit should have maximum 2 shifts per day
✅ Logs should show pattern application and coverage status
```

### Test Case 2: Monthly Limit Enforcement

#### Setup
```php
// Pre-populate a driver with 13 shifts in current month
$driver = Driver::where('type', 'batangan')->first();
for ($i = 1; $i <= 13; $i++) {
    Schedule::create([
        'route_id' => 1,
        'unit_id' => 1,
        'driver_id' => $driver->id,
        'schedule_date' => now()->format('Y-m-') . str_pad($i, 2, '0', STR_PAD_LEFT),
        'shift' => 'pagi',
        'status' => 'scheduled'
    ]);
}
```

#### Test Steps
1. Generate schedule for remaining days of month
2. Verify the driver with 13 shifts gets maximum 1 more shift
3. Check that cadangan drivers are used when batangan limits reached

#### Expected Results
```
✅ Batangan driver should not exceed 14 shifts per month
✅ Cadangan drivers should fill gaps when batangan unavailable
✅ Logs should show "monthly limit" validation messages
```

### Test Case 3: Duplicate Prevention

#### Setup
```php
// Create a conflicting schedule manually
Schedule::create([
    'route_id' => 1,
    'unit_id' => 1,
    'driver_id' => 1,
    'schedule_date' => '2025-01-15',
    'shift' => 'pagi',
    'status' => 'scheduled'
]);
```

#### Test Steps
1. Try to generate schedule for same date range including 2025-01-15
2. Verify system detects existing schedule
3. Check that no duplicate is created

#### Expected Results
```
✅ System should detect existing schedule
✅ No duplicate shifts should be created
✅ Logs should show "existing schedule" detection
```

### Test Case 4: Validation Integrity

#### Test Steps
1. Generate schedules normally
2. Manually corrupt data (create duplicate driver assignment)
3. Run integrity validation
4. Check validation response

#### Corruption Example
```sql
-- Create invalid duplicate
INSERT INTO schedules (route_id, unit_id, driver_id, schedule_date, shift, status)
VALUES (1, 1, 1, '2025-01-15', 'siang', 'scheduled');
```

#### Expected Results
```
✅ Validation should detect duplicate driver assignment
✅ Response should include validation_issues array
✅ Logs should warn about integrity problems
```

### Test Case 5: Coverage Statistics

#### Test Steps
1. Generate schedule for 30-day period
2. Check coverage statistics in response
3. Verify daily breakdown accuracy
4. Manually count and compare

#### Expected Results
```json
{
  "coverage_stats": {
    "total_days": 30,
    "days_with_full_coverage": 28,
    "days_with_partial_coverage": 2,
    "days_with_no_coverage": 0,
    "coverage_percentage": 93.33,
    "daily_breakdown": {
      "2025-01-15": {"shifts": 2, "status": "full"}
    }
  }
}
```

### Test Case 6: Phase Integration

#### Test Steps
1. Configure only 1 batangan driver (insufficient for pattern)
2. Generate schedules
3. Verify cadangan drivers are used instead
4. Check phase logging

#### Expected Results
```
⚠️ Should log "Insufficient batangan drivers"
✅ Cadangan drivers should fill all shifts
✅ Fallback phase should complete coverage
```

### Test Case 7: Real-time Conflict Detection

#### Test Steps
1. Start schedule generation
2. While running, manually insert conflicting schedule
3. Verify system detects and handles conflict
4. Check final integrity

#### Expected Results
```
✅ System should detect real-time conflicts
✅ No duplicate shifts should exist after generation
✅ Logs should show conflict detection messages
```

## Automated Testing Script

### PHP Test Script
```php
<?php
// tests/Feature/ScheduleGeneratorConflictTest.php

class ScheduleGeneratorConflictTest extends TestCase
{
    public function test_prevents_duplicate_driver_assignments()
    {
        // Create test data
        $unit = Unit::factory()->create();
        $driver = Driver::factory()->create(['type' => 'batangan']);
        
        // Pre-create a schedule
        Schedule::create([
            'route_id' => 1,
            'unit_id' => $unit->id,
            'driver_id' => $driver->id,
            'schedule_date' => '2025-01-15',
            'shift' => 'pagi',
            'status' => 'scheduled'
        ]);
        
        // Try to generate overlapping schedule
        $service = new ScheduleGeneratorService();
        $result = $service->generateSchedules(1, $unit->id, '2025-01-15', '2025-01-15');
        
        // Assert no duplicates created
        $scheduleCount = Schedule::where('driver_id', $driver->id)
            ->where('schedule_date', '2025-01-15')
            ->count();
            
        $this->assertEquals(1, $scheduleCount);
        $this->assertTrue($result['success']);
    }
    
    public function test_validates_schedule_integrity()
    {
        $service = new ScheduleGeneratorService();
        
        // Create duplicate schedules manually
        $unit = Unit::factory()->create();
        $driver = Driver::factory()->create();
        
        Schedule::create([
            'route_id' => 1,
            'unit_id' => $unit->id,
            'driver_id' => $driver->id,
            'schedule_date' => '2025-01-15',
            'shift' => 'pagi',
            'status' => 'scheduled'
        ]);
        
        Schedule::create([
            'route_id' => 1,
            'unit_id' => $unit->id,
            'driver_id' => $driver->id,
            'schedule_date' => '2025-01-15',
            'shift' => 'siang',
            'status' => 'scheduled'
        ]);
        
        // Use reflection to call private method
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('validateScheduleIntegrity');
        $method->setAccessible(true);
        
        $issues = $method->invoke($service, $unit->id, '2025-01-15');
        
        $this->assertNotEmpty($issues);
        $this->assertStringContains('has 2 shifts', $issues[0]);
    }
}
```

### Database Verification Queries

```sql
-- Check for duplicate driver assignments
SELECT driver_id, schedule_date, COUNT(*) as count
FROM schedules 
GROUP BY driver_id, schedule_date 
HAVING COUNT(*) > 1;

-- Check for excessive shifts per day
SELECT unit_id, schedule_date, COUNT(*) as shifts
FROM schedules 
GROUP BY unit_id, schedule_date 
HAVING COUNT(*) > 2;

-- Check monthly limits
SELECT d.name, d.type, COUNT(s.id) as monthly_shifts
FROM drivers d
LEFT JOIN schedules s ON d.id = s.driver_id 
WHERE s.schedule_date BETWEEN '2025-01-01' AND '2025-01-31'
GROUP BY d.id, d.name, d.type
ORDER BY monthly_shifts DESC;

-- Coverage analysis
SELECT 
    schedule_date,
    COUNT(*) as shifts,
    CASE 
        WHEN COUNT(*) >= 2 THEN 'Full'
        WHEN COUNT(*) = 1 THEN 'Partial'
        ELSE 'Empty'
    END as coverage_status
FROM schedules 
WHERE unit_id = 1 
GROUP BY schedule_date 
ORDER BY schedule_date;
```

## Performance Testing

### Load Test
```php
// Generate schedules for multiple units simultaneously
$units = Unit::take(5)->get();
$startTime = microtime(true);

foreach ($units as $unit) {
    $service = new ScheduleGeneratorService();
    $result = $service->generateSchedules(1, $unit->id, '2025-01-01', '2025-01-31');
}

$duration = microtime(true) - $startTime;
echo "Generation time: {$duration} seconds\n";
```

### Memory Usage Test
```php
$memoryBefore = memory_get_usage();

$service = new ScheduleGeneratorService();
$result = $service->generateSchedules(1, 1, '2025-01-01', '2025-12-31');

$memoryAfter = memory_get_usage();
$memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024;
echo "Memory used: {$memoryUsed} MB\n";
```

## Monitoring Setup

### Log Monitoring Commands
```bash
# Monitor conflict prevention
tail -f storage/logs/laravel.log | grep "conflict\|duplicate\|validation"

# Monitor coverage statistics
tail -f storage/logs/laravel.log | grep "COMPLETE\|coverage"

# Monitor phase execution
tail -f storage/logs/laravel.log | grep "PHASE\|Starting Phase"
```

### Database Monitoring
```sql
-- Monitor schedule generation activity
SELECT COUNT(*) as schedules_today 
FROM schedules 
WHERE created_at >= CURDATE();

-- Check system health
SELECT 
    'Duplicate drivers' as check_type,
    COUNT(*) as issues
FROM (
    SELECT driver_id, schedule_date, COUNT(*) 
    FROM schedules 
    GROUP BY driver_id, schedule_date 
    HAVING COUNT(*) > 1
) duplicates
UNION ALL
SELECT 
    'Excessive shifts per day' as check_type,
    COUNT(*) as issues
FROM (
    SELECT unit_id, schedule_date, COUNT(*) 
    FROM schedules 
    GROUP BY unit_id, schedule_date 
    HAVING COUNT(*) > 2
) excessive;
```

## Expected Outcomes

After running all tests, you should observe:

1. **Zero Conflicts**: No duplicate driver assignments or excessive shifts
2. **Proper Validation**: All integrity issues detected and reported
3. **Accurate Statistics**: Coverage percentages match manual calculations  
4. **Comprehensive Logging**: Detailed phase-by-phase execution logs
5. **Performance**: Generation completes within reasonable time limits
6. **Data Integrity**: All validation rules properly enforced

Any failures in these areas indicate issues that need to be addressed before production deployment.
