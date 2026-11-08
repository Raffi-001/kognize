<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Plan;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Select::make('plan')
                            ->options(Plan::class)
                            ->required(),
                        DateTimePicker::make('email_verified_at')
                            ->label('Email verified at')
                            ->helperText('Clear it to make the user verify their email again.'),
                        Toggle::make('is_admin')
                            ->label('Administrator')
                            ->helperText('Administrators can use this panel.')
                            ->disabled(fn (User $record): bool => UserResource::isCurrentUser($record)),
                    ]),
            ]);
    }
}
