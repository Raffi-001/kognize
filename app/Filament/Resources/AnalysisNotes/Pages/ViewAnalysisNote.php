<?php

declare(strict_types=1);

namespace App\Filament\Resources\AnalysisNotes\Pages;

use App\Filament\Resources\AnalysisNotes\AnalysisNoteResource;
use App\Filament\Resources\AnalysisNotes\QuestionActions;
use Filament\Resources\Pages\ViewRecord;

final class ViewAnalysisNote extends ViewRecord
{
    protected static string $resource = AnalysisNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            QuestionActions::resolve(),
            QuestionActions::reopen(),
        ];
    }
}
