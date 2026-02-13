<x-filament-panels::page>
    <div class="space-y-6">
        @php
            $subjects = $this->getSubjects();
        @endphp

        @if($subjects->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($subjects as $subject)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                                    <x-heroicon-o-book-open class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">
                                        {{ $subject->name }}
                                    </h3>
                                    @if($subject->code)
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Código: {{ $subject->code }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($subject->description)
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                {{ Str::limit($subject->description, 120) }}
                            </p>
                        @endif

                        @if($subject->category)
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                    {{ $subject->category->value ?? $subject->category }}
                                </span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-8 text-center">
                <x-heroicon-o-book-open class="w-12 h-12 text-blue-500 mx-auto mb-3" />
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    Nenhuma Disciplina Encontrada
                </h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Você não está matriculado em nenhuma disciplina no momento.
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
