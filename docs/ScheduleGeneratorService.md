# Schedule Generator Service Documentation

## Overview

The `ScheduleGeneratorService` is a comprehensive service for automatically generating driver schedules based on complex business rules and constraints. It handles driver assignments, shift scheduling, and validates all business logic related to schedule creation.

## Features

- **Automatic Schedule Generation**: Creates schedules for specified date ranges
- **Driver Type Support**: Handles both `batangan` and `cadangan` driver types
- **Monthly Limits**: Enforces different monthly shift limits per driver type
- **Shift Rules**: Implements complex shift transition rules
- **Conflict Prevention**: Prevents double-booking and invalid assignments
- **Priority System**: Prioritizes batangan drivers over cadangan drivers
- **Validation**: Comprehensive input validation and business rule checking

## Business Rules Implemented

### Driver Types and Limits
- **Batangan drivers**: Maximum 14 shifts per month
- **Cadangan drivers**: Maximum 11 shifts per month

### Shift Rules
- Two shifts available: `pagi` (morning) and `siang` (afternoon)
- If previous day had `siang` shift, current day can only have `siang` shift
- If previous day had only `pagi` shift, current day can have both shifts
- If two days ago had schedules, both shifts are allowed
- If no previous schedules exist, both shifts are allowed

### Driver Assignment Rules
- Driver cannot be scheduled for more than 1 shift on the same day
- Driver cannot be active in 2 different units on the same day
- Driver cannot have duplicate schedules for same date and shift
- Cadangan drivers cannot be scheduled for two shifts in one day
- Only active drivers (`status = 'aktif'`) can be assigned
- Driver must be registered for the specific unit
- Driver must not exceed monthly shift limits
- Priority given to batangan drivers over cadangan drivers

### Validation Rules
- Unit must have access to the specified route
- Unit must be active (`status = 'aktif'`)
- Route must exist
- Date range must be valid (start_date <= end_date)

## Usage

### Basic Usage

```php
use App\Services\ScheduleGeneratorService;

$service = new ScheduleGeneratorService();

$result = $service->generateSchedules(
    $routeId,      // Route ID
    $unitId,       // Unit ID  
    $startDate,    // Start date (Y-m-d format)
    $endDate       // End date (Y-m-d format)
);

if ($result['success']) {
    echo "Generated " . $result['data']['generated_schedules'] . " schedules";
} else {
    echo "Error: " . $result['message'];
}
```

### Advanced Usage

```php
// Clear existing schedules before generating new ones
$deleted = $service->clearExistingSchedules($routeId, $unitId, $startDate, $endDate);

// Generate new schedules
$result = $service->generateSchedules($routeId, $unitId, $startDate, $endDate);

// Get driver statistics
$stats = $service->getDriverMonthlyStats($driverId, $monthStart, $monthEnd);
```

## Controller Integration

### Web Interface

```php
public function generate(Request $request)
{
    $request->validate([
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'route_id' => 'required|exists:routes,id',
        'unit_id' => 'required|exists:units,id'
    ]);

    $scheduleService = new ScheduleGeneratorService();
    
    // Optional: Clear existing schedules
    if ($request->input('clear_existing', false)) {
        $scheduleService->clearExistingSchedules(
            $request->route_id,
            $request->unit_id,
            $request->start_date,
            $request->end_date
        );
    }

    $result = $scheduleService->generateSchedules(
        $request->route_id,
        $request->unit_id,
        $request->start_date,
        $request->end_date
    );

    if ($result['success']) {
        return redirect()->route('schedules.index')
            ->with('success', 'Schedules generated successfully');
    } else {
        return redirect()->back()
            ->with('error', $result['message']);
    }
}
```

# Schedule Generator Service Documentation

## Overview

The `ScheduleGeneratorService` is a comprehensive service for automatically generating driver schedules based on complex business rules and constraints. It handles driver assignments, shift scheduling, and validates all business logic related to schedule creation.

## Features

- **Automatic Schedule Generation**: Creates schedules for specified date ranges
- **Driver Type Support**: Handles both `batangan` and `cadangan` driver types
- **Monthly Limits**: Enforces different monthly shift limits per driver type
- **Shift Rules**: Implements complex shift transition rules
- **Conflict Prevention**: Prevents double-booking and invalid assignments
- **Priority System**: Prioritizes batangan drivers over cadangan drivers
- **Intelligent Distribution**: Distributes workload evenly among available drivers
- **Workload Analysis**: Analyzes capacity and suggests optimization strategies
- **Validation**: Comprehensive input validation and business rule checking

## Business Rules Implemented

### Driver Types and Limits
- **Batangan drivers**: Maximum 14 shifts per month
- **Cadangan drivers**: Maximum 11 shifts per month

### Shift Rules
- Two shifts available: `pagi` (morning) and `siang` (afternoon)
- If previous day had `siang` shift, current day can only have `siang` shift
- If previous day had only `pagi` shift, current day can have both shifts
- If two days ago had schedules, both shifts are allowed
- If no previous schedules exist, both shifts are allowed

### Driver Assignment Rules
- Driver cannot be scheduled for more than 1 shift on the same day
- Driver cannot be active in 2 different units on the same day
- Driver cannot have duplicate schedules for same date and shift
- Cadangan drivers cannot be scheduled for two shifts in one day
- Only active drivers (`status = 'aktif'`) can be assigned
- Driver must be registered for the specific unit
- Driver must not exceed monthly shift limits
- Priority given to batangan drivers over cadangan drivers

### Distribution Logic
- Prioritizes drivers with fewer monthly shifts
- Considers recent activity to avoid overworking specific drivers
- Takes into account time since last shift for fair rotation
- Maintains consistent ordering for predictable results

### Validation Rules
- Unit must have access to the specified route
- Unit must be active (`status = 'aktif'`)
- Route must exist
- Date range must be valid (start_date <= end_date)

## Usage

### Basic Usage

```php
use App\Services\ScheduleGeneratorService;

$service = new ScheduleGeneratorService();

$result = $service->generateSchedules(
    $routeId,      // Route ID
    $unitId,       // Unit ID  
    $startDate,    // Start date (Y-m-d format)
    $endDate       // End date (Y-m-d format)
);

if ($result['success']) {
    echo "Generated " . $result['data']['generated_schedules'] . " schedules";
} else {
    echo "Error: " . $result['message'];
}
```

### Advanced Usage

```php
// Clear existing schedules before generating new ones
$deleted = $service->clearExistingSchedules($routeId, $unitId, $startDate, $endDate);

// Generate new schedules
$result = $service->generateSchedules($routeId, $unitId, $startDate, $endDate);

// Get driver statistics
$stats = $service->getDriverMonthlyStats($driverId, $monthStart, $monthEnd);
```

## Controller Integration

### Web Interface

```php
public function generate(Request $request)
{
    $request->validate([
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'route_id' => 'required|exists:routes,id',
        'unit_id' => 'required|exists:units,id'
    ]);

    $scheduleService = new ScheduleGeneratorService();
    
    // Optional: Clear existing schedules
    if ($request->input('clear_existing', false)) {
        $scheduleService->clearExistingSchedules(
            $request->route_id,
            $request->unit_id,
            $request->start_date,
            $request->end_date
        );
    }

    $result = $scheduleService->generateSchedules(
        $request->route_id,
        $request->unit_id,
        $request->start_date,
        $request->end_date
    );

    if ($result['success']) {
        return redirect()->route('schedules.index')
            ->with('success', 'Schedules generated successfully');
    } else {
        return redirect()->back()
            ->with('error', $result['message']);
    }
}
```

## Response Format

### Success Response
```json
{
    "success": true,
    "message": "Jadwal berhasil dibuat",
    "data": {
        "generated_schedules": 5,
        "skipped_dates": ["2025-06-06"],
        "errors": [],
        "schedules": [...]
    }
}
```

### Error Response
```json
{
    "success": false,
    "message": "Unit tidak ditemukan atau tidak aktif",
    "data": []
}
```

## Driver Statistics

The service provides detailed statistics for drivers:

```php
$stats = $service->getDriverMonthlyStats($driverId, $monthStart, $monthEnd);

// Returns:
[
    'total_shifts' => 8,
    'pagi_shifts' => 4,
    'siang_shifts' => 4,
    'remaining_shifts' => 6,
    'max_shifts' => 14,
    'driver_type' => 'batangan'
]
```

## Testing

The service includes comprehensive tests covering:

- Valid schedule generation
- Monthly limit enforcement
- Double-booking prevention
- Shift rule validation
- Unit-route relationship validation
- Driver prioritization
- Workload distribution
- Statistics calculation

Run tests with:
```bash
php artisan test --filter=ScheduleGeneratorServiceTest
```

## Logging

The service logs important events and errors:

- Schedule creation success/failure
- Driver selection decisions
- Business rule violations
- Workload analysis results
- Performance metrics

Logs are written to Laravel's default log channel.

## Performance Considerations

- Uses database transactions for data integrity
- Implements efficient querying to minimize database calls
- Handles large date ranges gracefully
- Provides workload analysis for optimization
- Uses intelligent driver distribution algorithms

## Error Handling

The service handles various error scenarios:

- Invalid input parameters
- Missing or inactive entities
- Business rule violations
- Database constraint violations
- Unexpected exceptions

All errors are logged and return user-friendly messages.

## Extensibility

The service is designed to be easily extended:

- Add new shift types by modifying constants
- Implement additional business rules in validation methods
- Add new driver selection algorithms
- Integrate with external systems via hooks
- Customize distribution logic for specific needs

## Constants

```php
const BATANGAN_MAX_SHIFTS = 14;
const CADANGAN_MAX_SHIFTS = 11;
const SHIFT_PAGI = 'pagi';
const SHIFT_SIANG = 'siang';
const DRIVER_TYPE_BATANGAN = 'batangan';
const DRIVER_TYPE_CADANGAN = 'cadangan';
const STATUS_AKTIF = 'aktif';
const SCHEDULE_STATUS_SCHEDULED = 'scheduled';
```

## Dependencies

- Laravel Framework
- Carbon for date manipulation
- Eloquent ORM for database operations
- Laravel's DB facade for transactions

## Response Format

### Success Response
```json
{
    "success": true,
    "message": "Jadwal berhasil dibuat",
    "data": {
        "generated_schedules": 5,
        "skipped_dates": ["2025-06-06"],
        "errors": [],
        "schedules": [...]
    }
}
```

### Error Response
```json
{
    "success": false,
    "message": "Unit tidak ditemukan atau tidak aktif",
    "data": []
}
```

## Testing

The service includes comprehensive tests covering:

- Valid schedule generation
- Monthly limit enforcement
- Double-booking prevention
- Shift rule validation
- Unit-route relationship validation
- Driver prioritization
- Statistics calculation

Run tests with:
```bash
php artisan test --filter=ScheduleGeneratorServiceTest
```

## Logging

The service logs important events and errors:

- Schedule creation success/failure
- Driver selection decisions
- Business rule violations
- Performance metrics

Logs are written to Laravel's default log channel.

## Performance Considerations

- Uses database transactions for data integrity
- Implements efficient querying to minimize database calls
- Handles large date ranges gracefully
- Provides detailed error reporting for debugging

## Error Handling

The service handles various error scenarios:

- Invalid input parameters
- Missing or inactive entities
- Business rule violations
- Database constraint violations
- Unexpected exceptions

All errors are logged and return user-friendly messages.

## Extensibility

The service is designed to be easily extended:

- Add new shift types by modifying constants
- Implement additional business rules in validation methods
- Add new driver selection algorithms
- Integrate with external systems via hooks

## Constants

```php
const BATANGAN_MAX_SHIFTS = 14;
const CADANGAN_MAX_SHIFTS = 11;
const SHIFT_PAGI = 'pagi';
const SHIFT_SIANG = 'siang';
const DRIVER_TYPE_BATANGAN = 'batangan';
const DRIVER_TYPE_CADANGAN = 'cadangan';
const STATUS_AKTIF = 'aktif';
const SCHEDULE_STATUS_SCHEDULED = 'scheduled';
```

## Dependencies

- Laravel Framework
- Carbon for date manipulation
- Eloquent ORM for database operations
- Laravel's DB facade for transactions
