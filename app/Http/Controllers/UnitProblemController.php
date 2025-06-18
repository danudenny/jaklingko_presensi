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
use Illuminate\Support\Facades\Log;

class UnitProblemController extends Controller
{
    public function index()
    {
        $unitProblems = UnitProblem::with(['unit', 'driver', 'photos'])
            ->orderBy('date_reported', 'desc')
            ->orderBy('time_reported', 'desc')
            ->paginate(10);
            
        return view('modules.admin.unit-problems.index', compact('unitProblems'));
    }

    public function create()
    {
        $units = Unit::orderBy('unit_number')->get();
        $shifts = ['Pagi', 'Siang'];
        
        return view('modules.admin.unit-problems.create', compact('units', 'shifts'));
    }

    public function getDriversForUnit($unitId)
    {
        $unit = Unit::findOrFail($unitId);
        $drivers = $unit->drivers;
        
        return response()->json($drivers);
    }
    
    public function getDriverFromSchedule(Request $request)
    {
        $unitId = $request->unit_id;
        $date = $request->date;
        
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
            'photos.*' => 'required|image|max:2048',
            'on_schedule' => 'boolean',
            'needs_repair' => 'boolean',
        ]);
        
        $onSchedule = false;
        $scheduleHistoryId = null;
        
        if ($request->has('on_schedule') && $request->on_schedule) {
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
                'needs_repair' => $request->has('needs_repair') && $request->needs_repair,
            ]);
            
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('unit-problems', 'public');
                    
                    UnitProblemPhoto::create([
                        'unit_problem_id' => $unitProblem->id,
                        'photo_path' => $path,
                    ]);
                }
            }
            
            // If needs_repair is false, automatically create maintenance log
            if (!$request->has('needs_repair') || !$request->needs_repair) {
                $this->createMaintenanceLogForNonRepair($unitProblem);
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

    public function show(UnitProblem $unitProblem)
    {
        $unitProblem->load(['unit', 'driver', 'photos', 'scheduleHistory']);
        
        return view('modules.admin.unit-problems.show', compact('unitProblem'));
    }

    public function edit(UnitProblem $unitProblem)
    {
        $unitProblem->load(['unit', 'driver', 'photos']);
        
        $units = Unit::orderBy('unit_number')->get();
        $drivers = Driver::orderBy('name')->get();
        $shifts = ['Pagi', 'Siang'];
        
        return view('modules.admin.unit-problems.edit', compact('unitProblem', 'units', 'drivers', 'shifts'));
    }

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
            'needs_repair' => 'boolean',
        ]);
        
        $onSchedule = false;
        $scheduleHistoryId = null;
        
        if ($request->has('on_schedule') && $request->on_schedule) {
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
                'needs_repair' => $request->has('needs_repair') && $request->needs_repair,
            ]);
            
            if ($request->hasFile('photos')) {
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
            
            // If needs_repair is false, automatically create maintenance log
            if (!$request->has('needs_repair') || !$request->needs_repair) {
                // Check if maintenance log already exists for this unit problem
                $existingMaintenanceLog = MaintenanceLog::where('unit_id', $unitProblem->unit_id)
                    ->where('date_reported', $unitProblem->date_reported)
                    ->where('time_reported', $unitProblem->time_reported)
                    ->where('type', 'tidak_ada_perbaikan')
                    ->first();
                    
                if (!$existingMaintenanceLog) {
                    $this->createMaintenanceLogForNonRepair($unitProblem);
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

    public function destroy(UnitProblem $unitProblem)
    {
        DB::beginTransaction();
        
        try {
            foreach ($unitProblem->photos as $photo) {
                Storage::disk('public')->delete($photo->photo_path);
                $photo->delete();
            }
            
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
    
    public function deletePhoto($id)
    {
        $photo = UnitProblemPhoto::findOrFail($id);
        
        try {
            Storage::disk('public')->delete($photo->photo_path);
            $photo->delete();
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    private function createMaintenanceLogForNonRepair(UnitProblem $unitProblem)
    {
        try {
            $unit = Unit::findOrFail($unitProblem->unit_id);
            $route = $unit->routes->first();
            
            if (!$route) {
                // Log error but don't fail the main transaction
                Log::warning("Unit {$unit->unit_number} tidak memiliki rute yang terkait untuk maintenance log");
                return;
            }
            
            $maintenanceLog = MaintenanceLog::create([
                'unit_id' => $unitProblem->unit_id,
                'route_id' => $route->id,
                'driver_id' => $unitProblem->driver_id,
                'date_reported' => $unitProblem->date_reported,
                'time_reported' => $unitProblem->time_reported,
                'description' => $unitProblem->description,
                'type' => 'tidak_ada_perbaikan',
                'parts' => 'Tidak ada',
                'source_of_sparepart' => 'Tidak diperlukan',
                'costs' => [
                    [
                        'description' => 'Tidak ada biaya',
                        'amount' => 0
                    ]
                ],
                'status' => 'in_progress',
                'on_schedule' => $unitProblem->on_schedule,
                'schedule_history_id' => $unitProblem->schedule_history_id,
            ]);
            
            // Copy photos from unit problem to maintenance log
            foreach ($unitProblem->photos as $photo) {
                $originalPath = $photo->photo_path;
                $newPath = str_replace('unit-problems', 'maintenance-logs', $originalPath);
                
                if (Storage::disk('public')->exists($originalPath)) {
                    Storage::disk('public')->copy($originalPath, $newPath);
                    
                    MaintenanceLogPhoto::create([
                        'maintenance_log_id' => $maintenanceLog->id,
                        'photo_path' => $newPath,
                    ]);
                }
            }
            
            // Update unit status to maintenance
            $unit->status = 'maintenance';
            $unit->save();
            
            // Update affected schedules
            $schedules = Schedule::where('unit_id', $unitProblem->unit_id)
                ->where('schedule_date', '>=', $unitProblem->date_reported)
                ->whereIn('status', ['active', 'scheduled', 'confirmed'])
                ->get();
                
            foreach ($schedules as $schedule) {
                $originalStatus = $schedule->status;
                $schedule->status = 'maintenance';
                $schedule->notes = json_encode([
                    'maintenance_log_id' => $maintenanceLog->id,
                    'maintenance_reason' => $unitProblem->description,
                    'original_status' => $originalStatus
                ]);
                $schedule->save();
            }
            
        } catch (\Exception $e) {
            Log::error("Failed to create maintenance log for non-repair unit problem {$unitProblem->id}: " . $e->getMessage());
        }
    }
    
    public function convertToMaintenance($unitProblem)
    {
        if (!($unitProblem instanceof UnitProblem)) {
            $unitProblem = UnitProblem::findOrFail($unitProblem);
        }
        
        DB::beginTransaction();
        
        try {            
            $unit = Unit::findOrFail($unitProblem->unit_id);

            $existingMaintenanceLog = MaintenanceLog::where('unit_id', $unit->id)
                ->where('status', '!=', 'completed')
                ->first();
            $route = $unit->routes->first();
            
            if (!$route) {
                return redirect()->back()->with('error', 'Unit tidak memiliki rute yang terkait. Silakan tambahkan rute terlebih dahulu.');
            }
            
            $maintenanceLog = MaintenanceLog::create([
                'unit_id' => $unitProblem->unit_id,
                'route_id' => $route->id,
                'driver_id' => $unitProblem->driver_id,
                'date_reported' => $unitProblem->date_reported,
                'time_reported' => $unitProblem->time_reported,
                'description' => $unitProblem->description,
                'type' => 'perbaikan',
                'parts' => 'Perlu ditentukan',
                'source_of_sparepart' => 'Perlu ditentukan',
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
            
            foreach ($unitProblem->photos as $photo) {
                $originalPath = $photo->photo_path;
                
                $newPath = str_replace('unit-problems', 'maintenance-logs', $originalPath);
                
                if (Storage::disk('public')->exists($originalPath)) {
                    Storage::disk('public')->copy($originalPath, $newPath);
                    
                    MaintenanceLogPhoto::create([
                        'maintenance_log_id' => $maintenanceLog->id,
                        'photo_path' => $newPath,
                    ]);
                }
            }
            
            $unit = Unit::find($unitProblem->unit_id);
            $unit->status = 'maintenance';
            $unit->save();
            
            $schedules = Schedule::where('unit_id', $unitProblem->unit_id)
                ->where('schedule_date', '>=', $unitProblem->date_reported)
                ->whereIn('status', ['active', 'scheduled', 'confirmed'])
                ->get();
                
            foreach ($schedules as $schedule) {
                $originalStatus = $schedule->status;
                $schedule->status = 'maintenance';
                $schedule->notes = json_encode([
                    'maintenance_log_id' => $maintenanceLog->id,
                    'maintenance_reason' => $unitProblem->description,
                    'original_status' => $originalStatus
                ]);
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
