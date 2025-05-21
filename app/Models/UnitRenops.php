<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitRenops extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'date',
        'unit_id',
        'route_id',
        'day_type',
        'holiday_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the unit that is part of this renops plan.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the holiday associated with this renops plan (if applicable).
     */
    public function holiday(): BelongsTo
    {
        return $this->belongsTo(Holiday::class);
    }

    /**
     * Get the route associated with this renops plan.
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    /**
     * Scope a query to only include renops for a specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope a query to only include renops for a specific day type.
     */
    public function scopeForDayType($query, $dayType)
    {
        return $query->where('day_type', $dayType);
    }
}
