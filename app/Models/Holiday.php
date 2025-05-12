<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Holiday extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'date',
        'name',
        'type',
        'description',
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
     * Scope a query to only include active holidays.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Get the formatted type.
     *
     * @return string
     */
    public function getFormattedTypeAttribute()
    {
        return $this->type === 'cuti_bersama' ? 'Cuti Bersama' : 'Libur Nasional';
    }

    /**
     * Get the unit renops plans associated with this holiday.
     */
    public function unitRenops(): HasMany
    {
        return $this->hasMany(UnitRenops::class);
    }
}
