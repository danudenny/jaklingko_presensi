<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KilometerReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'route_id',
        'date',
        'kilometers',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'kilometers' => 'float',
    ];

    /**
     * Get the unit that owns the kilometer report.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the route that owns the kilometer report.
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    /**
     * Scope a query to only include reports for a specific unit.
     */
    public function scopeForUnit($query, $unitId)
    {
        return $query->where('unit_id', $unitId);
    }

    /**
     * Scope a query to only include reports for a specific date range.
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
}
