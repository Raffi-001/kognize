<?php

declare(strict_types=1);

namespace App\Filament\Resources\Submissions\Schemas;

use App\Filament\Resources\Users\UserResource;
use App\Models\Submission;
use App\Models\SubmissionAnalysis;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class SubmissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Submission')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('title')
                            ->columnSpanFull(),
                        TextEntry::make('user.name')
                            ->label('Author')
                            ->url(fn (Submission $record): string => UserResource::getUrl('view', ['record' => $record->user_id])),
                        TextEntry::make('state')
                            ->badge(),
                        TextEntry::make('kind')
                            ->badge()
                            ->color('gray')
                            ->placeholder('Not classified yet'),
                        TextEntry::make('source')
                            ->url(fn (Submission $record): ?string => $record->source, shouldOpenInNewTab: true)
                            ->placeholder('Pasted text')
                            ->columnSpan(2),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ]),
                Section::make('Analyses')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('analyses')
                            ->hiddenLabel()
                            ->columns(3)
                            ->placeholder('No analysis has run yet.')
                            ->schema([
                                TextEntry::make('type')
                                    ->formatStateUsing(fn (SubmissionAnalysis $record): string => $record->type->label()),
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn (SubmissionAnalysis $record): string => str($record->status->value)->replace('_', ' ')->ucfirst()->toString()),
                                TextEntry::make('findings')
                                    ->state(fn (SubmissionAnalysis $record): int => $record->notes()->count()),
                            ]),
                    ]),
                Section::make('Text')
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        TextEntry::make('body')
                            ->hiddenLabel()
                            ->extraAttributes(['lang' => 'hy', 'class' => 'whitespace-pre-line']),
                    ]),
            ]);
    }
}
