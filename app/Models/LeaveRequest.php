<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Driver;
use App\Models\User;
use App\Models\Schedule;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'start_date',
        'end_date',
        'type',
        'status',
        'reason',
        'admin_notes',
        'approved_by',
        'documentation',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the driver that owns the leave request.
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the admin who approved the leave request.
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if there are available backup drivers for all affected schedules.
     */
    public function hasAvailableBackups()
    {
        $driver = $this->driver;
        $startDate = $this->start_date;
        $endDate = $this->end_date;

        // Get all schedules for this driver within the leave period
        $affectedSchedules = Schedule::where('driver_id', $driver->id)
            ->where('schedule_date', '>=', $startDate)
            ->where('schedule_date', '<=', $endDate)
            ->get();

        // Check if each schedule has at least one available backup driver
        foreach ($affectedSchedules as $schedule) {
            $availableBackups = $schedule->findAvailableBackupDrivers();
            
            if ($availableBackups->isEmpty()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Scope a query to only include pending leave requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'requested');
    }

    /**
     * Scope a query to only include approved leave requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include rejected leave requests.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
