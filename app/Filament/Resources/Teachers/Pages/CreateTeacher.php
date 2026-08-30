<?php

namespace App\Filament\Resources\Teachers\Pages;

use App\Filament\Resources\Teachers\TeacherResource;
use App\Filament\Resources\Teachers\Schemas\TeacherOnboardingWizardSchema;
use App\Services\Teachers\TeacherOnboardingResult;
use App\Services\Teachers\TeacherOnboardingService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTeacher extends CreateRecord {

    use HasWizard;

    protected static string $resource = TeacherResource::class;
    protected static ?string $title   = 'Cadastrar professor';

    protected ?TeacherOnboardingResult $onboardingResult = null;

    /**
     * Retorna as etapas do Wizard de onboarding docente.
     *
     * @return array
     */
    public function getSteps(): array {
        return TeacherOnboardingWizardSchema::getSteps();
    }

    /**
     * Delega a criação do professor ao serviço único de onboarding.
     *
     * @param array<string, mixed> $data
     *
     * @return Model
     */
    protected function handleRecordCreation(array $data): Model {
        $this->onboardingResult = app(TeacherOnboardingService::class)->create($data);

        return $this->onboardingResult->teacher;
    }

    /**
     * Informa ao operador o resultado da criação de acesso e do convite.
     *
     * @return void
     */
    protected function afterCreate(): void {
        if (!$this->onboardingResult?->userCreated) {
            return;
        }

        if (!$this->onboardingResult->invitationSent) {
            Notification::make()
                ->title('Professor e usuário criados sem convite')
                ->body('O acesso poderá ser convidado separadamente na listagem de professores.')
                ->success()
                ->send();

            return;
        }

        Notification::make()
            ->title('Professor, usuário e convite criados')
            ->actions([
                Action::make('openInvitation')
                    ->label('Abrir link do convite')
                    ->url($this->onboardingResult->invitationUrl)
                    ->openUrlInNewTab(),
            ])
            ->success()
            ->duration(15000)
            ->send();
    }

    /**
     * Retorna a ação final do Wizard.
     *
     * @return Action
     */
    protected function getSubmitFormAction(): Action {
        return parent::getSubmitFormAction()->label('Confirmar onboarding');
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
