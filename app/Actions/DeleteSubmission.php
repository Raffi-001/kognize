<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Comment;
use App\Models\Report;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a submission with everything attached to it. The database cascades
 * analyses, findings, evidence and community notes; comments and reports are
 * polymorphic, so they are removed here.
 */
final readonly class DeleteSubmission
{
    public function handle(Submission $submission): void
    {
        DB::transaction(function () use ($submission): void {
            $noteIds = $submission->notes()->pluck('analysis_notes.id');
            $userNoteIds = $submission->userNotes()->pluck('id');

            $commentIds = Comment::query()
                ->where(fn (Builder $query) => $query
                    ->where(fn (Builder $query) => $query->where('commentable_type', 'analysis_note')->whereIn('commentable_id', $noteIds))
                    ->orWhere(fn (Builder $query) => $query->where('commentable_type', 'user_note')->whereIn('commentable_id', $userNoteIds)))
                ->pluck('id');

            Report::query()
                ->where(fn (Builder $query) => $query
                    ->where(fn (Builder $query) => $query->where('reportable_type', 'submission')->where('reportable_id', $submission->id))
                    ->orWhere(fn (Builder $query) => $query->where('reportable_type', 'analysis_note')->whereIn('reportable_id', $noteIds))
                    ->orWhere(fn (Builder $query) => $query->where('reportable_type', 'user_note')->whereIn('reportable_id', $userNoteIds))
                    ->orWhere(fn (Builder $query) => $query->where('reportable_type', 'comment')->whereIn('reportable_id', $commentIds)))
                ->delete();

            Comment::query()->whereKey($commentIds)->delete();

            $submission->delete();
        });
    }
}
