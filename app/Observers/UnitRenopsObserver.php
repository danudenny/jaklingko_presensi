<?php

namespace App\Observers;

use App\Models\UnitRenops;
use App\Models\Schedule;
use App\Models\Driver;
use App\Models\Unit;
use App\Models\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class UnitRenopsObserver
{
    /**
     * Handle the UnitRenops "created" event.
     *
     * @param  \App\Models\UnitRenops  $unitRenops
     * @return void
     */
    public function created(UnitRenops $unitRenops)
    {
        $this->removeExistingSchedules($unitRenops);
    }

    /**
     * Handle the UnitRenops "updated" event.
     *
     * @param  \App\Models\UnitRenops  $unitRenops
     * @return void
     */
    public function updated(UnitRenops $unitRenops)
    {
        // Check if date or unit_id has changed
        if ($unitRenops->isDirty('date') || $unitRenops->isDirty('unit_id')) {
            // If the date or unit changed, we need to:
            // 1. Save the original values before removing schedules
            $originalDate = null;
            $originalUnitId = null;
            
            if ($unitRenops->getOriginal('date') && $unitRenops->getOriginal('unit_id')) {
                $originalDate = Carbon::parse($unitRenops->getOriginal('date'));
                $originalUnitId = $unitRenops->getOriginal('unit_id');
                
                Log::info("Saving original schedule information for Unit #{$originalUnitId} on {$originalDate->format('Y-m-d')} before updating renops");
            }
            
            // 2. Remove schedules for the new date/unit combination
            $this->removeExistingSchedules($unitRenops);
            
            // 3. Check if the original date/unit is now available for scheduling
            if ($originalDate && $originalUnitId) {
                // Check if there are any other renops entries for the original date/unit
                $otherRenops = UnitRenops::where('unit_id', $originalUnitId)
                    ->whereDate('date', $originalDate->format('Y-m-d'))
                    ->where('id', '!=', $unitRenops->id)
                    ->exists();
                    
                if (!$otherRenops) {
                    Log::info("Unit #{$originalUnitId} is now available for scheduling on {$originalDate->format('Y-m-d')}");
                    
                    // Reassign schedules for this unit/date
                    $this->reassignSchedules($originalUnitId, $originalDate);
                }
            }
        }
    }
    
    /**
     * Handle the UnitRenops "deleted" event.
     *
     * @param  \App\Models\UnitRenops  $unitRenops
     * @return void
     */
    public function deleted(UnitRenops $unitRenops)
    {
        // When a renops entry is deleted, we should check if the unit is now available for scheduling
        $date = $unitRenops->date;
        $unitId = $unitRenops->unit_id;
        
        // Check if there are any other renops entries for this date/unit
        $otherRenops = UnitRenops::where('unit_id', $unitId)
            ->whereDate('date', $date->format('Y-m-d'))
            ->where('id', '!=', $unitRenops->id)
            ->exists();
            
        if (!$otherRenops) {
            Log::info("UnitRenops deleted for Unit #{$unitId} on {$date->format('Y-m-d')} - unit is now available for scheduling");
            
            // Reassign schedules for this unit/date
            $this->reassignSchedules($unitId, $date);
        } else {
            Log::info("UnitRenops entry deleted for Unit #{$unitId} on {$date->format('Y-m-d')}, but unit still has other renops entries for this date");
        }
    }

    /**
     * Update status of existing schedules for this unit on this date
     * when it's added to renops
     *
     * @param  \App\Models\UnitRenops  $unitRenops
     * @return void
     */
    private function removeExistingSchedules(UnitRenops $unitRenops)
    {
        $date = $unitRenops->date;
        $unitId = $unitRenops->unit_id;
        
        // Find any existing schedules for this unit on this date
        $schedules = Schedule::where('unit_id', $unitId)
            ->whereDate('schedule_date', $date->format('Y-m-d'))
            ->where('status', '!=', 'renops')
            ->get();
        
        if ($schedules->isNotEmpty()) {
            $count = $schedules->count();
            
            // Log the action
            Log::info("Updating {$count} schedule(s) for Unit #{$unitId} on {$date->format('Y-m-d')} due to renops assignment");
            
            // Update the schedules status instead of deleting them
            foreach ($schedules as $schedule) {
                // Get information about the schedule before updating
                $driverName = $schedule->driver ? $schedule->driver->name : 'Unknown';
                $unitNumber = $schedule->unit ? $schedule->unit->unit_number : 'Unknown';
                $shift = $schedule->shift;
                
                Log::info("Changing status to 'renops' for Driver {$driverName} on Unit {$unitNumber} for {$date->format('Y-m-d')} shift {$shift}");
                
                // Save the original status in a note field if available, or use a standard note
                $originalStatus = $schedule->status;
                $note = "Changed from {$originalStatus} to renops on " . now()->format('Y-m-d H:i:s');
                
                // If the original status was 'scheduled', decrement the driver's schedule count
                if ($originalStatus === 'scheduled' && $schedule->driver_id) {
                    $this->decrementDriverScheduleCount($schedule);
                }
                
                // Update the schedule status instead of deleting
                $schedule->status = 'renops';
                if (property_exists($schedule, 'notes') || isset($schedule->notes)) {
                    $schedule->notes = $note;
                }
                $schedule->save();
            }
        }
    }
    
    /**
     * Recalculate the schedule count for a driver when a schedule is changed to 'renops'
     *
     * @param  \App\Models\Schedule  $schedule
     * @return void
     */
    private function decrementDriverScheduleCount($schedule)
    {
        $date = $schedule->schedule_date;
        $driverId = $schedule->driver_id;
        
        // Determine the period based on the date
        $day = $date->day;
        $periodStart = $day <= 15 ? $date->copy()->startOfMonth() : $date->copy()->startOfMonth()->addDays(15);
        $periodEnd = $day <= 15 ? $date->copy()->startOfMonth()->addDays(14) : $date->copy()->endOfMonth();
        
        // Use the new recalculateScheduleCount method to accurately count only 'scheduled' status schedules
        $history = \App\Models\DriverScheduleHistory::recalculateScheduleCount(
            $driverId,
            $periodStart->format('Y-m-d'),
            $periodEnd->format('Y-m-d')
        );
        
        if ($history) {
            Log::info("Recalculated schedule count for Driver #{$driverId} in period {$periodStart->format('Y-m-d')} to {$periodEnd->format('Y-m-d')}. New count: {$history->total_schedules}");
        }
    }
    
    /**
     * Reassign schedules for a unit that has become available
     * after renops entry is deleted or updated
     *
     * @param  int  $unitId
     * @param  \Carbon\Carbon  $date
     * @return void
     */
    private function reassignSchedules($unitId, $date)
    {
        // Check if there are any schedules with 'renops' status for this unit/date
        $renopsSchedules = Schedule::where('unit_id', $unitId)
            ->whereDate('schedule_date', $date->format('Y-m-d'))
            ->where('status', 'renops')
            ->get();
        
        if ($renopsSchedules->isNotEmpty()) {
            // Restore the schedules that were previously set to 'renops'
            Log::info("Restoring {$renopsSchedules->count()} schedule(s) for Unit #{$unitId} on {$date->format('Y-m-d')} that were previously marked as renops");
            
            foreach ($renopsSchedules as $schedule) {
                $driverName = $schedule->driver ? $schedule->driver->name : 'Unknown';
                $unitNumber = $schedule->unit ? $schedule->unit->unit_number : 'Unknown';
                $shift = $schedule->shift;
                
                // Check if the driver is still available (not on leave or assigned elsewhere)
                if ($this->isDriverAvailable($schedule->driver_id, $date, $shift)) {
                    // Restore the schedule to active
                    $schedule->status = 'active';
                    if (property_exists($schedule, 'notes') || isset($schedule->notes)) {
                        $schedule->notes = "Restored from renops on " . now()->format('Y-m-d H:i:s');
                    }
                    $schedule->save();
                    
                    Log::info("Restored schedule for Driver {$driverName} on Unit {$unitNumber} for {$date->format('Y-m-d')} shift {$shift}");
                } else {
                    Log::info("Driver {$driverName} is no longer available for Unit {$unitNumber} on {$date->format('Y-m-d')} shift {$shift}, looking for replacement");
                    
                    // Try to find a replacement driver
                    $this->assignDriverForShift($unitId, $schedule->route_id, $date, $shift, $schedule);
                }
            }
        } else {
            // No existing schedules to restore, try to create new ones if the unit has a route
            $unit = Unit::find($unitId);
            if (!$unit) {
                Log::error("Cannot reassign schedules: Unit #{$unitId} not found");
                return;
            }
            
            // Get the route associated with this unit (if any)
            $routeId = $unit->route_id;
            if (!$routeId) {
                Log::info("Unit #{$unitId} ({$unit->unit_number}) has no associated route, skipping schedule reassignment");
                return;
            }
            
            // Find available drivers for morning shift
            $this->assignDriverForShift($unitId, $routeId, $date, 'pagi');
            
            // Find available drivers for afternoon shift
            $this->assignDriverForShift($unitId, $routeId, $date, 'siang');
        }
    }
    
    /**
     * Check if a driver is available for assignment
     * 
     * @param int $driverId
     * @param \Carbon\Carbon $date
     * @param string $shift
     * @return bool
     */
    private function isDriverAvailable($driverId, $date, $shift)
    {
        // Check if driver exists and is active
        $driver = Driver::find($driverId);
        if (!$driver || $driver->status !== 'aktif') {
            return false;
        }
        
        // Check if driver is already assigned to another unit on this date/shift
        $alreadyAssigned = Schedule::where('driver_id', $driverId)
            ->whereDate('schedule_date', $date->format('Y-m-d'))
            ->where('shift', $shift)
            ->where('status', 'active') // Only consider active schedules
            ->exists();
            
        // Check if driver is on leave
        $onLeave = $driver->leaveRequests()
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->where('status', 'approved')
            ->exists();
            
        return !$alreadyAssigned && !$onLeave;
    }
    
    /**
     * Assign a driver for a specific shift
     *
     * @param  int  $unitId
     * @param  int  $routeId
     * @param  \Carbon\Carbon  $date
     * @param  string  $shift
     * @param  \App\Models\Schedule|null  $existingSchedule
     * @return void
     */
    private function assignDriverForShift($unitId, $routeId, $date, $shift, $existingSchedule = null)
    {
        // Check if there's already an active schedule for this unit/date/shift
        $activeSchedule = Schedule::where('unit_id', $unitId)
            ->whereDate('schedule_date', $date->format('Y-m-d'))
            ->where('shift', $shift)
            ->where('status', 'active')
            ->first();
            
        if ($activeSchedule) {
            Log::info("Active schedule already exists for Unit #{$unitId} on {$date->format('Y-m-d')} shift {$shift}, skipping reassignment");
            return;
        }
        
        // Find available drivers for this route and shift
        // First, get drivers qualified for this route
        $qualifiedDrivers = Driver::whereHas('routes', function($query) use ($routeId) {
                $query->where('route_id', $routeId);
            })
            ->where('status', 'aktif')
            ->get();
            
        if ($qualifiedDrivers->isEmpty()) {
            Log::info("No qualified drivers found for route #{$routeId} on {$date->format('Y-m-d')} shift {$shift}");
            return;
        }
        
        // Filter out drivers who are already assigned for this date and shift
        $availableDrivers = $qualifiedDrivers->filter(function($driver) use ($date, $shift) {
            return $this->isDriverAvailable($driver->id, $date, $shift);
        });
        
        if ($availableDrivers->isEmpty()) {
            Log::info("No available drivers found for Unit #{$unitId} on {$date->format('Y-m-d')} shift {$shift}");
            return;
        }
        
        // Assign the first available driver
        $driver = $availableDrivers->first();
        
        if ($existingSchedule) {
            // Update the existing schedule with the new driver
            $existingSchedule->driver_id = $driver->id;
            $existingSchedule->status = 'active';
            if (property_exists($existingSchedule, 'notes') || isset($existingSchedule->notes)) {
                $existingSchedule->notes = "Reassigned to driver {$driver->name} (ID: {$driver->id}) on " . now()->format('Y-m-d H:i:s');
            }
            $existingSchedule->save();
            
            Log::info("Updated existing schedule with new Driver {$driver->name} (ID: {$driver->id}) for Unit #{$unitId} on {$date->format('Y-m-d')} shift {$shift}");
        } else {
            // Create a new schedule
            $schedule = new Schedule();
            $schedule->unit_id = $unitId;
            $schedule->driver_id = $driver->id;
            $schedule->route_id = $routeId;
            $schedule->schedule_date = $date->format('Y-m-d');
            $schedule->shift = $shift;
            $schedule->status = 'active';
            $schedule->save();
            
            Log::info("Created new schedule with Driver {$driver->name} (ID: {$driver->id}) for Unit #{$unitId} on {$date->format('Y-m-d')} shift {$shift}");
        }
    }
}
