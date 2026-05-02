<x-filament-panels::page>
    @php
        $data = $this->getPageData();
        $roles = $data['roles'];
        $selectedRole = $data['selectedRole'];
        $modules = $data['modules'];
        $moduleGroups = $data['moduleGroups'];
        $summary = $data['summary'];
    @endphp

    <style>
        .permission-matrix { display:flex; flex-direction:column; gap:1.25rem; }
        .permission-card { background:var(--ms-card-bg); border:1px solid var(--ms-card-border); border-radius:0.625rem; box-shadow:var(--lumina-shadow); }
        .permission-header-card { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1.25rem 1.5rem; }
        .permission-heading { display:flex; align-items:center; gap:0.875rem; }
        .permission-heading-icon { width:2.75rem; height:2.75rem; border-radius:0.625rem; display:flex; align-items:center; justify-content:center; color:var(--lumina-primary); background:var(--lumina-primary-soft); flex-shrink:0; }
        .permission-heading-icon svg { width:1.35rem; height:1.35rem; }
        .permission-heading h2 { font-size:1.25rem; font-weight:700; line-height:1.2; color:var(--ms-text-primary); margin:0; }
        .permission-heading p { font-size:0.875rem; color:var(--ms-text-secondary); margin:0.25rem 0 0; }
        .permission-save-button { min-height:2.5rem; display:inline-flex; align-items:center; justify-content:center; gap:0.5rem; padding:0 1rem; border-radius:0.5rem; background:var(--lumina-primary); border:1px solid var(--lumina-primary); color:#fff; font-size:0.8125rem; font-weight:700; }
        .permission-save-button svg { width:1rem; height:1rem; }
        .permission-role-card, .permission-toolbar { padding:1rem; }
        .permission-role-card label { display:block; font-size:0.75rem; font-weight:700; color:var(--ms-text-secondary); text-transform:uppercase; margin-bottom:0.375rem; }
        .permission-role-meta { display:flex; flex-wrap:wrap; gap:0.5rem 1rem; margin-top:0.75rem; font-size:0.8125rem; color:var(--ms-text-secondary); }
        .permission-role-meta strong { color:var(--ms-text-primary); }
        .permission-summary-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:1rem; }
        .permission-summary-card { display:flex; align-items:center; gap:0.75rem; padding:1rem; min-width:0; }
        .permission-summary-card > svg { width:1.5rem; height:1.5rem; color:var(--lumina-primary); flex-shrink:0; }
        .permission-summary-card strong { display:block; color:var(--ms-text-primary); font-size:1.5rem; font-weight:800; line-height:1; }
        .permission-summary-card span { color:var(--ms-text-secondary); font-size:0.75rem; }
        .permission-toolbar { display:grid; grid-template-columns:minmax(14rem,1fr) minmax(12rem,16rem) minmax(10rem,12rem); gap:0.75rem; }
        .permission-search-wrap { position:relative; }
        .permission-search-wrap > svg { position:absolute; left:0.75rem; top:50%; width:1rem; height:1rem; transform:translateY(-50%); color:var(--ms-text-muted); pointer-events:none; }
        .permission-search, .permission-select { width:100%; min-height:2.5rem; color:var(--ms-text-primary); background:var(--ms-cell-bg); border:1px solid var(--ms-card-border); border-radius:0.5rem; font-size:0.875rem; }
        .permission-search { padding:0 0.875rem 0 2.25rem; }
        .permission-select { padding:0 0.75rem; }
        .permission-module-list { display:flex; flex-direction:column; gap:1rem; }
        .permission-module-section { overflow:hidden; }
        .permission-module-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.25rem; border-bottom:1px solid var(--ms-bar-bg); background:linear-gradient(180deg, color-mix(in srgb, var(--ms-cell-bg) 72%, transparent), transparent); }
        .permission-module-title { display:flex; flex-direction:column; gap:0.25rem; }
        .permission-module-title h3 { color:var(--ms-text-primary); font-size:1rem; font-weight:800; margin:0; }
        .permission-module-stats { display:flex; flex-wrap:wrap; align-items:center; gap:0.375rem; }
        .permission-module-stats span { min-height:1.5rem; display:inline-flex; align-items:center; padding:0 0.5rem; color:var(--ms-text-secondary); background:var(--ms-cell-bg); border:1px solid var(--ms-card-border); border-radius:999px; font-size:0.75rem; font-weight:700; }
        .permission-module-actions { display:flex; align-items:center; flex-wrap:wrap; gap:0.5rem; }
        .permission-module-actions button { min-height:2rem; padding:0 0.75rem; border-radius:0.5rem; color:var(--ms-text-primary); background:var(--ms-cell-bg); border:1px solid var(--ms-card-border); font-size:0.8125rem; font-weight:700; }
        .permission-table-wrap { width:100%; overflow-x:auto; }
        .permission-table { width:100%; min-width:44rem; border-collapse:collapse; table-layout:auto; background:var(--ms-card-bg); }
        .permission-table th, .permission-table td { padding:0.875rem 1.25rem; text-align:left; vertical-align:middle; border-bottom:1px solid var(--ms-bar-bg); }
        .permission-table th { color:var(--ms-text-secondary); font-size:0.6875rem; font-weight:800; text-transform:uppercase; background:var(--ms-cell-bg); }
        .permission-table td:nth-child(2), .permission-table th:nth-child(2) { width:7rem; text-align:center; }
        .permission-table td:nth-child(3), .permission-table th:nth-child(3) { width:14rem; }
        .permission-table tbody tr:last-child td { border-bottom:0; }
        .permission-table tbody tr:hover { background:color-mix(in srgb, var(--ms-cell-bg) 55%, transparent); }
        .permission-name-cell { display:flex; flex-direction:column; gap:0.1875rem; min-width:0; }
        .permission-name-cell strong { color:var(--ms-text-primary); font-size:0.875rem; }
        .permission-name-cell span, .permission-name-cell small { color:var(--ms-text-muted); font-size:0.75rem; }
        .permission-module-badge { min-height:1.625rem; display:inline-flex; align-items:center; width:fit-content; padding:0 0.625rem; color:var(--lumina-primary-strong); background:var(--lumina-primary-soft); border:1px solid rgba(245,158,11,0.26); border-radius:999px; font-size:0.75rem; font-weight:700; white-space:nowrap; }
        .permission-mobile-module { display:none; }
        .permission-checkbox { width:2rem; height:2rem; display:inline-flex; align-items:center; justify-content:center; color:#fff; background:var(--ms-cell-bg); border:1px solid var(--lumina-border-strong); border-radius:0.5rem; }
        .permission-checkbox svg { width:1rem; height:1rem; }
        .permission-checkbox.is-active { background:var(--lumina-primary); border-color:var(--lumina-primary); box-shadow:0 0 0 4px var(--lumina-primary-soft); }
        .permission-empty { display:flex; flex-direction:column; align-items:center; gap:0.5rem; padding:3rem 1rem; text-align:center; }
        .permission-empty svg { width:2rem; height:2rem; color:var(--ms-text-muted); }
        .permission-empty h3 { color:var(--ms-text-primary); font-size:1rem; font-weight:700; margin:0; }
        .permission-empty p { color:var(--ms-text-secondary); font-size:0.875rem; margin:0; }
        @media (max-width:900px) {
            .permission-header-card, .permission-module-header { align-items:stretch; flex-direction:column; }
            .permission-save-button { width:100%; }
            .permission-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .permission-toolbar { grid-template-columns:1fr; }
        }
        @media (max-width:640px) {
            .permission-summary-grid { grid-template-columns:1fr; }
            .permission-table { min-width:0; }
            .permission-table th:nth-child(3), .permission-table td:nth-child(3) { display:none; }
            .permission-table th, .permission-table td { padding:0.875rem; }
            .permission-table td:nth-child(2), .permission-table th:nth-child(2) { width:4.5rem; }
            .permission-mobile-module { display:inline-flex; }
        }
    </style>

    <div class="permission-matrix">
        <section class="ms-card permission-card permission-header-card">
            <div>
                <div class="permission-heading">
                    <span class="permission-heading-icon">@svg('fas-user-shield')</span>
                    <div>
                        <h2>Permissões de Acesso</h2>
                        <p>Gerencie quais permissões estão ativas para cada perfil do sistema.</p>
                    </div>
                </div>
            </div>

            <button type="button" wire:click="save" class="permission-save-button">
                @svg('fas-floppy-disk')
                <span>Salvar alterações</span>
            </button>
        </section>

        <section class="ms-card permission-card permission-role-card">
            <label for="permission-role-select">Perfil</label>
            <select id="permission-role-select" wire:model.live="selectedRoleId" class="permission-select">
                @foreach($roles as $role)
                    <option value="{{ $role->id }}">
                        {{ $this->roleLabel($role->name) }} ({{ $role->users_count }} {{ $role->users_count === 1 ? 'usuário' : 'usuários' }})
                    </option>
                @endforeach
            </select>

            @if($selectedRole)
                <div class="permission-role-meta">
                    <span>Perfil selecionado: <strong>{{ $this->roleLabel($selectedRole->name) }}</strong></span>
                    <span>Técnico: {{ $selectedRole->name }}</span>
                    <span>{{ $selectedRole->users_count }} {{ $selectedRole->users_count === 1 ? 'usuário vinculado' : 'usuários vinculados' }}</span>
                    <span>Guard: {{ $selectedRole->guard_name }}</span>
                </div>
            @endif
        </section>

        <section class="permission-summary-grid">
            <article class="ms-card permission-card permission-summary-card">
                @svg('fas-list-check')
                <div>
                    <strong>{{ $summary['total'] }}</strong>
                    <span>Total de permissões</span>
                </div>
            </article>
            <article class="ms-card permission-card permission-summary-card">
                @svg('fas-circle-check')
                <div>
                    <strong>{{ $summary['active'] }}</strong>
                    <span>Permissões ativas</span>
                </div>
            </article>
            <article class="ms-card permission-card permission-summary-card">
                @svg('fas-circle-xmark')
                <div>
                    <strong>{{ $summary['inactive'] }}</strong>
                    <span>Permissões inativas</span>
                </div>
            </article>
            <article class="ms-card permission-card permission-summary-card">
                @svg('fas-layer-group')
                <div>
                    <strong>{{ $summary['modules'] }}</strong>
                    <span>Módulos disponíveis</span>
                </div>
            </article>
        </section>

        <section class="ms-card permission-card permission-toolbar">
            <div class="permission-search-wrap">
                @svg('fas-magnifying-glass')
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar permissão"
                    class="permission-search"
                />
            </div>

            <select wire:model.live="moduleFilter" class="permission-select">
                <option value="all">Todos os módulos</option>
                @foreach($modules as $module)
                    <option value="{{ $module }}">{{ $module }}</option>
                @endforeach
            </select>

            <select wire:model.live="statusFilter" class="permission-select">
                <option value="all">Todas</option>
                <option value="active">Ativas</option>
                <option value="inactive">Inativas</option>
            </select>
        </section>

        <div class="permission-module-list">
            @forelse($moduleGroups as $module => $group)
                @php
                    $permissions = $group['permissions'];
                @endphp
                <section class="ms-card permission-card permission-module-section">
                    <header class="permission-module-header">
                        <div class="permission-module-title">
                            <h3>{{ $group['name'] }}</h3>
                            <div class="permission-module-stats">
                                <span>{{ $group['total'] }} {{ $group['total'] === 1 ? 'permissão' : 'permissões' }}</span>
                                <span>{{ $group['active'] }} {{ $group['active'] === 1 ? 'ativa' : 'ativas' }}</span>
                            </div>
                        </div>

                        <div class="permission-module-actions">
                            <button type="button" wire:click="enableModule(@js($module))">Marcar todas</button>
                            <button type="button" wire:click="disableModule(@js($module))">Desmarcar todas</button>
                            <button type="button" wire:click="makeModuleReadOnly(@js($module))">Somente leitura</button>
                        </div>
                    </header>

                    <div class="permission-table-wrap">
                        <table class="permission-table">
                            <thead>
                                <tr>
                                    <th>Permissão</th>
                                    <th>Ativa</th>
                                    <th>Módulo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($permissions as $permission)
                                    @php
                                        $permissionName = $permission['name'];
                                        $isActive = (bool) ($this->permissionState[$permissionName] ?? false);
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="permission-name-cell">
                                                <strong>{{ $permission['label'] }}</strong>
                                                <span class="permission-module-badge permission-mobile-module">{{ $permission['module'] }}</span>
                                                <span>{{ $permissionName }}</span>
                                                @if(! empty($permission['description']))
                                                    <small>{{ $permission['description'] }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <button
                                                type="button"
                                                wire:click="togglePermission(@js($permissionName))"
                                                class="permission-checkbox {{ $isActive ? 'is-active' : '' }}"
                                                aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                                                aria-label="Alternar permissão {{ $permission['label'] }}"
                                            >
                                                @if($isActive)
                                                    @svg('fas-check')
                                                @endif
                                            </button>
                                        </td>
                                        <td>
                                            <span class="permission-module-badge">{{ $permission['module'] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @empty
                <section class="ms-card permission-card permission-empty">
                    @svg('fas-magnifying-glass')
                    <h3>Nenhuma permissão encontrada</h3>
                    <p>Ajuste os filtros para visualizar outras permissões.</p>
                </section>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
