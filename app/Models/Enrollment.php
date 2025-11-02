<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'class_id', 'enrollment_date', 'roll_number', 'status',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'status'          => EnrollmentStatus::class,
    ];

    public function student() {
        return $this->belongsTo(Student::class);
    }

    public function class() {
        return $this->belongsTo(SchoolClass::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(\App\Models\SchoolClass::class, 'class_id');
    }

    // Próximo número de chamada dentro da turma
    public static function nextRollNumberFor(int $classId): int
    {
        $max = static::where('class_id', $classId)->max('roll_number');
        return (int) $max + 1;
    }

    // Se quiser garantir auto-preenchimento
    protected static function booted()
    {
        static::creating(function (self $enr) {
            if (empty($enr->enrollment_date)) {
                $enr->enrollment_date = now();
            }
            if (empty($enr->roll_number)) {
                $enr->roll_number = self::nextRollNumberFor((int) $enr->class_id);
            }
        });
    }
}
