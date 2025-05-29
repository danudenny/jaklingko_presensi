<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchedulePattern extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'type',
        'driver_type',
        'days',
        'pattern',
        'description',
        'is_active'
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'pattern' => 'array',
        'is_active' => 'boolean',
        'days' => 'integer'
    ];
    
    /**
     * Scope a query to only include valid patterns.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValid($query)
    {
        return $query->where('type', 'valid');
    }
    
    /**
     * Scope a query to only include invalid patterns.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInvalid($query)
    {
        return $query->where('type', 'invalid');
    }
    
    /**
     * Scope a query to only include active patterns.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Get patterns for a specific driver type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $driverType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForDriverType($query, $driverType)
    {
        return $query->where(function($q) use ($driverType) {
            $q->where('driver_type', $driverType)
              ->orWhere('driver_type', 'all');
        });
    }
}
