<?php

namespace App\Models;

/**
 * Log de auditoria de matrículas.
 * Registros imutáveis — sem updated_at.
 *
 * @property int         $id
 * @property int         $enrollment_id
 * @property int|null    $operador_id
 * @property string      $acao
 * @property string|null $status_anterior
 * @property string|null $status_novo
 * @property string|null $observacao
 * @property string|null $ip_origem
 * @property \Carbon\Carbon $created_at
 */
class EnrollmentLog extends BaseModel
{
    /**
     * Indica que logs são imutáveis e possuem somente created_at controlado manualmente.
     */
    public $timestamps = false;

    /**
     * Campos que podem ser preenchidos em massa pela aplicação.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'enrollment_id',
        'operador_id',
        'acao',
        'status_anterior',
        'status_novo',
        'observacao',
        'ip_origem',
        'created_at',
    ];

    /**
     * Conversões automáticas de tipos dos atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Retorna a matrícula relacionada ao log.
     */
    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Retorna o operador que registrou a ação.
     */
    public function operador()
    {
        return $this->belongsTo(User::class, 'operador_id');
    }

    /**
     * Registra uma entrada de auditoria para a matrícula.
     *
     * @param  Enrollment  $enrollment  Matrícula afetada.
     * @param  string  $acao  Tipo de ação, como trancamento ou cancelamento.
     * @param  string|null  $statusAnterior  Status antes da ação.
     * @param  string|null  $statusNovo  Status após a ação.
     * @param  string|null  $observacao  Justificativa do operador.
     */
    public static function registrar(
        Enrollment $enrollment,
        string $acao,
        ?string $statusAnterior = null,
        ?string $statusNovo = null,
        ?string $observacao = null,
    ): void {
        static::create([
            'enrollment_id'   => $enrollment->id,
            'operador_id'     => auth()->id(),
            'acao'            => $acao,
            'status_anterior' => $statusAnterior,
            'status_novo'     => $statusNovo,
            'observacao'      => $observacao,
            'ip_origem'       => request()->ip(),
            'created_at'      => now(),
        ]);
    }
}
