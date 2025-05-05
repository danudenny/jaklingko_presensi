<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitProblem extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'driver_id',
        'date_reported',
        'time_reported',
        'shift',
        'description',
        'location',
        'on_schedule',
        'schedule_history_id',
    ];

    protected $casts = [
        'date_reported' => 'date',
        'on_schedule' => 'boolean',
    ];

    /**
     * Get the unit associated with the problem.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the driver associated with the problem.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the schedule history associated with the problem.
     */
    public function scheduleHistory(): BelongsTo
    {
        return $this->belongsTo(DriverScheduleHistory::class, 'schedule_history_id');
    }

    /**
     * Get the photos for the unit problem.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(UnitProblemPhoto::class);
    }
}
