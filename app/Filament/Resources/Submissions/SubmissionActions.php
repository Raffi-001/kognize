<?php

declare(strict_types=1);

namespace App\Filament\Resources\Submissions;

use App\Actions\DeleteSubmission;
use App\Actions\RetrySubmissionAnalysis;
use App\Enums\SubmissionState;
use App\Models\Submission;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Actions shared by the submission table and the submission view page.
 */
final class SubmissionActions
{
    public static function openOnSite(): Action
    {
        return Action::make('open')
            ->label('Open on site')
            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
            ->color('gray')
            ->url(fn (Submission $record): string => route('submissions.show', $record))
            ->openUrlInNewTab();
    }

    public static function delete(): DeleteAction
    {
        return DeleteAction::make()
            ->modalDescription('The text, its analysis, findings, evidence, discussion and reports are deleted for good.')
            ->using(function (Submission $record, DeleteSubmission $delete): bool {
                $delete->handle($record);

                return true;
            });
    }

    public static function retry(): Action
    {
        return Action::make('retry')
            ->label('Retry analysis')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('warning')
            ->visible(fn (Submission $record): bool => $record->state === SubmissionState::Failed)
            ->requiresConfirmation()
            ->modalDescription("The text is analyzed again from scratch. It does not count against the author's daily limit.")
            ->action(function (Submission $record, RetrySubmissionAnalysis $retry): void {
                $retry->handle($record);

                Notification::make()->title('Analysis queued again')->success()->send();
            });
    }
}
