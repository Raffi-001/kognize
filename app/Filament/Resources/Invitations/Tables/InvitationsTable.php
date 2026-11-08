<?php

declare(strict_types=1);

namespace App\Filament\Resources\Invitations\Tables;

use App\Filament\Resources\Users\UserResource;
use App\Models\Invitation;
use Filament\Actions\DeleteAction;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class InvitationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->fontFamily(FontFamily::Mono)
                    ->weight('bold')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->state(fn (Invitation $record): string => $record->used_at === null ? 'Unused' : 'Used')
                    ->color(fn (string $state): string => $state === 'Unused' ? 'success' : 'gray'),
                TextColumn::make('email')
                    ->placeholder('Open code')
                    ->searchable(),
                TextColumn::make('inviter.name')
                    ->label('Created by')
                    ->url(fn (Invitation $record): string => UserResource::getUrl('view', ['record' => $record->inviter_id])),
                TextColumn::make('invitee.name')
                    ->label('Used by')
                    ->placeholder('—')
                    ->url(fn (Invitation $record): ?string => $record->invitee_id === null
                        ? null
                        : UserResource::getUrl('view', ['record' => $record->invitee_id])),
                TextColumn::make('used_at')
                    ->since()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(['unused' => 'Unused', 'used' => 'Used'])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'unused' => $query->whereNull('used_at'),
                        'used' => $query->whereNotNull('used_at'),
                        default => $query,
                    }),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label('Revoke')
                    ->modalHeading('Revoke this code?')
                    ->visible(fn (Invitation $record): bool => $record->used_at === null),
            ]);
    }
}
