<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HolidayController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $holidays = Holiday::orderBy('date', 'desc')->paginate(10);
        
        return view('modules.admin.holidays.index', compact('holidays'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('modules.admin.holidays.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'name' => 'required|string|max:255',
            'type' => 'required|in:cuti_bersama,libur_nasional',
            'description' => 'nullable|string',
        ]);

        try {
            Holiday::create($validated);
            
            return redirect()->route('holidays.index')
                ->with('success', 'Hari libur berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan hari libur: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Holiday $holiday)
    {
        return view('modules.admin.holidays.show', compact('holiday'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Holiday $holiday)
    {
        return view('modules.admin.holidays.edit', compact('holiday'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Holiday $holiday)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'name' => 'required|string|max:255',
            'type' => 'required|in:cuti_bersama,libur_nasional',
            'description' => 'nullable|string',
        ]);

        try {
            $holiday->update($validated);
            
            return redirect()->route('holidays.index')
                ->with('success', 'Hari libur berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui hari libur: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Holiday $holiday)
    {
        try {
            $holiday->delete();
            
            return redirect()->route('holidays.index')
                ->with('success', 'Hari libur berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus hari libur: ' . $e->getMessage());
        }
    }

    /**
     * Get all holidays and weekends for a given period.
     * 
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHolidaysAndWeekends(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        
        // Get all holidays in the range
        $holidays = Holiday::whereBetween('date', [$startDate, $endDate])
            ->get()
            ->map(function ($holiday) {
                return [
                    'date' => $holiday->date->format('Y-m-d'),
                    'name' => $holiday->name,
                    'type' => 'holiday',
                    'holiday_id' => $holiday->id
                ];
            });
        
        // Get all weekends in the range
        $weekends = collect();
        $period = CarbonPeriod::create($startDate, $endDate);
        
        foreach ($period as $date) {
            if ($date->isSaturday() || $date->isSunday()) {
                $weekends->push([
                    'date' => $date->format('Y-m-d'),
                    'name' => $date->isSaturday() ? 'Saturday' : 'Sunday',
                    'type' => $date->isSaturday() ? 'saturday' : 'sunday',
                    'holiday_id' => null
                ]);
            }
        }
        
        // Combine holidays and weekends
        $result = $holidays->concat($weekends)->sortBy('date')->values();
        
        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Check if a date is a weekend or holiday.
     * 
     * @param string $date
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkDateStatus(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        $date = Carbon::parse($validated['date']);
        $isWeekend = $date->isWeekend();
        $holiday = Holiday::whereDate('date', $date)->first();
        
        $dayType = null;
        $holidayId = null;
        
        if ($date->isSaturday()) {
            $dayType = 'saturday';
        } elseif ($date->isSunday()) {
            $dayType = 'sunday';
        } elseif ($holiday) {
            $dayType = 'holiday';
            $holidayId = $holiday->id;
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date->format('Y-m-d'),
                'is_weekend' => $isWeekend,
                'is_holiday' => $holiday ? true : false,
                'day_type' => $dayType,
                'holiday_id' => $holidayId,
                'holiday_name' => $holiday ? $holiday->name : null
            ]
        ]);
    }
}
