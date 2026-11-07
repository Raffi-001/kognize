<?php

declare(strict_types=1);

namespace App\Filament\Resources\Polls\Schemas;

use App\Filament\Resources\AnalysisNotes\AnalysisNoteResource;
use App\Filament\Resources\Polls\PollResource;
use App\Models\Poll;
use App\Models\PollOption;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class PollInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Poll')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('question')
                            ->columnSpanFull(),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('published_at')
                            ->dateTime()
                            ->placeholder('Not published'),
                        TextEntry::make('votes_count')
                            ->label('Answers')
                            ->state(fn (Poll $record): int => $record->votes()->count()),
                    ]),
                Section::make('Results')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('options')
                            ->hiddenLabel()
                            ->columns(2)
                            ->schema([
                                TextEntry::make('label')
                                    ->hiddenLabel(),
                                TextEntry::make('votes')
                                    ->hiddenLabel()
                                    ->state(function (PollOption $record): string {
                                        $votes = $record->votes()->count();
                                        $total = max(1, $record->poll->votes()->count());

                                        return sprintf('%d answers (%d%%)', $votes, round($votes / $total * 100));
                                    }),
                            ]),
                    ]),
                Section::make('Where it came from')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('source.aspect_label')
                            ->label('Open question in the analysis')
                            ->placeholder('Created by an admin')
                            ->url(fn (Poll $record): ?string => $record->analysis_note_id === null
                                ? null
                                : AnalysisNoteResource::getUrl('view', ['record' => $record->analysis_note_id])),
                        TextEntry::make('mirror_question')
                            ->label('Mirrored version')
                            ->state(fn (Poll $record): ?string => ($record->mirrorOf ?? $record->mirror)?->question)
                            ->placeholder('None')
                            ->url(function (Poll $record): ?string {
                                $pair = $record->mirrorOf ?? $record->mirror;

                                return $pair === null ? null : PollResource::getUrl('view', ['record' => $pair]);
                            }),
                    ]),
            ]);
    }
}
