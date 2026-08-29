<?php

namespace App\Filament\Resources\Enrollments\Pages;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Filament\Resources\Enrollments\Schemas\EnrollmentWizardSchema;
use App\Services\Enrollments\StudentEnrollmentResult;
use App\Services\Enrollments\StudentEnrollmentService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateEnrollment extends CreateRecord {

    use HasWizard;

    protected static string $resource = EnrollmentResource::class;

    protected ?StudentEnrollmentResult $enrollmentResult = null;

    /**
     * Inicializa o estado necessário para exibir a página.
     *
     * @return void
     */
    public function mount(): void {
        parent::mount();
        $sid = request()->query('student_id');
        if (filled($sid)) {
            $this->form->fill([
                'student_id'     => $sid,
                'student_source' => 'existing',
            ]);
        }
    }

    /**
     * Retorna as etapas exibidas no assistente do formulário.
     *
     * @return array
     */
    public function getSteps(): array {
        return EnrollmentWizardSchema::getSteps();
    }

    /**
     * Delega a criação completa da matrícula ao serviço transacional.
     *
     * @param array<string, mixed> $data
     *
     * @return Model
     */
    protected function handleRecordCreation(array $data): Model {
        $this->enrollmentResult = app(StudentEnrollmentService::class)->create($data);

        return $this->enrollmentResult->enrollment;
    }

    /**
     * Informa a criação da conta de acesso após a conclusão do serviço.
     *
     * @return void
     */
    protected function afterCreate(): void {
        if (!$this->enrollmentResult?->userCreated) {
            return;
        }

        Notification::make()
            ->title('Usuário criado para o aluno')
            ->body('O aluno não possuía usuário de acesso. Foi criado um usuário. O aluno pode usar "Esqueci minha senha" para definir a senha.')
            ->success()
            ->send();
    }
}
