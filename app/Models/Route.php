<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Route extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'route_number',
        'name',
        'status',
    ];


    /**
     * Get the schedules for this route.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Get the units associated with this route.
     */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'unit_routes', 'route_id', 'unit_id');
    }

    /**
     * Get the drivers assigned to this route.
     */
    public function drivers(): BelongsToMany
    {
        return $this->belongsToMany(Driver::class, 'driver_routes', 'route_id', 'driver_id');
    }

    /**
     * Scope a query to only include active routes.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }
}
