<?php

namespace App\Models;

/**
 * Documento vinculado a uma matrícula.
 * Controla o checklist de documentos obrigatórios/opcionais com suporte a upload digital.
 *
 * @property int         $id
 * @property int         $enrollment_id
 * @property string      $tipo
 * @property string      $status   entregue | pendente | dispensado
 * @property \Carbon\Carbon|null $data_entrega
 * @property int|null    $recebido_por_user_id
 * @property string|null $observacoes
 * @property string|null $arquivo_path
 * @property string|null $arquivo_nome_original
 */
class EnrollmentDocument extends BaseModel
{
    /**
     * Campos que podem ser preenchidos em massa pela aplicação.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'enrollment_id',
        'tipo',
        'status',
        'data_entrega',
        'recebido_por_user_id',
        'observacoes',
        'arquivo_path',
        'arquivo_nome_original',
    ];

    /**
     * Conversões automáticas de tipos dos atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data_entrega' => 'date',
    ];

    /**
     * Tipos de documentos aceitos pelo sistema.
     *
     * @var array<string, string>
     */
    public const TIPOS = [
        'rg'                       => 'RG / Documento de Identidade',
        'cpf'                      => 'CPF',
        'certidao_nascimento'      => 'Certidão de Nascimento',
        'comprovante_residencia'   => 'Comprovante de Residência',
        'historico_escolar'        => 'Histórico Escolar',
        'declaracao_transferencia' => 'Declaração de Transferência',
        'foto_3x4'                 => 'Foto 3x4',
        'atestado_saude'           => 'Atestado de Saúde / Vacinação',
        'laudo_medico'             => 'Laudo Médico (necessidades especiais)',
        'outros'                   => 'Outros',
    ];

    /**
     * Status possíveis do documento.
     *
     * @var array<string, string>
     */
    public const STATUS_OPTIONS = [
        'pendente'   => 'Pendente',
        'entregue'   => 'Entregue',
        'dispensado' => 'Dispensado',
    ];

    /**
     * Cores de exibição por status do documento.
     *
     * @var array<string, string>
     */
    public const STATUS_COLORS = [
        'pendente'   => 'warning',
        'entregue'   => 'success',
        'dispensado' => 'gray',
    ];

    /**
     * Retorna a matrícula vinculada ao documento.
     */
    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Retorna o usuário que recebeu o documento.
     */
    public function recebidoPor()
    {
        return $this->belongsTo(User::class, 'recebido_por_user_id');
    }

    /**
     * Retorna o rótulo legível do tipo de documento.
     */
    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    /**
     * Retorna o rótulo legível do status do documento.
     */
    public function statusLabel(): string
    {
        return self::STATUS_OPTIONS[$this->status] ?? $this->status;
    }

    /**
     * Indica se o documento possui arquivo digital anexado.
     */
    public function temArquivo(): bool
    {
        return ! empty($this->arquivo_path);
    }
}
