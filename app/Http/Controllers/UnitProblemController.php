<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\DriverScheduleHistory;
use App\Models\Schedule;
use App\Models\Unit;
use App\Models\UnitProblem;
use App\Models\UnitProblemPhoto;
use App\Models\MaintenanceLog;
use App\Models\MaintenanceLogPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class UnitProblemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $unitProblems = UnitProblem::with(['unit', 'driver', 'photos'])
            ->orderBy('date_reported', 'desc')
            ->orderBy('time_reported', 'desc')
            ->paginate(10);
            
        return view('modules.admin.unit-problems.index', compact('unitProblems'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $units = Unit::orderBy('unit_number')->get();
        $shifts = ['Pagi', 'Siang'];
        
        return view('modules.admin.unit-problems.create', compact('units', 'shifts'));
    }

    /**
     * Get drivers assigned to a specific unit.
     */
    public function getDriversForUnit($unitId)
    {
        $unit = Unit::findOrFail($unitId);
        $drivers = $unit->drivers;
        
        return response()->json($drivers);
    }
    
    /**
     * Get drivers from schedules for a specific unit and date.
     */
    public function getDriverFromSchedule(Request $request)
    {
        $unitId = $request->unit_id;
        $date = $request->date;
        
        // Find all schedules for the unit and date
        $schedules = Schedule::where('unit_id', $unitId)
            ->where('schedule_date', $date)
            ->with('driver')
            ->get();
        
        $result = [
            'schedules' => [],
            'has_schedules' => false
        ];
        
        if ($schedules->isNotEmpty()) {
            $result['has_schedules'] = true;
            
            foreach ($schedules as $schedule) {
                if ($schedule->driver) {
                    // Find schedule history for this driver
                    $scheduleHistory = DriverScheduleHistory::where('driver_id', $schedule->driver_id)
                        ->where('period_start_date', '<=', $date)
                        ->where('period_end_date', '>=', $date)
                        ->first();
                    
                    $result['schedules'][] = [
                        'driver' => $schedule->driver,
                        'shift' => $schedule->shift,
                        'on_schedule' => true,
                        'schedule_history_id' => $scheduleHistory ? $scheduleHistory->id : null
                    ];
                }
            }
        }
        
        return response()->json($result);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'driver_id' => 'required|exists:drivers,id',
            'date_reported' => 'required|date',
            'time_reported' => 'required',
            'shift' => 'nullable|string',
            'description' => 'required|string',
            'location' => 'nullable|string',
            'photos' => 'required|array|min:1|max:3',
            'photos.*' => 'required|image|max:2048', // 2MB max per image
            'on_schedule' => 'boolean',
        ]);
        
        // Check if driver is on schedule
        $onSchedule = false;
        $scheduleHistoryId = null;
        
        if ($request->has('on_schedule') && $request->on_schedule) {
            // Find the schedule history for the driver on the reported date
            $scheduleHistory = DriverScheduleHistory::where('driver_id', $request->driver_id)
                ->whereDate('period_start_date', '<=', $request->date_reported)
                ->whereDate('period_end_date', '>=', $request->date_reported)
                ->first();
                
            if ($scheduleHistory) {
                $onSchedule = true;
                $scheduleHistoryId = $scheduleHistory->id;
            }
        }
        
        DB::beginTransaction();
        
        try {
            // Create the unit problem
            $unitProblem = UnitProblem::create([
                'unit_id' => $request->unit_id,
                'driver_id' => $request->driver_id,
                'date_reported' => $request->date_reported,
                'time_reported' => $request->time_reported,
                'shift' => $request->shift,
                'description' => $request->description,
                'location' => $request->location,
                'on_schedule' => $onSchedule,
                'schedule_history_id' => $scheduleHistoryId,
            ]);
            
            // Upload and store photos
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('unit-problems', 'public');
                    
                    UnitProblemPhoto::create([
                        'unit_problem_id' => $unitProblem->id,
                        'photo_path' => $path,
                    ]);
                }
            }
            
            DB::commit();
            
            return redirect()->route('unit-problems.index')
                ->with('success', 'Laporan masalah unit berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(UnitProblem $unitProblem)
    {
        $unitProblem->load(['unit', 'driver', 'photos', 'scheduleHistory']);
        
        return view('modules.admin.unit-problems.show', compact('unitProblem'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UnitProblem $unitProblem)
    {
        $unitProblem->load(['unit', 'driver', 'photos']);
        
        $units = Unit::orderBy('unit_number')->get();
        $drivers = Driver::orderBy('name')->get();
        $shifts = ['Pagi', 'Siang'];
        
        return view('modules.admin.unit-problems.edit', compact('unitProblem', 'units', 'drivers', 'shifts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UnitProblem $unitProblem)
    {
        $validated = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'driver_id' => 'required|exists:drivers,id',
            'date_reported' => 'required|date',
            'time_reported' => 'required',
            'shift' => 'nullable|string',
            'description' => 'required|string',
            'location' => 'nullable|string',
            'photos' => 'nullable|array|max:3',
            'photos.*' => 'image|max:2048', // 2MB max per image
            'on_schedule' => 'boolean',
        ]);
        
        // Check if driver is on schedule
        $onSchedule = false;
        $scheduleHistoryId = null;
        
        if ($request->has('on_schedule') && $request->on_schedule) {
            // Find the schedule history for the driver on the reported date
            $scheduleHistory = DriverScheduleHistory::where('driver_id', $request->driver_id)
                ->whereDate('period_start_date', '<=', $request->date_reported)
                ->whereDate('period_end_date', '>=', $request->date_reported)
                ->first();
                
            if ($scheduleHistory) {
                $onSchedule = true;
                $scheduleHistoryId = $scheduleHistory->id;
            }
        }
        
        DB::beginTransaction();
        
        try {
            // Update the unit problem
            $unitProblem->update([
                'unit_id' => $request->unit_id,
                'driver_id' => $request->driver_id,
                'date_reported' => $request->date_reported,
                'time_reported' => $request->time_reported,
                'shift' => $request->shift,
                'description' => $request->description,
                'location' => $request->location,
                'on_schedule' => $onSchedule,
                'schedule_history_id' => $scheduleHistoryId,
            ]);
            
            // Upload and store new photos if provided
            if ($request->hasFile('photos')) {
                // Check if total photos (existing + new) will exceed 3
                $currentPhotoCount = $unitProblem->photos->count();
                $newPhotoCount = count($request->file('photos'));
                
                if ($currentPhotoCount + $newPhotoCount > 3) {
                    return back()->withInput()
                        ->with('error', 'Total foto tidak boleh lebih dari 3.');
                }
                
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('unit-problems', 'public');
                    
                    UnitProblemPhoto::create([
                        'unit_problem_id' => $unitProblem->id,
                        'photo_path' => $path,
                    ]);
                }
            }
            
            DB::commit();
            
            return redirect()->route('unit-problems.show', $unitProblem)
                ->with('success', 'Laporan masalah unit berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UnitProblem $unitProblem)
    {
        DB::beginTransaction();
        
        try {
            // Delete associated photos from storage
            foreach ($unitProblem->photos as $photo) {
                Storage::disk('public')->delete($photo->photo_path);
                $photo->delete();
            }
            
            // Delete the unit problem
            $unitProblem->delete();
            
            DB::commit();
            
            return redirect()->route('unit-problems.index')
                ->with('success', 'Laporan masalah unit berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    
    /**
     * Remove a photo from a unit problem.
     */
    public function deletePhoto($id)
    {
        $photo = UnitProblemPhoto::findOrFail($id);
        
        try {
            // Delete the photo from storage
            Storage::disk('public')->delete($photo->photo_path);
            
            // Delete the photo record
            $photo->delete();
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Convert a unit problem to a maintenance log.
     */
    public function convertToMaintenance(UnitProblem $unitProblem)
    {
        DB::beginTransaction();
        
        try {
            // Get the unit's routes
            $unit = Unit::findOrFail($unitProblem->unit_id);
            
            // Check if the unit is already in maintenance
            if ($unit->status === 'maintenance') {
                return redirect()->back()->with('error', 'Unit ini sudah dalam status maintenance. Tidak dapat mengirim ke log perawatan lagi.');
            }
            
            // Check if there's an active maintenance log for this unit
            $existingMaintenanceLog = MaintenanceLog::where('unit_id', $unit->id)
                ->where('status', '!=', 'completed')
                ->first();
                
            if ($existingMaintenanceLog) {
                return redirect()->back()->with('error', 'Unit ini sudah memiliki log perawatan yang aktif. Selesaikan log perawatan yang ada terlebih dahulu.');
            }
            
            $route = $unit->routes->first(); // Get the first route for now
            
            if (!$route) {
                return redirect()->back()->with('error', 'Unit tidak memiliki rute yang terkait. Silakan tambahkan rute terlebih dahulu.');
            }
            
            // Create a new maintenance log from the unit problem
            $maintenanceLog = MaintenanceLog::create([
                'unit_id' => $unitProblem->unit_id,
                'route_id' => $route->id,
                'driver_id' => $unitProblem->driver_id,
                'date_reported' => $unitProblem->date_reported,
                'time_reported' => $unitProblem->time_reported,
                'description' => $unitProblem->description,
                'type' => 'perbaikan', // Default to 'perbaikan'
                'parts' => 'Perlu ditentukan', // Default value
                'source_of_sparepart' => 'Perlu ditentukan', // Default value
                'costs' => [
                    [
                        'description' => 'Biaya Perbaikan',
                        'amount' => 0
                    ]
                ],
                'status' => 'pending',
                'on_schedule' => $unitProblem->on_schedule,
                'schedule_history_id' => $unitProblem->schedule_history_id,
            ]);
            
            // Copy photos from unit problem to maintenance log
            foreach ($unitProblem->photos as $photo) {
                // Get the original file path
                $originalPath = $photo->photo_path;
                
                // Create a new path for the maintenance log photo
                $newPath = str_replace('unit-problems', 'maintenance-logs', $originalPath);
                
                // Copy the file to the new location
                if (Storage::disk('public')->exists($originalPath)) {
                    Storage::disk('public')->copy($originalPath, $newPath);
                    
                    // Create a new photo record for the maintenance log
                    MaintenanceLogPhoto::create([
                        'maintenance_log_id' => $maintenanceLog->id,
                        'photo_path' => $newPath,
                    ]);
                }
            }
            
            // Update unit status to maintenance
            $unit = Unit::find($unitProblem->unit_id);
            $unit->status = 'maintenance';
            $unit->save();
            
            // Update any active schedules for this unit to absent
            $schedules = Schedule::where('unit_id', $unitProblem->unit_id)
                ->where('schedule_date', '>=', $unitProblem->date_reported)
                ->where('status', 'scheduled')
                ->get();
                
            foreach ($schedules as $schedule) {
                $schedule->status = 'absent';
                $schedule->notes = 'Unit in maintenance: ' . $unitProblem->description;
                $schedule->save();
            }
            
            DB::commit();
            
            return redirect()->route('maintenance-logs.edit', $maintenanceLog)
                ->with('success', 'Laporan masalah berhasil dikonversi ke Log Perawatan.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Gagal mengkonversi ke Log Perawatan: ' . $e->getMessage());
        }
    }
}
