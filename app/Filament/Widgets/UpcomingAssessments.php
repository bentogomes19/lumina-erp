<?php

namespace App\Filament\Widgets;

use App\Models\Assessment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UpcomingAssessments extends BaseWidget
{
    protected static ?string $heading = 'Próximas Avaliações';
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('student') ?? false;
    }

    public function table(Table $table): Table
    {
        $student = auth()->user()?->student;
        $classIds = $student?->classes()->pluck('classes.id') ?? collect([-1]);

        return $table
            ->query(
                Assessment::query()
                    ->with(['subject', 'class'])
                    ->whereIn('class_id', $classIds)
                    ->where('scheduled_at', '>=', now())
                    ->orderBy('scheduled_at')
            )
            ->columns([
                TextColumn::make('scheduled_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('subject.name')
                    ->label('Disciplina')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('title')
                    ->label('Avaliação')
                    ->limit(40),

                TextColumn::make('weight')
                    ->label('Peso')
                    ->numeric()
                    ->alignRight(),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
