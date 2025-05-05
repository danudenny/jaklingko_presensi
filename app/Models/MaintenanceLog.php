<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'unit_id',
        'route_id',
        'driver_id',
        'date_reported',
        'time_reported',
        'description',
        'type',
        'parts',
        'category',
        'source_of_sparepart',
        'costs',
        'status',
        'on_schedule',
        'schedule_history_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date_reported' => 'date',
        'time_reported' => 'datetime:H:i',
        'costs' => 'json',
        'on_schedule' => 'boolean',
    ];

    /**
     * Get the unit that owns the maintenance log.
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the route that owns the maintenance log.
     */
    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    /**
     * Get the driver that owns the maintenance log.
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the schedule history that owns the maintenance log.
     */
    public function scheduleHistory()
    {
        return $this->belongsTo(DriverScheduleHistory::class, 'schedule_history_id');
    }

    /**
     * Get the photos for the maintenance log.
     */
    public function photos()
    {
        return $this->hasMany(MaintenanceLogPhoto::class);
    }
}
