<?php

declare(strict_types=1);

namespace App\Filament\Resources\AnalysisNotes\Schemas;

use App\Enums\QuestionKind;
use App\Filament\Resources\Polls\PollResource;
use App\Models\AnalysisNote;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class AnalysisNoteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Question')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('aspect_label')
                            ->hiddenLabel()
                            ->size('lg')
                            ->columnSpanFull(),
                        TextEntry::make('question_kind')
                            ->label('Kind')
                            ->badge(),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('suggested_verdict')
                            ->label('Evidence points to')
                            ->badge()
                            ->state(fn (AnalysisNote $record): mixed => $record->suggestedVerdict())
                            ->placeholder('No verified evidence yet'),
                        TextEntry::make('label')
                            ->label('Passage in the text')
                            ->columnSpanFull(),
                        TextEntry::make('text')
                            ->label('Why it was asked')
                            ->columnSpanFull(),
                        TextEntry::make('analysis.submission.title')
                            ->label('Submission')
                            ->url(fn (AnalysisNote $record): string => route('submissions.show', $record->analysis->submission_id))
                            ->openUrlInNewTab()
                            ->columnSpanFull(),
                        TextEntry::make('poll.question')
                            ->label('Public poll')
                            ->visible(fn (AnalysisNote $record): bool => $record->question_kind === QuestionKind::Value)
                            ->placeholder('No poll drafted')
                            ->url(fn (AnalysisNote $record): ?string => $record->poll === null
                                ? null
                                : PollResource::getUrl('view', ['record' => $record->poll]))
                            ->columnSpanFull(),
                    ]),
                Section::make('Resolution')
                    ->columns(3)
                    ->columnSpanFull()
                    ->visible(fn (AnalysisNote $record): bool => $record->resolved_at !== null)
                    ->schema([
                        TextEntry::make('verdict')
                            ->badge(),
                        TextEntry::make('resolver.name')
                            ->label('Resolved by'),
                        TextEntry::make('resolved_at')
                            ->dateTime(),
                        TextEntry::make('resolution')
                            ->label('Explanation')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
