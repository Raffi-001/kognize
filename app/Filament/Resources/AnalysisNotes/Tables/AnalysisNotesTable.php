<?php

declare(strict_types=1);

namespace App\Filament\Resources\AnalysisNotes\Tables;

use App\Enums\QuestionKind;
use App\Enums\QuestionStatus;
use App\Enums\Verdict;
use App\Filament\Resources\AnalysisNotes\QuestionActions;
use App\Models\AnalysisNote;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class AnalysisNotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('aspect_label')
                    ->label('Question')
                    ->searchable()
                    ->wrap()
                    ->lineClamp(2),
                TextColumn::make('question_kind')
                    ->label('Kind')
                    ->badge(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('evidence_count')
                    ->label('Evidence')
                    ->counts('evidence')
                    ->sortable(),
                TextColumn::make('suggested_verdict')
                    ->label('Evidence points to')
                    ->badge()
                    ->state(fn (AnalysisNote $record): ?Verdict => $record->status === QuestionStatus::Open
                        ? $record->suggestedVerdict()
                        : $record->verdict)
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('question_kind')
                    ->label('Kind')
                    ->options(QuestionKind::class),
                SelectFilter::make('status')
                    ->options(QuestionStatus::class)
                    ->default(QuestionStatus::Open->value),
            ])
            ->recordActions([
                QuestionActions::resolve(),
                ViewAction::make(),
            ]);
    }
}
