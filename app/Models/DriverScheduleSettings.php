<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverScheduleSettings extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'driver_type',
        'min_schedules',
        'max_schedules',
        'period_days',
        'notes',
    ];

    /**
     * Get the settings for a specific driver type or create default if none exist.
     *
     * @param string $driverType
     * @return \App\Models\DriverScheduleSettings
     */
    public static function getSettingsForType(string $driverType)
    {
        $settings = self::where('driver_type', $driverType)->first();
        
        if (!$settings) {
            // Create default settings based on driver type
            $defaults = [
                'driver_type' => $driverType,
                'period_days' => 15,
            ];
            
            if ($driverType === 'batangan') {
                $defaults['min_schedules'] = 13;
                $defaults['max_schedules'] = 14;
            } else { // cadangan
                $defaults['min_schedules'] = 11;
                $defaults['max_schedules'] = 12;
            }
            
            $settings = self::create($defaults);
        }
        
        return $settings;
    }
    
    /**
     * Get all driver type settings or create defaults if none exist.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAllSettings()
    {
        $settings = self::all();
        
        if ($settings->isEmpty()) {
            // Create default settings for both driver types
            self::create([
                'driver_type' => 'batangan',
                'min_schedules' => 13,
                'max_schedules' => 14,
                'period_days' => 15,
            ]);
            
            self::create([
                'driver_type' => 'cadangan',
                'min_schedules' => 11,
                'max_schedules' => 12,
                'period_days' => 15,
            ]);
            
            $settings = self::all();
        }
        
        return $settings;
    }
    
    /**
     * Check if a driver meets the minimum schedule requirement for their type.
     *
     * @param \App\Models\Driver $driver
     * @param int $scheduleCount
     * @return bool
     */
    public static function meetsMinimumRequirement(Driver $driver, int $scheduleCount): bool
    {
        $settings = self::getSettingsForType($driver->type);
        return $scheduleCount >= $settings->min_schedules;
    }
    
    /**
     * Check if a driver exceeds the maximum schedule limit for their type.
     *
     * @param \App\Models\Driver $driver
     * @param int $scheduleCount
     * @return bool
     */
    public static function exceedsMaximumLimit(Driver $driver, int $scheduleCount): bool
    {
        $settings = self::getSettingsForType($driver->type);
        return $scheduleCount > $settings->max_schedules;
    }
}
