<?php

declare(strict_types=1);

namespace App\Filament\Resources\AnalysisNotes;

use App\Actions\ReopenQuestion;
use App\Actions\ResolveQuestion;
use App\Enums\QuestionKind;
use App\Enums\QuestionStatus;
use App\Enums\Verdict;
use App\Models\AnalysisNote;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Actions shared by the questions table and the question view page.
 */
final class QuestionActions
{
    public static function resolve(): Action
    {
        return Action::make('resolve')
            ->icon(Heroicon::OutlinedScale)
            ->color('success')
            ->visible(fn (AnalysisNote $record): bool => $record->question_kind === QuestionKind::Factual
                && $record->status === QuestionStatus::Open)
            ->modalDescription('Resolve this question based on the verified evidence. Readers will see the verdict and your explanation next to the question.')
            ->fillForm(fn (AnalysisNote $record): array => [
                'verdict' => $record->suggestedVerdict(),
            ])
            ->schema([
                Select::make('verdict')
                    ->options(Verdict::class)
                    ->helperText('Pre-filled with what the verified evidence points to.')
                    ->required(),
                Textarea::make('resolution')
                    ->label('Explanation for readers')
                    ->helperText('In Armenian. Summarize the evidence the verdict is based on.')
                    ->required()
                    ->maxLength(2000)
                    ->rows(4),
            ])
            ->action(function (array $data, AnalysisNote $record, ResolveQuestion $resolve): void {
                $moderator = auth()->user();

                assert($moderator instanceof User);
                assert($data['verdict'] instanceof Verdict);
                assert(is_string($data['resolution']));

                $resolve->handle($record, $moderator, $data['verdict'], $data['resolution']);

                Notification::make()->title('Question resolved')->success()->send();
            });
    }

    public static function reopen(): Action
    {
        return Action::make('reopen')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('gray')
            ->visible(fn (AnalysisNote $record): bool => $record->status === QuestionStatus::Resolved)
            ->requiresConfirmation()
            ->action(function (AnalysisNote $record, ReopenQuestion $reopen): void {
                $reopen->handle($record);

                Notification::make()->title('Question reopened')->success()->send();
            });
    }
}
