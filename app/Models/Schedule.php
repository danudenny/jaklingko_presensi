<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'route_id',
        'unit_id',
        'schedule_date',
        'shift',
        'backup_driver_id',
        'notes',
        'status',
    ];

    protected $casts = [
        'schedule_date' => 'date',
    ];

    /**
     * Get the driver that owns the schedule.
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the backup driver for the schedule.
     */
    public function backupDriver()
    {
        return $this->belongsTo(Driver::class, 'backup_driver_id');
    }

    /**
     * Get the route associated with the schedule.
     */
    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    /**
     * Get the unit associated with the schedule.
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Scope a query to only include schedules for a specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('schedule_date', $date);
    }

    /**
     * Scope a query to only include schedules for a specific shift.
     */
    public function scopeForShift($query, $shift)
    {
        return $query->where('shift', $shift);
    }

    /**
     * Find available backup drivers for this schedule.
     */
    public function findAvailableBackupDrivers()
    {
        $date = $this->schedule_date;
        $shift = $this->shift;

        // First try to find batangan drivers
        $batanganDrivers = Driver::batangan()
            ->active()
            ->whereHas('units', function ($query) {
                $query->where('units.id', $this->unit_id);
            })
            ->whereDoesntHave('schedules', function ($query) use ($date) {
                $query->where('schedule_date', $date);
            })
            ->get()
            ->filter(function ($driver) use ($date, $shift) {
                return $driver->isAvailableFor($date, $shift);
            });

        if ($batanganDrivers->isNotEmpty()) {
            return $batanganDrivers;
        }

        // If no batangan drivers are available, try cadangan drivers
        $cadanganDrivers = Driver::cadangan()
            ->active()
            ->whereHas('units', function ($query) {
                $query->where('units.id', $this->unit_id);
            })
            ->whereDoesntHave('schedules', function ($query) use ($date) {
                $query->where('schedule_date', $date);
            })
            ->get()
            ->filter(function ($driver) use ($date, $shift) {
                return $driver->isAvailableFor($date, $shift);
            });

        return $cadanganDrivers;
    }

    /**
     * Check if a driver can be assigned to this schedule based on shift constraints
     * 
     * @param Driver $driver
     * @return bool
     */
    public function canAssignDriver(Driver $driver)
    {
        // Check if driver is active
        if ($driver->status !== 'aktif') {
            return false;
        }

        // Check if driver is on leave
        $onLeave = $driver->leaveRequests()
            ->where('status', 'approved')
            ->where('start_date', '<=', $this->schedule_date)
            ->where('end_date', '>=', $this->schedule_date)
            ->exists();

        if ($onLeave) {
            return false;
        }

        // Check if already assigned to any shift on this date
        $alreadyAssigned = $driver->schedules()
            ->where('schedule_date', $this->schedule_date)
            ->where('id', '!=', $this->id) // Exclude current schedule if updating
            ->exists();

        if ($alreadyAssigned) {
            return false;
        }

        // Check shift sequence constraint
        if ($this->shift === 'pagi') {
            $previousDay = Carbon::parse($this->schedule_date)->subDay()->format('Y-m-d');
            $previousDaySchedule = $driver->schedules()
                ->where('schedule_date', $previousDay)
                ->first();

            if ($previousDaySchedule && $previousDaySchedule->shift === 'siang') {
                // If previous shift was 'siang', cannot assign 'pagi' for next day
                return false;
            }
        }

        return true;
    }

    /**
     * Create a driver history record for this schedule
     * 
     * @param bool $asBackup
     * @return DriverHistory
     */
    public function createDriverHistory($asBackup = false)
    {
        return DriverHistory::create([
            'driver_id' => $asBackup ? $this->backup_driver_id : $this->driver_id,
            'unit_id' => $this->unit_id,
            'shift' => $this->shift,
            'as_backup' => $asBackup,
            'start_date' => $this->schedule_date,
            'end_date' => $this->schedule_date,
            'as_renops' => false,
            'on_leave' => false,
            'on_duty' => true,
        ]);
    }

    /**
     * Auto-generate schedules for a date range
     * 
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public static function autoGenerate($startDate, $endDate)
    {
        $service = app()->make(\App\Services\ScheduleGeneratorService::class);
        return $service->generateSchedules($startDate, $endDate);
    }
}
