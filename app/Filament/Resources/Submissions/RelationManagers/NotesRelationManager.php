<?php

declare(strict_types=1);

namespace App\Filament\Resources\Submissions\RelationManagers;

use App\Enums\AnalysisType;
use App\Models\AnalysisNote;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The findings of a submission, across all of its analyses.
 */
final class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    protected static ?string $title = 'Findings';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->badge()
                    ->state(fn (AnalysisNote $record): string => $record->analysis->type->label()),
                TextColumn::make('aspect_label')
                    ->label('Finding')
                    ->wrap()
                    ->placeholder('—'),
                TextColumn::make('label')
                    ->label('Passage')
                    ->wrap()
                    ->lineClamp(2)
                    ->placeholder('—'),
                TextColumn::make('strength')
                    ->sortable(),
                TextColumn::make('ethics')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(collect(AnalysisType::cases())->mapWithKeys(fn (AnalysisType $type): array => [$type->value => $type->label()]))
                    ->query(fn (Builder $query, array $data): Builder => blank($data['value'] ?? null)
                        ? $query
                        : $query->where('submission_analyses.type', $data['value'])),
            ]);
    }
}
