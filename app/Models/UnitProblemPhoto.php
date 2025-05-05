<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitProblemPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_problem_id',
        'photo_path',
    ];

    /**
     * Get the unit problem that owns the photo.
     */
    public function unitProblem(): BelongsTo
    {
        return $this->belongsTo(UnitProblem::class);
    }
}
