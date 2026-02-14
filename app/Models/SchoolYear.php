<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolYear extends Model
{
    protected $fillable = [
        'year',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_active' => 'boolean',
    ];

    public static function active()
    {
        return self::where('active', true)->first();
    }

    public function gradeLevel()
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public static function current()
    {
        return self::where('is_active', true)->first();
    }

}
