<?php

declare(strict_types=1);

namespace App\Filament\Resources\AnalysisNotes\Pages;

use App\Filament\Resources\AnalysisNotes\AnalysisNoteResource;
use Filament\Resources\Pages\ListRecords;

final class ListAnalysisNotes extends ListRecords
{
    protected static string $resource = AnalysisNoteResource::class;
}
