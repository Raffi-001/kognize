<?php

declare(strict_types=1);

namespace App\Filament\Resources\Submissions\Pages;

use App\Filament\Resources\Submissions\SubmissionActions;
use App\Filament\Resources\Submissions\SubmissionResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewSubmission extends ViewRecord
{
    protected static string $resource = SubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SubmissionActions::openOnSite(),
            SubmissionActions::retry(),
            SubmissionActions::delete(),
        ];
    }
}
