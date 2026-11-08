<?php

declare(strict_types=1);

namespace App\Filament\Resources\Newsletters\Pages;

use App\Filament\Resources\Newsletters\NewsletterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListNewsletters extends ListRecords
{
    protected static string $resource = NewsletterResource::class;

    public function getSubheading(): string
    {
        return 'To send one, select users in the Users table and choose “Send newsletter”.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
