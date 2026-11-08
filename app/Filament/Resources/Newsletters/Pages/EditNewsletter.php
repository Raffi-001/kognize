<?php

declare(strict_types=1);

namespace App\Filament\Resources\Newsletters\Pages;

use App\Filament\Resources\Newsletters\NewsletterActions;
use App\Filament\Resources\Newsletters\NewsletterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditNewsletter extends EditRecord
{
    protected static string $resource = NewsletterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            NewsletterActions::preview(),
            DeleteAction::make(),
        ];
    }
}
