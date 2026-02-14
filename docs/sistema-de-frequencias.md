# Sistema de Frequências - Lumina ERP

## Visão Geral

O sistema de frequências do Lumina ERP permite o lançamento e gerenciamento completo de presenças/faltas dos alunos, vinculado a aulas reais ministradas pelos professores.

## Fluxo de Entidades

```
Professor → Turma → Disciplina → Aula (Lesson) → Diário de Classe (Attendance) → Aluno
```

## Tabelas

### `lessons` (Aulas)
Registra cada aula ministrada:
- **teacher_id**: Professor responsável
- **class_id**: Turma
- **subject_id**: Disciplina
- **date**: Data da aula
- **start_time** / **end_time**: Horário
- **topic**: Tema/conteúdo
- **status**: scheduled | completed | cancelled | rescheduled
- **attendance_taken**: Se a chamada foi realizada
- **attendance_taken_at**: Quando a chamada foi feita

### `attendances` (Frequências)
Registra a presença de cada aluno em cada aula:
- **student_id**: Aluno
- **lesson_id**: Aula específica
- **class_id**: Turma
- **subject_id**: Disciplina
- **date**: Data
- **time**: Hora do registro
- **status**: present | absent | late | excused
- **notes**: Observações (justificativas)
- **recorded_by**: Quem registrou

## Uso Básico

### 1. Criar uma Aula

```php
use App\Models\Lesson;
use App\Enums\LessonStatus;

$lesson = Lesson::create([
    'teacher_id' => 1,
    'class_id' => 5,
    'subject_id' => 3,
    'date' => '2026-02-15',
    'start_time' => '14:00',
    'end_time' => '14:50',
    'topic' => 'Verbos irregulares',
    'status' => LessonStatus::SCHEDULED,
]);
```

### 2. Registrar Frequência

```php
use App\Models\Attendance;
use App\Enums\AttendanceStatus;

// Verificar se pode lançar (não passou do prazo)
if (Attendance::canRecordForDate($lesson->date, 3)) {
    Attendance::create([
        'student_id' => 10,
        'lesson_id' => $lesson->id,
        'class_id' => $lesson->class_id,
        'subject_id' => $lesson->subject_id,
        'date' => $lesson->date,
        'time' => now()->format('H:i'),
        'status' => AttendanceStatus::PRESENT,
        'recorded_by' => auth()->id(),
    ]);
}
```

### 3. Marcar Chamada como Realizada

```php
$lesson->markAttendanceTaken(auth()->id());
```

### 4. Calcular Frequência de um Aluno

```php
$stats = Attendance::calculateFrequency(
    studentId: 25,
    classId: 5,
    subjectId: 3,
    startDate: now()->startOfMonth(),
    endDate: now()->endOfMonth()
);

/*
Retorna:
[
    'frequency' => 87.5,     // Porcentagem
    'present' => 35,         // Total de presenças
    'late' => 3,             // Total de atrasos
    'absent' => 5,           // Total de faltas
    'total' => 40,           // Total de aulas
    'alert' => false,        // true se < 75%
]
*/
```

### 5. Relatório de Frequência da Turma

```php
$report = Attendance::getClassFrequencyReport(
    classId: 5,
    subjectId: 3,  // opcional
    startDate: now()->startOfYear(),
    endDate: now()
);

/*
Retorna array com dados de todos os alunos:
[
    [
        'student_id' => 10,
        'student_name' => 'João Silva',
        'frequency' => 92.5,
        'present' => 37,
        'late' => 2,
        'absent' => 3,
        'total' => 40,
        'alert' => false,
    ],
    ...
]
*/
```

### 6. Identificar Alunos em Risco

```php
$atRisk = Attendance::getStudentsAtRisk(
    classId: 5,
    subjectId: null,      // todas as disciplinas
    thresholdPercentage: 75.0
);

// Retorna apenas alunos com frequência < 75%
```

## Validações

### Bloqueio Retroativo

Por padrão, não é possível lançar frequência após **3 dias** da data da aula:

```php
// Verificar se pode lançar
$canRecord = Attendance::canRecordForDate(
    date: Carbon::parse('2026-02-10'),
    maxDaysAfter: 3
);

// Alterar o limite no método canTakeAttendance do Lesson:
$lesson->canTakeAttendance(maxDaysAfter: 7); // Permite até 7 dias
```

### Validação de Horário

O sistema valida se o horário de registro está dentro do período da aula (+ 30 min):

```php
$attendance->isTimeValid();
```

### Constraint Única

Um aluno não pode ter mais de 1 registro de frequência na **mesma aula**:
- Constraint: `uniq_attendance_student_lesson(student_id, lesson_id)`

## Fórmula de Frequência

A frequência é calculada pela fórmula:

$$F = \frac{P + A}{T} \times 100$$

Onde:
- **F**: Frequência (%)
- **P**: Presenças (status = present)
- **A**: Atrasos (status = late) - contam como presença
- **T**: Total de aulas

**Importante**: 
- Faltas justificadas (excused) **não contam** como presença
- Alerta é emitido quando F < 75%

## Enums Disponíveis

### LessonStatus
```php
use App\Enums\LessonStatus;

LessonStatus::SCHEDULED;    // Agendada
LessonStatus::COMPLETED;    // Realizada
LessonStatus::CANCELLED;    // Cancelada
LessonStatus::RESCHEDULED;  // Reagendada
```

### AttendanceStatus
```php
use App\Enums\AttendanceStatus;

AttendanceStatus::PRESENT;  // Presente
AttendanceStatus::ABSENT;   // Ausente
AttendanceStatus::LATE;     // Atrasado
AttendanceStatus::EXCUSED;  // Falta Justificada
```

## Métodos Úteis do Lesson

```php
// Verificar se pode lançar chamada
$lesson->canTakeAttendance($maxDaysAfter = 3);

// Obter horário formatado
$lesson->time_range; // "14:00 - 14:50"

// Verificar se está em andamento
$lesson->isInProgress();

// Taxa de presença da aula
$lesson->getAttendanceRate(); // 92.5
```

## Seeders

### LessonSeeder
Gera aulas automaticamente para todas as turmas:
- Período: últimos 60 dias + próximos 30 dias
- 4-6 aulas por dia (seg-sex)
- Horários respeitam turno da turma
- 80% das aulas passadas têm chamada realizada

```bash
php artisan db:seed --class=Database\\Seeders\\Academic\\LessonSeeder
```

### AttendanceSeeder
Gera registros de frequência para aulas com chamada:
- Perfis de alunos: excellent (70%), good (15%), moderate (10%), poor (5%)
- Estatísticas realistas
- Identifica automaticamente alunos em risco

```bash
php artisan db:seed --class=Database\\Seeders\\Academic\\AttendanceSeeder
```

## Próximos Passos (Sugestões)

1. **Interface Filament** para lançamento de frequência
2. **Relatórios** de frequência por período
3. **Notificações** automáticas para alunos/responsáveis em risco
4. **Exportação** de dados para PDF/Excel
5. **Dashboard** com gráficos de frequência
6. **Justificativas** com upload de documentos
7. **Integração** com sistema de notas (reprovação automática)

## Exemplo de Consultas

### Listar aulas de hoje de um professor
```php
$lessons = Lesson::forTeacher($teacherId)
    ->onDate(today())
    ->with(['schoolClass', 'subject'])
    ->get();
```

### Aulas pendentes de chamada
```php
$pending = Lesson::attendancePending()
    ->where('date', '<', now()->subDays(1))
    ->get();
```

### Frequência do mês de um aluno
```php
$attendances = Attendance::forStudent($studentId)
    ->month(now()->month)
    ->year(now()->year)
    ->with('lesson.subject')
    ->get();
```

---

**Desenvolvido para o Lumina ERP** | Fevereiro 2026
