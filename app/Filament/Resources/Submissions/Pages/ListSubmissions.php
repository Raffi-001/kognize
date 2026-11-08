<?php

declare(strict_types=1);

namespace App\Filament\Resources\Submissions\Pages;

use App\Filament\Resources\Submissions\SubmissionResource;
use Filament\Resources\Pages\ListRecords;

final class ListSubmissions extends ListRecords
{
    protected static string $resource = SubmissionResource::class;
}
