# Sistema de Dias Letivos - Lumina ERP

## Visão Geral

O sistema de dias letivos gerencia feriados, recessos escolares e eventos que impedem aulas regulares, garantindo que o cálculo de frequências e planejamento de aulas considere apenas dias efetivamente letivos.

## Tabela `school_holidays`

### Estrutura
- **school_year_id**: Ano letivo relacionado
- **name**: Nome do feriado/recesso
- **description**: Descrição detalhada
- **start_date**: Data de início
- **end_date**: Data de término
- **type**: Tipo de dia não letivo
- **is_active**: Status ativo/inativo

### Tipos de Dias Não Letivos

```php
enum HolidayType {
    NATIONAL_HOLIDAY    // Feriado Nacional
    STATE_HOLIDAY       // Feriado Estadual
    MUNICIPAL_HOLIDAY   // Feriado Municipal
    SCHOOL_RECESS       // Recesso Escolar
    SCHOOL_EVENT        // Evento Escolar (sem aula)
    EXAM_PERIOD         // Período de Provas
    OTHER               // Outro
}
```

## Uso Básico

### 1. Verificar se é Dia Letivo

```php
use App\Models\SchoolHoliday;
use Carbon\Carbon;

$date = Carbon::parse('2025-12-25');
$isDiaLetivo = SchoolHoliday::isSchoolDay($date);

// Retorna false (é Natal)
```

**Regra**: Não é dia letivo se:
- É fim de semana (sábado ou domingo)
- Existe feriado/recesso cadastrado na data

### 2. Contar Dias Letivos em um Período

```php
$inicio = Carbon::parse('2025-07-01');
$fim = Carbon::parse('2025-07-31');

$diasLetivos = SchoolHoliday::countSchoolDaysInPeriod($inicio, $fim);
// Retorna: 12 (Julho tem recesso de 14 a 28/07)
```

### 3. Obter Lista de Dias Não Letivos

```php
$diasNaoLetivos = SchoolHoliday::getNonSchoolDaysInPeriod($inicio, $fim);

// Retorna array: ['2025-07-05', '2025-07-06', '2025-07-12', '2025-07-13', ...]
```

### 4. Próximos Feriados

```php
$proximos = SchoolHoliday::getUpcoming(5);

foreach ($proximos as $feriado) {
    echo $feriado->name . ': ' . $feriado->getFormattedPeriod();
}
```

### 5. Verificar Status de um Feriado

```php
$feriado = SchoolHoliday::find(1);

$feriado->isUpcoming();           // Está no futuro?
$feriado->isPast();                // Já passou?
$feriado->getDurationInDays();     // Duração em dias
$feriado->getFormattedPeriod();    // "25/12/2025" ou "24/12/2025 a 26/12/2025"
```

## Integração com Aulas

O **LessonSeeder** foi atualizado para **não gerar aulas em dias não letivos**:

```php
// No LessonSeeder, cada data é verificada:
while ($currentDate->lte($endDate)) {
    if (!SchoolHoliday::isSchoolDay($currentDate)) {
        $currentDate->addDay();
        continue; // Pula dias não letivos
    }
    
    // Gera aulas normalmente...
}
```

## Feriados Pré-cadastrados (2025)

### Feriados Nacionais
- ✅ 01/01 - Ano Novo
- ✅ 24-26/02 - Carnaval
- ✅ 18/04 - Sexta-feira Santa
- ✅ 21/04 - Tiradentes
- ✅ 01/05 - Dia do Trabalho
- ✅ 19/06 - Corpus Christi
- ✅ 07/09 - Independência do Brasil
- ✅ 12/10 - Nossa Senhora Aparecida
- ✅ 02/11 - Finados
- ✅ 15/11 - Proclamação da República
- ✅ 20/11 - Consciência Negra
- ✅ 25/12 - Natal

### Recessos Escolares
- ✅ 14-28/07 - Recesso de Julho (Inverno)
- ✅ 16-31/12 - Recesso de Fim de Ano

### Eventos Escolares
- ✅ 15/03 - Reunião Pedagógica
- ✅ 13-14/06 - Festa Junina
- ✅ 05-06/09 - Semana da Pátria

**Total**: 17 períodos cadastrados

## Métodos Úteis do Model

### Scopes

```php
// Apenas ativos
SchoolHoliday::active()->get();

// De um ano letivo específico
SchoolHoliday::forYear($yearId)->get();

// Em um período
SchoolHoliday::inPeriod($startDate, $endDate)->get();
```

### Estatísticas

```php
// Contar dias letivos do ano letivo completo
$schoolYear = SchoolYear::current();
$diasLetivos = SchoolHoliday::countSchoolDaysInPeriod(
    $schoolYear->starts_at,
    $schoolYear->ends_at
);

// Dias letivos por mês
$inicio = now()->startOfMonth();
$fim = now()->endOfMonth();
$diasLetivos = SchoolHoliday::countSchoolDaysInPeriod($inicio, $fim);
```

## Exemplos de Consultas

### Exemplo 1: Verificar feriados no mês

```php
$mes = 12;
$ano = 2025;

$feriados = SchoolHoliday::active()
    ->whereMonth('start_date', $mes)
    ->whereYear('start_date', $ano)
    ->orderBy('start_date')
    ->get();

foreach ($feriados as $feriado) {
    echo $feriado->name . ' - ' . $feriado->type->label();
}
```

### Exemplo 2: Dias letivos já decorridos no ano

```php
$schoolYear = SchoolYear::current();
$hoje = now();

$diasDecorridos = SchoolHoliday::countSchoolDaysInPeriod(
    $schoolYear->starts_at,
    $hoje
);

$diasRestantes = SchoolHoliday::countSchoolDaysInPeriod(
    $hoje,
    $schoolYear->ends_at
);

$percentual = ($diasDecorridos / ($diasDecorridos + $diasRestantes)) * 100;
```

### Exemplo 3: Filtrar aulas em dias letivos

```php
use App\Models\Lesson;

// Aulas apenas em dias letivos
$aulas = Lesson::whereBetween('date', [$inicio, $fim])
    ->get()
    ->filter(function ($aula) {
        return SchoolHoliday::isSchoolDay($aula->date);
    });
```

## Impacto no Sistema

### ✅ Geração de Aulas
- Aulas **não são geradas** em dias não letivos
- LessonSeeder verifica cada data antes de criar

### ✅ Cálculo de Frequência
- Só considera aulas efetivamente realizadas
- Dias não letivos não afetam o cálculo

### ✅ Planejamento Escolar
- Calendário preciso com feriados
- Contagem exata de dias letivos

### ✅ Relatórios
- Estatísticas corretas de frequência
- Previsão de término do ano letivo

## Seeder

Criar feriados automaticamente:

```bash
php artisan db:seed --class=Database\\Seeders\\Academic\\SchoolHolidaySeeder
```

O seeder cria:
- 12 feriados nacionais
- 2 recessos escolares
- 3 eventos escolares

## Adicionando Novos Feriados

### Via Código

```php
use App\Models\SchoolHoliday;
use App\Enums\HolidayType;
use Carbon\Carbon;

SchoolHoliday::create([
    'school_year_id' => $yearId,
    'name' => 'Aniversário da Cidade',
    'description' => 'Feriado Municipal',
    'start_date' => Carbon::parse('2025-03-19'),
    'end_date' => Carbon::parse('2025-03-19'),
    'type' => HolidayType::MUNICIPAL_HOLIDAY,
    'is_active' => true,
]);
```

### Via Interface (Futuro)

Criar recurso Filament para gerenciar feriados:
- ✨ Criar/Editar/Excluir feriados
- 📅 Calendário visual
- 📊 Relatório de dias letivos

## Validações

### Ao Criar Aulas

```php
// Validar se a data é dia letivo
if (!SchoolHoliday::isSchoolDay($date)) {
    throw new \Exception('Não é possível criar aula em dia não letivo');
}
```

### Ao Lançar Frequência

```php
// Verificar se a aula aconteceu em dia letivo
$lesson = Lesson::find($lessonId);

if (!SchoolHoliday::isSchoolDay($lesson->date)) {
    // Alerta: esta aula foi em feriado/recesso
    // Pode ter sido uma reposição
}
```

## Testes Realizados

### ✅ Natal (25/12/2025)
```
Data: 25/12/2025
É dia letivo? NÃO
Motivo: Recesso de Fim de Ano
```

### ✅ Julho/2025
```
Dias letivos em Julho/2025: 12
(15 dias de recesso + fins de semana)
```

## API de Exemplo

Ver: `App\Http\Controllers\Examples\DiasLetivosExampleController`

Endpoints demonstrativos:
- `verificarDiaLetivo($date)` - Verifica se é dia letivo
- `proximosFeriados()` - Lista próximos feriados
- `diasLetivosNoMes($month, $year)` - Conta dias letivos
- `calendarioAnoLetivo()` - Calendário completo
- `diasLetivosRestantes()` - Dias letivos até o fim do ano

---

**Desenvolvido para o Lumina ERP** | Fevereiro 2026
