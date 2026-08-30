<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Services\Enrollments\StudentEnrollmentService;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStudent extends CreateRecord {

    protected static string $resource = StudentResource::class;
    protected static ?string $title   = 'Pré-cadastrar aluno';

    /**
     * Delega o pré-cadastro ao serviço único de onboarding do aluno.
     *
     * @param array<string, mixed> $data
     *
     * @return Model
     */
    protected function handleRecordCreation(array $data): Model {
        return app(StudentEnrollmentService::class)->preRegister($data);
    }

    /**
     * Retorna a ação de pré-cadastro com confirmação do resultado esperado.
     *
     * @return Action
     */
    protected function getCreateFormAction(): Action {
        return parent::getCreateFormAction()
            ->label('Pré-cadastrar aluno')
            ->requiresConfirmation()
            ->modalHeading('Confirmar pré-cadastro')
            ->modalDescription('Será criado somente o cadastro do aluno. Nenhum usuário, convite ou matrícula será criado nesta operação.')
            ->modalSubmitActionLabel('Confirmar pré-cadastro');
    }
}
