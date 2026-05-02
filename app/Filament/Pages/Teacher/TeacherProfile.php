<?php

namespace App\Filament\Pages\Teacher;

use App\Filament\Pages\Teacher\Concerns\HasTeacherPortalAccess;
use App\Models\Teacher;
use App\Services\CurrentTeacherService;
use App\Support\PermissionAccess;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;

class TeacherProfile extends Page
{
    use HasTeacherPortalAccess;
    use WithFileUploads;

    protected static ?string $navigationLabel = 'Meu Perfil';
    protected static ?string $title = 'Meu Perfil';
    protected static ?string $slug = 'teacher-profile';
    protected static string|null|\BackedEnum $navigationIcon = 'fas-user-tie';
    protected static string|null|\UnitEnum $navigationGroup = 'Portal do Professor';
    protected static ?int $navigationSort = 9;
    protected static ?string $teacherPortalPermission = 'teacher.profile.view';

    public ?string $personalEmail = null;
    public ?string $phone = null;
    public ?string $mobile = null;
    public $avatarUpload = null;

    public function mount(): void
    {
        $teacher = $this->currentTeacher();

        $this->personalEmail = $teacher?->email;
        $this->phone = $teacher?->phone;
        $this->mobile = $teacher?->mobile;
    }

    public function getView(): string
    {
        return 'filament.pages.teacher.teacher-profile';
    }

    public function getPageData(): array
    {
        $teacher = $this->currentTeacher();
        $assignments = app(CurrentTeacherService::class)->assignments($teacher);
        $user = $teacher?->user;

        $subjects = $assignments->pluck('subject')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $classes = $assignments->pluck('schoolClass')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $avatarUrl = $this->resolveAvatarUrl($teacher, $user?->avatar);

        return [
            'teacher' => $teacher,
            'user' => $user,
            'subjects' => $subjects,
            'classes' => $classes,
            'avatarUrl' => $avatarUrl,
            'canEdit' => PermissionAccess::can('teacher.profile.update-basic'),
        ];
    }

    public function saveBasic(): void
    {
        if (! PermissionAccess::can('teacher.profile.update-basic')) {
            throw ValidationException::withMessages([
                'permission' => 'Você não tem permissão para atualizar o perfil.',
            ]);
        }

        $teacher = $this->currentTeacher();

        if (! $teacher) {
            throw ValidationException::withMessages([
                'teacher' => 'Nenhum professor vinculado ao usuário atual.',
            ]);
        }

        $this->validate([
            'personalEmail' => 'nullable|email|max:120',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'avatarUpload' => 'nullable|image|max:2048',
        ]);

        $teacher->update([
            'email' => $this->personalEmail,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
        ]);

        if ($this->avatarUpload && $teacher->user) {
            $path = $this->avatarUpload->store('avatars', 'public');
            $teacher->user->update([
                'avatar' => $path,
            ]);
        }

        $this->avatarUpload = null;

        Notification::make()
            ->title('Perfil atualizado')
            ->body('Seus dados básicos foram salvos.')
            ->success()
            ->send();
    }

    private function currentTeacher(): ?Teacher
    {
        return app(CurrentTeacherService::class)->current();
    }

    private function resolveAvatarUrl(?Teacher $teacher, ?string $avatarPath): string
    {
        $name = urlencode($teacher?->name ?? auth()->user()?->name ?? 'Professor');

        if ($avatarPath) {
            return Storage::url($avatarPath);
        }

        return "https://ui-avatars.com/api/?name={$name}&background=0ea5e9&color=ffffff";
    }
}
