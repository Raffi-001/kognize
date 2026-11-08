<?php

declare(strict_types=1);

namespace App\Filament\Resources\Invitations\Pages;

use App\Actions\CreateInvitation;
use App\Filament\Resources\Invitations\InvitationResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

final class ListInvitations extends ListRecords
{
    protected static string $resource = InvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('Generate codes')
                ->icon(Heroicon::OutlinedPlus)
                ->modalDescription('Each code works once. People who sign up with one are recorded as invited by you.')
                ->schema([
                    TextInput::make('email')
                        ->label('Email address (optional)')
                        ->email()
                        ->helperText('With an email address, one code is created, mailed to it and only works for that address.'),
                    TextInput::make('count')
                        ->label('Number of open codes')
                        ->integer()
                        ->minValue(1)
                        ->maxValue(50)
                        ->default(1)
                        ->required()
                        ->helperText('Ignored when an email address is given.'),
                ])
                ->action(function (array $data, CreateInvitation $createInvitation): void {
                    $admin = auth()->user();

                    assert($admin instanceof User);

                    $email = is_string($data['email'] ?? null) && $data['email'] !== '' ? $data['email'] : null;
                    $count = $email === null && is_numeric($data['count'] ?? null) ? (int) $data['count'] : 1;

                    $codes = collect(range(1, $count))
                        ->map(fn (): string => $createInvitation->handle($admin, $email)->code);

                    Notification::make()
                        ->title($email === null ? sprintf('%d %s created', $count, str('code')->plural($count)) : 'Invitation sent to '.$email)
                        ->body($codes->join(', '))
                        ->success()
                        ->persistent()
                        ->send();
                }),
        ];
    }
}
