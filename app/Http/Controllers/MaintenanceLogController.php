<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\DriverScheduleHistory;
use App\Models\MaintenanceLog;
use App\Models\MaintenanceLogPhoto;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class MaintenanceLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $maintenanceLogs = MaintenanceLog::with(['unit', 'route', 'driver', 'photos'])
            ->orderBy('date_reported', 'desc')
            ->orderBy('time_reported', 'desc')
            ->paginate(10);
            
        return view('modules.admin.maintenance-logs.index', compact('maintenanceLogs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $units = Unit::orderBy('unit_number')->get();
        $routes = Route::orderBy('route_number')->get();
        $shifts = ['Pagi', 'Siang'];
        
        return view('modules.admin.maintenance-logs.create', compact('units', 'routes', 'shifts'));
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
     * Get routes assigned to a specific unit.
     */
    public function getRoutesForUnit($unitId)
    {
        $unit = Unit::findOrFail($unitId);
        $routes = $unit->routes;
        
        return response()->json($routes);
    }
    
    /**
     * Get drivers from schedules for a specific unit, route, and date.
     */
    public function getDriverFromSchedule(Request $request)
    {
        $unitId = $request->unit_id;
        $routeId = $request->route_id;
        $date = $request->date;
        
        // Find all schedules for the unit, route, and date
        $schedules = Schedule::where('unit_id', $unitId)
            ->where('route_id', $routeId)
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
            'route_id' => 'required|exists:routes,id',
            'driver_id' => 'required|exists:drivers,id',
            'date_reported' => 'required|date',
            'time_reported' => 'required',
            'shift' => 'nullable|string',
            'description' => 'required|string',
            'type' => 'required|in:perbaikan,penggantian',
            'parts' => 'required|string',
            'category' => 'required_if:type,penggantian|nullable|in:baru,bekas',
            'source_of_sparepart' => 'required|string',
            'costs' => 'nullable|array',
            'costs.*.description' => 'required|string',
            'costs.*.amount' => 'required|numeric|min:0',
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
            // Create the maintenance log
            $maintenanceLog = MaintenanceLog::create([
                'unit_id' => $request->unit_id,
                'route_id' => $request->route_id,
                'driver_id' => $request->driver_id,
                'date_reported' => $request->date_reported,
                'time_reported' => $request->time_reported,
                'shift' => $request->shift,
                'description' => $request->description,
                'type' => $request->type,
                'parts' => $request->parts,
                'category' => $request->type === 'penggantian' ? $request->category : null,
                'source_of_sparepart' => $request->source_of_sparepart,
                'costs' => $request->costs,
                'on_schedule' => $onSchedule,
                'schedule_history_id' => $scheduleHistoryId,
                'status' => 'pending',
            ]);
            
            // Upload and store photos
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('maintenance-logs', 'public');
                    
                    MaintenanceLogPhoto::create([
                        'maintenance_log_id' => $maintenanceLog->id,
                        'photo_path' => $path,
                    ]);
                }
            }
            
            // Update unit status to maintenance
            $unit = Unit::find($request->unit_id);
            $unit->status = 'maintenance';
            $unit->save();
            
            // Update any active schedules for this unit to absent
            $schedules = Schedule::where('unit_id', $request->unit_id)
                ->where('schedule_date', '>=', $request->date_reported)
                ->where('status', 'scheduled')
                ->get();
                
            foreach ($schedules as $schedule) {
                $schedule->status = 'absent';
                $schedule->notes = 'Unit in maintenance: ' . $request->description;
                $schedule->save();
            }
            
            DB::commit();
            
            return redirect()->route('maintenance-logs.index')
                ->with('success', 'Maintenance log created successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create maintenance log: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MaintenanceLog $maintenanceLog)
    {
        $maintenanceLog->load(['unit', 'route', 'driver', 'photos']);
        
        return view('modules.admin.maintenance-logs.show', compact('maintenanceLog'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MaintenanceLog $maintenanceLog)
    {
        $maintenanceLog->load(['unit', 'route', 'driver', 'photos']);
        
        $units = Unit::orderBy('unit_number')->get();
        $routes = Route::orderBy('route_number')->get();
        $shifts = ['Pagi', 'Siang'];
        
        return view('modules.admin.maintenance-logs.edit', compact('maintenanceLog', 'units', 'routes', 'shifts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MaintenanceLog $maintenanceLog)
    {
        $validated = $request->validate([
            'description' => 'required|string',
            'type' => 'required|in:perbaikan,penggantian',
            'parts' => 'required|string',
            'category' => 'required_if:type,penggantian|nullable|in:baru,bekas',
            'source_of_sparepart' => 'required|string',
            'costs' => 'nullable|array',
            'costs.*.description' => 'required|string',
            'costs.*.amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,in_progress,completed',
            'photos' => 'nullable|array|max:3',
            'photos.*' => 'image|max:2048', // 2MB max per image
        ]);
        
        DB::beginTransaction();
        
        try {
            // Update the maintenance log
            $maintenanceLog->update([
                'description' => $request->description,
                'type' => $request->type,
                'parts' => $request->parts,
                'category' => $request->type === 'penggantian' ? $request->category : null,
                'source_of_sparepart' => $request->source_of_sparepart,
                'costs' => $request->costs,
                'status' => $request->status,
            ]);
            
            // Upload and store new photos
            if ($request->hasFile('photos')) {
                // Count existing photos
                $existingPhotoCount = $maintenanceLog->photos->count();
                $newPhotoCount = count($request->file('photos'));
                
                // Check if the total number of photos would exceed 3
                if ($existingPhotoCount + $newPhotoCount > 3) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Maximum 3 photos allowed. You currently have ' . $existingPhotoCount . ' photos.');
                }
                
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('maintenance-logs', 'public');
                    
                    MaintenanceLogPhoto::create([
                        'maintenance_log_id' => $maintenanceLog->id,
                        'photo_path' => $path,
                    ]);
                }
            }
            
            // If status is completed, update the unit status back to active
            if ($request->status === 'completed') {
                $unit = Unit::find($maintenanceLog->unit_id);
                $unit->status = 'active';
                $unit->save();
            }
            
            DB::commit();
            
            return redirect()->route('maintenance-logs.show', $maintenanceLog)
                ->with('success', 'Maintenance log updated successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update maintenance log: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MaintenanceLog $maintenanceLog)
    {
        DB::beginTransaction();
        
        try {
            // Delete photos from storage
            foreach ($maintenanceLog->photos as $photo) {
                Storage::disk('public')->delete($photo->photo_path);
            }
            
            // Delete the maintenance log (photos will be deleted via cascade)
            $maintenanceLog->delete();
            
            DB::commit();
            
            return redirect()->route('maintenance-logs.index')
                ->with('success', 'Maintenance log deleted successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Failed to delete maintenance log: ' . $e->getMessage());
        }
    }

    /**
     * Remove a photo from a maintenance log.
     */
    public function deletePhoto($id)
    {
        $photo = MaintenanceLogPhoto::findOrFail($id);
        
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
}
