<?php

namespace App\Filament\Resources\Students\Tables;

use App\Enums\StudentStatus;
use App\Models\User;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class StudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('registration_number')->label('Matrícula')->searchable()->copyable(),
                TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                TextColumn::make('age')
                    ->label('Idade')
                    ->getStateUsing(fn($record) => $record?->birth_date
                        ? Carbon::parse($record->birth_date)->age
                        : null
                    )
                    ->placeholder('—')
                    ->alignRight()
                    // ordena por nascimento (mais novo/mais velho), mantendo nulos no fim
                    ->sortable(query: function ($query, string $direction) {
                        return $query
                            ->orderByRaw('birth_date IS NULL') // nulos por último
                            ->orderBy('birth_date', $direction === 'asc' ? 'desc' : 'asc');
                    }),
                TextColumn::make('classes.name')->label('Turmas')->limit(20)->toggleable(),
                TextColumn::make('email')->label('E-mail')->toggleable(),
                TextColumn::make('phone_number')->label('Telefone')->toggleable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(function ($state) {
                        $value = $state instanceof BackedEnum ? $state->value : $state;   // enum ou string
                        return StudentStatus::options()[$value] ?? '—';
                    })
                    ->colors([
                        'success' => fn($state) => ($state instanceof BackedEnum ? $state->value : $state) === StudentStatus::ACTIVE->value,
                        'warning' => fn($state) => ($state instanceof BackedEnum ? $state->value : $state) === StudentStatus::SUSPENDED->value,
                        'info' => fn($state) => ($state instanceof BackedEnum ? $state->value : $state) === StudentStatus::GRADUATED->value,
                        'gray' => fn($state) => ($state instanceof BackedEnum ? $state->value : $state) === StudentStatus::INACTIVE->value,
                    ]),
                TextColumn::make('enrollment_date')->label('Ingresso')->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(StudentStatus::options()),
                SelectFilter::make('class_id')
                    ->label('Turma (Ano atual)')
                    ->relationship('classes', 'name')
                    ->searchable()->preload(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('criarUsuario')
                    ->label('Criar usuário')
                    ->icon('heroicon-o-user-plus')
                    ->visible(fn($record) => !$record->user_id)
                    ->modalHeading(fn($record) => "Criar usuário para {$record->name}")
                    ->form([
                        TextInput::make('name')
                            ->label('Nome')
                            ->default(fn($record) => $record->name)
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('email')
                            ->label('E-mail institucional (opcional)')
                            ->email()
                            ->default(fn($record) => $record->email) // vem do aluno
                            ->nullable()
                            ->rule(Rule::unique('users', 'email')), // evita 'students.id <> ...'

                        TextInput::make('password')
                            ->label('Senha')
                            ->password()
                            ->required()
                            ->minLength(8),
                    ])
                    ->action(function (\App\Models\Student $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $email = filled($data['email']) ? $data['email'] : $record->email;

                            if ($email && User::where('email', $email)->exists()) {
                                throw ValidationException::withMessages([
                                    'email' => 'Este e-mail já está em uso por outro usuário.',
                                ]);
                            }

                            $user = User::create([
                                'name'     => $record->name,
                                'email'    => $email, // usa o do aluno por padrão
                                'password' => Hash::make($data['password']),
                                'active'   => true,
                            ]);

                            $user->syncRoles(['student']);
                            if ($role = Role::where('name', 'student')->with('permissions')->first()) {
                                $user->syncPermissions($role->permissions);
                            }

                            $record->user()->associate($user)->save();
                        });

                        Notification::make()
                            ->title('Usuário criado com sucesso')
                            ->body('O aluno agora tem acesso como “student”.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('bulkStatus')
                        ->label('Alterar status (selecionados)')
                        ->icon('heroicon-o-adjustments-vertical')
                        ->form([
                            \Filament\Forms\Components\Select::make('status')
                                ->label('Novo status')
                                ->options(StudentStatus::options())
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $status = $data['status'];
                            $records->each->update([
                                'status' => $status,
                                'status_changed_at' => now(),
                            ]);

                            Notification::make()
                                ->title('Status atualizado para os registros selecionados.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
