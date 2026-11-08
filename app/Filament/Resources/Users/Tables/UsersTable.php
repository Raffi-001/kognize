<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Tables;

use App\Enums\Plan;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('plan')
                    ->badge(),
                IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean(),
                IconColumn::make('verified')
                    ->boolean()
                    ->state(fn (User $record): bool => $record->email_verified_at !== null),
                TextColumn::make('referrer.name')
                    ->label('Invited by')
                    ->placeholder('—'),
                TextColumn::make('referees_count')
                    ->label('Invited')
                    ->counts('referees')
                    ->sortable(),
                TextColumn::make('submissions_count')
                    ->label('Analyses')
                    ->counts('submissions')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Joined')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('plan')
                    ->options(Plan::class),
                TernaryFilter::make('is_admin')
                    ->label('Administrator'),
                TernaryFilter::make('email_verified_at')
                    ->label('Verified')
                    ->nullable(),
                TernaryFilter::make('referrer_id')
                    ->label('Invited by someone')
                    ->nullable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
