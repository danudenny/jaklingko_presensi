<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\Unit;
use App\Models\UnitRenops;
use App\Models\RenopsSettings;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UnitRenopsController extends Controller
{
    /**
     * Display a listing of the renops plans.
     */
    public function index(Request $request): View
    {
        $date = $request->input('date', now()->format('Y-m-d'));
        $selectedDate = Carbon::parse($date);

        $dayOfWeek = $selectedDate->dayOfWeek;
        $dayType = null;
        $holiday = null;

        // Check if the selected date is a weekend or holiday
        if ($dayOfWeek == Carbon::SATURDAY) {
            $dayType = 'saturday';
        } elseif ($dayOfWeek == Carbon::SUNDAY) {
            $dayType = 'sunday';
        } else {
            // Check if it's a holiday
            $holiday = Holiday::whereDate('date', $selectedDate)->first();
            if ($holiday) {
                $dayType = 'holiday';
            }
        }

        // If not a weekend or holiday, redirect with error
        if (!$dayType) {
            return view('modules.admin.renops.index', [
                'date' => $selectedDate,
                'units' => collect(),
                'dayType' => null,
                'holiday' => null,
                'error' => 'Tanggal yang dipilih bukan hari libur.'
            ]);
        }

        // Get all active units with is_pool = true
        $units = Unit::active()->where('is_pool', true)->get();

        // Get units already in renops for this date
        $renopsUnits = UnitRenops::where('date', $selectedDate)
            ->pluck('unit_id')
            ->toArray();
            
        // Get the current settings
        $settings = RenopsSettings::getCurrentSettings();
        $maxLimit = $this->getMaxLimit($dayType);
        
        // Check if we're in automatic mode and there are no renops units yet
        $autoSuggestion = null;
        if ($settings->isAutomatic() && empty($renopsUnits)) {
            // Apply unit type filter for automatic selection
            $filteredUnits = $units;
            if ($settings->unit_type === 'pool') {
                $filteredUnits = $units->where('is_pool', true);
            }
            
            // Get random units up to the threshold limit for automatic selection
            $autoSuggestion = $filteredUnits->random(min($maxLimit, $filteredUnits->count()))
                ->pluck('id')
                ->toArray();
        }

        return view('modules.admin.renops.index', [
            'date' => $selectedDate,
            'units' => $units,
            'renopsUnits' => $renopsUnits,
            'dayType' => $dayType,
            'holiday' => $holiday,
            'maxLimit' => $maxLimit,
            'currentCount' => count($renopsUnits),
            'settings' => $settings,
            'autoSuggestion' => $autoSuggestion
        ]);
    }

    /**
     * Show the form for creating a new renops plan.
     */
    public function create(): View
    {
        $units = Unit::active()->get();
        $holidays = Holiday::whereDate('date', '>=', now())->orderBy('date')->get();

        // Get upcoming weekends
        $weekends = collect();
        $startDate = now();
        $endDate = now()->addMonths(3); // Show weekends for the next 3 months

        $period = CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $date) {
            if ($date->isWeekend()) {
                $weekends->push([
                    'date' => $date->format('Y-m-d'),
                    'name' => $date->format('l, F j, Y'),
                    'type' => $date->dayOfWeek === Carbon::SATURDAY ? 'saturday' : 'sunday'
                ]);
            }
        }

        return view('modules.admin.renops.create', [
            'units' => $units,
            'holidays' => $holidays,
            'weekends' => $weekends
        ]);
    }

    /**
     * Store a newly created renops plan in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'unit_ids' => 'required|array',
            'unit_ids.*' => 'exists:units,id',
            'day_type' => 'required|in:saturday,sunday,holiday',
            'holiday_id' => 'required_if:day_type,holiday|nullable|exists:holidays,id',
        ]);

        $date = Carbon::parse($validated['date']);
        $dayType = $validated['day_type'];
        $holidayId = $validated['holiday_id'] ?? null;

        // Verify the day type matches the actual date
        if ($dayType === 'saturday' && $date->dayOfWeek !== Carbon::SATURDAY) {
            return response()->json([
                'success' => false,
                'message' => 'Selected date is not a Saturday.'
            ], 422);
        }

        if ($dayType === 'sunday' && $date->dayOfWeek !== Carbon::SUNDAY) {
            return response()->json([
                'success' => false,
                'message' => 'Selected date is not a Sunday.'
            ], 422);
        }

        if ($dayType === 'holiday') {
            $holiday = Holiday::find($holidayId);
            if (!$holiday || !$date->isSameDay($holiday->date)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected date does not match the holiday date.'
                ], 422);
            }
        }

        // Check if we're exceeding the maximum limit
        $maxLimit = $this->getMaxLimit($dayType);
        $currentCount = UnitRenops::where('date', $date)->count();
        $newUnitsCount = count($validated['unit_ids']);

        if ($currentCount + $newUnitsCount > $maxLimit) {
            return response()->json([
                'success' => false,
                'message' => "Cannot add more units. Maximum limit of {$maxLimit} units would be exceeded."
            ], 422);
        }

        // Begin transaction
        DB::beginTransaction();

        try {
            // Create renops entries for each unit
            foreach ($validated['unit_ids'] as $unitId) {
                UnitRenops::updateOrCreate(
                    ['date' => $date, 'unit_id' => $unitId],
                    [
                        'day_type' => $dayType,
                        'holiday_id' => $holidayId
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Renops plan created successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create renops plan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified renops plan in storage.
     */
    public function update(Request $request, string $date): JsonResponse
    {
        $validated = $request->validate([
            'units' => 'required|array',
            'units.*' => 'exists:units,id',
            'day_type' => 'required|in:saturday,sunday,holiday',
            'holiday_id' => 'nullable|exists:holidays,id',
        ]);

        $selectedDate = Carbon::parse($date);
        $dayType = $validated['day_type'];
        $holidayId = $validated['holiday_id'] ?? null;
        $unitIds = $validated['units'];

        // Get the settings and check if we're in automatic mode
        $settings = RenopsSettings::getCurrentSettings();
        
        // Get the maximum limit for this day type
        $maxLimit = $this->getMaxLimit($dayType);

        // If we're in automatic mode and no units were provided, automatically select units
        if ($settings->isAutomatic() && empty($unitIds)) {
            // Filter units by is_pool if unit_type is set to 'pool'
            $query = Unit::active();
            if ($settings->unit_type === 'pool') {
                $query->where('is_pool', true);
            }
            $availableUnits = $query->get();
            $unitIds = $availableUnits->random(min($maxLimit, $availableUnits->count()))
                ->pluck('id')
                ->toArray();
        }

        // Check if the number of units exceeds the maximum limit
        if (count($unitIds) > $maxLimit) {
            return response()->json([
                'success' => false,
                'message' => "Jumlah unit melebihi batas maksimum untuk hari ini ({$maxLimit} unit)."
            ], 422);
        }

        // Start a database transaction
        DB::beginTransaction();

        try {
            // Get existing renops entries for this date
            $existingRenops = UnitRenops::where('date', $selectedDate)->get();
            $existingUnitIds = $existingRenops->pluck('unit_id')->toArray();

            // Units to add (in new request but not in existing)
            $unitsToAdd = array_diff($unitIds, $existingUnitIds);

            // Units to remove (in existing but not in new request)
            $unitsToRemove = array_diff($existingUnitIds, $unitIds);

            // Log the changes
            $logMessage = "Renops update for {$selectedDate->format('Y-m-d')} ({$dayType}):\n";
            $logMessage .= "- Units to add: " . implode(', ', $unitsToAdd) . "\n";
            $logMessage .= "- Units to remove: " . implode(', ', $unitsToRemove) . "\n";
            $logMessage .= "- Mode: " . ($settings->isAutomatic() ? 'Automatic' : 'Manual');
            
            \Log::info($logMessage);

            // Add new units to renops
            foreach ($unitsToAdd as $unitId) {
                UnitRenops::create([
                    'date' => $selectedDate,
                    'unit_id' => $unitId,
                    'day_type' => $dayType,
                    'holiday_id' => $holidayId,
                ]);
            }

            // Remove units from renops
            UnitRenops::where('date', $selectedDate)
                ->whereIn('unit_id', $unitsToRemove)
                ->delete();

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Rencana operasi berhasil diperbarui.',
                'added' => count($unitsToAdd),
                'removed' => count($unitsToRemove),
                'total' => count($unitIds),
                'automatic' => $settings->isAutomatic()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update renops plan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified renops plan from storage.
     */
    public function destroy(string $date): JsonResponse
    {
        $parsedDate = Carbon::parse($date);

        // Begin transaction
        DB::beginTransaction();
        
        try {
            // Get all units affected by this deletion for logging
            $affectedUnits = UnitRenops::where('date', $parsedDate)->get();
            $unitCount = $affectedUnits->count();
            
            if ($unitCount > 0) {
                // Log the units being removed from renops
                $unitNumbers = $affectedUnits->map(function($renops) {
                    $unit = Unit::find($renops->unit_id);
                    return $unit ? $unit->unit_number : "Unit #{$renops->unit_id}";
                })->implode(', ');
                
                // Delete the renops entries (this will trigger the observer for each entry)
                UnitRenops::where('date', $parsedDate)->delete();
                
                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'message' => "Renops plan deleted successfully. {$unitCount} units ({$unitNumbers}) are now available for scheduling on {$parsedDate->format('Y-m-d')}."
                ]);
            } else {
                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'message' => 'No renops entries found for this date.'
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete renops plan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle a unit's renops status for a specific date.
     */
    public function toggleUnit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'unit_id' => 'required|exists:units,id',
            'day_type' => 'required|in:saturday,sunday,holiday',
            'holiday_id' => 'required_if:day_type,holiday|nullable|exists:holidays,id',
        ]);

        $date = Carbon::parse($validated['date']);
        $unitId = $validated['unit_id'];
        $dayType = $validated['day_type'];
        $holidayId = $validated['holiday_id'] ?? null;

        // Get unit details for better logging
        $unit = Unit::find($unitId);
        $unitNumber = $unit ? $unit->unit_number : "Unit #{$unitId}";

        // Begin transaction
        DB::beginTransaction();
        
        try {
            // Check if the unit is already in renops for this date
            $existingRenops = UnitRenops::where('date', $date)
                ->where('unit_id', $unitId)
                ->first();

            if ($existingRenops) {
                // Remove the unit from renops (this will trigger the observer)
                $existingRenops->delete();
                
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "Unit {$unitNumber} removed from renops plan for {$date->format('Y-m-d')}. This unit is now available for scheduling.",
                    'status' => 'removed'
                ]);
            } else {
                // Check if we're exceeding the maximum limit
                $maxLimit = $this->getMaxLimit($dayType);
                $currentCount = UnitRenops::where('date', $date)->count();

                if ($currentCount >= $maxLimit) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot add more units. Maximum limit of {$maxLimit} units has been reached."
                    ], 422);
                }

                // Add the unit to renops (this will trigger the observer to update schedules)
                UnitRenops::create([
                    'date' => $date,
                    'unit_id' => $unitId,
                    'day_type' => $dayType,
                    'holiday_id' => $holidayId
                ]);
                
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "Unit {$unitNumber} added to renops plan for {$date->format('Y-m-d')}. Any existing schedules have been updated.",
                    'status' => 'added'
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle unit renops status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all renops plans for a given period.
     */
    public function getByDateRange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        $renopsPlans = UnitRenops::whereBetween('date', [$startDate, $endDate])
            ->with(['unit', 'holiday'])
            ->get()
            ->groupBy('date')
            ->map(function ($items) {
                $firstItem = $items->first();
                return [
                    'date' => $firstItem->date->format('Y-m-d'),
                    'day_type' => $firstItem->day_type,
                    'holiday' => $firstItem->holiday ? $firstItem->holiday->name : null,
                    'units_count' => $items->count(),
                    'max_limit' => $this->getMaxLimit($firstItem->day_type),
                    'units' => $items->map(function ($item) {
                        return [
                            'id' => $item->unit->id,
                            'unit_number' => $item->unit->unit_number,
                            'plate_number' => $item->unit->plate_number
                        ];
                    })
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $renopsPlans
        ]);
    }

    /**
     * Get the maximum limit of units for a specific day type.
     */
    private function getMaxLimit(string $dayType): int
    {
        $settings = RenopsSettings::getCurrentSettings();
        
        // Filter units by is_pool if unit_type is set to 'pool'
        $query = Unit::active();
        if ($settings->unit_type === 'pool') {
            $query->where('is_pool', true);
        }
        $totalUnits = $query->count();
        
        $threshold = $settings->getThresholdForDayType($dayType) / 100;
        return (int) ceil($totalUnits * $threshold);
    }
    
    /**
     * Show the settings page for renops.
     */
    public function showSettings(): View
    {
        $settings = RenopsSettings::getCurrentSettings();
        
        // Get total units based on unit_type setting
        $query = Unit::active();
        if ($settings->unit_type === 'pool') {
            $query->where('is_pool', true);
        }
        $totalUnits = $query->count();
        
        return view('modules.admin.renops.settings', [
            'settings' => $settings,
            'totalUnits' => $totalUnits
        ]);
    }
    
    /**
     * Update the renops settings.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mode' => 'required|in:manual,automatic',
            'unit_type' => 'required|in:all,pool',
            'saturday_threshold' => 'required|numeric|min:1|max:100',
            'sunday_threshold' => 'required|numeric|min:1|max:100',
            'holiday_threshold' => 'required|numeric|min:1|max:100',
            'notes' => 'nullable|string',
        ]);
        
        $settings = RenopsSettings::getCurrentSettings();
        $settings->update($validated);
        
        return redirect()->route('renops.settings')
            ->with('success', 'Renops settings updated successfully.');
    }
}
