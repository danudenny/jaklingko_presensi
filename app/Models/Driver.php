<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'ktp',
        'kpp',
        'kk',
        'rekening',
        'type',
        'phone',
        'email',
        'status',
    ];

    /**
     * Get the units that the driver is qualified to drive.
     */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'driver_units', 'driver_id', 'unit_id');
    }

    /**
     * Get the routes assigned to the driver.
     */
    public function routes(): BelongsToMany
    {
        return $this->belongsToMany(Route::class, 'driver_routes', 'driver_id', 'route_id');
    }

    /**
     * Get the schedules for the driver.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Get the backup schedules for the driver.
     */
    public function backupSchedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'backup_driver_id');
    }

    /**
     * Get the leave requests for the driver.
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Scope a query to only include fixed drivers.
     */
    public function scopeBatangan($query)
    {
        return $query->where('type', 'batangan');
    }

    /**
     * Scope a query to only include non-fixed drivers.
     */
    public function scopeCadangan($query)
    {
        return $query->where('type', 'cadangan');
    }

    /**
     * Scope a query to only include active drivers.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }

    /**
     * Check if the driver is available for a specific date and shift.
     */
    public function isAvailableFor($date, $shift): bool
    {
        // Check if driver is active
        if ($this->status !== 'aktif') {
            return false;
        }

        // Check if driver has an approved leave request for this date
        $onLeave = $this->leaveRequests()
            ->where('status', 'approved')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();

        if ($onLeave) {
            return false;
        }

        // Check shift sequence constraint
        if ($shift === 'pagi') {
            // Cannot work morning shift if worked evening shift the previous day
            $previousDayEvening = $this->schedules()
                ->where('schedule_date', date('Y-m-d', strtotime($date . ' -1 day')))
                ->where('shift', 'siang')
                ->where('status', '!=', 'cuti')
                ->exists();

            if ($previousDayEvening) {
                return false;
            }
        }

        // Check if already assigned to this shift on this date
        $alreadyAssigned = $this->schedules()
            ->where('schedule_date', $date)
            ->where('shift', $shift)
            ->exists();

        return !$alreadyAssigned;
    }

    public function driverHistories(): HasMany
    {
        return $this->hasMany(DriverHistory::class);
    }

    public function getMonthlyHistory($month, $year): Collection
    {
        return $this->driverHistories()
            ->whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->get();
    }
}
