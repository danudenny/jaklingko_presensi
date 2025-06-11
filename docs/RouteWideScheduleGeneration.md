# Route-Wide Schedule Generation Enhancement

## Overview

Fitur baru ini memungkinkan schedule generation untuk seluruh unit dalam satu route sekaligus, tidak hanya untuk unit spesifik. Hal ini memberikan fleksibilitas yang lebih besar dan efisiensi dalam management jadwal.

## Key Features

### 1. Flexible Unit Targeting
- **Specific Unit:** `generateSchedules(routeId, unitId, startDate, endDate)`
- **All Units in Route:** `generateSchedules(routeId, null, startDate, endDate)`

### 2. Enhanced Unit Pattern Rotation
- Setiap unit mendapat pattern offset yang berbeda
- Mengurangi konflik scheduling untuk driver cadangan
- Automatic conflict prevention across multiple units

### 3. Comprehensive Results
- Individual unit results dengan detailed statistics
- Route-wide summary dan analytics
- Pattern offset distribution tracking
- Multi-unit coverage statistics

## API Changes

### Method Signature Update

#### Before:
```php
public function generateSchedules(int $routeId, int $unitId, string $startDate, string $endDate): array
```

#### After:
```php
public function generateSchedules(int $routeId, ?int $unitId, string $startDate, string $endDate): array
```

### Input Validation Enhancement

#### New Logic:
1. **Route Validation:** Always validates route exists
2. **Unit Selection:**
   - If `$unitId` provided → Validates specific unit
   - If `$unitId` is null → Gets all active units for the route
3. **Unit-Route Relationship:** Validates all selected units have access to the route

## Usage Examples

### Generate for Specific Unit (Existing Behavior)
```php
$service = new ScheduleGeneratorService();

$result = $service->generateSchedules(
    routeId: 1,
    unitId: 5,
    startDate: '2025-01-01',
    endDate: '2025-01-15'
);

// Result structure remains the same for backward compatibility
```

### Generate for All Units in Route (New Feature)
```php
$service = new ScheduleGeneratorService();

$result = $service->generateSchedules(
    routeId: 1,
    unitId: null, // or simply omit the parameter
    startDate: '2025-01-01',
    endDate: '2025-01-15'
);

// Enhanced result structure with multi-unit data
```

## Enhanced Result Structure

### Single Unit Result (Unchanged)
```php
[
    'success' => true,
    'message' => 'Jadwal berhasil dibuat untuk unit 5...',
    'data' => [
        'generated_schedules' => 30,
        'processed_units' => 1,
        'schedules' => [...],
        'coverage_stats' => [...],
        'pattern_info' => [...]
    ]
]
```

### Multi-Unit Result (New)
```php
[
    'success' => true,
    'message' => 'Jadwal berhasil dibuat untuk 5 unit dalam route 1...',
    'data' => [
        'generated_schedules' => 150,      // Total across all units
        'processed_units' => 5,           // Number of units processed
        'schedules' => [...],             // All schedules combined
        'unit_results' => [               // Individual unit results
            1 => [
                'success' => true,
                'unit_info' => [...],
                'generated_schedules' => 30,
                'coverage_stats' => [...],
                'pattern_info' => [
                    'unit_pattern_offset' => 7
                ]
            ],
            2 => [
                'success' => true,
                'unit_info' => [...],
                'generated_schedules' => 30,
                'pattern_info' => [
                    'unit_pattern_offset' => 3
                ]
            ]
            // ... other units
        ],
        'pattern_info' => [
            'unit_rotation' => 'Enabled',
            'conflict_prevention' => 'Enabled'
        ]
    ]
]
```

## Pattern Offset Distribution

### Automatic Offset Assignment
Setiap unit mendapat offset yang berbeda berdasarkan hash function:

```php
Unit 1: Offset 7  → Day 1 starts at Pattern Day 8
Unit 2: Offset 3  → Day 1 starts at Pattern Day 4  
Unit 3: Offset 11 → Day 1 starts at Pattern Day 12
Unit 4: Offset 1  → Day 1 starts at Pattern Day 2
Unit 5: Offset 9  → Day 1 starts at Pattern Day 10
```

### Conflict Reduction Benefits
- Driver cadangan memiliki variasi shift yang lebih besar across units
- Reduced scheduling conflicts ketika cadangan drivers cover multiple units
- Natural load balancing untuk driver resources

## Logging Enhancement

### Individual Unit Processing
```
[INFO] Starting schedule generation for route 1 (all units)
[INFO] === PROCESSING UNIT 1 (A001) ===
[INFO] Pattern Day 8/15 (Unit 1 offset: 7): Driver1=Pagi, Driver2=Siang
[INFO] === PROCESSING UNIT 2 (A002) ===  
[INFO] Pattern Day 4/15 (Unit 2 offset: 3): Driver1=Pagi, Driver2=Siang
```

### Pattern Offset Tracking
```
[INFO] ✓ Batangan pattern: Driver John assigned pagi shift on 2025-01-01 (Pattern Day 8, Unit 1 offset: 7, Monthly: 5/14)
[INFO] ✓ Batangan pattern: Driver Jane assigned pagi shift on 2025-01-01 (Pattern Day 4, Unit 2 offset: 3, Monthly: 3/14)
```

## Database Impact

### Schedule Storage
- Setiap schedule tetap disimpan dengan `unit_id` yang spesifik
- `route_id` konsisten untuk semua schedules dalam satu generation
- No schema changes required

### Data Integrity
- Unit-specific pattern offsets maintain schedule predictability
- Cross-unit conflict prevention through validation rules
- Atomic transactions ensure data consistency

## Performance Considerations

### Optimizations
- **Batch Processing:** Process units sequentially dengan shared date range
- **Memory Management:** Results accumulated efficiently
- **Database Efficiency:** Minimal additional queries

### Scalability
- Linear scaling dengan number of units
- Pattern offset calculation is O(1) per unit
- Database transactions optimized for multi-unit operations

## Error Handling

### Unit-Level Errors
```php
'unit_results' => [
    1 => [
        'success' => true,
        'generated_schedules' => 30
    ],
    2 => [
        'success' => false,
        'message' => 'Tidak ada driver aktif untuk unit A002',
        'unit_info' => [...]
    ]
]
```

### Partial Success Handling
- System continues processing other units jika ada unit yang gagal
- Individual unit errors tidak menggagalkan entire operation
- Comprehensive error reporting per unit

## Backward Compatibility

### API Compatibility
- ✅ Existing calls dengan `unitId` tetap berfungsi seperti sebelumnya
- ✅ Response structure untuk single unit unchanged
- ✅ No breaking changes untuk existing integrations

### Method Signatures
```php
// Both calls valid and backward compatible
$service->generateSchedules(1, 5, '2025-01-01', '2025-01-15');    // Single unit
$service->generateSchedules(1, null, '2025-01-01', '2025-01-15'); // All units
```

## Testing Scenarios

### Scenario 1: Route dengan Multiple Units
```php
// Test generation untuk route dengan 5 units
$result = $service->generateSchedules(1, null, '2025-01-01', '2025-01-15');

// Verify:
// - 5 units processed
// - Each unit has different pattern offset
// - No scheduling conflicts
// - All schedules have correct unit_id
```

### Scenario 2: Mixed Driver Types
```php
// Test dengan units yang memiliki different driver compositions
// Unit 1: 2 batangan, 3 cadangan
// Unit 2: 3 batangan, 2 cadangan  
// Unit 3: 1 batangan, 4 cadangan

// Verify pattern adaptation per unit
```

### Scenario 3: Error Recovery
```php
// Test dengan some units having no drivers
// Verify system continues processing other units
// Check comprehensive error reporting
```

## Monitoring & Analytics

### Key Metrics
1. **Generation Success Rate:** Percentage of successful unit generations
2. **Pattern Distribution:** Distribution of pattern offsets across units
3. **Conflict Reduction:** Reduction in scheduling conflicts
4. **Coverage Percentage:** Overall schedule coverage across all units

### Dashboard Potential
- Route-wide schedule visualization
- Unit pattern offset mapping
- Driver utilization across multiple units
- Conflict analysis dan resolution tracking

## Configuration Options

### Future Enhancements
1. **Custom Pattern Offsets:** Manual offset assignment per unit
2. **Priority-Based Generation:** Process high-priority units first
3. **Parallel Processing:** Concurrent unit processing for large routes
4. **Advanced Conflict Resolution:** Cross-unit driver reallocation

## Implementation Benefits

### Operational Efficiency
- ✅ Single call generates schedules untuk entire route
- ✅ Consistent pattern application across all units  
- ✅ Reduced administrative overhead
- ✅ Better resource utilization

### System Scalability
- ✅ Handles routes dengan varying number of units
- ✅ Efficient pattern rotation prevents conflicts
- ✅ Comprehensive error handling dan recovery
- ✅ Detailed analytics dan monitoring capabilities

### Developer Experience
- ✅ Backward compatible API
- ✅ Enhanced result structure dengan detailed information
- ✅ Comprehensive logging untuk debugging
- ✅ Flexible input validation

## Conclusion

Route-wide schedule generation enhancement memberikan:

1. **Flexibility:** Generate untuk specific unit atau entire route
2. **Efficiency:** Single operation untuk multiple units
3. **Reliability:** Enhanced conflict prevention dan error handling
4. **Scalability:** Optimized untuk routes dengan banyak units
5. **Compatibility:** Fully backward compatible dengan existing usage

Fitur ini significantly improves operasional efficiency untuk route management while maintaining all existing functionality dan adding powerful new capabilities.
