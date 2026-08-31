<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AccessPending extends Page {

    protected static ?string $navigationLabel                = 'Acesso em configuração';
    protected static ?string $title                          = 'Acesso em configuração';
    protected static ?string $slug                           = 'acesso-pendente';
    protected static string|null|\BackedEnum $navigationIcon = 'fas-circle-info';

    /**
     * Oculta a página de orientação da navegação principal.
     *
     * @return bool
     */
    public static function shouldRegisterNavigation(): bool {
        return false;
    }

    /**
     * Permite que usuários autenticados recebam orientação sem erro genérico.
     *
     * @return bool
     */
    public static function canAccess(): bool {
        return auth()->check();
    }

    /**
     * Retorna o nome da visualização usada pela página.
     *
     * @return string
     */
    public function getView(): string {
        return 'filament.pages.access-pending';
    }
}
