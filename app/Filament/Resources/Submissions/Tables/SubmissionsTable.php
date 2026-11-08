<?php

declare(strict_types=1);

namespace App\Filament\Resources\Submissions\Tables;

use App\Actions\DeleteSubmission;
use App\Enums\SubmissionKind;
use App\Enums\SubmissionState;
use App\Filament\Resources\Submissions\SubmissionActions;
use App\Filament\Resources\Users\UserResource;
use App\Models\Submission;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

final class SubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->searchable(['title', 'body', 'source'])
                    ->wrap()
                    ->lineClamp(2)
                    ->description(fn (Submission $record): ?string => ($record->source === null ? null : parse_url($record->source, PHP_URL_HOST)) ?: null),
                TextColumn::make('user.name')
                    ->label('Author')
                    ->searchable()
                    ->url(fn (Submission $record): string => UserResource::getUrl('view', ['record' => $record->user_id])),
                TextColumn::make('state')
                    ->badge()
                    ->sortable(),
                TextColumn::make('kind')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
                TextColumn::make('notes_count')
                    ->label('Findings')
                    ->counts('notes')
                    ->sortable(),
                TextColumn::make('reports_count')
                    ->label('Reports')
                    ->counts('reports')
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('state')
                    ->options(SubmissionState::class),
                SelectFilter::make('kind')
                    ->options(SubmissionKind::class),
                SelectFilter::make('user')
                    ->label('Author')
                    ->relationship('user', 'name')
                    ->searchable(),
                TernaryFilter::make('source')
                    ->label('From a link')
                    ->nullable(),
            ])
            ->recordActions([
                SubmissionActions::retry(),
                ActionGroup::make([
                    ViewAction::make(),
                    SubmissionActions::openOnSite(),
                    SubmissionActions::delete(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->using(fn (Collection $records, DeleteSubmission $delete) => $records->whereInstanceOf(Submission::class)->each(
                            fn (Submission $record) => $delete->handle($record),
                        )),
                ]),
            ]);
    }
}
