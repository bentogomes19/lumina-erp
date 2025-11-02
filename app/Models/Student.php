<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\StudentStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'user_id', 'registration_number', 'name', 'birth_date', 'gender', 'cpf', 'rg',
        'email', 'phone_number', 'address', 'city', 'state', 'postal_code',
        'birth_city', 'birth_state', 'nationality',
        'mother_name', 'father_name', 'guardian_main', 'guardian_phone', 'guardian_email',
        'transport_mode', 'has_special_needs', 'medical_notes', 'allergies',
        'status', 'status_changed_at', 'enrollment_date', 'exit_date', 'photo_url', 'meta',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'enrollment_date' => 'date',
        'exit_date' => 'date',
        'status_changed_at' => 'datetime',
        'meta' => 'array',
        'status' => StudentStatus::class,
        'gender' => Gender::class,
        'has_special_needs' => 'bool',
    ];


    // Relações principais
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function students()
    {
        return $this->belongsToMany(
            \App\Models\Student::class,
            'enrollments',
            'class_id',        // FK desta model na pivot
            'student_id'       // FK do model relacionado na pivot
        )
            ->withTimestamps();
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function classes()
    {
        return $this->belongsToMany(
            \App\Models\SchoolClass::class,
            'enrollments',     // tabela pivot
            'student_id',      // FK deste model na pivot
            'class_id'         // FK do model relacionado na pivot (não "school_class_id")
        )
            ->withTimestamps();
        // ->withPivot([...]) // se tiver colunas extras na pivot, liste aqui
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    protected static function booted()
    {
        static::creating(function ($s) {
            if (empty($s->uuid)) {
                $s->uuid = (string) Str::uuid();
            }

            // Gera matrícula se não enviada
            if (empty($s->registration_number)) {
                $s->registration_number = self::generateRegistrationNumber();
            }
        });
    }

    public static function generateRegistrationNumber(): string
    {
        // Formato: ALU-2025-000123 (evita colisão com loop)
        do {
            $code = 'ALU-' . now()->format('Y') . '-' . str_pad(random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('registration_number', $code)->exists());

        return $code;
    }

    // Scopes úteis
    public function scopeActive($q){ return $q->where('status', StudentStatus::ACTIVE->value); }
    public function scopeOfYear($q, int $year){
        return $q->whereYear('enrollment_date', $year);
    }

    // Acessores úteis
    public function getAgeAttribute(): ?int
    {
        if (! $this->birth_date) return null;
        return Carbon::parse($this->birth_date)->age;
    }

}
