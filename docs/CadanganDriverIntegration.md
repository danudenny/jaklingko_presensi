# Enhanced Schedule Pattern with Cadangan Driver Integration

## Overview
Update implementasi pattern scheduling untuk mengintegrasikan driver cadangan sebagai backup, memastikan setiap hari memiliki 2 shift penuh (pagi dan siang).

## New Logic Flow

### Phase 1: Batangan Pattern Application
Driver batangan mengikuti pattern 15 hari yang sudah ditentukan:
```
Day:   D1  D2  D3  D4  D5  D6  D7  D8  D9  D10 D11 D12 D13 D14 D15
Dr1:   P   S   -   P   P   P   P   P   P   P   P   P   P   P   S
Dr2:   S   -   P   S   S   S   S   S   S   S   S   S   S   S   -
```

### Phase 2: Cadangan Backup Coverage
Driver cadangan mengisi slot yang kosong dari pattern batangan:
```
Original Pattern (Batangan Only):
Day 2:  Dr1=S, Dr2=- → Missing: Pagi shift
Day 3:  Dr1=-, Dr2=P → Missing: Siang shift  
Day 8:  Dr1=-, Dr2=S → Missing: Pagi shift
Day 15: Dr1=S, Dr2=- → Missing: Pagi shift

With Cadangan Backup:
Day 2:  Dr1=S, Dr2=-, Cadangan1=P → Complete
Day 3:  Dr1=-, Dr2=P, Cadangan1=S → Complete
Day 8:  Dr1=-, Dr2=S, Cadangan1=P → Complete  
Day 15: Dr1=S, Dr2=-, Cadangan1=P → Complete
```

### Phase 3: Fallback Coverage
Jika driver cadangan tidak tersedia, driver batangan lain akan mengisi slot kosong.

## Implementation Details

### 1. Multi-Phase Scheduling
```php
private function generateSchedulesForDate(...): array
{
    $assignedShifts = [];
    
    // Phase 1: Apply batangan pattern
    $schedules = $this->applyBatanganPattern(..., $assignedShifts);
    
    // Phase 2: Fill with cadangan drivers
    $schedules = array_merge($schedules, 
        $this->fillWithCadanganDrivers(..., $assignedShifts));
    
    // Phase 3: Fallback with remaining batangan
    $schedules = array_merge($schedules, 
        $this->fillRemainingSlots(..., $assignedShifts));
    
    return $schedules;
}
```

### 2. Shift Tracking
```php
// Track which shifts are already assigned
$assignedShifts = [
    'pagi' => $driverId,   // if assigned
    'siang' => $driverId   // if assigned
];

// Find empty shifts
$allShifts = ['pagi', 'siang'];
$emptyShifts = array_diff($allShifts, array_keys($assignedShifts));
```

### 3. Fair Distribution for Cadangan
```php
private function sortDriversForDistribution($drivers, $monthStart, $monthEnd, $dateString)
{
    // Sort by:
    // 1. Monthly shift count (fewer = higher priority)
    // 2. Recent activity (less = higher priority)  
    // 3. Time since last shift (longer = higher priority)
    // 4. Name (alphabetical for consistency)
}
```

## Benefits

### 1. Complete Coverage
- **Guaranteed 2 shifts per day**: Setiap hari akan memiliki shift pagi dan siang
- **No empty slots**: Driver cadangan mengisi semua slot kosong
- **Flexible fallback**: Driver batangan bisa mengisi jika cadangan tidak tersedia

### 2. Predictable Primary Schedule
- **Batangan pattern maintained**: Driver utama tetap mengikuti pattern 15 hari
- **Consistent assignment**: Driver batangan assignment tetap predictable
- **Pattern integrity**: Core pattern tidak berubah

### 3. Fair Cadangan Distribution
- **Workload balancing**: Driver cadangan didistribusi berdasarkan workload
- **Monthly limit respect**: Tetap menghormati batas 11 shifts per bulan
- **Rotation fairness**: Driver cadangan bergantian mengisi slot

### 4. Intelligent Priority
- **Primary pattern first**: Pattern batangan diaplikasikan dulu
- **Smart backup**: Cadangan hanya mengisi slot yang benar-benar kosong
- **No conflicts**: Tidak ada double assignment atau konflik

## Example Schedule Output

### Day 2 (Pattern Day 2)
```
Original Pattern: Dr1=Siang, Dr2=Libur
Result: 
- 07:00-15:00 (Pagi): Cadangan Driver A
- 15:00-23:00 (Siang): Batangan Driver 1
```

### Day 3 (Pattern Day 3)  
```
Original Pattern: Dr1=Libur, Dr2=Pagi
Result:
- 07:00-15:00 (Pagi): Batangan Driver 2
- 15:00-23:00 (Siang): Cadangan Driver B
```

### Day 4 (Pattern Day 4)
```
Original Pattern: Dr1=Pagi, Dr2=Siang
Result:
- 07:00-15:00 (Pagi): Batangan Driver 1
- 15:00-23:00 (Siang): Batangan Driver 2
(No cadangan needed - pattern complete)
```

## Logging Enhancement

### Enhanced Log Messages
```
Batangan pattern: Driver John (123) assigned pagi shift on 2025-01-02 (Pattern Day 2)
Cadangan fill: Driver Maria (456) assigned siang shift on 2025-01-03
Batangan fallback: Driver Bob (789) assigned pagi shift on 2025-01-08
All shifts already assigned by batangan drivers on 2025-01-04
```

### Coverage Tracking
- **Batangan assignments**: Tracked with pattern day
- **Cadangan fills**: Clearly identified as backup
- **Empty slots**: Logged as warnings
- **Complete coverage**: Confirmed per day

## Usage Scenarios

### Scenario 1: Optimal Setup
- **2+ Batangan drivers**: Pattern works normally
- **2+ Cadangan drivers**: All empty slots filled
- **Result**: Perfect 2 shifts per day coverage

### Scenario 2: Limited Cadangan
- **2+ Batangan drivers**: Pattern works normally  
- **1 Cadangan driver**: Some empty slots filled
- **Result**: Better coverage than pattern-only

### Scenario 3: No Cadangan
- **2+ Batangan drivers**: Pattern works normally
- **0 Cadangan drivers**: Fallback to batangan
- **Result**: Same as original pattern

### Scenario 4: Mixed Capacity
- **Batangan monthly limit reached**: Cadangan takes over
- **Cadangan monthly limit reached**: Batangan fallback
- **Result**: Maximum possible coverage within limits

## Testing Considerations

### Test Cases
1. **Full coverage**: 2 batangan + 2+ cadangan
2. **Partial coverage**: 2 batangan + 1 cadangan  
3. **Pattern only**: 2 batangan + 0 cadangan
4. **Monthly limits**: Test when drivers reach limits
5. **Mixed scenarios**: Various driver combinations

### Expected Results
- **Day 2, 3, 8, 15**: Always have 2 shifts (pattern + cadangan)
- **Other days**: Already have 2 shifts from pattern
- **Monthly compliance**: No driver exceeds their limits
- **Fair rotation**: Cadangan drivers rotate fairly

## Performance Impact

### Minimal Overhead
- **3-phase processing**: Sequential, not parallel
- **Smart filtering**: Only process empty shifts
- **Cached queries**: Monthly counts calculated once
- **Early exit**: Skip phases when not needed

### Database Efficiency
- **Batch processing**: All shifts for a date processed together
- **Optimized queries**: Reuse driver lists and counts
- **Transaction safety**: All changes in single transaction

## Configuration

### No Configuration Changes Needed
- **Same constants**: BATANGAN_MAX_SHIFTS = 14, CADANGAN_MAX_SHIFTS = 11
- **Same pattern**: 15-day pattern unchanged
- **Same validation**: All existing rules apply
- **Same UI**: No form changes required

### Automatic Adaptation
- **Driver availability**: Adapts to available drivers
- **Monthly limits**: Respects all existing limits
- **Conflict rules**: All existing conflict prevention maintained
