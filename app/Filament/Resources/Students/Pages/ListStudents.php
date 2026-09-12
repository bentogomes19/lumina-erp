<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStudents extends ListRecords {

    use HasResizableColumn;

    protected static string $resource = StudentResource::class;

    /**
     * Retorna as ações exibidas no cabeçalho.
     *
     * @return array
     */
    protected function getHeaderActions(): array {
        return [
            CreateAction::make()
                ->label('Pré-cadastrar aluno')
                ->icon('fas-user-plus'),
        ];
    }
}
