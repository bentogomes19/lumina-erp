<?php

namespace Tests\Feature\SchoolClasses;

use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\SystemParameter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolClassCodeGenerationTest extends TestCase {

    use RefreshDatabase;

    /**
     * Gera códigos sequenciais pelo formato padrão quando o campo vem vazio.
     *
     * @return void
     */
    public function test_generates_sequential_code_with_default_format(): void {
        $this->classFormat('TUR-{year}-{grade}-{sequence}');
        $year = SchoolYear::factory()->forYear(2026)->create();

        $first  = $this->createClass(['school_year_id' => $year->id]);
        $second = $this->createClass(['school_year_id' => $year->id]);

        $this->assertSame('TUR-2026-05-01', $first->code);
        $this->assertSame('TUR-2026-05-02', $second->code);
    }

    /**
     * Mantém a contagem separada por prefixo e respeita o formato com turno.
     *
     * @return void
     */
    public function test_uses_shift_format_and_counts_per_prefix(): void {
        $this->classFormat('{grade}-{shift}-{sequence}');

        $morning   = $this->createClass(['shift' => 'morning']);
        $afternoon = $this->createClass(['shift' => 'afternoon']);
        $morning2  = $this->createClass(['shift' => 'morning']);

        $this->assertSame('05-MAN-01', $morning->code);
        $this->assertSame('05-TAR-01', $afternoon->code);
        $this->assertSame('05-MAN-02', $morning2->code);
    }

    /**
     * Não reutiliza o número de uma turma excluída.
     *
     * @return void
     */
    public function test_does_not_reuse_sequence_of_deleted_class(): void {
        $this->classFormat('TUR-{year}-{sequence}');
        $year = SchoolYear::factory()->forYear(2026)->create();

        $this->createClass(['school_year_id' => $year->id])->delete();
        $next = $this->createClass(['school_year_id' => $year->id]);

        $this->assertSame('TUR-2026-02', $next->code);
    }

    /**
     * Preserva o código digitado pelo usuário.
     *
     * @return void
     */
    public function test_keeps_code_typed_by_user(): void {
        $this->classFormat('TUR-{year}-{grade}-{sequence}');

        $class = $this->createClass(['code' => '1A-MAN-2026']);

        $this->assertSame('1A-MAN-2026', $class->code);
    }

    /**
     * Não gera código quando o parâmetro está desativado.
     *
     * @return void
     */
    public function test_does_not_generate_when_parameter_is_inactive(): void {
        $this->classFormat('TUR-{year}-{grade}-{sequence}', active: false);

        $class = $this->createClass();

        $this->assertNull($class->code);
    }

    /**
     * Cria uma turma sem código (a fábrica sempre preenche um).
     *
     * @param array<string, mixed> $attributes
     *
     * @return SchoolClass
     */
    private function createClass(array $attributes = []): SchoolClass {
        static $sequence = 0;
        $sequence++;

        /* O nome varia porque turma, série, ano letivo e turno formam uma chave única. */
        return SchoolClass::factory()
            ->create(array_merge(['code' => null, 'name' => "Turma {$sequence}"], $attributes))
            ->refresh();
    }

    /**
     * Registra o parâmetro de formato do código da turma.
     *
     * @param string $format
     * @param bool $active
     *
     * @return void
     */
    private function classFormat(string $format, bool $active = true): void {
        SystemParameter::create([
            'key'           => 'class.number_format',
            'name'          => 'Formato do número da turma',
            'category'      => 'Matrículas e cadastros',
            'type'          => 'select',
            'value'         => $format,
            'default_value' => 'TUR-{year}-{grade}-{sequence}',
            'description'   => 'Regra que define o código automático de cada turma.',
            'is_active'     => $active,
            'is_system'     => true,
        ]);
    }

}
