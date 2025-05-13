<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenopsSettings extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'mode',
        'unit_type',
        'saturday_threshold',
        'sunday_threshold',
        'holiday_threshold',
        'notes',
    ];

    /**
     * Get the current settings or create default if none exist.
     *
     * @return \App\Models\RenopsSettings
     */
    public static function getCurrentSettings()
    {
        $settings = self::first();
        
        if (!$settings) {
            $settings = self::create([
                'mode' => 'manual',
                'unit_type' => 'all',
                'saturday_threshold' => 80.00,
                'sunday_threshold' => 70.00,
                'holiday_threshold' => 70.00,
            ]);
        }
        
        return $settings;
    }
    
    /**
     * Get the threshold for a specific day type.
     *
     * @param string $dayType
     * @return float
     */
    public function getThresholdForDayType(string $dayType): float
    {
        switch ($dayType) {
            case 'saturday':
                return $this->saturday_threshold;
            case 'sunday':
                return $this->sunday_threshold;
            case 'holiday':
                return $this->holiday_threshold;
            default:
                return 0;
        }
    }
    
    /**
     * Check if the mode is set to automatic.
     *
     * @return bool
     */
    public function isAutomatic(): bool
    {
        return $this->mode === 'automatic';
    }
}
