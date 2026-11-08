<?php

declare(strict_types=1);

use App\Actions\DeleteSubmission;
use App\Models\AnalysisNote;
use App\Models\Comment;
use App\Models\Report;
use App\Models\Submission;
use App\Models\SubmissionAnalysis;
use App\Models\UserNote;

it('deletes a submission with its findings, comments and reports', function (): void {
    $submission = Submission::factory()->create();
    $note = AnalysisNote::factory()->for(SubmissionAnalysis::factory()->for($submission), 'analysis')->create();
    $userNote = UserNote::factory()->for($submission)->create();
    $noteComment = Comment::factory()->create(['commentable_type' => 'analysis_note', 'commentable_id' => $note->id]);
    $userNoteComment = Comment::factory()->create(['commentable_type' => 'user_note', 'commentable_id' => $userNote->id]);

    $reports = collect([
        ['submission', $submission->id],
        ['analysis_note', $note->id],
        ['user_note', $userNote->id],
        ['comment', $noteComment->id],
    ])->map(fn (array $target): Report => Report::factory()->create([
        'reportable_type' => $target[0],
        'reportable_id' => $target[1],
    ]));

    $other = Submission::factory()->create();
    $otherNote = AnalysisNote::factory()->for(SubmissionAnalysis::factory()->for($other), 'analysis')->create();
    $otherComment = Comment::factory()->create(['commentable_type' => 'analysis_note', 'commentable_id' => $otherNote->id]);
    $otherReport = Report::factory()->create(['reportable_type' => 'submission', 'reportable_id' => $other->id]);

    resolve(DeleteSubmission::class)->handle($submission);

    expect(Submission::query()->whereKey($submission->id)->exists())->toBeFalse()
        ->and(AnalysisNote::query()->whereKey($note->id)->exists())->toBeFalse()
        ->and(UserNote::query()->whereKey($userNote->id)->exists())->toBeFalse()
        ->and(Comment::query()->whereKey([$noteComment->id, $userNoteComment->id])->exists())->toBeFalse()
        ->and(Report::query()->whereKey($reports->pluck('id'))->exists())->toBeFalse()
        ->and($otherComment->fresh())->not->toBeNull()
        ->and($otherReport->fresh())->not->toBeNull()
        ->and($otherNote->fresh())->not->toBeNull();
});
