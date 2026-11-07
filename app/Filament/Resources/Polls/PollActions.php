<?php

declare(strict_types=1);

namespace App\Filament\Resources\Polls;

use App\Actions\ClosePoll;
use App\Actions\PublishPoll;
use App\Enums\PollStatus;
use App\Models\Poll;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Actions shared by the poll table and the poll view page.
 */
final class PollActions
{
    public static function publish(): Action
    {
        return Action::make('publish')
            ->label(fn (Poll $record): string => $record->status === PollStatus::Closed ? 'Reopen' : 'Approve & publish')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (Poll $record): bool => $record->status !== PollStatus::Published)
            ->requiresConfirmation()
            ->modalDescription('The question will appear in the Public questions section for everyone to answer. Make sure it is neutral and names no people, parties or events.')
            ->action(function (Poll $record, PublishPoll $publish): void {
                $admin = auth()->user();

                assert($admin instanceof User);

                $publish->handle($record, $admin);

                Notification::make()->title('Poll published')->success()->send();
            });
    }

    public static function close(): Action
    {
        return Action::make('close')
            ->icon(Heroicon::OutlinedLockClosed)
            ->color('gray')
            ->visible(fn (Poll $record): bool => $record->status === PollStatus::Published)
            ->requiresConfirmation()
            ->modalDescription('People will no longer be able to answer. Results stay visible.')
            ->action(function (Poll $record, ClosePoll $close): void {
                $close->handle($record);

                Notification::make()->title('Poll closed')->success()->send();
            });
    }
}
