<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardStats;
use App\Filament\Widgets\EnrollmentStatsWidget;
use App\Support\AdministrativeDashboardAccess;
use Filament\Pages\Page;

class DashboardAdmin extends Page {

    protected static ?string $navigationLabel                = 'Painel Administrativo';
    protected static ?string $title                          = 'Painel Administrativo';
    protected static ?string $slug                           = 'dashboard-admin';
    protected static string|null|\BackedEnum $navigationIcon = 'fas-house';
    protected static ?int $navigationSort                    = 0;

    /**
     * Determina se a página deve ser registrada na navegação.
     *
     * @return bool
     */
    public static function shouldRegisterNavigation(): bool {
        return AdministrativeDashboardAccess::hasAdministrativeRole(auth()->user());
    }

    /**
     * Determina se o usuário atual pode acessar a página.
     *
     * @return bool
     */
    public static function canAccess(): bool {
        return AdministrativeDashboardAccess::hasAdministrativeRole(auth()->user());
    }

    /**
     * Retorna o nome da visualização usada pela página.
     *
     * @return string
     */
    public function getView(): string {
        return 'filament.pages.dashboard-admin';
    }

    /**
     * Retorna os widgets exibidos no cabeçalho da página.
     *
     * @return array
     */
    protected function getHeaderWidgets(): array {
        return [
            DashboardStats::class,
            EnrollmentStatsWidget::class,
        ];
    }

    /**
     * Retorna a quantidade de colunas dos widgets do cabeçalho.
     *
     * @return int|array
     */
    public function getHeaderWidgetsColumns(): int | array {
        return 2;
    }
}
