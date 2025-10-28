<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'registration_number',
        'name',
        'birth_date',
        'gender',
        'cpf',
        'email',
        'phone_number',
        'address',
        'city',
        'state',
        'postal_code',
        'mother_name',
        'father_name',
        'status',
        'enrollment_date',
        'exit_date',
        'meta',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function classes()
    {
        return $this->belongsToMany(SchoolClass::class, 'enrollments');
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    protected static function booted()
    {
        static::creating(function ($student) {
            if (empty($student->uuid)) {
                $student->uuid = (string) Str::uuid();
            }

            if (empty($student->registration_number)) {
                $student->registration_number = 'ALU-' . str_pad(Student::count() + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }



}
