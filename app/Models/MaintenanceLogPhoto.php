<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceLogPhoto extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'maintenance_log_id',
        'photo_path',
    ];

    /**
     * Get the maintenance log that owns the photo.
     */
    public function maintenanceLog()
    {
        return $this->belongsTo(MaintenanceLog::class);
    }
}
