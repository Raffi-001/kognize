<?php

declare(strict_types=1);

namespace App\Filament\Resources\AnalysisNotes\RelationManagers;

use App\Enums\EvidenceVerification;
use App\Jobs\VerifyEvidenceJob;
use App\Models\Evidence;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class EvidenceRelationManager extends RelationManager
{
    protected static string $relationship = 'evidence';

    /**
     * Questions only have a view page, where relation managers are read-only by default.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('stance')
                    ->badge(),
                TextColumn::make('quote')
                    ->wrap()
                    ->lineClamp(3)
                    ->description(fn (Evidence $record): ?string => $record->explanation),
                TextColumn::make('url')
                    ->label('Source')
                    ->state(fn (Evidence $record): string => $record->page_title ?? (string) parse_url($record->url, PHP_URL_HOST))
                    ->url(fn (Evidence $record): string => $record->url)
                    ->openUrlInNewTab()
                    ->wrap(),
                TextColumn::make('verification')
                    ->badge(),
                TextColumn::make('assessment')
                    ->label('AI assessment')
                    ->badge()
                    ->description(fn (Evidence $record): ?string => $record->assessment_reason)
                    ->placeholder('—'),
                TextColumn::make('user.name')
                    ->label('By'),
                TextColumn::make('created_at')
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('verification')
                    ->options(EvidenceVerification::class),
            ])
            ->recordActions([
                Action::make('reverify')
                    ->label('Check again')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->action(function (Evidence $record): void {
                        $record->update(['verification' => EvidenceVerification::Pending]);

                        dispatch(new VerifyEvidenceJob($record));

                        Notification::make()->title('The source will be checked again')->success()->send();
                    }),
                DeleteAction::make(),
            ]);
    }
}
