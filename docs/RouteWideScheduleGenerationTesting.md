# Testing Script untuk Route-Wide Schedule Generation

## Quick Verification Commands

### 1. Test Input Validation
```bash
php artisan tinker
```

```php
// Test validation dengan different scenarios
$service = new App\Services\ScheduleGeneratorService();

// Test 1: Valid route dengan unitId null
$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('validateInput');
$method->setAccessible(true);

$result1 = $method->invoke($service, 1, null, '2025-01-01', '2025-01-15');
echo "Route-wide validation: " . ($result1['valid'] ? 'PASS' : 'FAIL') . "\n";
if ($result1['valid']) {
    echo "Units found: " . $result1['units']->count() . "\n";
}

// Test 2: Valid route dengan specific unitId  
$result2 = $method->invoke($service, 1, 1, '2025-01-01', '2025-01-15');
echo "Specific unit validation: " . ($result2['valid'] ? 'PASS' : 'FAIL') . "\n";

// Test 3: Invalid route
$result3 = $method->invoke($service, 999, null, '2025-01-01', '2025-01-15');
echo "Invalid route validation: " . ($result3['valid'] ? 'FAIL' : 'PASS') . "\n";

exit;
```

### 2. Test Pattern Offset Distribution
```php
// Test pattern offset untuk multiple units
$service = new App\Services\ScheduleGeneratorService();
$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('getUnitPatternOffset');
$method->setAccessible(true);

echo "Pattern Offset Distribution Test:\n";
echo "================================\n";

$offsets = [];
for ($unitId = 1; $unitId <= 10; $unitId++) {
    $offset = $method->invoke($service, $unitId);
    $offsets[$unitId] = $offset;
    echo "Unit $unitId: Offset $offset\n";
}

// Check for distribution
$uniqueOffsets = array_unique($offsets);
echo "\nUnique offsets: " . count($uniqueOffsets) . "/10\n";
echo "Good distribution: " . (count($uniqueOffsets) >= 7 ? 'YES' : 'NO') . "\n";

exit;
```

### 3. Test Clear Existing Schedules
```php
// Test clearExistingSchedules untuk route-wide
use App\Models\Schedule;
use App\Services\ScheduleGeneratorService;

$service = new ScheduleGeneratorService();

// Check existing schedules
$before = Schedule::whereBetween('schedule_date', ['2025-01-01', '2025-01-15'])->count();
echo "Schedules before clear: $before\n";

// Test route-wide clear (unitId = null)
$cleared = $service->clearExistingSchedules(1, null, '2025-01-01', '2025-01-15');
echo "Schedules cleared (route-wide): $cleared\n";

$after = Schedule::whereBetween('schedule_date', ['2025-01-01', '2025-01-15'])->count();
echo "Schedules after clear: $after\n";

exit;
```

## Comprehensive Testing

### Test 1: Single Unit vs Route-Wide Generation
```bash
php artisan tinker --execute="
// Test single unit generation
\$service = new App\Services\ScheduleGeneratorService();

// Get first available route and unit
\$route = App\Models\Route::first();
\$unit = \$route->units()->where('status', 'aktif')->first();

if (\$route && \$unit) {
    echo 'Testing with Route ' . \$route->id . ' and Unit ' . \$unit->id . PHP_EOL;
    
    // Single unit generation
    \$result1 = \$service->generateSchedules(\$route->id, \$unit->id, '2025-01-01', '2025-01-03');
    echo 'Single unit: ' . (\$result1['success'] ? 'SUCCESS' : 'FAILED') . PHP_EOL;
    if (\$result1['success']) {
        echo 'Generated schedules: ' . \$result1['data']['generated_schedules'] . PHP_EOL;
        echo 'Processed units: ' . \$result1['data']['processed_units'] . PHP_EOL;
    }
    
    // Route-wide generation  
    \$result2 = \$service->generateSchedules(\$route->id, null, '2025-01-04', '2025-01-06');
    echo 'Route-wide: ' . (\$result2['success'] ? 'SUCCESS' : 'FAILED') . PHP_EOL;
    if (\$result2['success']) {
        echo 'Generated schedules: ' . \$result2['data']['generated_schedules'] . PHP_EOL;
        echo 'Processed units: ' . \$result2['data']['processed_units'] . PHP_EOL;
        
        if (isset(\$result2['data']['unit_results'])) {
            echo 'Unit results available: YES' . PHP_EOL;
            foreach (\$result2['data']['unit_results'] as \$unitId => \$unitResult) {
                echo \"Unit \$unitId: \" . (\$unitResult['success'] ? 'SUCCESS' : 'FAILED') . PHP_EOL;
            }
        }
    }
} else {
    echo 'No route or unit available for testing' . PHP_EOL;
}
"
```

### Test 2: Pattern Offset Conflict Analysis
```php
// Simulate scheduling untuk multiple units di tanggal yang sama
use App\Services\ScheduleGeneratorService;

$service = new ScheduleGeneratorService();
$reflection = new ReflectionClass($service);

$offsetMethod = $reflection->getMethod('getUnitPatternOffset');
$offsetMethod->setAccessible(true);

$patternMethod = $reflection->getMethod('getPatternForDay');
$patternMethod->setAccessible(true);

echo "Multi-Unit Conflict Analysis:\n";
echo "============================\n";

$testDate = '2025-01-01';
$units = range(1, 8); // Test dengan 8 units
$dayPosition = 1;

$shiftDistribution = ['pagi' => [], 'siang' => [], 'off' => []];

foreach ($units as $unitId) {
    $unitOffset = $offsetMethod->invoke($service, $unitId);
    $patternPosition = ((($dayPosition - 1) + $unitOffset) % 15) + 1;
    $pattern = $patternMethod->invoke($service, $patternPosition);
    
    echo "Unit $unitId (offset $unitOffset): Pattern Day $patternPosition\n";
    echo "  Driver1: {$pattern['driver1']}, Driver2: {$pattern['driver2']}\n";
    
    // Track distribution
    foreach (['driver1', 'driver2'] as $driver) {
        if ($pattern[$driver] === 'pagi') {
            $shiftDistribution['pagi'][] = "$unitId-$driver";
        } elseif ($pattern[$driver] === 'siang') {
            $shiftDistribution['siang'][] = "$unitId-$driver";
        } else {
            $shiftDistribution['off'][] = "$unitId-$driver";
        }
    }
}

echo "\nShift Distribution Analysis:\n";
echo "Pagi shifts needed: " . count($shiftDistribution['pagi']) . "\n";
echo "Siang shifts needed: " . count($shiftDistribution['siang']) . "\n";
echo "Day offs: " . count($shiftDistribution['off']) . "\n";

$maxShiftsPerType = max(count($shiftDistribution['pagi']), count($shiftDistribution['siang']));
echo "Max shifts of one type: $maxShiftsPerType\n";
echo "Conflict potential: " . ($maxShiftsPerType > 8 ? 'HIGH' : 'LOW') . "\n";

exit;
```

### Test 3: Multi-Unit Coverage Statistics
```php
// Test coverage statistics untuk multiple units
use App\Models\Unit;
use App\Services\ScheduleGeneratorService;

$service = new ScheduleGeneratorService();

// Get multiple units
$units = Unit::where('status', 'aktif')->take(3)->get();

if ($units->count() >= 3) {
    $unitIds = $units->pluck('id')->toArray();
    $dateRange = [];
    
    // Create date range
    for ($i = 0; $i < 5; $i++) {
        $dateRange[] = now()->addDays($i);
    }
    
    echo "Testing Multi-Unit Coverage Statistics:\n";
    echo "=====================================\n";
    echo "Units: " . implode(', ', $unitIds) . "\n";
    echo "Date range: 5 days\n";
    
    $stats = $service->calculateMultiUnitCoverageStatistics($unitIds, $dateRange);
    
    echo "Total possible shifts: {$stats['total_possible_shifts']}\n";
    echo "Total generated shifts: {$stats['total_generated_shifts']}\n";
    echo "Coverage percentage: {$stats['coverage_percentage']}%\n";
    
    foreach ($stats['unit_breakdown'] as $unitId => $unitStats) {
        echo "Unit $unitId: " . ($unitStats['total_schedules'] ?? 0) . " schedules\n";
    }
} else {
    echo "Not enough units for testing\n";
}

exit;
```

## Integration Testing

### Test 4: Real Data Generation
```php
// Full integration test dengan real data
use App\Models\Route;
use App\Models\Unit;
use App\Services\ScheduleGeneratorService;

$service = new ScheduleGeneratorService();

// Find route dengan multiple units
$route = Route::whereHas('units', function($q) {
    $q->where('status', 'aktif');
}, '>=', 2)->first();

if ($route) {
    $units = $route->units()->where('status', 'aktif')->get();
    
    echo "Full Integration Test:\n";
    echo "====================\n";
    echo "Route: {$route->id} ({$route->route_name})\n";
    echo "Units: {$units->count()}\n";
    
    // Clear existing schedules first
    $cleared = $service->clearExistingSchedules($route->id, null, '2025-01-07', '2025-01-09');
    echo "Cleared existing schedules: $cleared\n";
    
    // Generate new schedules
    $result = $service->generateSchedules(
        $route->id, 
        null, // All units
        '2025-01-07', 
        '2025-01-09'
    );
    
    if ($result['success']) {
        echo "SUCCESS!\n";
        echo "Generated schedules: {$result['data']['generated_schedules']}\n";
        echo "Processed units: {$result['data']['processed_units']}\n";
        
        // Check unit results
        if (isset($result['data']['unit_results'])) {
            foreach ($result['data']['unit_results'] as $unitId => $unitResult) {
                $unit = $units->find($unitId);
                $unitNumber = $unit ? $unit->unit_number : $unitId;
                echo "Unit $unitNumber: ";
                
                if ($unitResult['success']) {
                    echo "SUCCESS ({$unitResult['generated_schedules']} schedules)";
                    if (isset($unitResult['pattern_info']['unit_pattern_offset'])) {
                        echo " [Offset: {$unitResult['pattern_info']['unit_pattern_offset']}]";
                    }
                } else {
                    echo "FAILED - {$unitResult['message']}";
                }
                echo "\n";
            }
        }
        
        // Show pattern info
        if (isset($result['data']['pattern_info']['unit_rotation'])) {
            echo "Unit rotation: {$result['data']['pattern_info']['unit_rotation']}\n";
        }
        
    } else {
        echo "FAILED: {$result['message']}\n";
    }
} else {
    echo "No suitable route found for testing\n";
}

exit;
```

## Performance Testing

### Test 5: Performance Benchmark
```php
// Performance test untuk route dengan many units
use App\Models\Route;
use App\Services\ScheduleGeneratorService;

$service = new ScheduleGeneratorService();

// Find route dengan most units
$route = Route::withCount(['units' => function($q) {
    $q->where('status', 'aktif');
}])->orderBy('units_count', 'desc')->first();

if ($route && $route->units_count > 0) {
    echo "Performance Test:\n";
    echo "================\n";
    echo "Route: {$route->id} with {$route->units_count} units\n";
    
    $startTime = microtime(true);
    $startMemory = memory_get_usage();
    
    $result = $service->generateSchedules(
        $route->id,
        null,
        '2025-01-10',
        '2025-01-12' // 3 days
    );
    
    $endTime = microtime(true);
    $endMemory = memory_get_usage();
    
    $executionTime = round(($endTime - $startTime) * 1000, 2); // ms
    $memoryUsed = round(($endMemory - $startMemory) / 1024 / 1024, 2); // MB
    
    echo "Execution time: {$executionTime} ms\n";
    echo "Memory used: {$memoryUsed} MB\n";
    
    if ($result['success']) {
        $schedulesPerSecond = round($result['data']['generated_schedules'] / ($executionTime / 1000), 2);
        echo "Schedules generated: {$result['data']['generated_schedules']}\n";
        echo "Performance: {$schedulesPerSecond} schedules/second\n";
    }
} else {
    echo "No suitable route for performance testing\n";
}

exit;
```

## Error Handling Testing

### Test 6: Error Recovery
```php
// Test error handling dan recovery
use App\Services\ScheduleGeneratorService;

$service = new ScheduleGeneratorService();

echo "Error Handling Test:\n";
echo "===================\n";

// Test 1: Invalid route
$result1 = $service->generateSchedules(999, null, '2025-01-01', '2025-01-03');
echo "Invalid route: " . ($result1['success'] ? 'FAILED' : 'PASSED') . "\n";

// Test 2: Invalid date range
$result2 = $service->generateSchedules(1, null, '2025-01-15', '2025-01-01');
echo "Invalid date range: " . ($result2['success'] ? 'FAILED' : 'PASSED') . "\n";

// Test 3: Route dengan no units
// This test requires a route with no active units
// $result3 = $service->generateSchedules(routeWithNoUnits, null, '2025-01-01', '2025-01-03');

exit;
```

## Expected Results

### Successful Route-Wide Generation:
```
Route-wide generation: SUCCESS
Generated schedules: 90 (for 3 units × 3 days × 2 shifts × 5 pattern success rate)
Processed units: 3
Unit A001: SUCCESS (30 schedules) [Offset: 7]
Unit A002: SUCCESS (30 schedules) [Offset: 3] 
Unit A003: SUCCESS (30 schedules) [Offset: 11]
Unit rotation: Enabled
```

### Pattern Offset Distribution:
```
Unit 1: Offset 7
Unit 2: Offset 3
Unit 3: Offset 11
Unit 4: Offset 1
Unit 5: Offset 9
Unique offsets: 5/5
Good distribution: YES
```

### Performance Benchmarks:
- **Small Route (2-3 units):** <100ms, <1MB memory
- **Medium Route (5-8 units):** <300ms, <2MB memory  
- **Large Route (10+ units):** <500ms, <5MB memory

## Success Criteria

✅ **Input Validation:** Both unitId patterns work correctly
✅ **Pattern Distribution:** Units get different offsets
✅ **Multi-Unit Generation:** All units processed successfully
✅ **Error Handling:** Graceful handling of individual unit failures  
✅ **Performance:** Acceptable execution time dan memory usage
✅ **Data Integrity:** All schedules saved dengan correct unit_id
✅ **Backward Compatibility:** Existing single-unit calls work unchanged
✅ **Conflict Prevention:** Reduced scheduling conflicts across units
