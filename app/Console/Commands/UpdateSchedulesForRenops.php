<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UnitRenops;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateSchedulesForRenops extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedules:update-for-renops {--start-date= : Start date (YYYY-MM-DD)} {--end-date= : End date (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update schedules based on units in renops for a date range';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startDate = $this->option('start-date') ? Carbon::parse($this->option('start-date')) : Carbon::today();
        $endDate = $this->option('end-date') ? Carbon::parse($this->option('end-date')) : Carbon::today()->addDays(30);

        $this->info("Checking schedules for units in renops from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");

        // Get all renops entries in the date range
        $renopsEntries = UnitRenops::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        if ($renopsEntries->isEmpty()) {
            $this->info("No renops entries found for the specified date range.");
            return;
        }

        $this->info("Found {$renopsEntries->count()} renops entries to process.");
        
        $totalRemoved = 0;

        // Process each renops entry
        foreach ($renopsEntries as $renops) {
            $date = $renops->date;
            $unitId = $renops->unit_id;
            
            // Find schedules for this unit on this date
            $schedules = Schedule::where('unit_id', $unitId)
                ->whereDate('schedule_date', $date->format('Y-m-d'))
                ->get();
            
            if ($schedules->isNotEmpty()) {
                $count = $schedules->count();
                $totalRemoved += $count;
                
                $this->info("Removing {$count} schedule(s) for Unit #{$unitId} on {$date->format('Y-m-d')} due to renops assignment");
                
                // Delete the schedules
                foreach ($schedules as $schedule) {
                    // You could also change status instead of deleting
                    // $schedule->update(['status' => 'cancelled']);
                    $schedule->delete();
                }
            }
        }

        $this->info("Completed! Removed a total of {$totalRemoved} schedules for units in renops.");
    }
}
