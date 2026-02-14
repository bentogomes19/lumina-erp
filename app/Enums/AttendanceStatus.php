<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case ABSENT = 'absent';
    case LATE = 'late';
    case EXCUSED = 'excused';

    public function label(): string
    {
        return match ($this) {
            self::PRESENT => 'Presente',
            self::ABSENT => 'Ausente',
            self::LATE => 'Atrasado',
            self::EXCUSED => 'Falta Justificada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PRESENT => 'success',
            self::ABSENT => 'danger',
            self::LATE => 'warning',
            self::EXCUSED => 'info',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PRESENT => 'heroicon-o-check-circle',
            self::ABSENT => 'heroicon-o-x-circle',
            self::LATE => 'heroicon-o-clock',
            self::EXCUSED => 'heroicon-o-document-text',
        };
    }

    public static function options(): array
    {
        return [
            self::PRESENT->value => self::PRESENT->label(),
            self::ABSENT->value => self::ABSENT->label(),
            self::LATE->value => self::LATE->label(),
            self::EXCUSED->value => self::EXCUSED->label(),
        ];
    }

    /**
     * Verificar se é uma presença válida (conta como presente)
     */
    public function countsAsPresent(): bool
    {
        return in_array($this, [self::PRESENT, self::LATE]);
    }
}
