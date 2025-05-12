<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\Unit;
use App\Models\UnitRenops;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
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

        // Get all active units
        $units = Unit::active()->get();

        // Get units already in renops for this date
        $renopsUnits = UnitRenops::where('date', $selectedDate)
            ->pluck('unit_id')
            ->toArray();

        return view('modules.admin.renops.index', [
            'date' => $selectedDate,
            'units' => $units,
            'renopsUnits' => $renopsUnits,
            'dayType' => $dayType,
            'holiday' => $holiday,
            'maxLimit' => $this->getMaxLimit($dayType),
            'currentCount' => count($renopsUnits)
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
            'unit_ids' => 'required|array',
            'unit_ids.*' => 'exists:units,id',
            'day_type' => 'required|in:saturday,sunday,holiday',
            'holiday_id' => 'required_if:day_type,holiday|nullable|exists:holidays,id',
        ]);

        $parsedDate = Carbon::parse($date);
        $dayType = $validated['day_type'];
        $holidayId = $validated['holiday_id'] ?? null;

        // Check if we're exceeding the maximum limit
        $maxLimit = $this->getMaxLimit($dayType);

        if (count($validated['unit_ids']) > $maxLimit) {
            return response()->json([
                'success' => false,
                'message' => "Cannot add more units. Maximum limit is {$maxLimit} units."
            ], 422);
        }

        // Begin transaction
        DB::beginTransaction();

        try {
            // Delete existing entries for this date
            UnitRenops::where('date', $parsedDate)->delete();

            // Create new entries
            foreach ($validated['unit_ids'] as $unitId) {
                UnitRenops::create([
                    'date' => $parsedDate,
                    'unit_id' => $unitId,
                    'day_type' => $dayType,
                    'holiday_id' => $holidayId
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Renops plan updated successfully.'
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

        try {
            UnitRenops::where('date', $parsedDate)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Renops plan deleted successfully.'
            ]);
        } catch (\Exception $e) {
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

        // Check if the unit is already in renops for this date
        $existingRenops = UnitRenops::where('date', $date)
            ->where('unit_id', $unitId)
            ->first();

        if ($existingRenops) {
            // Remove the unit from renops
            $existingRenops->delete();

            return response()->json([
                'success' => true,
                'message' => 'Unit removed from renops plan.',
                'status' => 'removed'
            ]);
        } else {
            // Check if we're exceeding the maximum limit
            $maxLimit = $this->getMaxLimit($dayType);
            $currentCount = UnitRenops::where('date', $date)->count();

            if ($currentCount >= $maxLimit) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot add more units. Maximum limit of {$maxLimit} units has been reached."
                ], 422);
            }

            // Add the unit to renops
            UnitRenops::create([
                'date' => $date,
                'unit_id' => $unitId,
                'day_type' => $dayType,
                'holiday_id' => $holidayId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Unit added to renops plan.',
                'status' => 'added'
            ]);
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
        $totalUnits = Unit::active()->count();

        switch ($dayType) {
            case 'saturday':
                return (int) ceil($totalUnits * 0.8); // 80% of total units
            case 'sunday':
            case 'holiday':
                return (int) ceil($totalUnits * 0.7); // 70% of total units
            default:
                return 0;
        }
    }
}
