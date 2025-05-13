<?php

namespace App\Observers;

use App\Models\UnitRenops;
use App\Models\Schedule;
use Illuminate\Support\Facades\Log;

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
        $this->removeExistingSchedules($unitRenops);
    }

    /**
     * Remove any existing schedules for this unit on this date
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
            ->get();
        
        if ($schedules->isNotEmpty()) {
            $count = $schedules->count();
            
            // Log the action
            Log::info("Removing {$count} schedule(s) for Unit #{$unitId} on {$date->format('Y-m-d')} due to renops assignment");
            
            // Delete the schedules
            foreach ($schedules as $schedule) {
                // You could also change status instead of deleting
                // $schedule->update(['status' => 'cancelled']);
                $schedule->delete();
            }
        }
    }
}
