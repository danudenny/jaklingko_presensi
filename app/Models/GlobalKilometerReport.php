<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalKilometerReport extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    
    protected $fillable = [
        'driver_id',
        'unit_id',
        'route_id',
        'report_date',
        'kilometers',
        'period',
        'month',
        'year',
        'driver_count',
        'notes',
        'shift', // Adding shift to fillable array
    ];

    protected $casts = [
        'report_date' => 'date',
        'kilometers' => 'float',
        'period' => 'integer',
        'month' => 'integer',
        'year' => 'integer',
        'driver_count' => 'integer',
    ];

    /**
     * Get the driver that owns the kilometer report.
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the unit that owns the kilometer report.
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the route that owns the kilometer report.
     */
    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    /**
     * Scope a query to only include reports for a specific period.
     */
    public function scopeForPeriod($query, $period, $month, $year)
    {
        return $query->where('period', $period)
                     ->where('month', $month)
                     ->where('year', $year);
    }

    /**
     * Scope a query to only include reports for a specific route.
     */
    public function scopeForRoute($query, $routeId)
    {
        return $query->where('route_id', $routeId);
    }

    /**
     * Scope a query to only include reports for a specific route group.
     */
    public function scopeForRouteGroup($query, $routeGroup)
    {
        return $query->whereHas('route', function($q) use ($routeGroup) {
            $q->where('route_number', $routeGroup);
        });
    }
}
