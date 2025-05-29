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
    
    // Track which unit a cadangan driver is assigned to on each day
    protected $cadanganDriverUnitAssignments = [];

    // Unit coverage constants
    const REQUIRED_SHIFTS_PER_DAY = 2; // Each unit needs exactly 2 drivers per day (Pagi + Siang)
    const REQUIRED_DRIVERS_PER_DAY = 2; // Each unit needs exactly 2 drivers per day (1 per shift)
    const MAX_DRIVERS_PER_SHIFT = 1; // Maximum 1 driver per shift per unit
    const MIN_DRIVERS_PER_SHIFT = 1; // Minimum 1 driver per shift per unit

    // Priority system constants
    const PRIORITY_BATANGAN = 1; // Highest priority for batangan drivers
    const PRIORITY_CADANGAN = 2; // Lower priority for cadangan drivers

    // Day-off management constants
    const MIN_DAYS_OFF_PER_PERIOD = 1; // Minimum days off for batangan drivers per scheduling period
    const BATANGAN_DRIVERS_PER_UNIT = 2; // Expected number of batangan drivers per unit

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

        // Pre-check if schedule can be generated with available resources
        $feasibilityCheck = $this->canScheduleBeGenerated(
            $units, $batanganDrivers, $cadanganDrivers, $totalDays, $unitDayOffs
        );

        if (!$feasibilityCheck['can_generate']) {
            Log::warning('Schedule generation failed feasibility check', $feasibilityCheck);
            throw new \InvalidArgumentException(
                'Cannot generate schedule: ' . implode('; ', $feasibilityCheck['issues'])
            );
        }

        // Log warnings if any
        if (!empty($feasibilityCheck['warnings'])) {
            foreach ($feasibilityCheck['warnings'] as $warning) {
                Log::warning('Schedule generation warning: ' . $warning);
            }
        }

        // Initialize schedule plan
        $schedulePlan = [];
        
        // Initialize cadangan driver unit assignments tracking
        $this->cadanganDriverUnitAssignments = [];
        foreach ($cadanganDrivers as $driver) {
            $this->cadanganDriverUnitAssignments[$driver->id] = array_fill(0, $totalDays, null);
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

        // PRIORITY SYSTEM: Generate day-off assignments for batangan drivers
        $batanganDayOffs = $this->generateBatanganDayOffs($batanganDrivers, $totalDays, $unitDayOffs);
        Log::info("Generated day-off assignments for " . count($batanganDayOffs) . " batangan drivers");

        // Initialize schedule plan for all drivers
        foreach ($batanganDrivers as $driver) {
            $schedulePlan[$driver->id] = array_fill(0, $totalDays, self::SHIFT_NONE);
        }
        foreach ($cadanganDrivers as $driver) {
            $schedulePlan[$driver->id] = array_fill(0, $totalDays, self::SHIFT_NONE);
        }

        // PRIORITY SYSTEM: Build driver-unit assignments
        $driverUnitAssignments = [];

        // For batangan drivers, they can only be assigned to one unit (fixed assignment)
        foreach ($batanganDrivers as $driver) {
            $driverUnits = $driver->units;

            if ($driverUnits->isEmpty()) {
                Log::warning("Batangan driver {$driver->id} has no units assigned, skipping");
                continue;
            }

            // For batangan drivers, use the first assigned unit as their fixed unit
            $assignedUnit = $driverUnits->first();
            $driverUnitAssignments[$driver->id] = [$assignedUnit->id];

            Log::info("Batangan driver {$driver->id} assigned to Unit {$assignedUnit->unit_number} (fixed assignment)");
        }

        // For cadangan drivers, they can be assigned to multiple units (flexible assignment)
        foreach ($cadanganDrivers as $driver) {
            $driverUnits = $driver->units;

            if ($driverUnits->isEmpty()) {
                Log::warning("Cadangan driver {$driver->id} has no units assigned, skipping");
                continue;
            }

            $driverUnitAssignments[$driver->id] = $driverUnits->pluck('id')->toArray();

            Log::info("Cadangan driver {$driver->id} qualified for Units: " . implode(', ', $driverUnitAssignments[$driver->id]) . " (flexible assignment)");
        }

        // PRIORITY SYSTEM: Use priority-based assignment to fill all shifts
        Log::info("Starting priority-based driver assignment...");

        $assignmentLog = $this->assignDriversWithPriority(
            $unitCoverage,
            $units,
            $batanganDrivers,
            $cadanganDrivers,
            $driverUnitAssignments,
            $batanganDayOffs,
            $schedulePlan,
            $startDate,
            $totalDays,
            $unitDayOffs
        );

        // Log assignment summary
        $batanganAssignments = 0;
        $cadanganAssignments = 0;
        $dayOffCoverages = 0;

        foreach ($assignmentLog as $logEntry) {
            if (strpos($logEntry, 'batangan driver') !== false) {
                $batanganAssignments++;
            } elseif (strpos($logEntry, 'cadangan driver') !== false) {
                $cadanganAssignments++;
                if (strpos($logEntry, 'covering for batangan driver') !== false) {
                    $dayOffCoverages++;
                }
            }
        }

        Log::info("Assignment Summary: {$batanganAssignments} batangan assignments, {$cadanganAssignments} cadangan assignments, {$dayOffCoverages} day-off coverages");

        // Validate the generated schedule against all constraints including priority system
        $validationResults = $this->validateScheduleConstraintsWithPriority(
            $unitCoverage, $units, $batanganDrivers, $cadanganDrivers,
            $driverUnitAssignments, $batanganDayOffs, $startDate, $totalDays, $unitDayOffs
        );

        // Log validation results
        if (!$validationResults['is_valid']) {
            Log::warning('Generated schedule violates constraints', [
                'total_violations' => $validationResults['total_violations'],
                'basic_violations' => count($validationResults['basic_violations']),
                'priority_violations' => count($validationResults['priority_violations']),
                'summary' => $validationResults['summary']
            ]);

            // Log basic constraint violations
            foreach ($validationResults['basic_violations'] as $violation) {
                Log::warning('Basic constraint violation: ' . $violation['message'], $violation);
            }

            // Log priority system violations
            foreach ($validationResults['priority_violations'] as $violation) {
                Log::warning('Priority system violation: ' . $violation['message'], $violation);
            }
        } else {
            Log::info('Generated schedule meets all constraints including priority system', [
                'total_units' => $validationResults['summary']['total_units'],
                'total_days' => $validationResults['summary']['total_days'],
                'batangan_drivers' => $validationResults['summary']['total_batangan_drivers'],
                'cadangan_drivers' => $validationResults['summary']['total_cadangan_drivers'],
                'day_offs_assigned' => $validationResults['summary']['batangan_day_offs_assigned']
            ]);
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
     * Validate that each day has exactly 2 drivers assigned (one per shift)
     *
     * @param array $unitCoverage Unit coverage tracking array
     * @param Collection $units Collection of units
     * @param string $startDate Start date in Y-m-d format
     * @param int $totalDays Total number of days
     * @param array $unitDayOffs Array of unit day offs
     * @return array Validation results with violations
     */
    protected function validateDailyDriverConstraints(
        array $unitCoverage,
        Collection $units,
        string $startDate,
        int $totalDays,
        array $unitDayOffs = []
    ): array {
        $violations = [];
        $start = Carbon::parse($startDate);

        foreach ($units as $unit) {
            for ($day = 0; $day < $totalDays; $day++) {
                $currentDate = $start->copy()->addDays($day)->format('Y-m-d');

                // Skip if unit is in day off
                if (isset($unitDayOffs[$currentDate]) && in_array($unit->id, $unitDayOffs[$currentDate])) {
                    continue;
                }

                $driversAssigned = 0;
                $shiftCoverage = [];

                foreach ([self::SHIFT_PAGI, self::SHIFT_SIANG] as $shift) {
                    $assignedDrivers = $unitCoverage[$unit->id][$currentDate][$shift] ?? [];
                    $driverCount = is_array($assignedDrivers) ? count($assignedDrivers) : (empty($assignedDrivers) ? 0 : 1);

                    $shiftCoverage[$shift] = $driverCount;
                    $driversAssigned += $driverCount;

                    // Check for over-assignment (more than 1 driver per shift)
                    if ($driverCount > self::MAX_DRIVERS_PER_SHIFT) {
                        $violations[] = [
                            'type' => 'over_assignment',
                            'unit_id' => $unit->id,
                            'date' => $currentDate,
                            'shift' => $shift,
                            'assigned_count' => $driverCount,
                            'max_allowed' => self::MAX_DRIVERS_PER_SHIFT,
                            'message' => "Unit {$unit->unit_number} on {$currentDate} has {$driverCount} drivers assigned to {$shift} shift (max: " . self::MAX_DRIVERS_PER_SHIFT . ")"
                        ];
                    }

                    // Check for under-assignment (less than 1 driver per shift)
                    if ($driverCount < self::MIN_DRIVERS_PER_SHIFT) {
                        $violations[] = [
                            'type' => 'under_assignment',
                            'unit_id' => $unit->id,
                            'date' => $currentDate,
                            'shift' => $shift,
                            'assigned_count' => $driverCount,
                            'min_required' => self::MIN_DRIVERS_PER_SHIFT,
                            'message' => "Unit {$unit->unit_number} on {$currentDate} has {$driverCount} drivers assigned to {$shift} shift (min: " . self::MIN_DRIVERS_PER_SHIFT . ")"
                        ];
                    }
                }

                // Check total drivers per day constraint
                if ($driversAssigned !== self::REQUIRED_DRIVERS_PER_DAY) {
                    $violations[] = [
                        'type' => 'daily_driver_count',
                        'unit_id' => $unit->id,
                        'date' => $currentDate,
                        'assigned_count' => $driversAssigned,
                        'required_count' => self::REQUIRED_DRIVERS_PER_DAY,
                        'shift_coverage' => $shiftCoverage,
                        'message' => "Unit {$unit->unit_number} on {$currentDate} has {$driversAssigned} drivers assigned (required: " . self::REQUIRED_DRIVERS_PER_DAY . ")"
                    ];
                }
            }
        }

        return $violations;
    }

    /**
     * Validate that each day has exactly 2 shifts scheduled
     *
     * @param array $unitCoverage Unit coverage tracking array
     * @param Collection $units Collection of units
     * @param string $startDate Start date in Y-m-d format
     * @param int $totalDays Total number of days
     * @param array $unitDayOffs Array of unit day offs
     * @return array Validation results with violations
     */
    protected function validateDailyShiftConstraints(
        array $unitCoverage,
        Collection $units,
        string $startDate,
        int $totalDays,
        array $unitDayOffs = []
    ): array {
        $violations = [];
        $start = Carbon::parse($startDate);

        foreach ($units as $unit) {
            for ($day = 0; $day < $totalDays; $day++) {
                $currentDate = $start->copy()->addDays($day)->format('Y-m-d');

                // Skip if unit is in day off
                if (isset($unitDayOffs[$currentDate]) && in_array($unit->id, $unitDayOffs[$currentDate])) {
                    continue;
                }

                $shiftsScheduled = 0;
                $missingShifts = [];

                foreach ([self::SHIFT_PAGI, self::SHIFT_SIANG] as $shift) {
                    $assignedDrivers = $unitCoverage[$unit->id][$currentDate][$shift] ?? [];
                    $hasShift = is_array($assignedDrivers) ? !empty($assignedDrivers) : !empty($assignedDrivers);

                    if ($hasShift) {
                        $shiftsScheduled++;
                    } else {
                        $missingShifts[] = $shift;
                    }
                }

                // Check if we have exactly the required number of shifts
                if ($shiftsScheduled !== self::REQUIRED_SHIFTS_PER_DAY) {
                    $violations[] = [
                        'type' => 'daily_shift_count',
                        'unit_id' => $unit->id,
                        'date' => $currentDate,
                        'scheduled_count' => $shiftsScheduled,
                        'required_count' => self::REQUIRED_SHIFTS_PER_DAY,
                        'missing_shifts' => $missingShifts,
                        'message' => "Unit {$unit->unit_number} on {$currentDate} has {$shiftsScheduled} shifts scheduled (required: " . self::REQUIRED_SHIFTS_PER_DAY . ")"
                    ];
                }
            }
        }

        return $violations;
    }

    /**
     * Validate priority system requirements
     *
     * @param array $unitCoverage Unit coverage tracking array
     * @param Collection $units Collection of units
     * @param Collection $batanganDrivers Collection of batangan drivers
     * @param Collection $cadanganDrivers Collection of cadangan drivers
     * @param array $driverUnitAssignments Driver-unit assignments
     * @param array $batanganDayOffs Batangan day-off assignments
     * @param string $startDate Start date in Y-m-d format
     * @param int $totalDays Total number of days
     * @param array $unitDayOffs Array of unit day offs
     * @return array Validation results with priority violations
     */
    protected function validatePrioritySystemConstraints(
        array $unitCoverage,
        Collection $units,
        Collection $batanganDrivers,
        Collection $cadanganDrivers,
        array $driverUnitAssignments,
        array $batanganDayOffs,
        string $startDate,
        int $totalDays,
        array $unitDayOffs = []
    ): array {
        $violations = [];
        $start = Carbon::parse($startDate);

        // Get driver types for quick lookup
        $driverTypes = [];
        foreach ($batanganDrivers as $driver) {
            $driverTypes[$driver->id] = 'batangan';
        }
        foreach ($cadanganDrivers as $driver) {
            $driverTypes[$driver->id] = 'cadangan';
        }

        foreach ($units as $unit) {
            for ($day = 0; $day < $totalDays; $day++) {
                $currentDate = $start->copy()->addDays($day)->format('Y-m-d');

                // Skip if unit is in day off
                if (isset($unitDayOffs[$currentDate]) && in_array($unit->id, $unitDayOffs[$currentDate])) {
                    continue;
                }

                foreach ([self::SHIFT_PAGI, self::SHIFT_SIANG] as $shift) {
                    $assignedDrivers = $unitCoverage[$unit->id][$currentDate][$shift] ?? [];

                    if (empty($assignedDrivers)) {
                        continue;
                    }

                    foreach ($assignedDrivers as $driverId) {
                        $driverType = $driverTypes[$driverId] ?? 'unknown';

                        // Check if cadangan driver is assigned when batangan driver is available
                        if ($driverType === 'cadangan') {
                            // Find batangan drivers assigned to this unit
                            $unitBatanganDrivers = [];
                            foreach ($batanganDrivers as $batanganDriver) {
                                if (isset($driverUnitAssignments[$batanganDriver->id]) &&
                                    in_array($unit->id, $driverUnitAssignments[$batanganDriver->id])) {
                                    $unitBatanganDrivers[] = $batanganDriver->id;
                                }
                            }

                            // Check if any batangan driver was available for this shift
                            $batanganAvailable = false;
                            foreach ($unitBatanganDrivers as $batanganId) {
                                // Check if batangan driver was on day-off
                                if ($this->isBatanganDriverOnDayOff($batanganId, $day, $batanganDayOffs)) {
                                    continue; // This is acceptable - cadangan covering for day-off
                                }

                                // Check if batangan driver was already assigned to another shift this day
                                $alreadyAssigned = false;
                                foreach ([self::SHIFT_PAGI, self::SHIFT_SIANG] as $otherShift) {
                                    $otherAssignments = $unitCoverage[$unit->id][$currentDate][$otherShift] ?? [];
                                    if (in_array($batanganId, $otherAssignments)) {
                                        $alreadyAssigned = true;
                                        break;
                                    }
                                }

                                if (!$alreadyAssigned) {
                                    $batanganAvailable = true;
                                    break;
                                }
                            }

                            if ($batanganAvailable) {
                                $violations[] = [
                                    'type' => 'priority_violation',
                                    'unit_id' => $unit->id,
                                    'date' => $currentDate,
                                    'shift' => $shift,
                                    'assigned_driver_id' => $driverId,
                                    'assigned_driver_type' => $driverType,
                                    'message' => "Cadangan driver {$driverId} assigned to Unit {$unit->unit_number} on {$currentDate} {$shift} shift when batangan driver was available"
                                ];
                            }
                        }
                    }
                }
            }
        }

        // Validate day-off requirements for batangan drivers
        foreach ($batanganDrivers as $driver) {
            $daysOff = $batanganDayOffs[$driver->id] ?? [];

            if (count($daysOff) < self::MIN_DAYS_OFF_PER_PERIOD) {
                $violations[] = [
                    'type' => 'insufficient_days_off',
                    'driver_id' => $driver->id,
                    'driver_type' => 'batangan',
                    'days_off_assigned' => count($daysOff),
                    'min_required' => self::MIN_DAYS_OFF_PER_PERIOD,
                    'message' => "Batangan driver {$driver->id} has only " . count($daysOff) . " days off (min required: " . self::MIN_DAYS_OFF_PER_PERIOD . ")"
                ];
            }
        }

        return $violations;
    }

    /**
     * Main validation method to check all scheduling constraints
     *
     * @param array $unitCoverage Unit coverage tracking array
     * @param Collection $units Collection of units
     * @param string $startDate Start date in Y-m-d format
     * @param int $totalDays Total number of days
     * @param array $unitDayOffs Array of unit day offs
     * @return array Comprehensive validation results
     */
    public function validateScheduleConstraints(
        array $unitCoverage,
        Collection $units,
        string $startDate,
        int $totalDays,
        array $unitDayOffs = []
    ): array {
        $driverViolations = $this->validateDailyDriverConstraints(
            $unitCoverage, $units, $startDate, $totalDays, $unitDayOffs
        );

        $shiftViolations = $this->validateDailyShiftConstraints(
            $unitCoverage, $units, $startDate, $totalDays, $unitDayOffs
        );

        $allViolations = array_merge($driverViolations, $shiftViolations);

        return [
            'is_valid' => empty($allViolations),
            'violations' => $allViolations,
            'driver_violations' => $driverViolations,
            'shift_violations' => $shiftViolations,
            'total_violations' => count($allViolations),
            'summary' => $this->generateValidationSummary($allViolations, $units, $totalDays)
        ];
    }

    /**
     * Generate a summary of validation results
     *
     * @param array $violations All violations found
     * @param Collection $units Collection of units
     * @param int $totalDays Total number of days
     * @return array Summary statistics
     */
    protected function generateValidationSummary(array $violations, Collection $units, int $totalDays): array
    {
        $summary = [
            'total_units' => $units->count(),
            'total_days' => $totalDays,
            'total_unit_days' => $units->count() * $totalDays,
            'violation_types' => [],
            'affected_units' => [],
            'affected_dates' => []
        ];

        foreach ($violations as $violation) {
            $type = $violation['type'];
            $unitId = $violation['unit_id'];
            $date = $violation['date'];

            // Count violation types
            if (!isset($summary['violation_types'][$type])) {
                $summary['violation_types'][$type] = 0;
            }
            $summary['violation_types'][$type]++;

            // Track affected units
            if (!in_array($unitId, $summary['affected_units'])) {
                $summary['affected_units'][] = $unitId;
            }

            // Track affected dates
            if (!in_array($date, $summary['affected_dates'])) {
                $summary['affected_dates'][] = $date;
            }
        }

        $summary['affected_unit_count'] = count($summary['affected_units']);
        $summary['affected_date_count'] = count($summary['affected_dates']);

        return $summary;
    }

    /**
     * Check if a schedule can be generated given available resources
     *
     * @param Collection $units Collection of units
     * @param Collection $batanganDrivers Collection of batangan drivers
     * @param Collection $cadanganDrivers Collection of cadangan drivers
     * @param int $totalDays Total number of days to schedule
     * @param array $unitDayOffs Array of unit day offs
     * @return array Feasibility check results
     */
    public function canScheduleBeGenerated(
        Collection $units,
        Collection $batanganDrivers,
        Collection $cadanganDrivers,
        int $totalDays,
        array $unitDayOffs = []
    ): array {
        $issues = [];
        $warnings = [];

        // Calculate total driver requirements
        $workingDays = 0;
        foreach ($units as $unit) {
            for ($day = 0; $day < $totalDays; $day++) {
                $currentDate = Carbon::now()->addDays($day)->format('Y-m-d');
                if (!isset($unitDayOffs[$currentDate]) || !in_array($unit->id, $unitDayOffs[$currentDate])) {
                    $workingDays++;
                }
            }
        }

        $totalDriverSlotsNeeded = $workingDays * self::REQUIRED_DRIVERS_PER_DAY;
        $totalAvailableDrivers = $batanganDrivers->count() + $cadanganDrivers->count();

        // Check if we have enough drivers
        if ($totalAvailableDrivers === 0) {
            $issues[] = "No drivers available for scheduling";
        } elseif ($totalDriverSlotsNeeded > ($totalAvailableDrivers * $totalDays)) {
            $issues[] = "Insufficient drivers: need {$totalDriverSlotsNeeded} driver-slots but only have {$totalAvailableDrivers} drivers for {$totalDays} days";
        }

        // Check unit-driver assignments with priority system requirements
        foreach ($units as $unit) {
            $assignedDrivers = $unit->drivers;
            if ($assignedDrivers->isEmpty()) {
                $issues[] = "Unit {$unit->unit_number} has no drivers assigned";
                continue;
            }

            // Check batangan driver assignments
            $batanganCount = $assignedDrivers->where('type', 'batangan')->count();
            $cadanganCount = $assignedDrivers->where('type', 'cadangan')->count();

            if ($batanganCount === 0) {
                $issues[] = "Unit {$unit->unit_number} has no batangan drivers assigned (required for priority system)";
            } elseif ($batanganCount < self::BATANGAN_DRIVERS_PER_UNIT) {
                $warnings[] = "Unit {$unit->unit_number} has only {$batanganCount} batangan drivers (recommended: " . self::BATANGAN_DRIVERS_PER_UNIT . ")";
            }

            if ($cadanganCount === 0) {
                $warnings[] = "Unit {$unit->unit_number} has no cadangan drivers assigned (may cause coverage issues during batangan day-offs)";
            }

            $totalDrivers = $assignedDrivers->count();
            if ($totalDrivers < self::REQUIRED_DRIVERS_PER_DAY) {
                $issues[] = "Unit {$unit->unit_number} has only {$totalDrivers} drivers assigned (minimum required: " . self::REQUIRED_DRIVERS_PER_DAY . ")";
            }
        }

        // Check if units have routes
        foreach ($units as $unit) {
            if ($unit->routes->isEmpty()) {
                $issues[] = "Unit {$unit->unit_number} has no routes assigned";
            }
        }

        return [
            'can_generate' => empty($issues),
            'issues' => $issues,
            'warnings' => $warnings,
            'requirements' => [
                'total_driver_slots_needed' => $totalDriverSlotsNeeded,
                'total_available_drivers' => $totalAvailableDrivers,
                'working_days' => $workingDays,
                'total_days' => $totalDays
            ]
        ];
    }

    /**
     * Generate day-off assignments for batangan drivers
     *
     * @param Collection $batanganDrivers Collection of batangan drivers
     * @param int $totalDays Total number of days in the scheduling period
     * @param array $unitDayOffs Array of unit day offs
     * @return array Day-off assignments [driver_id => [day_indices]]
     */
    protected function generateBatanganDayOffs(
        Collection $batanganDrivers,
        int $totalDays,
        array $unitDayOffs = []
    ): array {
        $dayOffAssignments = [];

        foreach ($batanganDrivers as $driver) {
            $driverUnits = $driver->units;
            if ($driverUnits->isEmpty()) {
                continue;
            }

            // For batangan drivers, use their primary unit
            $primaryUnit = $driverUnits->first();

            // Find suitable day-off days for this driver
            $availableDays = [];
            for ($day = 0; $day < $totalDays; $day++) {
                $currentDate = Carbon::now()->addDays($day)->format('Y-m-d');

                // Skip if unit is already in day off
                if (isset($unitDayOffs[$currentDate]) && in_array($primaryUnit->id, $unitDayOffs[$currentDate])) {
                    continue;
                }

                $availableDays[] = $day;
            }

            // Assign minimum required days off
            $daysOffNeeded = min(self::MIN_DAYS_OFF_PER_PERIOD, count($availableDays));

            if ($daysOffNeeded > 0) {
                // Distribute day-offs evenly across the period
                $dayOffIndices = [];
                $interval = max(1, floor(count($availableDays) / $daysOffNeeded));

                for ($i = 0; $i < $daysOffNeeded; $i++) {
                    $dayIndex = $availableDays[min($i * $interval, count($availableDays) - 1)];
                    $dayOffIndices[] = $dayIndex;
                }

                $dayOffAssignments[$driver->id] = $dayOffIndices;

                Log::info("Assigned day-offs for batangan driver {$driver->id}: " . implode(', ', $dayOffIndices));
            }
        }

        return $dayOffAssignments;
    }

    /**
     * Check if a batangan driver is on day-off for a specific day
     *
     * @param int $driverId Driver ID
     * @param int $dayIndex Day index (0-based)
     * @param array $batanganDayOffs Day-off assignments
     * @return bool True if driver is on day-off
     */
    protected function isBatanganDriverOnDayOff(int $driverId, int $dayIndex, array $batanganDayOffs): bool
    {
        return isset($batanganDayOffs[$driverId]) && in_array($dayIndex, $batanganDayOffs[$driverId]);
    }

    /**
     * Get priority-ordered drivers for a specific unit and shift
     *
     * @param int $unitId Unit ID
     * @param string $shift Shift type
     * @param int $dayIndex Day index
     * @param Collection $batanganDrivers Collection of batangan drivers
     * @param Collection $cadanganDrivers Collection of cadangan drivers
     * @param array $driverUnitAssignments Driver-unit assignments
     * @param array $batanganDayOffs Batangan day-off assignments
     * @param array $schedulePlan Current schedule plan
     * @return array Priority-ordered list of available drivers
     */
    protected function getPriorityOrderedDrivers(
        int $unitId,
        string $shift,
        int $dayIndex,
        Collection $batanganDrivers,
        Collection $cadanganDrivers,
        array $driverUnitAssignments,
        array $batanganDayOffs,
        array $schedulePlan
    ): array {
        $priorityDrivers = [];

        // PRIORITY 1: Available batangan drivers assigned to this unit
        foreach ($batanganDrivers as $driver) {
            // Check if driver is assigned to this unit
            if (!isset($driverUnitAssignments[$driver->id]) ||
                !in_array($unitId, $driverUnitAssignments[$driver->id])) {
                continue;
            }

            // Check if driver is on day-off
            if ($this->isBatanganDriverOnDayOff($driver->id, $dayIndex, $batanganDayOffs)) {
                Log::info("Batangan driver {$driver->id} is on day-off for day {$dayIndex}, skipping");
                continue;
            }

            // Check if driver is already assigned for this day
            if (isset($schedulePlan[$driver->id][$dayIndex]) &&
                $schedulePlan[$driver->id][$dayIndex] !== self::SHIFT_NONE) {
                continue;
            }

            $priorityDrivers[] = [
                'driver_id' => $driver->id,
                'type' => 'batangan',
                'priority' => self::PRIORITY_BATANGAN,
                'unit_id' => $unitId
            ];
        }

        // PRIORITY 2: Available cadangan drivers qualified for this unit
        foreach ($cadanganDrivers as $driver) {
            // Check if driver is qualified for this unit
            if (!isset($driverUnitAssignments[$driver->id]) ||
                !in_array($unitId, $driverUnitAssignments[$driver->id])) {
                continue;
            }

            // Check if driver is already assigned for this day
            if (isset($schedulePlan[$driver->id][$dayIndex]) &&
                $schedulePlan[$driver->id][$dayIndex] !== self::SHIFT_NONE) {
                continue;
            }
            
            // Calculate how many times this driver has been assigned to this unit
            $assignmentsToThisUnit = 0;
            $totalAssignments = 0;
            
            if (isset($this->cadanganDriverUnitAssignments[$driver->id])) {
                foreach ($this->cadanganDriverUnitAssignments[$driver->id] as $day => $assignedUnitId) {
                    if ($assignedUnitId !== null) {
                        $totalAssignments++;
                        if ($assignedUnitId === $unitId) {
                            $assignmentsToThisUnit++;
                        }
                    }
                }
            }
            
            // Adjust priority based on assignment distribution
            // Lower priority value means higher priority
            $adjustedPriority = self::PRIORITY_CADANGAN;
            
            // If driver has fewer total assignments, give higher priority
            $adjustedPriority -= (10 - min(10, $totalAssignments)) * 0.1;
            
            // If driver has been assigned to this unit less frequently, give higher priority
            if ($totalAssignments > 0) {
                $unitRatio = $assignmentsToThisUnit / $totalAssignments;
                // Lower ratio means driver has been assigned to this unit less frequently
                $adjustedPriority -= (1 - $unitRatio) * 0.5;
            }

            $priorityDrivers[] = [
                'driver_id' => $driver->id,
                'type' => 'cadangan',
                'priority' => $adjustedPriority,
                'unit_id' => $unitId,
                'assignments_to_unit' => $assignmentsToThisUnit,
                'total_assignments' => $totalAssignments
            ];
        }

        // Sort by priority (lower number = higher priority)
        usort($priorityDrivers, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        return $priorityDrivers;
    }

    /**
     * Assign drivers using priority-based system
     *
     * @param array $unitCoverage Unit coverage tracking
     * @param Collection $units Collection of units
     * @param Collection $batanganDrivers Collection of batangan drivers
     * @param Collection $cadanganDrivers Collection of cadangan drivers
     * @param array $driverUnitAssignments Driver-unit assignments
     * @param array $batanganDayOffs Batangan day-off assignments
     * @param array $schedulePlan Current schedule plan
     * @param string $startDate Start date
     * @param int $totalDays Total number of days
     * @param array $unitDayOffs Unit day-offs
     * @return array Updated schedule plan
     */
    protected function assignDriversWithPriority(
        array &$unitCoverage,
        Collection $units,
        Collection $batanganDrivers,
        Collection $cadanganDrivers,
        array $driverUnitAssignments,
        array $batanganDayOffs,
        array &$schedulePlan,
        string $startDate,
        int $totalDays,
        array $unitDayOffs = []
    ): array {
        $start = Carbon::parse($startDate);
        $assignmentLog = [];

        // Process each day
        for ($day = 0; $day < $totalDays; $day++) {
            $currentDate = $start->copy()->addDays($day)->format('Y-m-d');

            // Process each unit
            foreach ($units as $unit) {
                // Skip if unit is in day off
                if (isset($unitDayOffs[$currentDate]) && in_array($unit->id, $unitDayOffs[$currentDate])) {
                    continue;
                }

                // Process each shift
                foreach ([self::SHIFT_PAGI, self::SHIFT_SIANG] as $shift) {
                    // Check if shift is already covered
                    if (!empty($unitCoverage[$unit->id][$currentDate][$shift])) {
                        continue;
                    }

                    // Get priority-ordered drivers for this unit and shift
                    $priorityDrivers = $this->getPriorityOrderedDrivers(
                        $unit->id,
                        $shift,
                        $day,
                        $batanganDrivers,
                        $cadanganDrivers,
                        $driverUnitAssignments,
                        $batanganDayOffs,
                        $schedulePlan
                    );

                    // Assign the highest priority available driver
                    foreach ($priorityDrivers as $driverInfo) {
                        $driverId = $driverInfo['driver_id'];
                        $driverType = $driverInfo['type'];

                        // Check transition rules
                        $canAssign = true;
                        if ($day > 0) {
                            $previousShift = $schedulePlan[$driverId][$day - 1] ?? self::SHIFT_NONE;
                            $previousShiftCode = $this->shiftToShiftCode($previousShift);
                            $currentShiftCode = $this->shiftToShiftCode($shift);

                            $validNextShifts = $this->transitionRules[$previousShiftCode] ?? [];
                            if (!in_array($currentShiftCode, $validNextShifts)) {
                                $canAssign = false;
                            }
                        }

                        if ($canAssign) {
                            // Assign the driver
                            $schedulePlan[$driverId][$day] = $shift;
                            $unitCoverage[$unit->id][$currentDate][$shift][] = $driverId;
                            
                            // Track which unit the cadangan driver is assigned to on this day
                            if ($driverType === 'cadangan') {
                                $this->cadanganDriverUnitAssignments[$driverId][$day] = $unit->id;
                            }

                            $logMessage = "PRIORITY ASSIGNMENT: {$driverType} driver {$driverId} assigned to Unit {$unit->unit_number} on {$currentDate} {$shift} shift";

                            // Check if this is a cadangan covering for batangan day-off
                            if ($driverType === 'cadangan') {
                                $batanganOnDayOff = false;
                                foreach ($batanganDrivers as $batanganDriver) {
                                    if (isset($driverUnitAssignments[$batanganDriver->id]) &&
                                        in_array($unit->id, $driverUnitAssignments[$batanganDriver->id]) &&
                                        $this->isBatanganDriverOnDayOff($batanganDriver->id, $day, $batanganDayOffs)) {
                                        $batanganOnDayOff = true;
                                        $logMessage .= " (covering for batangan driver {$batanganDriver->id} day-off)";
                                        break;
                                    }
                                }
                            }

                            Log::info($logMessage);
                            $assignmentLog[] = $logMessage;
                            break;
                        }
                    }

                    // If no driver could be assigned, log the gap
                    if (empty($unitCoverage[$unit->id][$currentDate][$shift])) {
                        $gapMessage = "COVERAGE GAP: No available driver for Unit {$unit->unit_number} on {$currentDate} {$shift} shift";
                        Log::warning($gapMessage);
                        $assignmentLog[] = $gapMessage;
                    }
                }
            }
        }

        return $assignmentLog;
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
                    // Use the tracked unit assignment if available
                    $assignedUnitId = $this->cadanganDriverUnitAssignments[$driverId][$day] ?? null;
                    
                    if ($assignedUnitId !== null && isset($unitCoverage[$assignedUnitId][$currentDate])) {
                        // Skip if unit is in day off
                        if (isset($unitDayOffs[$currentDate]) && in_array($assignedUnitId, $unitDayOffs[$currentDate])) {
                            // Set to no shift if unit is in day off
                            $schedulePlan[$driverId][$day] = self::SHIFT_NONE;
                            continue;
                        }
                        
                        $unitCoverage[$assignedUnitId][$currentDate][$shift][] = $driverId;
                    } else {
                        // If no unit assignment is tracked, try to find an appropriate unit
                        $possibleUnits = $driverUnitAssignments[$driverId] ?? [];
                        foreach ($possibleUnits as $possibleUnitId) {
                            // Skip if unit is in day off
                            if (isset($unitDayOffs[$currentDate]) && in_array($possibleUnitId, $unitDayOffs[$currentDate])) {
                                continue;
                            }
                            
                            // Assign to this unit if it has less than MAX_DRIVERS_PER_SHIFT drivers for this shift
                            if (isset($unitCoverage[$possibleUnitId][$currentDate][$shift]) && 
                                count($unitCoverage[$possibleUnitId][$currentDate][$shift]) < self::MAX_DRIVERS_PER_SHIFT) {
                                $unitCoverage[$possibleUnitId][$currentDate][$shift][] = $driverId;
                                $this->cadanganDriverUnitAssignments[$driverId][$day] = $possibleUnitId;
                                break;
                            }
                        }
                    }
                }
            }
        }

        // Validate and fix the schedule to enforce constraints
        // 1. Check for over-assignments (more than one driver per shift per unit) - ENFORCE MAX 1 DRIVER PER SHIFT
        // 2. Check for under-assignments (missing shifts) - ENFORCE EXACTLY 2 SHIFTS PER DAY
        // 3. Ensure batangan drivers are only assigned to their specific unit
        // 4. Ensure cadangan drivers fill in the gaps to meet EXACTLY 2 DRIVERS PER DAY constraint

        // First, fix batangan assignments
        foreach ($units as $unit) {
            for ($day = 0; $day < $totalDays; $day++) {
                $currentDate = $start->copy()->addDays($day)->format('Y-m-d');

                // Skip if unit is in day off
                if (isset($unitDayOffs[$currentDate]) && in_array($unit->id, $unitDayOffs[$currentDate])) {
                    continue;
                }

                // ENFORCE CONSTRAINT: Check for over-assignments in each shift (MAX 1 DRIVER PER SHIFT)
                foreach ([self::SHIFT_PAGI, self::SHIFT_SIANG] as $shift) {
                    $assignedDrivers = $unitCoverage[$unit->id][$currentDate][$shift];

                    // CONSTRAINT ENFORCEMENT: If more than MAX_DRIVERS_PER_SHIFT drivers assigned, keep only one
                    if (count($assignedDrivers) > self::MAX_DRIVERS_PER_SHIFT) {
                        Log::warning("Over-assignment detected: Unit {$unit->unit_number} on {$currentDate} {$shift} shift has " . count($assignedDrivers) . " drivers (max: " . self::MAX_DRIVERS_PER_SHIFT . ")");

                        // Prioritize batangan drivers over cadangan drivers
                        $batanganAssigned = array_filter($assignedDrivers, function($driverId) use ($driverTypes) {
                            return ($driverTypes[$driverId] ?? '') === 'batangan';
                        });

                        $cadanganAssigned = array_filter($assignedDrivers, function($driverId) use ($driverTypes) {
                            return ($driverTypes[$driverId] ?? '') === 'cadangan';
                        });

                        // Keep one driver (prefer batangan if available)
                        $keepDriverId = null;
                        if (!empty($batanganAssigned)) {
                            $keepDriverId = reset($batanganAssigned);
                        } elseif (!empty($cadanganAssigned)) {
                            $keepDriverId = reset($cadanganAssigned);
                        }

                        // Remove all other drivers from this shift
                        foreach ($assignedDrivers as $driverId) {
                            if ($driverId !== $keepDriverId) {
                                // Set this driver to no shift for this day
                                $schedulePlan[$driverId][$day] = self::SHIFT_NONE;
                                Log::info("Removed driver {$driverId} from over-assigned shift: Unit {$unit->unit_number} on {$currentDate} {$shift}");
                            }
                        }

                        // Update unit coverage to reflect the constraint
                        $unitCoverage[$unit->id][$currentDate][$shift] = $keepDriverId ? [$keepDriverId] : [];
                    }
                }
            }
        }

        // ENFORCE CONSTRAINT: Assign cadangan drivers to ensure EXACTLY 2 SHIFTS PER DAY
        for ($day = 0; $day < $totalDays; $day++) {
            $currentDate = $start->copy()->addDays($day)->format('Y-m-d');

            // Track which cadangan drivers are already assigned for this day
            $assignedCadangan = [];

            foreach ($units as $unit) {
                // Skip if unit is in day off
                if (isset($unitDayOffs[$currentDate]) && in_array($unit->id, $unitDayOffs[$currentDate])) {
                    continue;
                }

                // CONSTRAINT CHECK: Count current shift coverage for this unit on this day
                $currentShiftCount = 0;
                $missingShifts = [];

                foreach ([self::SHIFT_PAGI, self::SHIFT_SIANG] as $shift) {
                    $assignedDrivers = $unitCoverage[$unit->id][$currentDate][$shift];
                    if (!empty($assignedDrivers)) {
                        $currentShiftCount++;
                    } else {
                        $missingShifts[] = $shift;
                    }
                }

                // CONSTRAINT ENFORCEMENT: Must have exactly REQUIRED_SHIFTS_PER_DAY shifts
                if ($currentShiftCount < self::REQUIRED_SHIFTS_PER_DAY) {
                    Log::info("Unit {$unit->unit_number} on {$currentDate} has {$currentShiftCount} shifts (required: " . self::REQUIRED_SHIFTS_PER_DAY . "), filling gaps");

                    // Fill missing shifts to meet the constraint
                    foreach ($missingShifts as $shift) {
                        // Get cadangan drivers assigned to this unit
                        $unitCadanganDrivers = array_filter($unitDriverAssignments[$unit->id] ?? [], function($driverId) use ($driverTypes) {
                            return ($driverTypes[$driverId] ?? '') === 'cadangan';
                        });

                        // Find an available cadangan driver to meet the constraint
                        $assigned = false;
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
                                // CONSTRAINT ENFORCEMENT: Assign this cadangan driver to meet shift requirement
                                $schedulePlan[$cadanganId][$day] = $shift;
                                $unitCoverage[$unit->id][$currentDate][$shift][] = $cadanganId;
                                $assignedCadangan[$cadanganId] = true;
                                $assigned = true;
                                Log::info("Assigned cadangan driver {$cadanganId} to Unit {$unit->unit_number} on {$currentDate} {$shift} shift to meet constraint");
                                break;
                            }
                        }

                        // CONSTRAINT VIOLATION: If we couldn't assign a driver, log the constraint violation
                        if (!$assigned) {
                            Log::warning("CONSTRAINT VIOLATION: Could not assign driver to Unit {$unit->unit_number} on {$currentDate} {$shift} shift - insufficient available cadangan drivers");
                        }
                    }
                } elseif ($currentShiftCount > self::REQUIRED_SHIFTS_PER_DAY) {
                    // CONSTRAINT VIOLATION: Too many shifts scheduled
                    Log::warning("CONSTRAINT VIOLATION: Unit {$unit->unit_number} on {$currentDate} has {$currentShiftCount} shifts (max: " . self::REQUIRED_SHIFTS_PER_DAY . ")");
                }
            }
        }

        // Final constraint validation after optimization
        if ($startDate !== null && $endDate !== null && $units !== null) {
            $finalValidation = $this->validateScheduleConstraints(
                $unitCoverage, $units, $startDate, $totalDays, $unitDayOffs
            );

            if (!$finalValidation['is_valid']) {
                Log::warning('Optimized schedule still violates constraints', [
                    'total_violations' => $finalValidation['total_violations'],
                    'summary' => $finalValidation['summary']
                ]);

                // Log critical constraint violations
                foreach ($finalValidation['violations'] as $violation) {
                    if (in_array($violation['type'], ['daily_driver_count', 'daily_shift_count'])) {
                        Log::error('CRITICAL CONSTRAINT VIOLATION: ' . $violation['message'], $violation);
                    }
                }
            } else {
                Log::info('Optimized schedule meets all constraints', [
                    'total_units' => $finalValidation['summary']['total_units'],
                    'total_days' => $finalValidation['summary']['total_days']
                ]);
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

                // Skip if no shift assigned
                if ($shift === self::SHIFT_NONE) {
                    continue;
                }

                // Determine which unit this cadangan driver is assigned to on this day
                $assignedUnitId = null;
                
                // First check if we have tracked a unit assignment for this day
                if (isset($this->cadanganDriverUnitAssignments[$driverId][$day])) {
                    $assignedUnitId = $this->cadanganDriverUnitAssignments[$driverId][$day];
                } else {
                    // If no specific unit assignment is tracked, choose one from the driver's qualified units
                    $possibleUnits = $driverUnitAssignments[$driverId];
                    
                    // Filter out units that are on day-off
                    $availableUnits = array_filter($possibleUnits, function($unitId) use ($unitDayOffs, $dateStr) {
                        return !(isset($unitDayOffs[$dateStr]) && in_array($unitId, $unitDayOffs[$dateStr]));
                    });
                    
                    if (!empty($availableUnits)) {
                        // Choose the first available unit
                        $assignedUnitId = reset($availableUnits);
                    }
                }

                // Skip if no unit could be assigned
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
     * Get comprehensive validation results including priority system constraints
     *
     * @param array $unitCoverage Unit coverage tracking array
     * @param Collection $units Collection of units
     * @param Collection $batanganDrivers Collection of batangan drivers
     * @param Collection $cadanganDrivers Collection of cadangan drivers
     * @param array $driverUnitAssignments Driver-unit assignments
     * @param array $batanganDayOffs Batangan day-off assignments
     * @param string $startDate Start date in Y-m-d format
     * @param int $totalDays Total number of days
     * @param array $unitDayOffs Array of unit day offs
     * @return array Comprehensive validation results
     */
    public function validateScheduleConstraintsWithPriority(
        array $unitCoverage,
        Collection $units,
        Collection $batanganDrivers,
        Collection $cadanganDrivers,
        array $driverUnitAssignments,
        array $batanganDayOffs,
        string $startDate,
        int $totalDays,
        array $unitDayOffs = []
    ): array {
        // Basic constraint validation
        $basicValidation = $this->validateScheduleConstraints(
            $unitCoverage, $units, $startDate, $totalDays, $unitDayOffs
        );

        // Priority system validation
        $priorityViolations = $this->validatePrioritySystemConstraints(
            $unitCoverage, $units, $batanganDrivers, $cadanganDrivers,
            $driverUnitAssignments, $batanganDayOffs, $startDate, $totalDays, $unitDayOffs
        );

        // Combine all violations
        $allViolations = array_merge($basicValidation['violations'], $priorityViolations);

        return [
            'is_valid' => empty($allViolations),
            'violations' => $allViolations,
            'basic_violations' => $basicValidation['violations'],
            'priority_violations' => $priorityViolations,
            'total_violations' => count($allViolations),
            'summary' => array_merge(
                $basicValidation['summary'],
                [
                    'priority_violations_count' => count($priorityViolations),
                    'batangan_day_offs_assigned' => count($batanganDayOffs),
                    'total_batangan_drivers' => $batanganDrivers->count(),
                    'total_cadangan_drivers' => $cadanganDrivers->count()
                ]
            )
        ];
    }

    /**
     * Get constraint validation results for a given schedule plan
     *
     * @param array $schedulePlan Schedule plan to validate
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @param Collection $units Collection of units
     * @param Collection $batanganDrivers Collection of batangan drivers
     * @param Collection $cadanganDrivers Collection of cadangan drivers
     * @param array $unitDayOffs Array of unit day offs
     * @return array Validation results
     */
    public function getConstraintValidationResults(
        array $schedulePlan,
        string $startDate,
        string $endDate,
        Collection $units,
        Collection $batanganDrivers,
        Collection $cadanganDrivers,
        array $unitDayOffs = []
    ): array {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $totalDays = $start->diffInDays($end) + 1;

        // Build unit coverage from schedule plan
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
        foreach ($batanganDrivers as $driver) {
            $driverUnits = $driver->units;
            if (!$driverUnits->isEmpty()) {
                $driverUnitAssignments[$driver->id] = [$driverUnits->first()->id];
            }
        }
        foreach ($cadanganDrivers as $driver) {
            $driverUnits = $driver->units;
            if (!$driverUnits->isEmpty()) {
                $driverUnitAssignments[$driver->id] = $driverUnits->pluck('id')->toArray();
            }
        }

        // Build unit coverage from the schedule plan
        foreach ($schedulePlan as $driverId => $driverSchedule) {
            if (!isset($driverUnitAssignments[$driverId])) {
                continue;
            }

            $driverType = $driverTypes[$driverId] ?? 'unknown';

            for ($day = 0; $day < min($totalDays, count($driverSchedule)); $day++) {
                $currentDate = $start->copy()->addDays($day)->format('Y-m-d');
                $shift = $driverSchedule[$day];

                if ($shift === self::SHIFT_NONE) {
                    continue;
                }

                // For batangan drivers, assign to their specific unit
                if ($driverType === 'batangan') {
                    $unitId = $driverUnitAssignments[$driverId][0] ?? null;
                    if ($unitId !== null) {
                        if (!isset($unitDayOffs[$currentDate]) || !in_array($unitId, $unitDayOffs[$currentDate])) {
                            $unitCoverage[$unitId][$currentDate][$shift][] = $driverId;
                        }
                    }
                }
                // For cadangan drivers, find which unit they're covering
                elseif ($driverType === 'cadangan') {
                    // Try to assign to a unit that needs coverage
                    foreach ($driverUnitAssignments[$driverId] as $unitId) {
                        if (!isset($unitDayOffs[$currentDate]) || !in_array($unitId, $unitDayOffs[$currentDate])) {
                            if (empty($unitCoverage[$unitId][$currentDate][$shift])) {
                                $unitCoverage[$unitId][$currentDate][$shift][] = $driverId;
                                break;
                            }
                        }
                    }
                }
            }
        }

        // Validate constraints
        return $this->validateScheduleConstraints(
            $unitCoverage, $units, $startDate, $totalDays, $unitDayOffs
        );
    }

    /**
     * Get day-off assignments for batangan drivers in a schedule plan
     *
     * @param array $schedulePlan Schedule plan to analyze
     * @param Collection $batanganDrivers Collection of batangan drivers
     * @param int $totalDays Total number of days
     * @return array Day-off information [driver_id => [day_indices]]
     */
    public function getBatanganDayOffsFromSchedulePlan(
        array $schedulePlan,
        Collection $batanganDrivers,
        int $totalDays
    ): array {
        $dayOffs = [];

        foreach ($batanganDrivers as $driver) {
            if (!isset($schedulePlan[$driver->id])) {
                continue;
            }

            $driverSchedule = $schedulePlan[$driver->id];
            $dayOffIndices = [];

            for ($day = 0; $day < min($totalDays, count($driverSchedule)); $day++) {
                if ($driverSchedule[$day] === self::SHIFT_NONE) {
                    $dayOffIndices[] = $day;
                }
            }

            if (!empty($dayOffIndices)) {
                $dayOffs[$driver->id] = $dayOffIndices;
            }
        }

        return $dayOffs;
    }

    /**
     * Get priority system statistics for a schedule plan
     *
     * @param array $schedulePlan Schedule plan to analyze
     * @param Collection $units Collection of units
     * @param Collection $batanganDrivers Collection of batangan drivers
     * @param Collection $cadanganDrivers Collection of cadangan drivers
     * @param string $startDate Start date
     * @param int $totalDays Total number of days
     * @return array Priority system statistics
     */
    public function getPrioritySystemStatistics(
        array $schedulePlan,
        Collection $units,
        Collection $batanganDrivers,
        Collection $cadanganDrivers,
        string $startDate,
        int $totalDays
    ): array {
        $stats = [
            'total_assignments' => 0,
            'batangan_assignments' => 0,
            'cadangan_assignments' => 0,
            'day_off_coverages' => 0,
            'units_with_batangan_coverage' => 0,
            'units_with_cadangan_coverage' => 0,
            'batangan_day_offs' => [],
            'coverage_by_unit' => []
        ];

        // Get day-offs for batangan drivers
        $batanganDayOffs = $this->getBatanganDayOffsFromSchedulePlan($schedulePlan, $batanganDrivers, $totalDays);
        $stats['batangan_day_offs'] = $batanganDayOffs;

        // Get driver types
        $driverTypes = [];
        foreach ($batanganDrivers as $driver) {
            $driverTypes[$driver->id] = 'batangan';
        }
        foreach ($cadanganDrivers as $driver) {
            $driverTypes[$driver->id] = 'cadangan';
        }

        // Analyze assignments
        foreach ($schedulePlan as $driverId => $driverSchedule) {
            $driverType = $driverTypes[$driverId] ?? 'unknown';

            for ($day = 0; $day < min($totalDays, count($driverSchedule)); $day++) {
                $shift = $driverSchedule[$day];

                if ($shift !== self::SHIFT_NONE) {
                    $stats['total_assignments']++;

                    if ($driverType === 'batangan') {
                        $stats['batangan_assignments']++;
                    } elseif ($driverType === 'cadangan') {
                        $stats['cadangan_assignments']++;

                        // Check if this is covering for a batangan day-off
                        foreach ($batanganDrivers as $batanganDriver) {
                            if ($this->isBatanganDriverOnDayOff($batanganDriver->id, $day, $batanganDayOffs)) {
                                $stats['day_off_coverages']++;
                                break;
                            }
                        }
                    }
                }
            }
        }

        // Calculate coverage percentages
        if ($stats['total_assignments'] > 0) {
            $stats['batangan_coverage_percentage'] = round(($stats['batangan_assignments'] / $stats['total_assignments']) * 100, 2);
            $stats['cadangan_coverage_percentage'] = round(($stats['cadangan_assignments'] / $stats['total_assignments']) * 100, 2);
        } else {
            $stats['batangan_coverage_percentage'] = 0;
            $stats['cadangan_coverage_percentage'] = 0;
        }

        return $stats;
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
