<?php

namespace Tests\Feature\Enrollments;

use App\Filament\Resources\Enrollments\Pages\EditEnrollment;
use App\Filament\Resources\Enrollments\RelationManagers\EnrollmentDocumentsRelationManager;
use App\Models\Enrollment;
use App\Models\EnrollmentDocument;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EnrollmentDocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('lumina'));
    }

    public function test_private_disk_used_by_enrollment_documents_is_configured(): void
    {
        $this->assertSame('local', config('filesystems.disks.private.driver'));
        $this->assertSame(storage_path('app/private'), config('filesystems.disks.private.root'));
    }

    public function test_secretary_can_attach_a_pdf_to_an_enrollment_document(): void
    {
        Storage::fake('private');

        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('admin', 'web'));

        foreach ([
            'academic.enrollments.view_any',
            'academic.enrollments.view',
            'academic.enrollments.update',
            'academic.enrollments.documents.view',
        ] as $permissionName) {
            $user->givePermissionTo(Permission::findOrCreate($permissionName, 'web'));
        }

        $enrollment = Enrollment::factory()->create();
        $this->actingAs($user);

        $file = UploadedFile::fake()->create('Documentação Integração Cadastro.pdf', 662, 'application/pdf');

        Livewire::test(EnrollmentDocumentsRelationManager::class, [
            'ownerRecord' => $enrollment,
            'pageClass'   => EditEnrollment::class,
        ])
            ->callAction(TestAction::make('create')->table(), [
                'tipo'         => 'cpf',
                'status'       => 'pendente',
                'arquivo_path' => $file,
            ])
            ->assertHasNoFormErrors();

        $document = EnrollmentDocument::query()->where('enrollment_id', $enrollment->getKey())->firstOrFail();

        $this->assertSame('Documentação Integração Cadastro.pdf', $document->arquivo_nome_original);
        $this->assertStringStartsWith('enrollment-documents/', $document->arquivo_path);
        Storage::disk('private')->assertExists($document->arquivo_path);
    }
}
