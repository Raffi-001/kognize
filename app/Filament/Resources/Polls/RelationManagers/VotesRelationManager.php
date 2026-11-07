<?php

declare(strict_types=1);

namespace App\Filament\Resources\Polls\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Every answer, kept as the record the system learns from. Read-only.
 */
final class VotesRelationManager extends RelationManager
{
    protected static string $relationship = 'votes';

    protected static ?string $title = 'Answers';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('option.label')
                    ->label('Answer')
                    ->badge(),
                TextColumn::make('reason')
                    ->wrap()
                    ->placeholder('No reason given'),
                TextColumn::make('user.name')
                    ->label('By')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('poll_option_id')
                    ->label('Answer')
                    ->relationship('option', 'label'),
            ]);
    }
}
