<?php

declare(strict_types=1);

namespace App\Filament\Resources\AnalysisNotes;

use App\Enums\QuestionKind;
use App\Enums\QuestionStatus;
use App\Filament\Resources\AnalysisNotes\Pages\ListAnalysisNotes;
use App\Filament\Resources\AnalysisNotes\Pages\ViewAnalysisNote;
use App\Filament\Resources\AnalysisNotes\RelationManagers\EvidenceRelationManager;
use App\Filament\Resources\AnalysisNotes\Schemas\AnalysisNoteInfolist;
use App\Filament\Resources\AnalysisNotes\Tables\AnalysisNotesTable;
use App\Models\AnalysisNote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Open questions raised by analyses, where moderators resolve factual ones
 * based on the evidence readers submitted.
 */
final class AnalysisNoteResource extends Resource
{
    protected static ?string $model = AnalysisNote::class;

    protected static ?string $slug = 'questions';

    protected static ?string $modelLabel = 'question';

    protected static ?string $navigationLabel = 'Questions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Moderation';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'aspect_label';

    /**
     * Records are long Armenian questions, so titles are shortened for headings and breadcrumbs.
     */
    public static function getRecordTitle(?Model $record): string
    {
        $title = $record?->getAttribute('aspect_label');

        return Str::limit(is_string($title) ? $title : '', 70);
    }

    /**
     * @return Builder<AnalysisNote>
     */
    public static function getEloquentQuery(): Builder
    {
        return AnalysisNote::query()
            ->whereNotNull('question_kind')
            ->with(['analysis.submission', 'evidence']);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AnalysisNoteInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AnalysisNotesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            EvidenceRelationManager::class,
        ];
    }

    /**
     * Factual questions that have evidence but no resolution yet.
     */
    public static function getNavigationBadge(): ?string
    {
        $waiting = AnalysisNote::query()
            ->where('question_kind', QuestionKind::Factual)
            ->where('status', QuestionStatus::Open)
            ->whereHas('evidence')
            ->count();

        return $waiting > 0 ? (string) $waiting : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnalysisNotes::route('/'),
            'view' => ViewAnalysisNote::route('/{record}'),
        ];
    }
}
