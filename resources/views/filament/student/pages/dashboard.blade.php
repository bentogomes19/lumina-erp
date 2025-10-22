<x-filament::page>
    <div class="p-6 space-y-4">
        <h1 class="text-2xl font-bold text-gray-800">
            🎓 Bem-vindo, {{ auth()->user()->name }}
        </h1>
        <p class="text-gray-600">
            Este é o seu portal de aluno. Aqui você poderá ver notas, turmas e mensagens.
        </p>
    </div>
</x-filament::page>
