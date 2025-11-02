<?php

namespace App\Models;

use App\Enums\EducationStage;
use Illuminate\Database\Eloquent\Model;

class GradeLevel extends Model
{
    protected $fillable = ['name', 'stage', 'display_order', 'description'];

    protected $casts = [
        'stage' => EducationStage::class,
    ];

    public function classes()
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function schoolYears()
    {
        return $this->hasMany(SchoolYear::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    public function subjects()
    {
        return $this->belongsToMany(\App\Models\Subject::class)
            ->withPivot('hours_weekly')
            ->withTimestamps();
    }
}
