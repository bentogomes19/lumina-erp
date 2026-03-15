<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'class_id',
        'school_year_id',
        'registration_number',
        'enrollment_date',
        'roll_number',
        'status',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'status'          => EnrollmentStatus::class,
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function class()
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    /** Próximo número de chamada dentro da turma */
    public static function nextRollNumberFor(int $classId): int
    {
        $max = static::where('class_id', $classId)->max('roll_number');
        return (int) $max + 1;
    }

    /** Gera registration_number no formato ANO + ID com 6 dígitos (ex: 2025000042) */
    public static function generateRegistrationNumber(self $enr): string
    {
        $year = $enr->schoolYear?->year
            ?? SchoolYear::where('id', $enr->school_year_id)->value('year')
            ?? now()->year;

        return $year . str_pad($enr->id, 6, '0', STR_PAD_LEFT);
    }

    protected static function booted()
    {
        static::creating(function (self $enr) {
            if (empty($enr->enrollment_date)) {
                $enr->enrollment_date = now();
            }
            if (empty($enr->roll_number)) {
                $enr->roll_number = self::nextRollNumberFor((int) $enr->class_id);
            }
            // Herda school_year_id da turma se não informado
            if (empty($enr->school_year_id) && $enr->class_id) {
                $enr->school_year_id = SchoolClass::find($enr->class_id)?->school_year_id;
            }
        });

        // registration_number só pode ser gerado após ter o ID (no created)
        static::created(function (self $enr) {
            if (empty($enr->registration_number)) {
                $enr->updateQuietly([
                    'registration_number' => self::generateRegistrationNumber($enr),
                ]);
            }
        });

        // Impede alteração do registration_number após criação
        static::updating(function (self $enr) {
            if ($enr->isDirty('registration_number') && $enr->getOriginal('registration_number')) {
                $enr->registration_number = $enr->getOriginal('registration_number');
            }
        });
    }
}
