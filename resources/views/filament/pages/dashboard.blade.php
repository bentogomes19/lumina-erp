<x-filament::page>
    <div class="space-y-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                👋 Bem-vindo, {{ auth()->user()->name }}!
            </h1>
            <p class="text-gray-600 mt-1">
                Aqui está um resumo geral do sistema Lumina ERP.
            </p>
        </div>

        {{-- Estatísticas --}}
        @foreach ($this->getHeaderWidgets() as $widget)
            @livewire($widget)
        @endforeach

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mt-8">
            {{-- Bloco de avisos rápidos --}}
            <x-filament::card>
                <h2 class="text-lg font-semibold text-gray-700">📢 Avisos</h2>
                <ul class="mt-3 space-y-1 text-sm text-gray-600">
                    <li>🗓️ Próximo fechamento de notas: <b>30/10/2025</b></li>
                    <li>📚 Novo semestre inicia em <b>03/02/2026</b></li>
                    <li>👨‍🏫 Professores devem atualizar presença semanal.</li>
                </ul>
            </x-filament::card>

            {{-- Bloco de atalhos --}}
            <x-filament::card>
                <h2 class="text-lg font-semibold text-gray-700">⚡ Acessos rápidos</h2>
                <div class="flex flex-wrap gap-3 mt-3">
                    <x-filament::button tag="a" href="{{ route('filament.admin.resources.students.index') }}" color="primary">
                        Alunos
                    </x-filament::button>
                    <x-filament::button tag="a" href="{{ route('filament.admin.resources.teachers.index') }}" color="info">
                        Professores
                    </x-filament::button>
                    <x-filament::button tag="a" href="{{ route('filament.admin.resources.enrollments.index') }}" color="success">
                        Matrículas
                    </x-filament::button>
                </div>
            </x-filament::card>

            {{-- Bloco de notas --}}
            <x-filament::card>
                <h2 class="text-lg font-semibold text-gray-700">📊 Desempenho Acadêmico</h2>
                <p class="text-sm text-gray-600 mt-3">
                    Este módulo apresentará gráficos e médias de notas (em breve).
                </p>
            </x-filament::card>
        </div>
    </div>
</x-filament::page>
