<x-filament-panels::page>
    @php
        $data = $this->getPageData();
        $teacher = $data['teacher'];
        $user = $data['user'];
        $subjects = $data['subjects'];
        $classes = $data['classes'];
        $avatarUrl = $data['avatarUrl'];
        $canEdit = $data['canEdit'];

        $statusLabel = $teacher?->status?->label() ?? '—';
        $institutionalEmail = $user?->email ?? $teacher?->email ?? '—';
        $socialName = '—';
        $workload = $teacher?->weekly_workload ? $teacher->weekly_workload . 'h/semana' : '—';
    @endphp

    <style>
        .teacher-profile-shell {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .teacher-profile-card {
            background: var(--ms-card-bg);
            border: 1px solid var(--ms-card-border);
            border-radius: 0.875rem;
            box-shadow: var(--lumina-shadow);
        }

        .teacher-profile-hero {
            padding: 1.5rem;
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 1.25rem;
            align-items: center;
        }

        .teacher-profile-avatar {
            width: 5rem;
            height: 5rem;
            border-radius: 999px;
            object-fit: cover;
            border: 3px solid var(--ms-card-border);
        }

        .teacher-profile-title h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--ms-text-primary);
        }

        .teacher-profile-title p {
            margin: 0.25rem 0 0;
            color: var(--ms-text-secondary);
            font-size: 0.8125rem;
        }

        .teacher-profile-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .teacher-profile-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: var(--ms-cell-bg);
            border: 1px solid var(--ms-card-border);
            color: var(--ms-text-primary);
            font-size: 0.75rem;
            font-weight: 600;
        }

        .teacher-profile-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .teacher-profile-section {
            padding: 1.25rem 1.5rem;
        }

        .teacher-profile-section h3 {
            margin: 0 0 0.75rem;
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--ms-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .teacher-profile-row {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--ms-bar-bg);
        }

        .teacher-profile-row:last-child {
            border-bottom: 0;
        }

        .teacher-profile-row strong {
            color: var(--ms-text-secondary);
            font-size: 0.75rem;
        }

        .teacher-profile-row span {
            color: var(--ms-text-primary);
            font-size: 0.8125rem;
            text-align: right;
        }

        .teacher-profile-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .teacher-profile-pill {
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: var(--ms-cell-bg);
            border: 1px solid var(--ms-card-border);
            font-size: 0.75rem;
            color: var(--ms-text-primary);
            font-weight: 600;
        }

        .teacher-profile-form {
            display: grid;
            gap: 0.85rem;
        }

        .teacher-profile-form label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--ms-text-secondary);
            margin-bottom: 0.35rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .teacher-profile-form input {
            width: 100%;
            border-radius: 0.65rem;
            border: 1px solid var(--ms-card-border);
            background: var(--ms-cell-bg);
            color: var(--ms-text-primary);
            padding: 0.7rem 0.85rem;
            outline: none;
        }

        .teacher-profile-form input:focus {
            border-color: var(--lumina-primary, #0f766e);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--lumina-primary, #0f766e) 18%, transparent);
        }

        .teacher-profile-form small {
            display: block;
            margin-top: 0.25rem;
            color: var(--ms-text-muted);
            font-size: 0.7rem;
        }

        .teacher-profile-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 0.75rem;
        }

        @media (max-width: 1024px) {
            .teacher-profile-hero {
                grid-template-columns: 1fr;
                text-align: center;
                justify-items: center;
            }

            .teacher-profile-grid {
                grid-template-columns: 1fr;
            }

            .teacher-profile-row {
                flex-direction: column;
                align-items: flex-start;
            }

            .teacher-profile-row span {
                text-align: left;
            }
        }
    </style>

    <div class="teacher-profile-shell">
        <div class="teacher-profile-card teacher-profile-hero">
            <img
                src="{{ $this->avatarUpload ? $this->avatarUpload->temporaryUrl() : $avatarUrl }}"
                alt="Foto do professor"
                class="teacher-profile-avatar"
            />
            <div class="teacher-profile-title">
                <h2>{{ $teacher?->name ?? 'Professor' }}</h2>
                <p>{{ $teacher?->qualification ?? 'Perfil do professor' }}</p>
                <div class="teacher-profile-badges">
                    <span class="teacher-profile-badge">Status: {{ $statusLabel }}</span>
                    <span class="teacher-profile-badge">Carga horaria: {{ $workload }}</span>
                    <span class="teacher-profile-badge">Turmas: {{ $classes->count() }}</span>
                </div>
            </div>
        </div>

        <div class="teacher-profile-grid">
            <div class="teacher-profile-card teacher-profile-section">
                <h3>Dados Basicos</h3>
                <div class="teacher-profile-row">
                    <strong>Nome completo</strong>
                    <span>{{ $teacher?->name ?? '—' }}</span>
                </div>
                <div class="teacher-profile-row">
                    <strong>Nome social</strong>
                    <span>{{ $socialName }}</span>
                </div>
                <div class="teacher-profile-row">
                    <strong>E-mail institucional</strong>
                    <span>{{ $institutionalEmail }}</span>
                </div>
                <div class="teacher-profile-row">
                    <strong>E-mail pessoal</strong>
                    <span>{{ $teacher?->email ?? '—' }}</span>
                </div>
                <div class="teacher-profile-row">
                    <strong>Telefone</strong>
                    <span>{{ $teacher?->phone ?? '—' }}</span>
                </div>
                <div class="teacher-profile-row">
                    <strong>Celular</strong>
                    <span>{{ $teacher?->mobile ?? '—' }}</span>
                </div>
            </div>

            <div class="teacher-profile-card teacher-profile-section">
                <h3>Disciplinas Habilitadas</h3>
                <div class="teacher-profile-list">
                    @forelse($subjects as $subject)
                        <span class="teacher-profile-pill">{{ $subject->name }}</span>
                    @empty
                        <span class="teacher-profile-pill">Nenhuma disciplina vinculada</span>
                    @endforelse
                </div>

                <h3 style="margin-top:1.25rem">Turmas Atuais</h3>
                <div class="teacher-profile-list">
                    @forelse($classes as $class)
                        <span class="teacher-profile-pill">{{ $class->name }}</span>
                    @empty
                        <span class="teacher-profile-pill">Nenhuma turma atribuida</span>
                    @endforelse
                </div>
            </div>

            <div class="teacher-profile-card teacher-profile-section">
                <h3>Editar Dados</h3>
                <div class="teacher-profile-form">
                    <div>
                        <label for="teacher-profile-email">E-mail pessoal</label>
                        <input
                            id="teacher-profile-email"
                            type="email"
                            wire:model.defer="personalEmail"
                            @if(! $canEdit) disabled @endif
                        />
                    </div>
                    <div>
                        <label for="teacher-profile-phone">Telefone</label>
                        <input
                            id="teacher-profile-phone"
                            type="text"
                            wire:model.defer="phone"
                            @if(! $canEdit) disabled @endif
                        />
                    </div>
                    <div>
                        <label for="teacher-profile-mobile">Celular</label>
                        <input
                            id="teacher-profile-mobile"
                            type="text"
                            wire:model.defer="mobile"
                            @if(! $canEdit) disabled @endif
                        />
                    </div>
                    <div>
                        <label for="teacher-profile-photo">Foto de perfil</label>
                        <input
                            id="teacher-profile-photo"
                            type="file"
                            accept="image/*"
                            wire:model="avatarUpload"
                            @if(! $canEdit) disabled @endif
                        />
                        <small>Formatos aceitos: JPG, PNG. Tamanho maximo 2MB.</small>
                    </div>
                </div>

                @if($canEdit)
                    <div class="teacher-profile-actions">
                        <x-filament::button
                            type="button"
                            color="primary"
                            icon="fas-floppy-disk"
                            wire:click="saveBasic"
                        >
                            Salvar edicao
                        </x-filament::button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
