<?php

declare(strict_types=1);

namespace App\Filament\Resources\Polls\Tables;

use App\Enums\PollStatus;
use App\Filament\Resources\Polls\PollActions;
use App\Models\Poll;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class PollsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('question')
                    ->searchable()
                    ->wrap()
                    ->lineClamp(2),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_mirror')
                    ->label('Mirror')
                    ->boolean()
                    ->state(fn (Poll $record): bool => $record->mirror_of_id !== null),
                TextColumn::make('votes_count')
                    ->label('Answers')
                    ->counts('votes')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(PollStatus::class),
            ])
            ->recordActions([
                PollActions::publish(),
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn (Poll $record): bool => $record->status === PollStatus::Draft),
                    PollActions::close(),
                    DeleteAction::make()
                        ->visible(fn (Poll $record): bool => $record->status === PollStatus::Draft),
                ]),
            ]);
    }
}
