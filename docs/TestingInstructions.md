# Testing Instructions for Enhanced Pattern-Based Schedule Generator

## What Has Been Implemented

✅ **Fixed 15-Day Pattern** untuk 2 driver batangan  
✅ **Cadangan Driver Integration** untuk mengisi slot kosong
✅ **Automatic Pattern Cycling** setiap 15 hari
✅ **Complete 2-Shift Coverage** setiap hari
✅ **Monthly Limit Compliance** untuk semua driver types
✅ **Enhanced Logging** dengan tracking phase assignments

## Enhanced Logic Flow

### Phase 1: Batangan Pattern
```
Day:   D1  D2  D3  D4  D5  D6  D7  D8  D9  D10 D11 D12 D13 D14 D15
Dr1:   P   S   -   P   P   P   S   -   P   P   P   P   P   P   S
Dr2:   S   -   P   S   S   S   S   S   S   S   S   S   S   S   -
```

### Phase 2: Cadangan Backup
```
Missing slots filled by cadangan drivers:
Day 2:  Cadangan → Pagi shift (Dr1=Siang, Dr2=Libur)
Day 3:  Cadangan → Siang shift (Dr1=Libur, Dr2=Pagi) 
Day 8:  Cadangan → Pagi shift (Dr1=Libur, Dr2=Siang)
Day 15: Cadangan → Pagi shift (Dr1=Siang, Dr2=Libur)
```

## Updated Testing Scenarios

### Test 1: Complete Coverage (Optimal)
**Setup**: 2+ batangan drivers + 2+ cadangan drivers
**Steps**:
1. Generate 15 hari schedule
2. Check setiap hari memiliki 2 shifts
3. Verify pattern compliance untuk batangan
4. Verify cadangan mengisi slot kosong

**Expected Results**:
- **15 days × 2 shifts = 30 total shifts**
- **Batangan drivers**: Follow pattern exactly
- **Cadangan drivers**: Fill Days 2, 3, 8, 15 missing slots
- **No empty shifts on any day**

### Test 2: Limited Cadangan
**Setup**: 2+ batangan drivers + 1 cadangan driver  
**Steps**:
1. Generate 15 hari schedule
2. Check coverage improvement vs pattern-only
3. Verify fair rotation untuk single cadangan

**Expected Results**:
- **Better coverage** than pattern-only
- **1 cadangan driver** fills multiple empty slots
- **Monthly limit respected** (max 11 shifts for cadangan)

### Test 4: Monthly Limit Scenarios
**Setup**: Drivers with existing shifts close to monthly limits
**Steps**:
1. Pre-populate some drivers with 10+ shifts in current month
2. Generate schedule for remaining days
3. Check limit compliance and fallback behavior

**Expected Results**:
- **No driver exceeds** their monthly limit
- **Graceful degradation** when primary drivers reach limits
- **Automatic substitution** with available drivers

### Test 5: Mixed Driver Availability
**Setup**: Various combinations of driver types and availability
**Combinations to test**:
- 3 batangan + 1 cadangan
- 1 batangan + 3 cadangan  
- 4 batangan + 0 cadangan
- 0 batangan + 4 cadangan

**Expected Results**:
- **Adapts to available drivers**
- **Maintains coverage quality**
- **Appropriate warnings** for insufficient drivers

### Test 6: Large Date Range (30 days)
**Setup**: 2 batangan + 2 cadangan drivers
**Steps**:
1. Generate 30-day schedule (2 pattern cycles)
2. Monitor pattern cycling behavior
3. Check monthly limit enforcement

**Expected Results**:
- **Pattern repeats correctly** after day 15
- **Monthly limits enforced** (drivers stop at 14/11 shifts)
- **Cadangan backup continues** when batangan reach limits

## What to Check in Results

### A. Schedule Coverage
- [ ] **Every day has exactly 2 shifts** (pagi + siang)
- [ ] **No missing shifts** on any date
- [ ] **No duplicate assignments** (same driver, same day, multiple shifts)
- [ ] **Pattern integrity** maintained for batangan drivers

### B. Driver Distribution  
- [ ] **Batangan drivers follow pattern** exactly when possible
- [ ] **Cadangan drivers fill empty slots** only
- [ ] **Fair rotation** among cadangan drivers
- [ ] **No driver overload** within same day

### C. Monthly Compliance
- [ ] **Batangan drivers ≤ 14 shifts** per month
- [ ] **Cadangan drivers ≤ 11 shifts** per month
- [ ] **Automatic limit enforcement** prevents violations
- [ ] **Graceful handling** when limits reached

### D. Log Analysis
Check log patterns untuk different phases:
```bash
# Batangan pattern assignments
grep "Batangan pattern:" storage/logs/laravel.log

# Cadangan backup assignments  
grep "Cadangan fill:" storage/logs/laravel.log

# Fallback assignments
grep "Batangan fallback:" storage/logs/laravel.log

# Coverage confirmations
grep "All shifts already assigned" storage/logs/laravel.log
```

### E. Database Validation
```sql
-- Check daily coverage (should be 2 shifts per day)
SELECT 
    schedule_date,
    COUNT(*) as shifts_count,
    GROUP_CONCAT(CONCAT(shift, ':', driver_id)) as assignments
FROM schedules 
WHERE unit_id = [UNIT_ID] AND route_id = [ROUTE_ID]
GROUP BY schedule_date
HAVING shifts_count != 2;  -- Should return no rows

-- Check pattern compliance for batangan drivers
SELECT 
    s.schedule_date,
    s.shift,
    d.name,
    d.type,
    (ROW_NUMBER() OVER (ORDER BY s.schedule_date) - 1) % 15 + 1 as pattern_day
FROM schedules s
JOIN drivers d ON s.driver_id = d.id
WHERE s.unit_id = [UNIT_ID] 
    AND s.route_id = [ROUTE_ID] 
    AND d.type = 'batangan'
ORDER BY s.schedule_date, s.shift;
```

## Success Criteria Updated

✅ **Complete Coverage**: Setiap hari memiliki 2 shifts (pagi + siang)
✅ **Pattern Accuracy**: Batangan drivers mengikuti pattern 15 hari
✅ **Backup Efficiency**: Cadangan drivers mengisi semua slot kosong  
✅ **Monthly Compliance**: Semua drivers respect monthly limits
✅ **Fair Distribution**: Cadangan drivers rotate fairly
✅ **Conflict Prevention**: No double-booking atau unit conflicts
✅ **Intelligent Fallback**: Graceful degradation saat driver tidak tersedia
✅ **Performance**: Reasonable speed untuk large date ranges

## Log Examples untuk Verification

### Successful Complete Coverage
```
[2025-01-02] Batangan pattern: Driver John (123) assigned siang shift on 2025-01-02 (Pattern Day 2)
[2025-01-02] Cadangan fill: Driver Maria (456) assigned pagi shift on 2025-01-02
[2025-01-03] Cadangan fill: Driver Carlos (789) assigned siang shift on 2025-01-03  
[2025-01-03] Batangan pattern: Driver Jane (124) assigned pagi shift on 2025-01-03 (Pattern Day 3)
```

### Monthly Limit Handling
```
[2025-01-15] Batangan pattern conflict: Driver John (123) cannot take siang shift on 2025-01-15 (Pattern Day 15) - likely monthly limit reached
[2025-01-15] Cadangan fill: Driver Maria (456) assigned siang shift on 2025-01-15
```

### Complete Day Coverage
```
[2025-01-04] Batangan pattern: Driver John (123) assigned pagi shift on 2025-01-04 (Pattern Day 4)
[2025-01-04] Batangan pattern: Driver Jane (124) assigned siang shift on 2025-01-04 (Pattern Day 4)  
[2025-01-04] All shifts already assigned by batangan drivers on 2025-01-04
```

## Cara Testing

### 1. Prerequisites
- Pastikan unit memiliki minimal **2 driver batangan** dengan status **aktif**
- Driver harus sudah ter-assign ke unit yang dipilih
- Pastikan route sudah ter-assign ke unit

### 2. Testing Steps

#### Test 1: Pattern 15 Hari
1. Buka halaman **Generate Schedule**
2. Pilih route dan unit yang sesuai
3. Set tanggal: **15 hari** (contoh: 1 Jan 2025 - 15 Jan 2025)
4. Checklist **"Clear existing schedules"** jika perlu
5. Klik **Generate**
6. **Expected Result**: 
   - Driver 1: 12 shifts (8 pagi, 4 siang)
   - Driver 2: 13 shifts (1 pagi, 12 siang)

#### Test 2: Pattern 30 Hari (2 Cycles)
1. Set tanggal: **30 hari** (contoh: 1 Jan 2025 - 30 Jan 2025)
2. Generate schedule
3. **Expected Result**:
   - Pattern akan repeat 2x (hari 16-30 sama dengan hari 1-15)
   - Sistem akan stop assign shifts ketika mencapai 14 shifts per driver
   - Log akan menunjukkan "monthly limit reached"

#### Test 3: Pattern Starting Mid-Month
1. Set tanggal mulai: **pertengahan bulan** (contoh: 15 Jan 2025 - 29 Jan 2025)
2. Generate schedule
3. **Expected Result**:
   - Pattern tetap berjalan sesuai day position
   - Monthly limit tetap dihitung dari awal bulan

### 3. What to Check

#### A. Di Halaman Schedule
- [ ] Schedule ter-generate sesuai pattern
- [ ] Driver assignment konsisten (Driver ID terkecil = Driver 1)
- [ ] Tidak ada conflict (1 driver multiple shifts per day)
- [ ] Shift distribution sesuai pattern

#### B. Di Log File (`storage/logs/laravel.log`)
```bash
tail -f storage/logs/laravel.log | grep "Pattern"
```
- [ ] Log menunjukkan "Pattern applied" untuk assigned shifts
- [ ] Log menunjukkan "Pattern day off" untuk rest days
- [ ] Log menunjukkan "Pattern conflict" jika monthly limit reached
- [ ] Pattern day position (1-15) ter-tracking dengan benar

#### C. Di Response JSON/Message
- [ ] Success message mentions "pattern 15 hari"
- [ ] `pattern_info` berisi informasi pattern
- [ ] `pattern_cycles` menunjukkan jumlah cycle yang benar
- [ ] `drivers_used` menunjukkan 2

### 4. Edge Cases to Test

#### Test 4: Insufficient Drivers
1. **Setup**: Unit dengan hanya 1 driver batangan
2. **Expected**: Warning log "Pattern requires at least 2 batangan drivers"
3. **Result**: No schedules generated

#### Test 5: Monthly Limit Reached
1. **Setup**: Driver sudah memiliki shifts mendekati 14 di bulan yang sama
2. **Expected**: Beberapa pattern assignments di-skip
3. **Log**: "Pattern conflict: ... monthly limit reached"

#### Test 6: Mixed Driver Types
1. **Setup**: Unit dengan 1 batangan + 1 cadangan driver
2. **Expected**: Warning tentang insufficient batangan drivers
3. **Note**: Pattern butuh 2 batangan drivers

### 5. Performance Check

#### Test 7: Large Date Range
1. Set tanggal: **3 bulan** (90 hari)
2. Monitor performance dan memory usage
3. Check log untuk proper pattern cycling

### 6. Data Validation

#### Check Database
```sql
-- Verify pattern compliance
SELECT 
    schedule_date,
    shift,
    driver_id,
    (ROW_NUMBER() OVER (ORDER BY schedule_date) - 1) % 15 + 1 as pattern_day
FROM schedules 
WHERE unit_id = [YOUR_UNIT_ID] 
    AND route_id = [YOUR_ROUTE_ID]
ORDER BY schedule_date, shift;
```

#### Expected Data Pattern
- Pattern day 1: Driver X pagi, Driver Y siang
- Pattern day 2: Driver X siang, Driver Y libur
- Pattern day 3: Driver X libur, Driver Y pagi
- dst sesuai pattern table

## Troubleshooting

### Issue 1: Pattern Tidak Sesuai
**Check**: 
- Driver assignment (apakah Driver 1 & 2 sama?)
- Pattern day calculation
- Log untuk pattern conflicts

### Issue 2: Monthly Limit Tidak Bekerja
**Check**:
- Method `canDriverTakeShift()` validation
- Month start/end calculation
- Existing schedule count

### Issue 3: Pattern Tidak Cycling
**Check**:
- Pattern position calculation: `(($dayPosition - 1) % 15) + 1`
- Date range calculation
- Log pattern day numbers

## Success Criteria

✅ **Pattern Accuracy**: Shifts assigned exactly as per pattern table
✅ **Driver Consistency**: Same drivers always get same pattern positions  
✅ **Cycling Works**: Pattern repeats correctly after day 15
✅ **Monthly Compliance**: No driver exceeds 14 shifts per month
✅ **Conflict Prevention**: No double-booking or unit conflicts
✅ **Logging Quality**: Clear, informative logs for monitoring
✅ **Performance**: Reasonable speed for typical date ranges

## Next Steps After Testing

1. **If successful**: Document any edge cases found
2. **If issues found**: Report specific scenarios that failed
3. **Enhancement requests**: Any pattern modifications needed
4. **Production readiness**: Consider backup patterns for special cases

## File Locations

- **Service**: `app/Services/ScheduleGeneratorService.php`
- **Controller**: `app/Http/Controllers/ScheduleController.php`
- **Documentation**: `docs/SchedulePatternImplementation.md`
- **Improvements**: `docs/ScheduleGeneratorImprovements.md`
