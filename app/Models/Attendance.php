<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    // se o nome da tabela for 'attendances', não precisa declarar:
    // protected $table = 'attendances';

    protected $fillable = [
        'student_id',
        'class_id',
        'subject_id',
        'date',
        'status', // 'present' | 'absent' | 'late'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // Relações
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // tua tabela de turmas é 'classes'
    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    // Scopes úteis
    public function scopeForStudent($q, int $studentId)
    {
        return $q->where('student_id', $studentId);
    }

    public function scopeMonth($q, int $month)
    {
        return $q->whereMonth('date', $month);
    }

    public function scopeYear($q, int $year)
    {
        return $q->whereYear('date', $year);
    }
}
