<?php

declare(strict_types=1);

namespace App\Filament\Resources\Newsletters\Tables;

use App\Filament\Resources\Newsletters\NewsletterActions;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class NewslettersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('subject')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('submissions_count')
                    ->label('Submissions')
                    ->counts('submissions'),
                TextColumn::make('polls_count')
                    ->label('Polls')
                    ->counts('polls'),
                TextColumn::make('questions_count')
                    ->label('Questions')
                    ->counts('questions'),
                TextColumn::make('recipients_count')
                    ->label('Sent to')
                    ->counts('recipients')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    NewsletterActions::preview(),
                    DeleteAction::make(),
                ]),
            ]);
    }
}
