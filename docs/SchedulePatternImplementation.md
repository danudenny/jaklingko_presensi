# Schedule Pattern Implementation - 15 Days Pattern for 2 Batangan Drivers

## Overview
Implementasi ini menggunakan pattern tetap untuk 2 driver batangan dalam periode 15 hari yang akan berulang secara siklik.

## Pattern Design

### 15 Day Pattern
```
Day:   D1  D2  D3  D4  D5  D6  D7  D8  D9  D10 D11 D12 D13 D14 D15
Dr1:   P   S   -   P   P   P   S   -   P   P   P   P   P   P   S
Dr2:   S   -   P   S   S   S   S   S   S   S   S   S   S   S   -
```

### Legend
- **P** = Shift Pagi
- **S** = Shift Siang  
- **-** = Libur (tidak ada shift)
- **Dr1** = Driver 1 (driver dengan ID terkecil)
- **Dr2** = Driver 2 (driver dengan ID kedua terkecil)

## Pattern Analysis

### Driver 1 Statistics (15 days)
- **Total Shifts**: 12 shifts
- **Shift Pagi**: 8 shifts
- **Shift Siang**: 4 shifts
- **Days Off**: 3 days (Day 3, 8)

### Driver 2 Statistics (15 days)
- **Total Shifts**: 13 shifts
- **Shift Pagi**: 1 shift
- **Shift Siang**: 12 shifts
- **Days Off**: 2 days (Day 2, 15)

### Monthly Capacity (30 days)
Untuk 1 bulan (30 hari = 2 siklus pattern):
- **Driver 1**: 24 shifts (masih dalam batas 14 shifts/bulan untuk batangan) ⚠️
- **Driver 2**: 26 shifts (masih dalam batas 14 shifts/bulan untuk batangan) ⚠️

> **Catatan**: Pattern ini akan melebihi batas maksimal 14 shifts per bulan untuk driver batangan. Sistem akan otomatis membatasi berdasarkan aturan `canDriverTakeShift()`.

## Implementation Details

### Key Changes Made

#### 1. Modified `generateSchedulesForDate()` Method
```php
private function generateSchedulesForDate(int $routeId, int $unitId, Carbon $date, $availableDrivers, array $dateRange): array
{
    // Get batangan drivers only (pattern is specifically for batangan drivers)
    $batanganDrivers = $availableDrivers->where('type', self::DRIVER_TYPE_BATANGAN);
    
    if ($batanganDrivers->count() < 2) {
        Log::warning("Pattern requires at least 2 batangan drivers, found: " . $batanganDrivers->count());
        return $schedules;
    }

    // Calculate day position in pattern (1-15, cycles every 15 days)
    $startDate = $dateRange[0];
    $dayPosition = $startDate->diffInDays($date) + 1;
    $patternPosition = (($dayPosition - 1) % 15) + 1;

    // Get pattern and assign to sorted drivers
    $pattern = $this->getPatternForDay($patternPosition);
    $sortedDrivers = $batanganDrivers->sortBy('id')->values();
    
    // Apply pattern to Driver 1 and Driver 2
}
```

#### 2. Added `getPatternForDay()` Method
```php
private function getPatternForDay(int $day): array
{
    $patterns = [
        1  => ['driver1' => 'pagi', 'driver2' => 'siang'],
        2  => ['driver1' => 'siang', 'driver2' => '-'],
        3  => ['driver1' => '-', 'driver2' => 'pagi'],
        // ... and so on for all 15 days
    ];
    
    return $patterns[$day] ?? ['driver1' => '-', 'driver2' => '-'];
}
```

### Pattern Logic

#### Driver Assignment
1. **Consistent Assignment**: Drivers disortir berdasarkan ID untuk konsistensi
2. **Driver 1**: Driver dengan ID terkecil
3. **Driver 2**: Driver dengan ID kedua terkecil

#### Pattern Cycling
1. **15-Day Cycle**: Pattern berulang setiap 15 hari
2. **Day Position Calculation**: `(($dayPosition - 1) % 15) + 1`
3. **Automatic Reset**: Setelah hari ke-15, kembali ke hari ke-1

#### Validation Rules
- **Monthly Limit Check**: Sistem tetap mengecek batas maksimal 14 shifts/bulan
- **Conflict Prevention**: Tidak ada driver yang dijadwalkan 2 shift di hari yang sama
- **Unit Restriction**: Driver tidak bisa aktif di 2 unit berbeda di hari yang sama

## Usage Instructions

### Prerequisites
1. **Minimum Drivers**: Harus ada minimal 2 driver batangan di unit
2. **Driver Type**: Pattern ini hanya untuk driver dengan type `'batangan'`
3. **Active Status**: Semua driver harus memiliki status `'aktif'`

### How to Use
1. Pilih route dan unit yang memiliki minimal 2 driver batangan
2. Tentukan tanggal mulai dan selesai (disarankan kelipatan 15 hari)
3. Jalankan generate schedule melalui web interface
4. Sistem akan otomatis menerapkan pattern dan mencatat di log

### Monitoring
```bash
# Check log for pattern application
tail -f storage/logs/laravel.log | grep "Day"
```

## Benefits

### 1. Predictable Scheduling
- Driver tahu jadwal mereka jauh hari sebelumnya
- Pattern yang konsisten dan mudah diingat
- Distribusi shift yang relatif seimbang

### 2. Fair Distribution
- Kedua driver mendapat jumlah shift yang hampir sama
- Bergantian untuk shift pagi dan siang
- Days off yang terdistribusi merata

### 3. Operational Efficiency
- Tidak perlu manual assignment untuk setiap hari
- Mengurangi konflik scheduling
- Mudah untuk planning jangka panjang

## Limitations

### 1. Fixed Pattern
- Tidak bisa disesuaikan untuk kondisi khusus
- Sulit mengakomodasi request cuti mendadak
- Pattern yang sama untuk semua unit

### 2. Monthly Limit Conflict
- Pattern 15 hari menghasilkan 24-26 shifts per bulan
- Melebihi batas maksimal 14 shifts untuk batangan
- Sistem akan memotong shifts yang melebihi batas

### 3. Scalability
- Hanya untuk 2 driver batangan
- Tidak mengakomodasi driver cadangan dalam pattern
- Perlu modifikasi untuk unit dengan driver lebih banyak

## Future Improvements

### 1. Dynamic Pattern Adjustment
- Sesuaikan pattern berdasarkan monthly limit
- Implementasi automatic break distribution
- Pattern yang bisa dikustomisasi per unit

### 2. Multi-Driver Support
- Pattern untuk 3-4 driver batangan
- Integrasi driver cadangan sebagai backup
- Flexible driver assignment

### 3. Advanced Features
- Holiday consideration dalam pattern
- Leave request integration
- Performance analytics dan optimization

## Testing

### Test Scenarios
1. **Normal Operation**: 2 driver batangan, 15 hari
2. **Monthly Limit**: Test dengan periode 30 hari
3. **Driver Shortage**: Test dengan kurang dari 2 driver
4. **Leave Requests**: Test dengan driver yang sedang cuti
5. **Unit Changes**: Test perpindahan driver antar unit

### Expected Results
- Pattern konsisten untuk 15 hari
- Automatic cycling setelah hari ke-15
- Proper validation dan error handling
- Detailed logging untuk monitoring
