<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'cpf',
        'rg',
        'birth_date',
        'gender',
        'address',
        'district',
        'city',
        'state',
        'postal_code',
        'phone',
        'cellphone',
        'avatar',
        'active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Gera UUID automaticamente ao criar o registro.
     */
    protected static function booted()
    {
        static::creating(function ($user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });

        static::saving(function ($user) {
            if ($user->name) {
                $user->name = mb_strtoupper($user->name, 'UTF-8');
            }
        });

        // 🔥 Após criar ou atualizar um usuário
        static::saved(function ($user) {
            $roleName = $user->roles()->pluck('name')->first();

            if ($roleName === 'student') {
                Student::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'uuid'               => $user->uuid,
                        'name'               => $user->name,
                        'email'              => $user->email,
                        'cpf'                => $user->cpf,
                        'birth_date'         => $user->birth_date,
                        // MAPA DE GÊNERO: aceita 'Masculino'/'Feminino'/'Outro' OU 'M'/'F'/'O'
                        'gender'             => match ((string) $user->gender) {
                            'Masculino', 'M' => Gender::M->value,
                            'Feminino',  'F' => Gender::F->value,
                            'Outro',     'O' => Gender::O->value,
                            default          => null,
                        },
                        'address'            => $user->address,
                        'city'               => $user->city,
                        'state'              => $user->state,
                        'postal_code'        => $user->postal_code,
                        'phone_number'       => $user->cellphone ?? $user->phone,
                        // AQUI ESTAVA O PROBLEMA: NUNCA use o rótulo "Ativo"
                        'status'             => $user->active
                            ? StudentStatus::ACTIVE->value
                            : StudentStatus::INACTIVE->value,
                        // Se você quer deixar a matrícula ser gerada pelo boot(), remova esta linha.
                        // 'registration_number' => Student::generateRegistrationNumber(),
                    ]
                );
            }
        });
    }

    /**
     * Relacionamento 1:1 com Student.
     */
    public function student()
    {
        return $this->hasOne(Student::class, 'user_id', );
    }

    /**
     * Relacionamento 1:1 com Teacher.
     */
    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    /**
     * Exibe o nome completo + papel (para relatórios ou listagem)
     */
    public function getDisplayNameAttribute(): string
    {
        $role = $this->roles()->pluck('name')->first();
        return "{$this->name}" . ($role ? " ({$role})" : '');
    }
}
