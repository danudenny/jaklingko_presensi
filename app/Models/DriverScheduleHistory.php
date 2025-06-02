<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Driver;

class DriverScheduleHistory extends Model
{
    protected $table = 'driver_schedule_history';
    
    protected $fillable = [
        'driver_id',
        'period_start_date',
        'period_end_date',
        'total_schedules',
        'morning_shifts',
        'afternoon_shifts',
        'target_count',
        'target_met',
    ];
    
    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'total_schedules' => 'integer',
        'morning_shifts' => 'integer',
        'afternoon_shifts' => 'integer',
        'target_count' => 'integer',
        'target_met' => 'boolean',
    ];
    
    /**
     * Get the driver that owns the schedule history.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
    
    /**
     * Increment the schedule count for a driver in a specific period
     *
     * @param int $driverId
     * @param string $periodStartDate
     * @param string $periodEndDate
     * @return DriverScheduleHistory
     */
    public static function incrementScheduleCount(int $driverId, string $periodStartDate, string $periodEndDate): self
    {
        $history = self::firstOrCreate(
            [
                'driver_id' => $driverId,
                'period_start_date' => $periodStartDate,
                'period_end_date' => $periodEndDate,
            ],
            [
                'total_schedules' => 0,
                'morning_shifts' => 0,
                'afternoon_shifts' => 0,
                'target_count' => 14,
                'target_met' => false,
            ]
        );
        
        $history->total_schedules += 1;
        $history->target_met = $history->total_schedules >= $history->target_count;
        $history->save();
        
        return $history;
    }
    
    /**
     * Get drivers that haven't met their target in the current period
     *
     * @param string $periodStartDate
     * @param string $periodEndDate
     * @param int $targetCount
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getDriversBelowTarget(string $periodStartDate, string $periodEndDate, int $targetCount = 14)
    {
        return self::where('period_start_date', $periodStartDate)
            ->where('period_end_date', $periodEndDate)
            ->where('total_schedules', '<', $targetCount)
            ->where('target_met', false)
            ->with('driver')
            ->get();
    }
    
    /**
     * Recalculate the total schedules for a driver in a specific period
     * based only on schedules with 'scheduled' status
     *
     * @param int $driverId
     * @param string $periodStartDate
     * @param string $periodEndDate
     * @return self|null
     */
    public static function recalculateScheduleCount(int $driverId, string $periodStartDate, string $periodEndDate): ?self
    {
        // Find or create the history record
        $history = self::firstOrCreate(
            [
                'driver_id' => $driverId,
                'period_start_date' => $periodStartDate,
                'period_end_date' => $periodEndDate,
            ],
            [
                'total_schedules' => 0,
                'morning_shifts' => 0,
                'afternoon_shifts' => 0,
                'target_count' => 14,
                'target_met' => false,
            ]
        );
        
        // Count only schedules with 'scheduled' status
        $scheduleCounts = \App\Models\Schedule::where('driver_id', $driverId)
            ->whereBetween('schedule_date', [$periodStartDate, $periodEndDate])
            ->where('status', 'scheduled')
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN shift = "Pagi" THEN 1 ELSE 0 END) as morning, SUM(CASE WHEN shift = "Siang" THEN 1 ELSE 0 END) as afternoon')
            ->first();
        
        if ($scheduleCounts) {
            $history->total_schedules = $scheduleCounts->total ?? 0;
            $history->morning_shifts = $scheduleCounts->morning ?? 0;
            $history->afternoon_shifts = $scheduleCounts->afternoon ?? 0;
            
            // Get the driver to determine the correct target count
            $driver = \App\Models\Driver::find($driverId);
            $targetCount = 14; // Default
            
            if ($driver) {
                // Apply different thresholds based on driver type
                $targetCount = $driver->is_fixed ? 13 : 11; // Based on driver type thresholds from memory
            }
            
            $history->target_count = $targetCount;
            $history->target_met = $history->total_schedules >= $targetCount;
            $history->save();
        }
        
        return $history;
    }
}
