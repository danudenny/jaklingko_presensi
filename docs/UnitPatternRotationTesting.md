# Testing Script untuk Unit-Based Pattern Rotation

## Quick Test Commands

### 1. Test Pattern Offset Calculation
```bash
php artisan tinker
```

```php
// Test offset calculation untuk different units
$service = new App\Services\ScheduleGeneratorService();

// Get reflection untuk access private method
$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('getUnitPatternOffset');
$method->setAccessible(true);

// Test multiple units
for ($unitId = 1; $unitId <= 10; $unitId++) {
    $offset = $method->invoke($service, $unitId);
    echo "Unit $unitId: Offset $offset\n";
}

// Verify consistency - same unit should always get same offset
$unit1_offset1 = $method->invoke($service, 1);
$unit1_offset2 = $method->invoke($service, 1);
echo "Unit 1 consistency: " . ($unit1_offset1 === $unit1_offset2 ? "PASS" : "FAIL") . "\n";

exit;
```

### 2. Test Pattern Position Calculation
```php
// Test pattern position dengan different offsets
$service = new App\Services\ScheduleGeneratorService();
$reflection = new ReflectionClass($service);

$offsetMethod = $reflection->getMethod('getUnitPatternOffset');
$offsetMethod->setAccessible(true);

$patternMethod = $reflection->getMethod('getPatternForDay');
$patternMethod->setAccessible(true);

// Test untuk 3 units berbeda di day 1
$units = [1, 2, 3];
$dayPosition = 1;

foreach ($units as $unitId) {
    $unitOffset = $offsetMethod->invoke($service, $unitId);
    $patternPosition = ((($dayPosition - 1) + $unitOffset) % 15) + 1;
    $pattern = $patternMethod->invoke($service, $patternPosition);
    
    echo "Unit $unitId (offset $unitOffset): Day $dayPosition -> Pattern Day $patternPosition\n";
    echo "  Driver1: {$pattern['driver1']}, Driver2: {$pattern['driver2']}\n\n";
}

exit;
```

### 3. Test Conflict Reduction Simulation
```php
// Simulate scheduling untuk multiple units di tanggal sama
$testDate = '2025-01-01';
$units = [1, 2, 3, 4, 5];

$service = new App\Services\ScheduleGeneratorService();
$reflection = new ReflectionClass($service);

$offsetMethod = $reflection->getMethod('getUnitPatternOffset');
$offsetMethod->setAccessible(true);

$patternMethod = $reflection->getMethod('getPatternForDay');
$patternMethod->setAccessible(true);

echo "Conflict Analysis for $testDate:\n";
echo "=====================================\n";

$shiftDistribution = ['pagi' => [], 'siang' => [], 'off' => []];

foreach ($units as $unitId) {
    $unitOffset = $offsetMethod->invoke($service, $unitId);
    $patternPosition = ((0 + $unitOffset) % 15) + 1; // Day 1
    $pattern = $patternMethod->invoke($service, $patternPosition);
    
    echo "Unit $unitId: Pattern Day $patternPosition (offset $unitOffset)\n";
    echo "  Driver1: {$pattern['driver1']}, Driver2: {$pattern['driver2']}\n";
    
    // Track shift distribution
    if ($pattern['driver1'] === 'pagi') $shiftDistribution['pagi'][] = "Unit$unitId-D1";
    elseif ($pattern['driver1'] === 'siang') $shiftDistribution['siang'][] = "Unit$unitId-D1";
    else $shiftDistribution['off'][] = "Unit$unitId-D1";
    
    if ($pattern['driver2'] === 'pagi') $shiftDistribution['pagi'][] = "Unit$unitId-D2";
    elseif ($pattern['driver2'] === 'siang') $shiftDistribution['siang'][] = "Unit$unitId-D2";
    else $shiftDistribution['off'][] = "Unit$unitId-D2";
}

echo "\nShift Distribution Summary:\n";
echo "Pagi shifts: " . count($shiftDistribution['pagi']) . " (" . implode(', ', $shiftDistribution['pagi']) . ")\n";
echo "Siang shifts: " . count($shiftDistribution['siang']) . " (" . implode(', ', $shiftDistribution['siang']) . ")\n";
echo "Day offs: " . count($shiftDistribution['off']) . " (" . implode(', ', $shiftDistribution['off']) . ")\n";

echo "\nConflict Potential: " . (count($shiftDistribution['pagi']) > 5 || count($shiftDistribution['siang']) > 5 ? "HIGH" : "LOW") . "\n";

exit;
```

## Comprehensive Testing

### Test 1: Pattern Offset Distribution
```bash
php artisan tinker --execute="
\$service = new App\Services\ScheduleGeneratorService();
\$reflection = new ReflectionClass(\$service);
\$method = \$reflection->getMethod('getUnitPatternOffset');
\$method->setAccessible(true);

\$offsets = [];
for (\$i = 1; \$i <= 50; \$i++) {
    \$offset = \$method->invoke(\$service, \$i);
    \$offsets[] = \$offset;
}

echo 'Offset Distribution: ' . PHP_EOL;
\$distribution = array_count_values(\$offsets);
ksort(\$distribution);
foreach (\$distribution as \$offset => \$count) {
    echo \"Offset \$offset: \$count units\" . PHP_EOL;
}

echo PHP_EOL . 'Statistics:' . PHP_EOL;
echo 'Min: ' . min(\$offsets) . PHP_EOL;
echo 'Max: ' . max(\$offsets) . PHP_EOL;
echo 'Unique offsets: ' . count(array_unique(\$offsets)) . '/15' . PHP_EOL;
"
```

### Test 2: Consistency Check
```bash
php artisan tinker --execute="
\$service = new App\Services\ScheduleGeneratorService();
\$reflection = new ReflectionClass(\$service);
\$method = \$reflection->getMethod('getUnitPatternOffset');
\$method->setAccessible(true);

echo 'Consistency Test:' . PHP_EOL;
for (\$unitId = 1; \$unitId <= 10; \$unitId++) {
    \$offset1 = \$method->invoke(\$service, \$unitId);
    \$offset2 = \$method->invoke(\$service, \$unitId);
    \$offset3 = \$method->invoke(\$service, \$unitId);
    
    \$consistent = (\$offset1 === \$offset2 && \$offset2 === \$offset3);
    echo \"Unit \$unitId: \" . (\$consistent ? 'PASS' : 'FAIL') . \" (offset: \$offset1)\" . PHP_EOL;
}
"
```

### Test 3: Real Schedule Generation
```php
// Test dengan real data
use App\Models\Unit;
use App\Models\Route;
use App\Services\ScheduleGeneratorService;

$service = new ScheduleGeneratorService();

// Get first available unit dan route
$unit = Unit::where('status', 'aktif')->first();
$route = $unit->routes()->first();

if ($unit && $route) {
    echo "Testing with Unit {$unit->id} ({$unit->unit_number}) and Route {$route->id}\n";
    
    // Generate schedule
    $result = $service->generateSchedules(
        $route->id,
        $unit->id,
        '2025-01-01',
        '2025-01-03'
    );
    
    if ($result['success']) {
        echo "SUCCESS: Generated " . count($result['data']['schedules']) . " schedules\n";
        echo "Pattern info: " . json_encode($result['data']['pattern_info'], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "FAILED: " . $result['message'] . "\n";
    }
} else {
    echo "No unit or route available for testing\n";
}

exit;
```

## Manual Testing Steps

### Step 1: Verify Pattern Offset
1. Run pattern offset test untuk memastikan setiap unit dapat offset yang berbeda
2. Check consistency - unit yang sama harus dapat offset yang sama
3. Verify distribution - offset should be reasonably distributed across 0-14

### Step 2: Test Conflict Reduction
1. Simulate scheduling untuk 5 units di tanggal yang sama
2. Check bahwa driver cadangan tidak akan dapat shift yang sama di semua units
3. Verify pattern masih maintain balance

### Step 3: Integration Test
1. Generate real schedules untuk 2-3 units berbeda
2. Check logs untuk pattern offset information
3. Verify tidak ada scheduling conflicts
4. Check database untuk ensure schedules created correctly

### Step 4: Performance Test
1. Generate schedules untuk multiple units dalam period yang sama
2. Monitor execution time dan memory usage
3. Check logs untuk any performance degradation

## Expected Results

### Pattern Offset Distribution:
```
Unit 1: Offset 7
Unit 2: Offset 3
Unit 3: Offset 11
Unit 4: Offset 5
Unit 5: Offset 14
```

### Conflict Reduction:
- Before: Semua units dapat pattern yang sama → High conflict potential
- After: Setiap unit dapat pattern offset → Low conflict potential

### Logging Output:
```
[INFO] Pattern Day 8/15 (Unit 1 offset: 7): Driver1=Pagi, Driver2=Siang
[INFO] Pattern Day 4/15 (Unit 2 offset: 3): Driver1=Pagi, Driver2=Siang
[INFO] ✓ Batangan pattern: Driver John assigned pagi shift on 2025-01-01 (Pattern Day 8, Unit 1 offset: 7, Monthly: 5/14)
```

## Troubleshooting

### Issue 1: Same Offset for Different Units
- Very rare dengan CRC32
- Check dengan larger sample size
- Consider alternative hash function jika perlu

### Issue 2: Pattern Not Working
- Check method accessibility dalam reflection
- Verify pattern array structure
- Check log outputs untuk debugging

### Issue 3: Performance Issues
- Minimal impact expected
- Monitor database query counts
- Check memory usage patterns

## Success Criteria

✅ **Pattern Offset Consistency:** Same unit always gets same offset
✅ **Offset Distribution:** Reasonable spread across 0-14 range  
✅ **Conflict Reduction:** Reduced scheduling conflicts for cadangan drivers
✅ **Pattern Balance:** Each unit maintains 15-day cycle balance
✅ **Backward Compatibility:** Existing functionality not affected
✅ **Performance:** No significant performance degradation
✅ **Logging:** Clear visibility into pattern offset information
