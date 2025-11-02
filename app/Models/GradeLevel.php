<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeLevel extends Model
{
    protected $fillable = ['name','order'];

    public function classes()
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function schoolYears()
    {
        return $this->hasMany(SchoolYear::class);
    }
}
