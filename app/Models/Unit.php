<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Driver;
use App\Models\Route;
use App\Models\Schedule;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'unit_number',
        'plate_number',
        'unit_reg',
        'serial_number',
        'kir',
        'expired_stnk',
        'expired_kir',
        'expired_kp',
        'status',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'expired_stnk' => 'date',
        'expired_kir' => 'date',
        'expired_kp' => 'date',
    ];

    /**
     * Get the drivers qualified to drive this unit.
     */
    public function drivers(): BelongsToMany
    {
        return $this->belongsToMany(Driver::class, 'driver_units');
    }

    /**
     * Get the routes for this unit.
     */
    public function routes(): BelongsToMany
    {
        return $this->belongsToMany(Route::class, 'unit_routes', 'unit_id', 'route_id');
    }

    /**
     * Get the schedules for this unit.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Scope a query to only include active units.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }
}
