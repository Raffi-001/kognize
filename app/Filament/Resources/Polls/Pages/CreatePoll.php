<?php

declare(strict_types=1);

namespace App\Filament\Resources\Polls\Pages;

use App\Filament\Resources\Polls\PollResource;
use Filament\Resources\Pages\CreateRecord;

final class CreatePoll extends CreateRecord
{
    protected static string $resource = PollResource::class;
}
