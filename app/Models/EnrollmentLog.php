<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
class EnrollmentLog extends Model
{
    // Logs são imutáveis — somente created_at
    public $timestamps = false;

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

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // ── Relações ──────────────────────────────────────────────────────────────

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function operador()
    {
        return $this->belongsTo(User::class, 'operador_id');
    }

    // ── Helper estático ───────────────────────────────────────────────────────

    /**
     * Registra uma entrada de auditoria para a matrícula.
     *
     * @param  Enrollment   $enrollment    Matrícula afetada
     * @param  string       $acao          Tipo de ação (ex: 'trancamento', 'cancelamento')
     * @param  string|null  $statusAnterior Status antes da ação
     * @param  string|null  $statusNovo     Status após a ação
     * @param  string|null  $observacao     Justificativa do operador
     */
    public static function registrar(
        Enrollment $enrollment,
        string $acao,
        ?string $statusAnterior = null,
        ?string $statusNovo = null,
        ?string $observacao = null,
    ): void {
        static::create([
            'enrollment_id'  => $enrollment->id,
            'operador_id'    => auth()->id(),
            'acao'           => $acao,
            'status_anterior' => $statusAnterior,
            'status_novo'    => $statusNovo,
            'observacao'     => $observacao,
            'ip_origem'      => request()->ip(),
            'created_at'     => now(),
        ]);
    }
}
