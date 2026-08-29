<?php

namespace App\Filament\Resources\Enrollments\Pages;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Filament\Widgets\EnrollmentStatsWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEnrollments extends ListRecords {

    protected static string $resource = EnrollmentResource::class;

    /**
     * Retorna as ações exibidas no cabeçalho.
     *
     * @return array
     */
    protected function getHeaderActions(): array {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Retorna os widgets exibidos no cabeçalho da página.
     *
     * @return array
     */
    protected function getHeaderWidgets(): array {
        return [
            EnrollmentStatsWidget::class,
        ];
    }
}
