<?php

namespace App\Support;

/**
 * Regras das máscaras de numeração editadas em Parametrizações do Sistema.
 *
 * A escola escreve a máscara livremente; o sistema só exige o marcador {sequence}
 * (que garante números únicos), aceita apenas os marcadores de cada cadastro e
 * confere se o número gerado cabe na coluna do banco.
 */
class NumberFormatRules {

    /**
     * Marcadores aceitos por parâmetro, com o valor de exemplo usado na pré-visualização
     * (com o maior tamanho que o marcador pode assumir).
     *
     * @var array<string, array{label: string, max_length: int, sequence_digits: int, tokens: array<string, string>}>
     */
    public const FORMATS = [
        'class.number_format' => [
            'label'           => 'turma',
            'max_length'      => 20,
            'sequence_digits' => 2,
            'tokens'          => ['{year}' => '2026', '{grade}' => '01', '{shift}' => 'MAN', '{sequence}' => '01'],
        ],
        'student.number_format' => [
            'label'           => 'aluno',
            'max_length'      => 255,
            'sequence_digits' => 6,
            'tokens'          => ['{year}' => '2026', '{sequence}' => '000001'],
        ],
        'enrollment.number_format' => [
            'label'           => 'matrícula',
            'max_length'      => 20,
            'sequence_digits' => 6,
            'tokens'          => ['{year}' => '2026', '{sequence}' => '000001'],
        ],
        'teacher.number_format' => [
            'label'           => 'matrícula funcional',
            'max_length'      => 20,
            'sequence_digits' => 4,
            'tokens'          => ['{year}' => '2026', '{sequence}' => '0001'],
        ],
    ];

    /**
     * Sugestões exibidas no campo (as antigas opções fixas de cada parâmetro).
     *
     * @var array<string, array<int, string>>
     */
    public const SUGGESTIONS = [
        'class.number_format'      => ['TUR-{year}-{grade}-{sequence}', 'TUR-{year}-{sequence}', '{grade}-{shift}-{sequence}'],
        'student.number_format'    => ['ALU-{year}-{sequence}', 'ALU-{sequence}', '{year}-{sequence}'],
        'enrollment.number_format' => ['MAT-{year}-{sequence}', '{year}{sequence}', 'MAT-{sequence}'],
        'teacher.number_format'    => ['PROF-{sequence}', 'FUNC-{year}-{sequence}', '{sequence}'],
    ];

    /**
     * Indica se a chave é de uma máscara de numeração.
     *
     * @param string|null $key
     *
     * @return bool
     */
    public static function handles(?string $key): bool {
        return $key !== null && array_key_exists($key, self::FORMATS);
    }

    /**
     * Valida a máscara e devolve a mensagem de erro, ou null se estiver correta.
     *
     * @param string $key
     * @param string|null $format
     *
     * @return string|null
     */
    public static function error(string $key, ?string $format): ?string {
        $rules  = self::FORMATS[$key];
        $format = trim((string) $format);

        if ($format === '') {
            return 'Informe o formato do número.';
        }

        if (substr_count($format, '{sequence}') !== 1) {
            return 'O formato precisa ter o marcador {sequence} exatamente uma vez; é ele que garante números diferentes.';
        }

        preg_match_all('/\{[^}]*\}/', $format, $matches);
        $unknown = array_diff(array_unique($matches[0]), array_keys($rules['tokens']));

        if ($unknown !== []) {
            return 'Marcador não aceito para '.$rules['label'].': '.implode(', ', $unknown).'. Use apenas '.implode(', ', array_keys($rules['tokens'])).'.';
        }

        $literal = strtr($format, array_fill_keys(array_keys($rules['tokens']), ''));

        if (!preg_match('/^[A-Za-z0-9\-_\/.]*$/', $literal)) {
            return 'Use apenas letras sem acento, números e os separadores - _ / . fora dos marcadores.';
        }

        $length = mb_strlen(self::preview($key, $format));

        if ($length > $rules['max_length']) {
            return 'O número gerado teria '.$length.' caracteres; o limite para '.$rules['label'].' é '.$rules['max_length'].'.';
        }

        return null;
    }

    /**
     * Mostra como ficaria o número com os valores de exemplo.
     *
     * @param string $key
     * @param string|null $format
     *
     * @return string
     */
    public static function preview(string $key, ?string $format): string {
        return strtr(trim((string) $format), self::FORMATS[$key]['tokens']);
    }

    /**
     * Texto de ajuda com os marcadores aceitos.
     *
     * @param string $key
     *
     * @return string
     */
    public static function help(string $key): string {
        $descriptions = [
            '{year}'     => '{year} = ano',
            '{grade}'    => '{grade} = série (01 a 12)',
            '{shift}'    => '{shift} = turno (MAN, TAR, NOI)',
            '{sequence}' => '{sequence} = número sequencial (obrigatório)',
        ];

        $tokens = array_map(fn (string $token): string => $descriptions[$token], array_keys(self::FORMATS[$key]['tokens']));

        return 'Marcadores: '.implode(' · ', $tokens).'. Vale para novos cadastros; números já emitidos não mudam.';
    }

}
