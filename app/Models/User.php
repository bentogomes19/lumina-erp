<?php

namespace App\Models;

use App\Support\AdministrativeDashboardAccess;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser {

    use HasFactory;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;

    /**
     * Os papéis e permissões do ERP são armazenados sob o guard de autorização web,
     * mesmo quando o usuário autentica por uma sessão de portal separada.
     */
    protected string $guard_name = 'web';

    /**
     * Número máximo de tentativas antes do bloqueio automático.
     */
    public const MAX_LOGIN_ATTEMPTS = 5;

    /**
     * Campos que podem ser preenchidos em massa pela aplicação.
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
        'force_password_change',
        'login_attempts',
        'locked_at',
        'inactive_reason',
    ];

    /**
     * Campos ocultos em serializações.
     *
     * @var array<int, string>
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * Retorna as conversões automáticas de tipos dos atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array {
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

    /**
     * Configura os valores automáticos usados na criação do usuário.
     *
     * @return void
     */
    protected static function booted(): void {
        static::creating(function ($user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });

    }

    /**
     * Indica se o usuário está bloqueado.
     *
     * @return bool
     */
    public function getIsLockedAttribute(): bool {
        return !is_null($this->locked_at);
    }

    /**
     * Retorna o nome do usuário com o perfil principal para exibição.
     *
     * @return string
     */
    public function getDisplayNameAttribute(): string {
        $role = $this->roles()->pluck('name')->first();

        return "{$this->name}".($role ? " ({$role})" : '');
    }

    /**
     * Registra tentativa de login falha e bloqueia se atingir o limite.
     *
     * @return void
     */
    public function registerFailedLogin(): void {
        DB::transaction(function (): void {
            $user = self::query()->lockForUpdate()->find($this->getKey());

            if (!$user || $user->is_locked) {
                return;
            }

            $attempts = min($user->login_attempts + 1, self::MAX_LOGIN_ATTEMPTS);
            $data     = ['login_attempts' => $attempts];

            if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
                $data['locked_at'] = now();
            }

            $user->updateQuietly($data);
            $this->setRawAttributes($user->getAttributes(), true);
        });
    }

    /**
     * Registra login bem-sucedido.
     *
     * @return void
     */
    public function registerSuccessfulLogin(): void {
        $this->updateQuietly([
            'last_login_at'  => now(),
            'login_attempts' => 0,
        ]);
    }

    /**
     * Desbloqueia o usuário e registra a ação administrativa de segurança.
     *
     * @return void
     */
    public function unlock(): void {
        $this->updateQuietly([
            'locked_at'      => null,
            'login_attempts' => 0,
        ]);

        Log::notice('security.user.unlocked', [
            'target_user_id' => $this->getKey(),
            'actor_user_id'  => auth()->id(),
            'ip'             => request()->ip(),
        ]);
    }

    /**
     * Indica se o usuário pode acessar o painel Filament.
     *
     * @param Panel $panel
     *
     * @return bool
     */
    public function canAccessPanel(Panel $panel): bool {
        if (!$this->active || $this->is_locked) {
            return false;
        }

        return match ($panel->getId()) {
            'aluno' => $this->hasRole('student')
                && $this->student()->where('status', 'active')->exists(),
            'professor' => $this->hasRole('teacher') && (bool) $this->teacher?->canAccessOperationally(),
            'lumina' => AdministrativeDashboardAccess::hasAdministrativeRole($this),
            default => false,
        };
    }

    /**
     * Inativa somente a conta de acesso do usuário.
     *
     * @param string $reason
     *
     * @return void
     */
    public function inactivate(string $reason): void {
        $this->update([
            'active'          => false,
            'inactive_reason' => $reason,
        ]);
    }

    /**
     * Reativa somente a conta de acesso e remove bloqueios de login.
     *
     * @return void
     */
    public function activate(): void {
        $this->update([
            'active'          => true,
            'inactive_reason' => null,
            'locked_at'       => null,
            'login_attempts'  => 0,
        ]);
    }

    /**
     * Retorna o aluno vinculado ao usuário.
     *
     * @return HasOne
     */
    public function student(): HasOne {
        return $this->hasOne(Student::class, 'user_id');
    }

    /**
     * Retorna o professor vinculado ao usuário.
     *
     * @return HasOne
     */
    public function teacher(): HasOne {
        return $this->hasOne(Teacher::class);
    }

}
