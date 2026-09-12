<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Users\Widgets\UsersOverview;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords {

    use HasResizableColumn;

    protected static string $resource = UserResource::class;

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
            UsersOverview::class,
        ];
    }
    /**
     * Retorna a URL usada após concluir a operação.
     *
     * @return string
     */
    protected function getRedirectUrl(): string {
        return $this->getResource()::getUrl('index');
    }


}
