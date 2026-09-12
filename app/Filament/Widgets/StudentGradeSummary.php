<?php

namespace App\Filament\Widgets;

use App\Services\GradeCalculationService;
use App\Support\PermissionAccess;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class StudentGradeSummary extends StatsOverviewWidget {

    protected static bool $isLazy = false;
    protected ?string $pollingInterval = null;

    #[Reactive]
    public array $stats = [];

    public static function canView(): bool {
        return PermissionAccess::can('student.grades.view');
    }

    public function mount(): void {
        abort_unless(static::canView(), 403);
    }

    protected function getStats(): array {
        $average = $this->stats['average'] ?? null;
        $attention = ($this->stats['failed'] ?? 0) + ($this->stats['recovery'] ?? 0);
        $total = $this->stats['total'] ?? 0;

        return [
            Stat::make('Média do período', $average === null ? '—' : number_format($average, 1, ',', ''))
                ->description('Referência: '.number_format(GradeCalculationService::MIN_APPROVAL, 1, ',', '')),
            Stat::make('Na média', ($this->stats['approved'] ?? 0).' de '.$total)
                ->description('Disciplinas com média ≥ 6,0')
                ->color('success'),
            Stat::make('Precisam de atenção', $attention)
                ->description('Disciplinas abaixo de 6,0')
                ->color($attention > 0 ? 'warning' : 'gray'),
        ];
    }
}
