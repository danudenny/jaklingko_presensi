# Schedule View Improvement - Single Driver Display

## Overview
Modified the consolidated schedule view to display each driver only once per unit, showing their shift assignments as letter codes (P, S, P+S) instead of duplicating the driver row for each shift.

## Changes Made

### 1. View Layout Changes

**BEFORE:**
```
Unit 260
  ├─ H. ABDUL MUNIR - Pagi:   ✓ ✓ ✓ ✓ ✓ ✓ X ✓ ✓ ✓ ✓ ✓ ✓ X
  └─ H. ABDUL MUNIR - Siang:  ○ ○ ○ ○ ○ ○ ○ ○ ○ ○ ○ ○ ○ ○
```

**AFTER:**
```
Unit 260
  └─ H. ABDUL MUNIR:  P P P P P P OFF P P P P P P OFF
```

### 2. Shift Display Codes

| Code | Meaning | Color | Driver Type |
|------|---------|-------|-------------|
| **P** | Shift Pagi | Green (Batangan) / Blue (Cadangan) | Both |
| **S** | Shift Siang | Green (Batangan) / Blue (Cadangan) | Both |
| **P+S** | Both Shifts | Green (Batangan) / Blue (Cadangan) | Both |
| **B** | Backup | Amber | Both |
| **OFF** | Cuti/Libur | Red | Both |
| **M** | Maintenance | Teal | N/A (Unit status) |
| **R** | Renops | Orange | N/A (Unit status) |
| **-** | Not Scheduled | Gray | Both |

### 3. Color Coding

**Batangan Drivers:**
- Background: `bg-green-100`
- Text: `text-green-800`
- Hover: `hover:bg-green-200`

**Cadangan Drivers:**
- Background: `bg-blue-100`
- Text: `text-blue-800`
- Hover: `hover:bg-blue-200`

### 4. File Changes

#### `resources/views/modules/admin/schedules/consolidated.blade.php`

**Removed:**
- Shift column from table header
- Nested loop over shifts (`@foreach(['pagi', 'siang'] as $shift)`)
- Separate rows for each shift
- Shift badge display (Pagi/Siang icons)

**Added:**
- Combined shift logic in single driver row
- Letter-based shift indicators (P, S, P+S, B, OFF, M, R, -)
- Consolidated total count (sum of all shifts)

**Updated:**
- Column count: Changed from 4 columns to 3 columns (removed Shift column)
- Colspan values: Updated all colspan calculations from `4 + count($dateRange) + 1` to `3 + count($dateRange) + 1`
- Cell display: Text-based codes instead of checkmark icons

#### `resources/views/modules/admin/schedules/components/legends.blade.php`

**Updated Legend Categories:**

1. **Shift Display Section** (New)
   - P (Pagi) - Batangan
   - S (Siang) - Batangan
   - P (Pagi) - Cadangan
   - S (Siang) - Cadangan
   - P+S (Both shifts)
   - B (Backup)
   - OFF (Cuti/Libur)
   - `-` (Not scheduled)

2. **Unit Status Section** (Updated)
   - M (Maintenance)
   - R (Renops)

3. **Waktu Shift Section** (Kept)
   - Pagi (06:00 - 14:00)
   - Siang (14:00 - 22:00)

### 5. Benefits

1. **Reduced Clutter**: Each driver appears only once instead of twice
2. **Better Readability**: Clear pattern visualization (e.g., "P P P P P P OFF")
3. **Space Efficiency**: More compact table layout
4. **Easier Pattern Recognition**: Can quickly see work patterns like "6-1 cycle"
5. **Better for Printing**: More condensed format fits better on paper

### 6. Example Display

**For a batangan driver with 6-1 cycle:**
```
H. ABDUL MUNIR:  P P P P P P OFF P P P P P P OFF P P P P P P OFF
```

**For a cadangan driver covering SIANG on a single-batangan unit:**
```
FIRMANDO:  S - S - S - - S - S - S - - S - S - - 
```

**For a driver working both shifts:**
```
DRIVER X:  P+S P+S - - P - S - - P+S - - - - -
```

### 7. Technical Implementation

**Shift Detection Logic:**
```php
// Check both shifts for this driver on this date
$pagiAssigned = in_array($date, $driverInfo['shifts']['pagi']['dates']);
$siangAssigned = in_array($date, $driverInfo['shifts']['siang']['dates']);
$pagiBackup = in_array($date, $driverInfo['shifts']['pagi']['backup_dates']);
$siangBackup = in_array($date, $driverInfo['shifts']['siang']['backup_dates']);

// Determine display
if ($pagiAssigned && $siangAssigned) {
    $shiftDisplay = 'P+S';
} elseif ($pagiAssigned) {
    $shiftDisplay = 'P';
} elseif ($siangAssigned) {
    $shiftDisplay = 'S';
} elseif ($pagiBackup || $siangBackup) {
    $shiftDisplay = 'B';
}
```

**Total Count Calculation:**
```php
$totalPagiAssigned = count($driverInfo['shifts']['pagi']['dates']);
$totalSiangAssigned = count($driverInfo['shifts']['siang']['dates']);
$totalPagiBackup = count($driverInfo['shifts']['pagi']['backup_dates']);
$totalSiangBackup = count($driverInfo['shifts']['siang']['backup_dates']);
$total = $totalPagiAssigned + $totalSiangAssigned + $totalPagiBackup + $totalSiangBackup;
```

## Testing

To verify the changes:

1. Navigate to `/schedules` route
2. Select a route with drivers
3. Verify each driver appears only once per unit
4. Check shift codes display correctly:
   - Batangan drivers show "P" for their working days
   - Cadangan drivers show "S" when covering SIANG shifts
   - OFF appears on day 7, 14, 21 for batangan drivers
5. Verify legend shows correct shift code meanings
6. Test collapsible route/unit sections still work
7. Verify export functions still work correctly

## Future Enhancements

1. Add click-to-edit functionality on shift codes
2. Show tooltip with shift time on hover
3. Add color intensity based on consecutive days worked
4. Export with letter codes to Excel/PDF
5. Add filter for specific shift patterns (e.g., "show only drivers working P+S")

## Conclusion

The view now provides a cleaner, more compact representation of driver schedules while maintaining all necessary information. The letter-based system makes it easy to identify patterns at a glance, especially the 6-1 cycle pattern for batangan drivers.
