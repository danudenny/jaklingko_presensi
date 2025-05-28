<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Unit;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SchedulePlannerService
{
    // Shift constants
    const SHIFT_PAGI = 'pagi';
    const SHIFT_SIANG = 'siang';
    const SHIFT_NONE = 'none';

    // Shift codes for pattern representation
    const SHIFT_CODE_PAGI = 'P';
    const SHIFT_CODE_SIANG = 'S';
    const SHIFT_CODE_NONE = 'N';
    
    // Unit coverage constants
    const REQUIRED_SHIFTS_PER_DAY = 2; // Each unit needs exactly 2 drivers per day (Pagi + Siang)

    /**
     * Default 5-day scheduling patterns for different driver types
     * 
     * @var array
     */
    protected $defaultPatterns = [
        'batangan' => [
            // Pattern 1: P-P-S-N-P (Pagi, Pagi, Siang, None, Pagi)
            [
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE,
                self::SHIFT_CODE_PAGI,
            ],
            // Pattern 2: P-S-S-N-P (Pagi, Siang, Siang, None, Pagi)
            [
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE,
                self::SHIFT_CODE_PAGI,
            ],
            // Pattern 3: S-S-N-P-P (Siang, Siang, None, Pagi, Pagi)
            [
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE,
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_PAGI,
            ],
            // Pattern 4: P-P-P-S-N (Pagi, Pagi, Pagi, Siang, None)
            [
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE,
            ],
        ],
        'cadangan' => [
            // Pattern 1: N-P-S-S-N (None, Pagi, Siang, Siang, None)
            [
                self::SHIFT_CODE_NONE,
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE,
            ],
            // Pattern 2: S-N-S-S-N (Siang, None, Siang, Siang, None)
            [
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE,
            ],
            // Pattern 3: P-S-N-P-S (Pagi, Siang, None, Pagi, Siang)
            [
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE,
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_SIANG,
            ],
            // Pattern 4: N-N-P-S-N (None, None, Pagi, Siang, None)
            [
                self::SHIFT_CODE_NONE,
                self::SHIFT_CODE_NONE,
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE,
            ],
        ],
    ];

    /**
     * Transition rules for shift sequences
     * 
     * @var array
     */
    protected $transitionRules = [
        self::SHIFT_CODE_PAGI => [self::SHIFT_CODE_PAGI, self::SHIFT_CODE_SIANG], // Pagi can be followed by Pagi or Siang
        self::SHIFT_CODE_SIANG => [self::SHIFT_CODE_SIANG, self::SHIFT_CODE_NONE], // Siang can be followed by Siang or None
        self::SHIFT_CODE_NONE => [self::SHIFT_CODE_PAGI, self::SHIFT_CODE_SIANG, self::SHIFT_CODE_NONE], // None can be followed by any shift
    ];

    /**
     * Generate a schedule plan for the given date range
     * 
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @param Collection $units Collection of units
     * @param Collection $batanganDrivers Collection of batangan (fixed) drivers
     * @param Collection $cadanganDrivers Collection of cadangan (non-fixed) drivers
     * @param array $batanganSettings Settings for batangan drivers
     * @param array $cadanganSettings Settings for cadangan drivers
     * @param array $unitDayOffs Array of unit day offs
     * @return array Schedule plan
     */
    public function generateSchedulePlan(
        string $startDate,
        string $endDate,
        Collection $units,
        Collection $batanganDrivers,
        Collection $cadanganDrivers,
        array $batanganSettings,
        array $cadanganSettings,
        array $unitDayOffs
    ): array {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $totalDays = $start->diffInDays($end) + 1;
        
        // Initialize schedule plan
        $schedulePlan = [];
        
        // Initialize unit coverage tracking
        $unitCoverage = [];
        foreach ($units as $unit) {
            $unitCoverage[$unit->id] = [];
            for ($day = 0; $day < $totalDays; $day++) {
                $currentDate = $start->copy()->addDays($day)->format('Y-m-d');
                $unitCoverage[$unit->id][$currentDate] = [
                    self::SHIFT_PAGI => null,
                    self::SHIFT_SIANG => null
                ];
            }
        }
        
        // Step 1: Assign Batangan drivers to their specific units
        // Each batangan driver can only be assigned to one unit
        $batanganUnitAssignments = [];
        
        // Get the unit assignments for each batangan driver
        foreach ($batanganDrivers as $driver) {
            $driverUnits = $driver->units;
            
            if ($driverUnits->isEmpty()) {
                Log::warning("Batangan driver {$driver->id} has no units assigned, skipping");
                continue;
            }
            
            // For batangan drivers, we'll use the first assigned unit
            $assignedUnit = $driverUnits->first();
            $batanganUnitAssignments[$driver->id] = $assignedUnit->id;
            
            // Generate a valid pattern for this batangan driver
            $driverPattern = $this->generateDriverSchedulePattern(
                $driver, 
                'batangan', 
                $totalDays, 
                $batanganSettings
            );
            
            // Assign the driver to their unit based on the pattern
            for ($day = 0; $day < $totalDays; $day++) {
                $currentDate = $start->copy()->addDays($day)->format('Y-m-d');
                $shift = $driverPattern[$day];
                
                // Skip if no shift or unit is in day off
                if ($shift === self::SHIFT_NONE || 
                    (isset($unitDayOffs[$currentDate]) && in_array($assignedUnit->id, $unitDayOffs[$currentDate]))) {
                    continue;
                }
                
                // Check if this shift is already covered for this unit on this day
                if ($unitCoverage[$assignedUnit->id][$currentDate][$shift] === null) {
                    // Assign this driver to this shift
                    $unitCoverage[$assignedUnit->id][$currentDate][$shift] = $driver->id;
                    
                    // Add to schedule plan
                    if (!isset($schedulePlan[$driver->id])) {
                        $schedulePlan[$driver->id] = array_fill(0, $totalDays, self::SHIFT_NONE);
                    }
                    $schedulePlan[$driver->id][$day] = $shift;
                }
            }
        }
        
        // Step 2: Identify gaps in coverage after batangan assignments
        $coverageGaps = [];
        foreach ($units as $unit) {
            $coverageGaps[$unit->id] = [];
            
            for ($day = 0; $day < $totalDays; $day++) {
                $currentDate = $start->copy()->addDays($day)->format('Y-m-d');
                
                // Skip if unit is in day off
                if (isset($unitDayOffs[$currentDate]) && in_array($unit->id, $unitDayOffs[$currentDate])) {
                    continue;
                }
                
                // Check for missing shifts
                foreach ([self::SHIFT_PAGI, self::SHIFT_SIANG] as $shift) {
                    if ($unitCoverage[$unit->id][$currentDate][$shift] === null) {
                        if (!isset($coverageGaps[$unit->id][$currentDate])) {
                            $coverageGaps[$unit->id][$currentDate] = [];
                        }
                        $coverageGaps[$unit->id][$currentDate][] = $shift;
                    }
                }
            }
        }
        
        // Step 3: Assign Cadangan drivers to fill the gaps
        // Cadangan drivers can work across multiple units
        $cadanganAssignments = [];
        foreach ($cadanganDrivers as $driver) {
            $cadanganAssignments[$driver->id] = [];
            
            // Initialize schedule for this cadangan driver
            if (!isset($schedulePlan[$driver->id])) {
                $schedulePlan[$driver->id] = array_fill(0, $totalDays, self::SHIFT_NONE);
            }
        }
        
        // Process each day to fill gaps with cadangan drivers
        for ($day = 0; $day < $totalDays; $day++) {
            $currentDate = $start->copy()->addDays($day)->format('Y-m-d');
            
            // Get available cadangan drivers for this day
            $availableCadangan = $cadanganDrivers->filter(function($driver) use ($cadanganAssignments, $day, $currentDate) {
                // A cadangan driver is available if they don't have an assignment for this day yet
                return !isset($cadanganAssignments[$driver->id][$currentDate]);
            });
            
            // Process each unit with gaps
            foreach ($units as $unit) {
                // Skip if unit is in day off
                if (isset($unitDayOffs[$currentDate]) && in_array($unit->id, $unitDayOffs[$currentDate])) {
                    continue;
                }
                
                // Check if this unit has gaps for this day
                if (isset($coverageGaps[$unit->id][$currentDate])) {
                    foreach ($coverageGaps[$unit->id][$currentDate] as $missingShift) {
                        // Find a suitable cadangan driver
                        foreach ($availableCadangan as $cadanganDriver) {
                            // Check if this driver is qualified for this unit
                            $isQualified = $cadanganDriver->units->contains('id', $unit->id);
                            
                            if (!$isQualified) {
                                continue;
                            }
                            
                            // Check if assigning this shift would violate transition rules
                            $canAssign = true;
                            if ($day > 0) {
                                $previousShift = $schedulePlan[$cadanganDriver->id][$day - 1];
                                $previousShiftCode = $this->shiftToShiftCode($previousShift);
                                $currentShiftCode = $this->shiftToShiftCode($missingShift);
                                
                                $validNextShifts = $this->transitionRules[$previousShiftCode] ?? [];
                                if (!in_array($currentShiftCode, $validNextShifts)) {
                                    $canAssign = false;
                                }
                            }
                            
                            if ($canAssign) {
                                // Assign this cadangan driver to fill the gap
                                $unitCoverage[$unit->id][$currentDate][$missingShift] = $cadanganDriver->id;
                                $schedulePlan[$cadanganDriver->id][$day] = $missingShift;
                                
                                // Record this assignment
                                $cadanganAssignments[$cadanganDriver->id][$currentDate] = [
                                    'unit_id' => $unit->id,
                                    'shift' => $missingShift
                                ];
                                
                                // Remove this driver from available list for this day
                                $availableCadangan = $availableCadangan->reject(function($driver) use ($cadanganDriver) {
                                    return $driver->id === $cadanganDriver->id;
                                });
                                
                                // This gap is filled
                                break;
                            }
                        }
                    }
                }
            }
        }
        
        return $schedulePlan;
    }
    
    /**
     * Generate a schedule pattern for a driver
     * 
     * @param Driver $driver The driver
     * @param string $driverType Type of driver (batangan or cadangan)
     * @param int $totalDays Total number of days to schedule
     * @param array $settings Driver type settings
     * @return array Schedule pattern for the driver
     */
    protected function generateDriverSchedulePattern(
        Driver $driver,
        string $driverType,
        int $totalDays,
        array $settings
    ): array {
        $pattern = [];
        $patternLength = 5; // Default pattern length
        
        // Select a pattern template based on driver type
        $patternTemplates = $this->defaultPatterns[$driverType] ?? $this->defaultPatterns['batangan'];
        $selectedTemplate = $patternTemplates[array_rand($patternTemplates)];
        
        // Adjust pattern if total days is less than default pattern length
        if ($totalDays < $patternLength) {
            // Take a subset of the pattern that is valid according to transition rules
            $validSubPattern = $this->generateValidSubPattern($selectedTemplate, $totalDays);
            
            for ($i = 0; $i < $totalDays; $i++) {
                $pattern[$i] = $this->shiftCodeToShift($validSubPattern[$i]);
            }
        } else {
            // Repeat the pattern as needed to cover all days
            $fullRepeatCount = floor($totalDays / $patternLength);
            $remainder = $totalDays % $patternLength;
            
            for ($repeat = 0; $repeat < $fullRepeatCount; $repeat++) {
                for ($i = 0; $i < $patternLength; $i++) {
                    $dayIndex = ($repeat * $patternLength) + $i;
                    $pattern[$dayIndex] = $this->shiftCodeToShift($selectedTemplate[$i]);
                }
            }
            
            // Add remaining days if any
            if ($remainder > 0) {
                // Generate a valid sub-pattern for the remaining days
                $remainingPattern = $this->generateValidSubPattern(
                    $selectedTemplate, 
                    $remainder, 
                    $selectedTemplate[$patternLength - 1]
                );
                
                for ($i = 0; $i < $remainder; $i++) {
                    $dayIndex = ($fullRepeatCount * $patternLength) + $i;
                    $pattern[$dayIndex] = $this->shiftCodeToShift($remainingPattern[$i]);
                }
            }
        }
        
        return $pattern;
    }
    
    /**
     * Generate a valid sub-pattern of the given length
     * 
     * @param array $template The pattern template
     * @param int $length Length of sub-pattern
     * @param string|null $previousShift Previous shift code (if any)
     * @return array Valid sub-pattern
     */
    protected function generateValidSubPattern(array $template, int $length, ?string $previousShift = null): array
    {
        $subPattern = [];
        
        // If length is greater than or equal to template length, return the template
        if ($length >= count($template)) {
            return $template;
        }
        
        // Try different starting points to find a valid sub-pattern
        for ($startPos = 0; $startPos <= count($template) - $length; $startPos++) {
            $candidate = array_slice($template, $startPos, $length);
            
            // Check if this candidate is valid
            $isValid = true;
            
            // Check if the first shift is valid given the previous shift
            if ($previousShift !== null) {
                $validNextShifts = $this->transitionRules[$previousShift] ?? [];
                if (!in_array($candidate[0], $validNextShifts)) {
                    $isValid = false;
                    continue;
                }
            }
            
            // Check transitions within the candidate
            for ($i = 0; $i < $length - 1; $i++) {
                $currentShift = $candidate[$i];
                $nextShift = $candidate[$i + 1];
                
                $validNextShifts = $this->transitionRules[$currentShift] ?? [];
                
                if (!in_array($nextShift, $validNextShifts)) {
                    $isValid = false;
                    break;
                }
            }
            
            if ($isValid) {
                $subPattern = $candidate;
                break;
            }
        }
        
        // If no valid sub-pattern found, generate one manually
        if (empty($subPattern)) {
            $subPattern = $this->generateManualValidPattern($length, $previousShift);
        }
        
        return $subPattern;
    }
    
    /**
     * Generate a manually created valid pattern
     * 
     * @param int $length Length of pattern
     * @param string|null $previousShift Previous shift code (if any)
     * @return array Valid pattern
     */
    protected function generateManualValidPattern(int $length, ?string $previousShift = null): array
    {
        $pattern = [];
        $currentShift = $previousShift ?? self::SHIFT_CODE_NONE;
        
        for ($i = 0; $i < $length; $i++) {
            $validNextShifts = $this->transitionRules[$currentShift] ?? [self::SHIFT_CODE_PAGI];
            $nextShift = $validNextShifts[array_rand($validNextShifts)];
            $pattern[] = $nextShift;
            $currentShift = $nextShift;
        }
        
        return $pattern;
    }
    
    /**
     * Convert shift code to full shift name
     * 
     * @param string $shiftCode Shift code (P, S, N)
     * @return string Full shift name
     */
    protected function shiftCodeToShift(string $shiftCode): string
    {
        switch ($shiftCode) {
            case self::SHIFT_CODE_PAGI:
                return self::SHIFT_PAGI;
            case self::SHIFT_CODE_SIANG:
                return self::SHIFT_SIANG;
            case self::SHIFT_CODE_NONE:
            default:
                return self::SHIFT_NONE;
        }
    }
    
    /**
     * Convert full shift name to shift code
     * 
     * @param string $shift Full shift name
     * @return string Shift code
     */
    protected function shiftToShiftCode(string $shift): string
    {
        switch ($shift) {
            case self::SHIFT_PAGI:
                return self::SHIFT_CODE_PAGI;
            case self::SHIFT_SIANG:
                return self::SHIFT_CODE_SIANG;
            case self::SHIFT_NONE:
            default:
                return self::SHIFT_CODE_NONE;
        }
    }
    
    /**
     * Optimize the schedule plan to ensure all constraints are met
     * 
     * @param array $schedulePlan Initial schedule plan
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @param Collection $units Collection of units
     * @param Collection $batanganDrivers Collection of batangan (fixed) drivers
     * @param Collection $cadanganDrivers Collection of cadangan (non-fixed) drivers
     * @param array $unitDayOffs Array of unit day offs
     * @return array Optimized schedule plan
     */
    public function optimizeSchedulePlan(
        array $schedulePlan,
        string $startDate = null,
        string $endDate = null,
        Collection $units = null,
        Collection $batanganDrivers = null,
        Collection $cadanganDrivers = null,
        array $unitDayOffs = []
    ): array {
        // If no date range provided, just check and fix invalid transitions
        if ($startDate === null || $endDate === null || $units === null) {
            // Basic optimization - just check and fix invalid transitions
            foreach ($schedulePlan as $driverId => $driverSchedule) {
                for ($day = 1; $day < count($driverSchedule); $day++) {
                    $previousShift = $this->shiftToShiftCode($driverSchedule[$day - 1]);
                    $currentShift = $this->shiftToShiftCode($driverSchedule[$day]);
                    
                    $validNextShifts = $this->transitionRules[$previousShift] ?? [];
                    
                    // If current shift is not valid after previous shift, fix it
                    if (!in_array($currentShift, $validNextShifts)) {
                        // Choose a valid shift
                        $newShift = $validNextShifts[array_rand($validNextShifts)];
                        $schedulePlan[$driverId][$day] = $this->shiftCodeToShift($newShift);
                    }
                }
            }
            
            return $schedulePlan;
        }
        
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $totalDays = $start->diffInDays($end) + 1;
        
        // Get driver types
        $driverTypes = [];
        foreach ($batanganDrivers as $driver) {
            $driverTypes[$driver->id] = 'batangan';
        }
        foreach ($cadanganDrivers as $driver) {
            $driverTypes[$driver->id] = 'cadangan';
        }
        
        // Get driver-unit assignments
        $driverUnitAssignments = [];
        $unitDriverAssignments = [];
        
        // For batangan drivers, they can only be assigned to one unit
        foreach ($batanganDrivers as $driver) {
            $driverUnits = $driver->units;
            if (!$driverUnits->isEmpty()) {
                $assignedUnit = $driverUnits->first();
                $driverUnitAssignments[$driver->id] = [$assignedUnit->id];
                
                if (!isset($unitDriverAssignments[$assignedUnit->id])) {
                    $unitDriverAssignments[$assignedUnit->id] = [];
                }
                $unitDriverAssignments[$assignedUnit->id][] = $driver->id;
            }
        }
        
        // For cadangan drivers, they can be assigned to multiple units
        foreach ($cadanganDrivers as $driver) {
            $driverUnits = $driver->units;
            if (!$driverUnits->isEmpty()) {
                $driverUnitAssignments[$driver->id] = $driverUnits->pluck('id')->toArray();
                
                foreach ($driverUnits as $unit) {
                    if (!isset($unitDriverAssignments[$unit->id])) {
                        $unitDriverAssignments[$unit->id] = [];
                    }
                    $unitDriverAssignments[$unit->id][] = $driver->id;
                }
            }
        }
        
        // Initialize unit coverage tracking
        $unitCoverage = [];
        foreach ($units as $unit) {
            $unitCoverage[$unit->id] = [];
            for ($day = 0; $day < $totalDays; $day++) {
                $currentDate = $start->copy()->addDays($day)->format('Y-m-d');
                $unitCoverage[$unit->id][$currentDate] = [
                    self::SHIFT_PAGI => [],
                    self::SHIFT_SIANG => []
                ];
            }
        }
        
        // Build unit coverage from the schedule plan
        foreach ($schedulePlan as $driverId => $driverSchedule) {
            // Skip if driver has no unit assignments
            if (!isset($driverUnitAssignments[$driverId])) {
                continue;
            }
            
            $driverType = $driverTypes[$driverId] ?? 'unknown';
            
            for ($day = 0; $day < min($totalDays, count($driverSchedule)); $day++) {
                $currentDate = $start->copy()->addDays($day)->format('Y-m-d');
                $shift = $driverSchedule[$day];
                
                // Skip if no shift
                if ($shift === self::SHIFT_NONE) {
                    continue;
                }
                
                // For batangan drivers, they can only work at their assigned unit
                if ($driverType === 'batangan') {
                    $unitId = $driverUnitAssignments[$driverId][0] ?? null;
                    
                    if ($unitId !== null) {
                        // Skip if unit is in day off
                        if (isset($unitDayOffs[$currentDate]) && in_array($unitId, $unitDayOffs[$currentDate])) {
                            // Set to no shift if unit is in day off
                            $schedulePlan[$driverId][$day] = self::SHIFT_NONE;
                            continue;
                        }
                        
                        $unitCoverage[$unitId][$currentDate][$shift][] = $driverId;
                    }
                }
                // For cadangan drivers, we need to determine which unit they're working at on this day
                else if ($driverType === 'cadangan') {
                    // This will be filled in the next phase
                }
            }
        }
        
        // Validate and fix the schedule
        // 1. Check for over-assignments (more than one driver per shift per unit)
        // 2. Check for under-assignments (missing shifts)
        // 3. Ensure batangan drivers are only assigned to their specific unit
        // 4. Ensure cadangan drivers fill in the gaps
        
        // First, fix batangan assignments
        foreach ($units as $unit) {
            for ($day = 0; $day < $totalDays; $day++) {
                $currentDate = $start->copy()->addDays($day)->format('Y-m-d');
                
                // Skip if unit is in day off
                if (isset($unitDayOffs[$currentDate]) && in_array($unit->id, $unitDayOffs[$currentDate])) {
                    continue;
                }
                
                // Check for over-assignments in each shift
                foreach ([self::SHIFT_PAGI, self::SHIFT_SIANG] as $shift) {
                    $assignedDrivers = $unitCoverage[$unit->id][$currentDate][$shift];
                    
                    // If more than one batangan driver is assigned to this shift, keep only one
                    $batanganAssigned = array_filter($assignedDrivers, function($driverId) use ($driverTypes) {
                        return ($driverTypes[$driverId] ?? '') === 'batangan';
                    });
                    
                    if (count($batanganAssigned) > 1) {
                        // Keep only the first batangan driver
                        $keepDriverId = reset($batanganAssigned);
                        
                        foreach ($batanganAssigned as $driverId) {
                            if ($driverId !== $keepDriverId) {
                                // Set this driver to no shift for this day
                                $schedulePlan[$driverId][$day] = self::SHIFT_NONE;
                                
                                // Remove from unit coverage
                                $unitCoverage[$unit->id][$currentDate][$shift] = array_filter(
                                    $unitCoverage[$unit->id][$currentDate][$shift],
                                    function($id) use ($driverId) {
                                        return $id !== $driverId;
                                    }
                                );
                            }
                        }
                    }
                }
            }
        }
        
        // Now assign cadangan drivers to fill gaps
        for ($day = 0; $day < $totalDays; $day++) {
            $currentDate = $start->copy()->addDays($day)->format('Y-m-d');
            
            // Track which cadangan drivers are already assigned for this day
            $assignedCadangan = [];
            
            foreach ($units as $unit) {
                // Skip if unit is in day off
                if (isset($unitDayOffs[$currentDate]) && in_array($unit->id, $unitDayOffs[$currentDate])) {
                    continue;
                }
                
                // Check each shift for missing coverage
                foreach ([self::SHIFT_PAGI, self::SHIFT_SIANG] as $shift) {
                    $assignedDrivers = $unitCoverage[$unit->id][$currentDate][$shift];
                    
                    // If no drivers assigned to this shift, try to assign a cadangan driver
                    if (empty($assignedDrivers)) {
                        // Get cadangan drivers assigned to this unit
                        $unitCadanganDrivers = array_filter($unitDriverAssignments[$unit->id] ?? [], function($driverId) use ($driverTypes) {
                            return ($driverTypes[$driverId] ?? '') === 'cadangan';
                        });
                        
                        // Find an available cadangan driver
                        foreach ($unitCadanganDrivers as $cadanganId) {
                            // Skip if already assigned for this day
                            if (isset($assignedCadangan[$cadanganId])) {
                                continue;
                            }
                            
                            // Check if assigning this shift would violate transition rules
                            $canAssign = true;
                            if ($day > 0) {
                                $previousShift = $schedulePlan[$cadanganId][$day - 1] ?? self::SHIFT_NONE;
                                $previousShiftCode = $this->shiftToShiftCode($previousShift);
                                $currentShiftCode = $this->shiftToShiftCode($shift);
                                
                                $validNextShifts = $this->transitionRules[$previousShiftCode] ?? [];
                                if (!in_array($currentShiftCode, $validNextShifts)) {
                                    $canAssign = false;
                                }
                            }
                            
                            if ($canAssign) {
                                // Assign this cadangan driver
                                $schedulePlan[$cadanganId][$day] = $shift;
                                $unitCoverage[$unit->id][$currentDate][$shift][] = $cadanganId;
                                $assignedCadangan[$cadanganId] = true;
                                break;
                            }
                        }
                    }
                }
            }
        }
        
        return $schedulePlan;
    }
    
    /**
     * Create actual schedules from the optimized plan
     * 
     * @param array $schedulePlan Optimized schedule plan
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @param Collection $units Collection of units
     * @param Collection $batanganDrivers Collection of batangan (fixed) drivers
     * @param Collection $cadanganDrivers Collection of cadangan (non-fixed) drivers
     * @param array $unitDayOffs Array of unit day offs
     * @return array Result with success and failure counts
     */
    public function createSchedulesFromPlan(
        array $schedulePlan,
        string $startDate = null,
        string $endDate = null,
        Collection $units = null,
        Collection $batanganDrivers = null,
        Collection $cadanganDrivers = null,
        array $unitDayOffs = []
    ): array {
        // Always use the provided date parameters, no defaults
        if (!$startDate || !$endDate) {
            Log::warning('Missing date parameters in createSchedulesFromPlan');
            return [
                'success' => 0,
                'failed' => 0,
                'messages' => ['Missing required date parameters']
            ];
        }
        
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $totalDays = $start->diffInDays($end) + 1;
        
        $successCount = 0;
        $failedCount = 0;
        $messages = [];
        $schedulesToCreate = [];
        
        Log::info("Creating schedules from plan for " . count($schedulePlan) . " drivers");
        
        // Get driver types
        $driverTypes = [];
        if ($batanganDrivers !== null && $cadanganDrivers !== null) {
            foreach ($batanganDrivers as $driver) {
                $driverTypes[$driver->id] = 'batangan';
            }
            foreach ($cadanganDrivers as $driver) {
                $driverTypes[$driver->id] = 'cadangan';
            }
        }
        
        // Get driver-unit assignments
        $driverUnitAssignments = [];
        
        // Build driver-unit assignments
        foreach ($schedulePlan as $driverId => $driverSchedule) {
            $driver = Driver::find($driverId);
            
            if (!$driver) {
                $messages[] = "Driver with ID {$driverId} not found, skipping";
                $failedCount++;
                continue;
            }
            
            // Find units this driver is assigned to
            $driverUnits = $driver->units;
            
            if ($driverUnits->isEmpty()) {
                $messages[] = "Driver {$driver->name} has no units assigned, skipping";
                $failedCount++;
                continue;
            }
            
            // For batangan drivers, they can only be assigned to one unit
            if (isset($driverTypes[$driverId]) && $driverTypes[$driverId] === 'batangan') {
                $driverUnitAssignments[$driverId] = [$driverUnits->first()->id];
            } 
            // For cadangan drivers, they can be assigned to multiple units
            else {
                $driverUnitAssignments[$driverId] = $driverUnits->pluck('id')->toArray();
            }
        }
        
        // Initialize unit coverage tracking to ensure proper unit assignments
        $unitCoverage = [];
        if ($units !== null) {
            foreach ($units as $unit) {
                $unitCoverage[$unit->id] = [];
                for ($day = 0; $day < $totalDays; $day++) {
                    $currentDate = $start->copy()->addDays($day)->format('Y-m-d');
                    $unitCoverage[$unit->id][$currentDate] = [
                        self::SHIFT_PAGI => [],
                        self::SHIFT_SIANG => []
                    ];
                }
            }
        }
        
        // First pass: Assign batangan drivers to their specific units
        foreach ($schedulePlan as $driverId => $driverSchedule) {
            // Skip if not a batangan driver or no unit assignments
            if (!isset($driverTypes[$driverId]) || $driverTypes[$driverId] !== 'batangan' || !isset($driverUnitAssignments[$driverId])) {
                continue;
            }
            
            $driver = Driver::find($driverId);
            if (!$driver) continue;
            
            // Get the assigned unit for this batangan driver
            $unitId = $driverUnitAssignments[$driverId][0];
            $unit = Unit::find($unitId);
            if (!$unit) continue;
            
            // Get routes for this unit
            $routes = $unit->routes;
            if ($routes->isEmpty()) {
                $messages[] = "Unit {$unit->unit_number} has no routes assigned, skipping";
                continue;
            }
            
            $route = $routes->first();
            
            // Process each day in the schedule
            for ($day = 0; $day < min($totalDays, count($driverSchedule)); $day++) {
                $currentDate = $start->copy()->addDays($day);
                $dateStr = $currentDate->format('Y-m-d');
                $shift = $driverSchedule[$day];
                
                // Skip if no shift or unit is in day off
                if ($shift === self::SHIFT_NONE || 
                    (isset($unitDayOffs[$dateStr]) && in_array($unitId, $unitDayOffs[$dateStr]))) {
                    continue;
                }
                
                // Record this assignment in unit coverage
                $unitCoverage[$unitId][$dateStr][$shift][] = $driverId;
                
                // Check if a schedule already exists for this driver on this date
                $existingSchedule = Schedule::where('driver_id', $driverId)
                    ->where('schedule_date', $dateStr)
                    ->first();
                
                if ($existingSchedule) {
                    // Update existing schedule
                    $existingSchedule->shift = $shift;
                    $existingSchedule->route_id = $route->id;
                    $existingSchedule->unit_id = $unitId;
                    $existingSchedule->save();
                    
                    $successCount++;
                } else {
                    // Create a new schedule
                    $schedulesToCreate[] = [
                        'driver_id' => $driverId,
                        'route_id' => $route->id,
                        'unit_id' => $unitId,
                        'schedule_date' => $dateStr,
                        'shift' => $shift,
                        'status' => 'scheduled'
                    ];
                }
            }
        }
        
        // Second pass: Assign cadangan drivers to fill gaps
        foreach ($schedulePlan as $driverId => $driverSchedule) {
            // Skip if not a cadangan driver or no unit assignments
            if (!isset($driverTypes[$driverId]) || $driverTypes[$driverId] !== 'cadangan' || !isset($driverUnitAssignments[$driverId])) {
                continue;
            }
            
            $driver = Driver::find($driverId);
            if (!$driver) continue;
            
            // Process each day in the schedule
            for ($day = 0; $day < min($totalDays, count($driverSchedule)); $day++) {
                $currentDate = $start->copy()->addDays($day);
                $dateStr = $currentDate->format('Y-m-d');
                $shift = $driverSchedule[$day];
                
                // Skip if no shift
                if ($shift === self::SHIFT_NONE) {
                    continue;
                }
                
                // Find a unit that needs coverage for this shift
                $assignedUnitId = null;
                
                // First, check units this driver is assigned to
                foreach ($driverUnitAssignments[$driverId] as $unitId) {
                    // Skip if unit is in day off
                    if (isset($unitDayOffs[$dateStr]) && in_array($unitId, $unitDayOffs[$dateStr])) {
                        continue;
                    }
                    
                    // Check if this unit needs coverage for this shift
                    if (isset($unitCoverage[$unitId][$dateStr][$shift]) && 
                        empty($unitCoverage[$unitId][$dateStr][$shift])) {
                        $assignedUnitId = $unitId;
                        break;
                    }
                }
                
                // If no unit found, skip this assignment
                if ($assignedUnitId === null) {
                    continue;
                }
                
                $unit = Unit::find($assignedUnitId);
                if (!$unit) continue;
                
                // Get routes for this unit
                $routes = $unit->routes;
                if ($routes->isEmpty()) {
                    continue;
                }
                
                $route = $routes->first();
                
                // Record this assignment in unit coverage
                $unitCoverage[$assignedUnitId][$dateStr][$shift][] = $driverId;
                
                // Check if a schedule already exists for this driver on this date
                $existingSchedule = Schedule::where('driver_id', $driverId)
                    ->where('schedule_date', $dateStr)
                    ->first();
                
                if ($existingSchedule) {
                    // Update existing schedule
                    $existingSchedule->shift = $shift;
                    $existingSchedule->route_id = $route->id;
                    $existingSchedule->unit_id = $assignedUnitId;
                    $existingSchedule->save();
                    
                    $successCount++;
                } else {
                    // Create a new schedule
                    $schedulesToCreate[] = [
                        'driver_id' => $driverId,
                        'route_id' => $route->id,
                        'unit_id' => $assignedUnitId,
                        'schedule_date' => $dateStr,
                        'shift' => $shift,
                        'status' => 'scheduled'
                    ];
                }
            }
        }
        
        // Bulk insert new schedules
        if (!empty($schedulesToCreate)) {
            try {
                Schedule::insert($schedulesToCreate);
                $successCount += count($schedulesToCreate);
                $messages[] = "Successfully created " . count($schedulesToCreate) . " new schedules";
            } catch (\Exception $e) {
                $failedCount += count($schedulesToCreate);
                $messages[] = "Failed to create schedules: " . $e->getMessage();
                Log::error("Failed to create schedules: " . $e->getMessage());
            }
        }
        
        return [
            'success' => $successCount,
            'failed' => $failedCount,
            'messages' => $messages
        ];
    }
    
    /**
     * Validate if a shift transition is valid according to rules
     * 
     * @param string $currentShift Current shift
     * @param string $nextShift Next shift
     * @return bool Whether the transition is valid
     */
    public function isValidTransition(string $currentShift, string $nextShift): bool
    {
        $currentShiftCode = $this->shiftToShiftCode($currentShift);
        $nextShiftCode = $this->shiftToShiftCode($nextShift);
        
        $validNextShifts = $this->transitionRules[$currentShiftCode] ?? [];
        
        return in_array($nextShiftCode, $validNextShifts);
    }
    
    /**
     * Get predefined valid schedule patterns
     * 
     * @return array Array of valid patterns
     */
    public function getValidPatterns(): array
    {
        return [
            // Example 1: P→P✓, P→S✓, S→S✓, S→N✓
            [
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE
            ],
            // Example 2: P→S✓, S→S✓, S→N✓, N→P✓
            [
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE,
                self::SHIFT_CODE_PAGI
            ],
            // Example 3: S→S✓, S→S✓, S→N✓, N→S✓
            [
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE,
                self::SHIFT_CODE_SIANG
            ],
            // Example 4: N→P✓, P→P✓, P→S✓, S→S✓
            [
                self::SHIFT_CODE_NONE,
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_SIANG
            ],
            // Example 5: P→S✓, S→N✓, N→P✓, P→S✓
            [
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE,
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_SIANG
            ],
            // Example 6: N→N✓, N→P✓, P→S✓, S→N✓
            [
                self::SHIFT_CODE_NONE,
                self::SHIFT_CODE_NONE,
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE
            ],
            // Example 7: S→N✓, N→S✓, S→S✓, S→N✓
            [
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE
            ],
            // Example 8: P→P✓, P→P✓, P→S✓, S→N✓
            [
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE
            ],
        ];
    }
    
    /**
     * Get predefined invalid schedule patterns
     * 
     * @return array Array of invalid patterns with error messages
     */
    public function getInvalidPatterns(): array
    {
        return [
            // Invalid 1: S→P✗ (Day 1→2 FAILED!)
            [
                'pattern' => [
                    self::SHIFT_CODE_SIANG,
                    self::SHIFT_CODE_PAGI,
                    self::SHIFT_CODE_SIANG,
                    self::SHIFT_CODE_NONE,
                    self::SHIFT_CODE_PAGI
                ],
                'error' => 'S→P✗ (Day 1→2 FAILED!)'
            ],
            // Invalid 2: S→P✗ (Day 2→3 FAILED!)
            [
                'pattern' => [
                    self::SHIFT_CODE_PAGI,
                    self::SHIFT_CODE_SIANG,
                    self::SHIFT_CODE_PAGI,
                    self::SHIFT_CODE_SIANG,
                    self::SHIFT_CODE_SIANG
                ],
                'error' => 'S→P✗ (Day 2→3 FAILED!)'
            ],
            // Invalid 3: S→P✗ (Day 2→3 FAILED!)
            [
                'pattern' => [
                    self::SHIFT_CODE_SIANG,
                    self::SHIFT_CODE_SIANG,
                    self::SHIFT_CODE_PAGI,
                    self::SHIFT_CODE_PAGI,
                    self::SHIFT_CODE_SIANG
                ],
                'error' => 'S→P✗ (Day 2→3 FAILED!)'
            ],
            // Invalid 4: S→P✗ (Day 2→3 FAILED!)
            [
                'pattern' => [
                    self::SHIFT_CODE_NONE,
                    self::SHIFT_CODE_SIANG,
                    self::SHIFT_CODE_PAGI,
                    self::SHIFT_CODE_SIANG,
                    self::SHIFT_CODE_NONE
                ],
                'error' => 'S→P✗ (Day 2→3 FAILED!)'
            ],
            // Invalid 5: S→P✗ (Day 3→4 FAILED!)
            [
                'pattern' => [
                    self::SHIFT_CODE_PAGI,
                    self::SHIFT_CODE_PAGI,
                    self::SHIFT_CODE_SIANG,
                    self::SHIFT_CODE_PAGI,
                    self::SHIFT_CODE_SIANG
                ],
                'error' => 'S→P✗ (Day 3→4 FAILED!)'
            ],
            // Invalid 6: S→P✗ (Day 3→4 FAILED!)
            [
                'pattern' => [
                    self::SHIFT_CODE_SIANG,
                    self::SHIFT_CODE_NONE,
                    self::SHIFT_CODE_SIANG,
                    self::SHIFT_CODE_PAGI,
                    self::SHIFT_CODE_PAGI
                ],
                'error' => 'S→P✗ (Day 3→4 FAILED!)'
            ],
        ];
    }
}
