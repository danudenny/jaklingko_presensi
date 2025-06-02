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
    const SHIFT_PAGI = 'pagi';
    const SHIFT_SIANG = 'siang';
    const SHIFT_NONE = 'none';

    const SHIFT_CODE_PAGI = 'P';
    const SHIFT_CODE_SIANG = 'S';
    const SHIFT_CODE_NONE = 'N';

    protected $cadanganDriverUnitAssignments = [];

    const REQUIRED_SHIFTS_PER_DAY = 2;
    const REQUIRED_DRIVERS_PER_DAY = 2;
    const MAX_DRIVERS_PER_SHIFT = 1;
    const MIN_DRIVERS_PER_SHIFT = 1;

    const PRIORITY_BATANGAN = 1;
    const PRIORITY_CADANGAN = 2;

    const MIN_DAYS_OFF_PER_PERIOD = 1;
    const BATANGAN_DRIVERS_PER_UNIT = 2;

    protected $defaultPatterns = [
        'batangan' => [
            [
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE,
                self::SHIFT_CODE_PAGI,
            ],
            [
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE,
                self::SHIFT_CODE_PAGI,
            ],
            [
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE,
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_PAGI,
            ],
            [
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE,
            ],
        ],
        'cadangan' => [
            [
                self::SHIFT_CODE_NONE,
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE,
            ],
            [
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE,
            ],
            [
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE,
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_SIANG,
            ],
            [
                self::SHIFT_CODE_NONE,
                self::SHIFT_CODE_NONE,
                self::SHIFT_CODE_PAGI,
                self::SHIFT_CODE_SIANG,
                self::SHIFT_CODE_NONE,
            ],
        ],
    ];

    protected $transitionRules = [
        self::SHIFT_CODE_PAGI => [self::SHIFT_CODE_PAGI, self::SHIFT_CODE_SIANG],
        self::SHIFT_CODE_SIANG => [self::SHIFT_CODE_SIANG, self::SHIFT_CODE_NONE],
        self::SHIFT_CODE_NONE => [self::SHIFT_CODE_PAGI, self::SHIFT_CODE_SIANG, self::SHIFT_CODE_NONE],
    ];

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

        $feasibilityCheck = $this->canScheduleBeGenerated(
            $units, $batanganDrivers, $cadanganDrivers, $totalDays, $unitDayOffs
        );

        if (!$feasibilityCheck['can_generate']) {
            Log::warning('Schedule generation failed feasibility check', $feasibilityCheck);
            throw new \InvalidArgumentException(
                'Cannot generate schedule: ' . implode('; ', $feasibilityCheck['issues'])
            );
        }

        if (!empty($feasibilityCheck['warnings'])) {
            foreach ($feasibilityCheck['warnings'] as $warning) {
                Log::warning('Schedule generation warning: ' . $warning);
            }
        }

        $schedulePlan = [];
        $this->cadanganDriverUnitAssignments = [];
        foreach ($cadanganDrivers as $driver) {
            $this->cadanganDriverUnitAssignments[$driver->id] = array_fill(0, $totalDays, null);
        }

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

        $batanganDayOffs = $this->generateBatanganDayOffs($batanganDrivers, $totalDays, $unitDayOffs);
        foreach ($batanganDrivers as $driver) {
            $schedulePlan[$driver->id] = array_fill(0, $totalDays, self::SHIFT_NONE);
        }
        foreach ($cadanganDrivers as $driver) {
            $schedulePlan[$driver->id] = array_fill(0, $totalDays, self::SHIFT_NONE);
        }

        $driverUnitAssignments = [];
        foreach ($batanganDrivers as $driver) {
            $driverUnits = $driver->units;

            if ($driverUnits->isEmpty()) {
                continue;
            }

            $assignedUnit = $driverUnits->first();
            $driverUnitAssignments[$driver->id] = [$assignedUnit->id];
        }

        foreach ($cadanganDrivers as $driver) {
            $driverUnits = $driver->units;

            if ($driverUnits->isEmpty()) {
                continue;
            }

            $driverUnitAssignments[$driver->id] = $driverUnits->pluck('id')->toArray();
        }

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

        $validationResults = $this->validateScheduleConstraintsWithPriority(
            $unitCoverage, $units, $batanganDrivers, $cadanganDrivers,
            $driverUnitAssignments, $batanganDayOffs, $startDate, $totalDays, $unitDayOffs
        );

        if (!$validationResults['is_valid']) {
            foreach ($validationResults['basic_violations'] as $violation) {
                Log::warning('Basic constraint violation: ' . $violation['message'], $violation);
            }

            foreach ($validationResults['priority_violations'] as $violation) {
                Log::warning('Priority system violation: ' . $violation['message'], $violation);
            }
        } else {
            Log::info('Generated schedule meets all constraints including priority system');
        }

        return $schedulePlan;
    }

    protected function generateDriverSchedulePattern(
        Driver $driver,
        string $driverType,
        int $totalDays,
        array $settings
    ): array {
        $pattern = [];
        $patternLength = 5;

        $patternTemplates = $this->defaultPatterns[$driverType] ?? $this->defaultPatterns['batangan'];
        $selectedTemplate = $patternTemplates[array_rand($patternTemplates)];

        if ($totalDays < $patternLength) {
            $validSubPattern = $this->generateValidSubPattern($selectedTemplate, $totalDays);

            for ($i = 0; $i < $totalDays; $i++) {
                $pattern[$i] = $this->shiftCodeToShift($validSubPattern[$i]);
            }
        } else {
            $fullRepeatCount = floor($totalDays / $patternLength);
            $remainder = $totalDays % $patternLength;

            for ($repeat = 0; $repeat < $fullRepeatCount; $repeat++) {
                for ($i = 0; $i < $patternLength; $i++) {
                    $dayIndex = ($repeat * $patternLength) + $i;
                    $pattern[$dayIndex] = $this->shiftCodeToShift($selectedTemplate[$i]);
                }
            }

            if ($remainder > 0) {
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

    protected function generateValidSubPattern(array $template, int $length, ?string $previousShift = null): array
    {
        $subPattern = [];

        if ($length >= count($template)) {
            return $template;
        }

        for ($startPos = 0; $startPos <= count($template) - $length; $startPos++) {
            $candidate = array_slice($template, $startPos, $length);

            $isValid = true;

            if ($previousShift !== null) {
                $validNextShifts = $this->transitionRules[$previousShift] ?? [];
                if (!in_array($candidate[0], $validNextShifts)) {
                    continue;
                }
            }
            if ($previousShift !== null) {
                $validNextShifts = $this->transitionRules[$previousShift] ?? [];
                if (!in_array($candidate[0], $validNextShifts)) {
                    $isValid = false;
                    continue;
                }
            }

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

        if (empty($subPattern)) {
            $subPattern = $this->generateManualValidPattern($length, $previousShift);
        }

        return $subPattern;
    }

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

                        if ($driverType === 'cadangan') {
                            $unitBatanganDrivers = [];
                            foreach ($batanganDrivers as $batanganDriver) {
                                if (isset($driverUnitAssignments[$batanganDriver->id]) &&
                                    in_array($unit->id, $driverUnitAssignments[$batanganDriver->id])) {
                                    $unitBatanganDrivers[] = $batanganDriver->id;
                                }
                            }

                            $batanganAvailable = false;
                            foreach ($unitBatanganDrivers as $batanganId) {
                                if ($this->isBatanganDriverOnDayOff($batanganId, $day, $batanganDayOffs)) {
                                    continue;
                                }

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

            if (!isset($summary['violation_types'][$type])) {
                $summary['violation_types'][$type] = 0;
            }
            $summary['violation_types'][$type]++;

            if (!in_array($unitId, $summary['affected_units'])) {
                $summary['affected_units'][] = $unitId;
            }
            if (!in_array($date, $summary['affected_dates'])) {
                $summary['affected_dates'][] = $date;
            }
        }

        $summary['affected_unit_count'] = count($summary['affected_units']);
        $summary['affected_date_count'] = count($summary['affected_dates']);

        return $summary;
    }

    public function canScheduleBeGenerated(
        Collection $units,
        Collection $batanganDrivers,
        Collection $cadanganDrivers,
        int $totalDays,
        array $unitDayOffs = []
    ): array {
        $issues = [];
        $warnings = [];

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

        if ($totalAvailableDrivers === 0) {
            $issues[] = "No drivers available for scheduling";
        } elseif ($totalDriverSlotsNeeded > ($totalAvailableDrivers * $totalDays)) {
            $issues[] = "Insufficient drivers: need {$totalDriverSlotsNeeded} driver-slots but only have {$totalAvailableDrivers} drivers for {$totalDays} days";
        }

        foreach ($units as $unit) {
            $assignedDrivers = $unit->drivers;
            if ($assignedDrivers->isEmpty()) {
                $issues[] = "Unit {$unit->unit_number} has no drivers assigned";
                continue;
            }

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

    protected function generateBatanganDayOffs(
        Collection $batanganDrivers,
        int $totalDays,
        array $unitDayOffs = []
    ): array {
        $dayOffAssignments = [];
        $unitDriverMap = [];
        
        foreach ($batanganDrivers as $driver) {
            $driverUnits = $driver->units;
            if ($driverUnits->isEmpty()) {
                continue;
            }
            
            $primaryUnit = $driverUnits->first();
            $unitId = $primaryUnit->id;
            
            if (!isset($unitDriverMap[$unitId])) {
                $unitDriverMap[$unitId] = [];
            }
            
            $unitDriverMap[$unitId][] = $driver->id;
        }
        
        foreach ($unitDriverMap as $unitId => $driverIds) {
            if (empty($driverIds)) {
                continue;
            }
            $driverId = $driverIds[0];
            
            $availableDays = [];
            for ($day = 0; $day < $totalDays; $day++) {
                $currentDate = Carbon::now()->addDays($day)->format('Y-m-d');
                
                if (isset($unitDayOffs[$currentDate]) && in_array($unitId, $unitDayOffs[$currentDate])) {
                    continue;
                }
                
                $availableDays[] = $day;
            }
            
            $daysOffNeeded = min(self::MIN_DAYS_OFF_PER_PERIOD, count($availableDays));
            
            if ($daysOffNeeded > 0) {
                $dayOffIndices = [];
                $interval = max(1, floor(count($availableDays) / $daysOffNeeded));
                
                for ($i = 0; $i < $daysOffNeeded; $i++) {
                    $dayIndex = $availableDays[min($i * $interval, count($availableDays) - 1)];
                    $dayOffIndices[] = $dayIndex;
                }
                
                $dayOffAssignments[$driverId] = $dayOffIndices;                
            }
        }
        
        return $dayOffAssignments;
    }

    protected function isBatanganDriverOnDayOff(int $driverId, int $dayIndex, array $batanganDayOffs): bool
    {
        return isset($batanganDayOffs[$driverId]) && in_array($dayIndex, $batanganDayOffs[$driverId]);
    }

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
        
        // Count assigned days for each driver type to ensure balanced distribution
        $batanganAssignmentCounts = [];
        $cadanganAssignmentCounts = [];
        
        // Calculate current assignment counts for all drivers
        foreach ($batanganDrivers as $driver) {
            $batanganAssignmentCounts[$driver->id] = 0;
            if (isset($schedulePlan[$driver->id])) {
                foreach ($schedulePlan[$driver->id] as $day => $assignedShift) {
                    if ($assignedShift !== self::SHIFT_NONE) {
                        $batanganAssignmentCounts[$driver->id]++;
                    }
                }
            }
        }
        
        foreach ($cadanganDrivers as $driver) {
            $cadanganAssignmentCounts[$driver->id] = 0;
            if (isset($schedulePlan[$driver->id])) {
                foreach ($schedulePlan[$driver->id] as $day => $assignedShift) {
                    if ($assignedShift !== self::SHIFT_NONE) {
                        $cadanganAssignmentCounts[$driver->id]++;
                    }
                }
            }
        }
        
        // Calculate the average assignment counts for both driver types
        $avgBatanganAssignments = count($batanganAssignmentCounts) > 0 ? 
            array_sum($batanganAssignmentCounts) / count($batanganAssignmentCounts) : 0;
        $avgCadanganAssignments = count($cadanganAssignmentCounts) > 0 ? 
            array_sum($cadanganAssignmentCounts) / count($cadanganAssignmentCounts) : 0;
        
        // Target ratio: batangan drivers should get approximately 30% more assignments than cadangan
        $targetBatanganToCadanganRatio = 1.3;
        $currentRatio = $avgBatanganAssignments > 0 && $avgCadanganAssignments > 0 ? 
            $avgBatanganAssignments / $avgCadanganAssignments : $targetBatanganToCadanganRatio;
        
        // Adjust priorities based on the current ratio vs target ratio
        $batanganPriorityMultiplier = $currentRatio < $targetBatanganToCadanganRatio ? 0.7 : 1.0;
        $cadanganPriorityMultiplier = $currentRatio > $targetBatanganToCadanganRatio ? 0.7 : 1.0;

        // Process batangan drivers
        foreach ($batanganDrivers as $driver) {
            if (!isset($driverUnitAssignments[$driver->id]) ||
                !in_array($unitId, $driverUnitAssignments[$driver->id])) {
                continue;
            }

            if ($this->isBatanganDriverOnDayOff($driver->id, $dayIndex, $batanganDayOffs)) {
                Log::info("Batangan driver {$driver->id} is on day-off for day {$dayIndex}, skipping");
                continue;
            }

            if (isset($schedulePlan[$driver->id][$dayIndex]) &&
                $schedulePlan[$driver->id][$dayIndex] !== self::SHIFT_NONE) {
                continue;
            }
            
            // Adjust priority based on current assignment count
            // Lower priority (better) for drivers with fewer assignments
            $assignmentCount = $batanganAssignmentCounts[$driver->id] ?? 0;
            $assignmentFactor = max(0.5, 1 - ($assignmentCount / 15)); // Normalize to a factor between 0.5 and 1
            
            $adjustedPriority = self::PRIORITY_BATANGAN * $batanganPriorityMultiplier * $assignmentFactor;

            $priorityDrivers[] = [
                'driver_id' => $driver->id,
                'type' => 'batangan',
                'priority' => $adjustedPriority,
                'unit_id' => $unitId,
                'assignments' => $assignmentCount
            ];
        }

        // Process cadangan drivers
        foreach ($cadanganDrivers as $driver) {
            if (!isset($driverUnitAssignments[$driver->id]) ||
                !in_array($unitId, $driverUnitAssignments[$driver->id])) {
                continue;
            }

            if (isset($schedulePlan[$driver->id][$dayIndex]) &&
                $schedulePlan[$driver->id][$dayIndex] !== self::SHIFT_NONE) {
                continue;
            }
            
            $assignmentsToThisUnit = 0;
            $totalAssignments = $cadanganAssignmentCounts[$driver->id] ?? 0;
            
            if (isset($this->cadanganDriverUnitAssignments[$driver->id])) {
                foreach ($this->cadanganDriverUnitAssignments[$driver->id] as $day => $assignedUnitId) {
                    if ($assignedUnitId !== null && $assignedUnitId === $unitId) {
                        $assignmentsToThisUnit++;
                    }
                }
            }
            
            // Base priority for cadangan drivers
            $adjustedPriority = self::PRIORITY_CADANGAN * $cadanganPriorityMultiplier;
            
            // Adjust based on total assignments (higher assignments = higher priority number = lower actual priority)
            $adjustedPriority += ($totalAssignments * 0.2);
            
            // Adjust based on unit consistency
            if ($totalAssignments > 0) {
                $unitRatio = $assignmentsToThisUnit / $totalAssignments;
                $adjustedPriority -= (1 - $unitRatio) * 0.5; // Favor consistent unit assignments
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

        for ($day = 0; $day < $totalDays; $day++) {
            $currentDate = $start->copy()->addDays($day)->format('Y-m-d');

            foreach ($units as $unit) {
                if (isset($unitDayOffs[$currentDate]) && in_array($unit->id, $unitDayOffs[$currentDate])) {
                    continue;
                }
                
                // Check if both shifts need to be filled for this unit and day
                $pagiEmpty = empty($unitCoverage[$unit->id][$currentDate][self::SHIFT_PAGI]);
                $siangEmpty = empty($unitCoverage[$unit->id][$currentDate][self::SHIFT_SIANG]);
                
                // Skip if both shifts are already filled
                if (!$pagiEmpty && !$siangEmpty) {
                    continue;
                }
                
                // If one shift is filled but the other isn't, we need to prioritize filling the empty one
                // to ensure paired shifts
                if (!$pagiEmpty && $siangEmpty) {
                    $this->assignShiftForUnit($unit, $currentDate, self::SHIFT_SIANG, $day, $batanganDrivers, $cadanganDrivers, 
                        $driverUnitAssignments, $batanganDayOffs, $schedulePlan, $unitCoverage, $assignmentLog);
                } else if ($pagiEmpty && !$siangEmpty) {
                    $this->assignShiftForUnit($unit, $currentDate, self::SHIFT_PAGI, $day, $batanganDrivers, $cadanganDrivers, 
                        $driverUnitAssignments, $batanganDayOffs, $schedulePlan, $unitCoverage, $assignmentLog);
                } else {
                    // Both shifts are empty, try to assign both shifts together
                    // First try to find a pair of drivers (one for each shift) that can be assigned together
                    $pagiAssigned = $this->assignShiftForUnit($unit, $currentDate, self::SHIFT_PAGI, $day, $batanganDrivers, $cadanganDrivers, 
                        $driverUnitAssignments, $batanganDayOffs, $schedulePlan, $unitCoverage, $assignmentLog);
                    
                    if ($pagiAssigned) {
                        // If pagi was assigned, now try to assign siang
                        $siangAssigned = $this->assignShiftForUnit($unit, $currentDate, self::SHIFT_SIANG, $day, $batanganDrivers, $cadanganDrivers, 
                            $driverUnitAssignments, $batanganDayOffs, $schedulePlan, $unitCoverage, $assignmentLog);
                        
                        // If we couldn't assign siang, we should consider unassigning pagi to maintain paired shifts
                        if (!$siangAssigned && !empty($unitCoverage[$unit->id][$currentDate][self::SHIFT_PAGI])) {
                            $pagiDriverId = $unitCoverage[$unit->id][$currentDate][self::SHIFT_PAGI][0];
                            $pagiDriverType = $this->getDriverTypeById($pagiDriverId, $batanganDrivers, $cadanganDrivers);
                            
                            // Only unassign if this is not a critical assignment
                            if ($pagiDriverType !== 'critical') {
                                // Unassign the pagi driver
                                $schedulePlan[$pagiDriverId][$day] = self::SHIFT_NONE;
                                $unitCoverage[$unit->id][$currentDate][self::SHIFT_PAGI] = [];
                                
                                if ($pagiDriverType === 'cadangan') {
                                    $this->cadanganDriverUnitAssignments[$pagiDriverId][$day] = null;
                                }
                                
                                $logMessage = "UNASSIGNED: {$pagiDriverType} driver {$pagiDriverId} unassigned from Unit {$unit->unit_number} on {$currentDate} pagi shift to maintain paired shifts";
                                Log::info($logMessage);
                                $assignmentLog[] = $logMessage;
                            }
                        }
                    }
                }
            }
        }

        return $assignmentLog;
    }
    
    /**
     * Helper method to assign a driver to a specific shift
     */
    protected function assignShiftForUnit(
        $unit, 
        $currentDate, 
        $shift, 
        $day, 
        $batanganDrivers, 
        $cadanganDrivers, 
        $driverUnitAssignments, 
        $batanganDayOffs, 
        &$schedulePlan, 
        &$unitCoverage, 
        &$assignmentLog
    ): bool {
        if (!empty($unitCoverage[$unit->id][$currentDate][$shift])) {
            return true; // Already assigned
        }
        
        // Calculate current assignment counts for batangan and cadangan drivers
        $batanganAssignmentCounts = [];
        $cadanganAssignmentCounts = [];
        
        foreach ($batanganDrivers as $driver) {
            $batanganAssignmentCounts[$driver->id] = 0;
            if (isset($schedulePlan[$driver->id])) {
                foreach ($schedulePlan[$driver->id] as $dayShift) {
                    if ($dayShift !== self::SHIFT_NONE) {
                        $batanganAssignmentCounts[$driver->id]++;
                    }
                }
            }
        }
        
        foreach ($cadanganDrivers as $driver) {
            $cadanganAssignmentCounts[$driver->id] = 0;
            if (isset($schedulePlan[$driver->id])) {
                foreach ($schedulePlan[$driver->id] as $dayShift) {
                    if ($dayShift !== self::SHIFT_NONE) {
                        $cadanganAssignmentCounts[$driver->id]++;
                    }
                }
            }
        }
        
        // Find the minimum assignment count for batangan drivers
        $minBatanganAssignments = !empty($batanganAssignmentCounts) ? min($batanganAssignmentCounts) : 0;
        $maxCadanganAssignments = !empty($cadanganAssignmentCounts) ? max($cadanganAssignmentCounts) : 0;
        
        // If the minimum batangan assignment count is less than the maximum cadangan assignment count,
        // prioritize batangan drivers even more
        $forceBatanganPriority = $minBatanganAssignments < $maxCadanganAssignments;
        
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
        
        // If we need to force batangan priority, filter out cadangan drivers if there are valid batangan drivers
        if ($forceBatanganPriority) {
            $batanganPriorityDrivers = array_filter($priorityDrivers, function($driver) {
                return $driver['type'] === 'batangan';
            });
            
            // Only use batangan drivers if there are any available
            if (!empty($batanganPriorityDrivers)) {
                $priorityDrivers = $batanganPriorityDrivers;
            }
        }

        foreach ($priorityDrivers as $driverInfo) {
            $driverId = $driverInfo['driver_id'];
            $driverType = $driverInfo['type'];
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
                $schedulePlan[$driverId][$day] = $shift;
                $unitCoverage[$unit->id][$currentDate][$shift][] = $driverId;
                
                if ($driverType === 'cadangan') {
                    $this->cadanganDriverUnitAssignments[$driverId][$day] = $unit->id;
                }

                $logMessage = "PRIORITY ASSIGNMENT: {$driverType} driver {$driverId} assigned to Unit {$unit->unit_number} on {$currentDate} {$shift} shift";

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
                return true;
            }
        }

        if (empty($unitCoverage[$unit->id][$currentDate][$shift])) {
            $gapMessage = "COVERAGE GAP: No available driver for Unit {$unit->unit_number} on {$currentDate} {$shift} shift";
            $assignmentLog[] = $gapMessage;
            return false;
        }
        
        return false;
    }
    
    /**
     * Helper method to get driver type by ID
     */
    protected function getDriverTypeById($driverId, $batanganDrivers, $cadanganDrivers) {
        foreach ($batanganDrivers as $driver) {
            if ($driver->id === $driverId) {
                return 'batangan';
            }
        }
        
        foreach ($cadanganDrivers as $driver) {
            if ($driver->id === $driverId) {
                return 'cadangan';
            }
        }
        
        return 'unknown';
    }

    public function optimizeSchedulePlan(
        array $schedulePlan,
        string $startDate = null,
        string $endDate = null,
        Collection $units = null,
        Collection $batanganDrivers = null,
        Collection $cadanganDrivers = null,
        array $unitDayOffs = []
    ): array {
        if ($startDate === null || $endDate === null || $units === null) {
            foreach ($schedulePlan as $driverId => $driverSchedule) {
                for ($day = 1; $day < count($driverSchedule); $day++) {
                    $previousShift = $this->shiftToShiftCode($driverSchedule[$day - 1]);
                    $currentShift = $this->shiftToShiftCode($driverSchedule[$day]);

                    $validNextShifts = $this->transitionRules[$previousShift] ?? [];

                    if (!in_array($currentShift, $validNextShifts)) {
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

        $driverTypes = [];
        foreach ($batanganDrivers as $driver) {
            $driverTypes[$driver->id] = 'batangan';
        }
        foreach ($cadanganDrivers as $driver) {
            $driverTypes[$driver->id] = 'cadangan';
        }

        $driverUnitAssignments = [];
        $unitDriverAssignments = [];

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
                if ($driverType === 'batangan') {
                    $unitId = $driverUnitAssignments[$driverId][0] ?? null;

                    if ($unitId !== null) {
                        if (isset($unitDayOffs[$currentDate]) && in_array($unitId, $unitDayOffs[$currentDate])) {
                            $schedulePlan[$driverId][$day] = self::SHIFT_NONE;
                            continue;
                        }

                        $unitCoverage[$unitId][$currentDate][$shift][] = $driverId;
                    }
                }
                else if ($driverType === 'cadangan') {
                    $assignedUnitId = $this->cadanganDriverUnitAssignments[$driverId][$day] ?? null;
                    
                    if ($assignedUnitId !== null && isset($unitCoverage[$assignedUnitId][$currentDate])) {
                        if (isset($unitDayOffs[$currentDate]) && in_array($assignedUnitId, $unitDayOffs[$currentDate])) {
                            $schedulePlan[$driverId][$day] = self::SHIFT_NONE;
                            continue;
                        }
                        
                        $unitCoverage[$assignedUnitId][$currentDate][$shift][] = $driverId;
                    } else {
                        $possibleUnits = $driverUnitAssignments[$driverId] ?? [];
                        foreach ($possibleUnits as $possibleUnitId) {
                            if (isset($unitDayOffs[$currentDate]) && in_array($possibleUnitId, $unitDayOffs[$currentDate])) {
                                continue;
                            }
                            
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

        foreach ($units as $unit) {
            for ($day = 0; $day < $totalDays; $day++) {
                $currentDate = $start->copy()->addDays($day)->format('Y-m-d');

                if (isset($unitDayOffs[$currentDate]) && in_array($unit->id, $unitDayOffs[$currentDate])) {
                    continue;
                }

                foreach ([self::SHIFT_PAGI, self::SHIFT_SIANG] as $shift) {
                    $assignedDrivers = $unitCoverage[$unit->id][$currentDate][$shift];

                    if (count($assignedDrivers) > self::MAX_DRIVERS_PER_SHIFT) {
                        $batanganAssigned = array_filter($assignedDrivers, function($driverId) use ($driverTypes) {
                            return ($driverTypes[$driverId] ?? '') === 'batangan';
                        });

                        $cadanganAssigned = array_filter($assignedDrivers, function($driverId) use ($driverTypes) {
                            return ($driverTypes[$driverId] ?? '') === 'cadangan';
                        });

                        $keepDriverId = null;
                        if (!empty($batanganAssigned)) {
                            $keepDriverId = reset($batanganAssigned);
                        } elseif (!empty($cadanganAssigned)) {
                            $keepDriverId = reset($cadanganAssigned);
                        }

                        foreach ($assignedDrivers as $driverId) {
                            if ($driverId !== $keepDriverId) {
                                $schedulePlan[$driverId][$day] = self::SHIFT_NONE;
                            }
                        }

                        $unitCoverage[$unit->id][$currentDate][$shift] = $keepDriverId ? [$keepDriverId] : [];
                    }
                }
            }
        }

        for ($day = 0; $day < $totalDays; $day++) {
            $currentDate = $start->copy()->addDays($day)->format('Y-m-d');

            $assignedCadangan = [];

            foreach ($units as $unit) {
                if (isset($unitDayOffs[$currentDate]) && in_array($unit->id, $unitDayOffs[$currentDate])) {
                    continue;
                }
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

                if ($currentShiftCount < self::REQUIRED_SHIFTS_PER_DAY) {
                    foreach ($missingShifts as $shift) {
                        $unitCadanganDrivers = array_filter($unitDriverAssignments[$unit->id] ?? [], function($driverId) use ($driverTypes) {
                            return ($driverTypes[$driverId] ?? '') === 'cadangan';
                        });
                        $assigned = false;
                        foreach ($unitCadanganDrivers as $cadanganId) {
                            if (isset($assignedCadangan[$cadanganId])) {
                                continue;
                            }
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
                                $schedulePlan[$cadanganId][$day] = $shift;
                                $unitCoverage[$unit->id][$currentDate][$shift][] = $cadanganId;
                                $assignedCadangan[$cadanganId] = true;
                                $assigned = true;
                                break;
                            }
                        }

                        if (!$assigned) {
                            Log::warning("CONSTRAINT VIOLATION: Could not assign driver to Unit {$unit->unit_number} on {$currentDate} {$shift} shift - insufficient available cadangan drivers");
                        }
                    }
                } elseif ($currentShiftCount > self::REQUIRED_SHIFTS_PER_DAY) {
                    Log::warning("CONSTRAINT VIOLATION: Unit {$unit->unit_number} on {$currentDate} has {$currentShiftCount} shifts (max: " . self::REQUIRED_SHIFTS_PER_DAY . ")");
                }
            }
        }

        if ($startDate !== null && $endDate !== null && $units !== null) {
            $finalValidation = $this->validateScheduleConstraints(
                $unitCoverage, $units, $startDate, $totalDays, $unitDayOffs
            );

            if (!$finalValidation['is_valid']) {
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

    public function createSchedulesFromPlan(
        array $schedulePlan,
        string $startDate = null,
        string $endDate = null,
        Collection $units = null,
        Collection $batanganDrivers = null,
        Collection $cadanganDrivers = null,
        array $unitDayOffs = []
    ): array {
        if (!$startDate || !$endDate) {
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

        $driverTypes = [];
        if ($batanganDrivers !== null && $cadanganDrivers !== null) {
            foreach ($batanganDrivers as $driver) {
                $driverTypes[$driver->id] = 'batangan';
            }
            foreach ($cadanganDrivers as $driver) {
                $driverTypes[$driver->id] = 'cadangan';
            }
        }

        $driverUnitAssignments = [];

        foreach ($schedulePlan as $driverId => $driverSchedule) {
            $driver = Driver::find($driverId);

            if (!$driver) {
                $messages[] = "Driver with ID {$driverId} not found, skipping";
                $failedCount++;
                continue;
            }

            $driverUnits = $driver->units;

            if ($driverUnits->isEmpty()) {
                $messages[] = "Driver {$driver->name} has no units assigned, skipping";
                $failedCount++;
                continue;
            }

            if (isset($driverTypes[$driverId]) && $driverTypes[$driverId] === 'batangan') {
                $driverUnitAssignments[$driverId] = [$driverUnits->first()->id];
            }
            else {
                $driverUnitAssignments[$driverId] = $driverUnits->pluck('id')->toArray();
            }
        }

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

        foreach ($schedulePlan as $driverId => $driverSchedule) {
            if (!isset($driverTypes[$driverId]) || $driverTypes[$driverId] !== 'batangan' || !isset($driverUnitAssignments[$driverId])) {
                continue;
            }

            $driver = Driver::find($driverId);
            if (!$driver) continue;

            $unitId = $driverUnitAssignments[$driverId][0];
            $unit = Unit::find($unitId);
            if (!$unit) continue;
            $routes = $unit->routes;
            if ($routes->isEmpty()) {
                $messages[] = "Unit {$unit->unit_number} has no routes assigned, skipping";
                continue;
            }

            $route = $routes->first();

            for ($day = 0; $day < min($totalDays, count($driverSchedule)); $day++) {
                $currentDate = $start->copy()->addDays($day);
                $dateStr = $currentDate->format('Y-m-d');
                $shift = $driverSchedule[$day];
                if ($shift === self::SHIFT_NONE ||
                    (isset($unitDayOffs[$dateStr]) && in_array($unitId, $unitDayOffs[$dateStr]))) {
                    continue;
                }

                $unitCoverage[$unitId][$dateStr][$shift][] = $driverId;

                $existingSchedule = Schedule::where('driver_id', $driverId)
                    ->where('schedule_date', $dateStr)
                    ->first();

                if ($existingSchedule) {
                    $existingSchedule->shift = $shift;
                    $existingSchedule->route_id = $route->id;
                    $existingSchedule->unit_id = $unitId;
                    $existingSchedule->save();

                    $successCount++;
                } else {
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

        foreach ($schedulePlan as $driverId => $driverSchedule) {
            if (!isset($driverTypes[$driverId]) || $driverTypes[$driverId] !== 'cadangan' || !isset($driverUnitAssignments[$driverId])) {
                continue;
            }

            $driver = Driver::find($driverId);
            if (!$driver) continue;

            for ($day = 0; $day < min($totalDays, count($driverSchedule)); $day++) {
                $currentDate = $start->copy()->addDays($day);
                $dateStr = $currentDate->format('Y-m-d');
                $shift = $driverSchedule[$day];

                if ($shift === self::SHIFT_NONE) {
                    continue;
                }
                $assignedUnitId = null;
                
                if (isset($this->cadanganDriverUnitAssignments[$driverId][$day])) {
                    $assignedUnitId = $this->cadanganDriverUnitAssignments[$driverId][$day];
                } else {
                    $possibleUnits = $driverUnitAssignments[$driverId];
                    
                    $availableUnits = array_filter($possibleUnits, function($unitId) use ($unitDayOffs, $dateStr) {
                        return !(isset($unitDayOffs[$dateStr]) && in_array($unitId, $unitDayOffs[$dateStr]));
                    });
                    
                    if (!empty($availableUnits)) {
                        $assignedUnitId = reset($availableUnits);
                    }
                }

                if ($assignedUnitId === null) {
                    continue;
                }

                $unit = Unit::find($assignedUnitId);
                if (!$unit) continue;

                $routes = $unit->routes;
                if ($routes->isEmpty()) {
                    continue;
                }

                $route = $routes->first();
                $unitCoverage[$assignedUnitId][$dateStr][$shift][] = $driverId;
                $existingSchedule = Schedule::where('driver_id', $driverId)
                    ->where('schedule_date', $dateStr)
                    ->first();

                if ($existingSchedule) {
                    $existingSchedule->shift = $shift;
                    $existingSchedule->route_id = $route->id;
                    $existingSchedule->unit_id = $assignedUnitId;
                    $existingSchedule->save();

                    $successCount++;
                } else {
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

    public function isValidTransition(string $currentShift, string $nextShift): bool
    {
        $currentShiftCode = $this->shiftToShiftCode($currentShift);
        $nextShiftCode = $this->shiftToShiftCode($nextShift);
        $validNextShifts = $this->transitionRules[$currentShiftCode] ?? [];
        return in_array($nextShiftCode, $validNextShifts);
    }

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
        $basicValidation = $this->validateScheduleConstraints(
            $unitCoverage, $units, $startDate, $totalDays, $unitDayOffs
        );

        $priorityViolations = $this->validatePrioritySystemConstraints(
            $unitCoverage, $units, $batanganDrivers, $cadanganDrivers,
            $driverUnitAssignments, $batanganDayOffs, $startDate, $totalDays, $unitDayOffs
        );

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

        $driverTypes = [];
        foreach ($batanganDrivers as $driver) {
            $driverTypes[$driver->id] = 'batangan';
        }
        foreach ($cadanganDrivers as $driver) {
            $driverTypes[$driver->id] = 'cadangan';
        }

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

                if ($driverType === 'batangan') {
                    $unitId = $driverUnitAssignments[$driverId][0] ?? null;
                    if ($unitId !== null) {
                        if (!isset($unitDayOffs[$currentDate]) || !in_array($unitId, $unitDayOffs[$currentDate])) {
                            $unitCoverage[$unitId][$currentDate][$shift][] = $driverId;
                        }
                    }
                }
                elseif ($driverType === 'cadangan') {
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

        return $this->validateScheduleConstraints(
            $unitCoverage, $units, $startDate, $totalDays, $unitDayOffs
        );
    }

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

        $batanganDayOffs = $this->getBatanganDayOffsFromSchedulePlan($schedulePlan, $batanganDrivers, $totalDays);
        $stats['batangan_day_offs'] = $batanganDayOffs;

        $driverTypes = [];
        foreach ($batanganDrivers as $driver) {
            $driverTypes[$driver->id] = 'batangan';
        }
        foreach ($cadanganDrivers as $driver) {
            $driverTypes[$driver->id] = 'cadangan';
        }

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

        if ($stats['total_assignments'] > 0) {
            $stats['batangan_coverage_percentage'] = round(($stats['batangan_assignments'] / $stats['total_assignments']) * 100, 2);
            $stats['cadangan_coverage_percentage'] = round(($stats['cadangan_assignments'] / $stats['total_assignments']) * 100, 2);
        } else {
            $stats['batangan_coverage_percentage'] = 0;
            $stats['cadangan_coverage_percentage'] = 0;
        }

        return $stats;
    }

}
