<?php

declare(strict_types=1);

namespace App\Filament\Resources\Polls\Pages;

use App\Enums\PollStatus;
use App\Filament\Resources\Polls\PollActions;
use App\Filament\Resources\Polls\PollResource;
use App\Models\Poll;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewPoll extends ViewRecord
{
    protected static string $resource = PollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PollActions::publish(),
            PollActions::close(),
            EditAction::make()
                ->visible(fn (Poll $record): bool => $record->status === PollStatus::Draft),
        ];
    }
}
