<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverHistory extends Model
{
    protected $fillable = [
        'driver_id',
        'unit_id',
        'shift',
        'as_backup',
        'start_date',
        'end_date',
        'as_renops',
        'on_leave',
        'on_duty',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'as_backup' => 'boolean',
        'as_renops' => 'boolean',
        'on_leave' => 'boolean',
        'on_duty' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the driver that owns the history.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the unit that owns the history.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
