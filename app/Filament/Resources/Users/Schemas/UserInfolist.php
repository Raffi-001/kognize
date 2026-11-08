<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email')
                            ->copyable(),
                        TextEntry::make('plan')
                            ->badge(),
                        IconEntry::make('is_admin')
                            ->label('Administrator')
                            ->boolean(),
                        TextEntry::make('email_verified_at')
                            ->label('Email verified')
                            ->dateTime()
                            ->placeholder('Not verified'),
                        TextEntry::make('created_at')
                            ->label('Joined')
                            ->dateTime(),
                    ]),
                Section::make('Referrals')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('referrer.name')
                            ->label('Invited by')
                            ->placeholder('Nobody (first users and admins)')
                            ->url(fn (User $record): ?string => $record->referrer_id === null
                                ? null
                                : UserResource::getUrl('view', ['record' => $record->referrer_id])),
                        TextEntry::make('referees_count')
                            ->label('People they invited')
                            ->state(fn (User $record): int => $record->referees()->count()),
                        TextEntry::make('unused_invitations')
                            ->label('Unused codes')
                            ->state(fn (User $record): int => $record->invitations()->whereNull('used_at')->count()),
                    ]),
                Section::make('Activity')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('submissions_count')
                            ->label('Analyses')
                            ->state(fn (User $record): int => $record->submissions()->count()),
                        TextEntry::make('submissions_today')
                            ->label('Analyses today')
                            ->state(fn (User $record): string => sprintf('%d of %d', $record->submissionsUsedToday(), $record->plan->dailySubmissions())),
                    ]),
            ]);
    }
}
