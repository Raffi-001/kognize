<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Everyone who signed up with one of this user's invitation codes. Read-only.
 */
final class RefereesRelationManager extends RelationManager
{
    protected static string $relationship = 'referees';

    protected static ?string $title = 'People they invited';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->url(fn (User $record): string => UserResource::getUrl('view', ['record' => $record])),
                TextColumn::make('email'),
                TextColumn::make('referees_count')
                    ->label('Invited in turn')
                    ->counts('referees'),
                TextColumn::make('created_at')
                    ->label('Joined')
                    ->since()
                    ->sortable(),
            ]);
    }
}
