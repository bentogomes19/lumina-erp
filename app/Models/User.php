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

    /** Número máximo de tentativas antes do bloqueio automático */
    public const MAX_LOGIN_ATTEMPTS = 5;

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
        'force_password_change',
        'login_attempts',
        'locked_at',
        'inactive_reason',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'     => 'datetime',
            'password'              => 'hashed',
            'birth_date'            => 'date',
            'active'                => 'boolean',
            'last_login_at'         => 'datetime',
            'force_password_change' => 'boolean',
            'login_attempts'        => 'integer',
            'locked_at'             => 'datetime',
        ];
    }

    protected static function booted()
    {
        static::creating(function ($user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });

        static::saved(function ($user) {
            $roleName = $user->roles()->pluck('name')->first();

            if ($roleName === 'student') {
                Student::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'uuid'         => $user->uuid,
                        'name'         => $user->name,
                        'email'        => $user->email,
                        'cpf'          => $user->cpf,
                        'birth_date'   => $user->birth_date,
                        'gender'       => match ((string) $user->gender) {
                            'Masculino', 'M' => Gender::M->value,
                            'Feminino',  'F' => Gender::F->value,
                            'Outro',     'O' => Gender::O->value,
                            default          => null,
                        },
                        'address'      => $user->address,
                        'city'         => $user->city,
                        'state'        => $user->state,
                        'postal_code'  => $user->postal_code,
                        'phone_number' => $user->cellphone ?? $user->phone,
                        'status'       => $user->active
                            ? StudentStatus::ACTIVE->value
                            : StudentStatus::INACTIVE->value,
                    ]
                );
            }

            if ($roleName === 'teacher') {
                Teacher::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'uuid'  => $user->uuid,
                        'name'  => $user->name,
                        'email' => $user->email,
                        'cpf'   => $user->cpf,
                        'phone' => $user->cellphone ?? $user->phone,
                    ]
                );
            }
        });
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    public function getIsLockedAttribute(): bool
    {
        return ! is_null($this->locked_at);
    }

    public function getDisplayNameAttribute(): string
    {
        $role = $this->roles()->pluck('name')->first();
        return "{$this->name}" . ($role ? " ({$role})" : '');
    }

    // ─── Métodos de negócio ───────────────────────────────────────────────────

    /**
     * Registra tentativa de login falha e bloqueia se atingir o limite.
     */
    public function registerFailedLogin(): void
    {
        $attempts = $this->login_attempts + 1;
        $data = ['login_attempts' => $attempts];

        if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
            $data['locked_at'] = now();
        }

        $this->updateQuietly($data);
    }

    /**
     * Registra login bem-sucedido.
     */
    public function registerSuccessfulLogin(): void
    {
        $this->updateQuietly([
            'last_login_at'  => now(),
            'login_attempts' => 0,
        ]);
    }

    /**
     * Desbloqueia o usuário. Apenas perfil TI pode executar esta ação.
     */
    public function unlock(): void
    {
        $this->updateQuietly([
            'locked_at'      => null,
            'login_attempts' => 0,
        ]);
    }

    /**
     * Gera senha temporária, força troca no próximo acesso.
     * Retorna a senha em texto para exibição ao administrador.
     */
    public function resetToTemporaryPassword(): string
    {
        $tempPassword = Str::upper(Str::random(3)) . rand(10, 99) . Str::random(3);

        $this->updateQuietly([
            'password'              => bcrypt($tempPassword),
            'force_password_change' => true,
            'login_attempts'        => 0,
            'locked_at'             => null,
        ]);

        return $tempPassword;
    }

    // ─── Relacionamentos ─────────────────────────────────────────────────────

    public function student()
    {
        return $this->hasOne(Student::class, 'user_id');
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }
}
