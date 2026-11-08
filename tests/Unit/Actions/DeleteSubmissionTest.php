<?php

declare(strict_types=1);

use App\Actions\CastVote;
use App\Actions\DeleteSubmission;
use App\Enums\AnalysisType;
use App\Models\AnalysisNote;
use App\Models\Comment;
use App\Models\Evidence;
use App\Models\Report;
use App\Models\Submission;
use App\Models\SubmissionAnalysis;
use App\Models\User;
use App\Models\UserNote;
use App\Models\Vote;

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

it('removes votes on the community notes, replies and sources of the submission', function (): void {
    $submission = Submission::factory()->create();
    $note = AnalysisNote::factory()->question()->create([
        'submission_analysis_id' => SubmissionAnalysis::factory()->for($submission)->create(['type' => AnalysisType::Questions])->id,
    ]);
    $userNote = UserNote::factory()->for($submission)->create();
    $comment = Comment::factory()->create(['commentable_type' => 'user_note', 'commentable_id' => $userNote->id]);
    $evidence = Evidence::factory()->for($note, 'question')->create();
    $elsewhere = UserNote::factory()->for($userNote->user)->create();
    $voter = User::factory()->create();

    foreach ([$userNote, $comment, $evidence, $elsewhere] as $votable) {
        resolve(CastVote::class)->handle($voter, $votable, 1);
    }

    resolve(DeleteSubmission::class)->handle($submission);

    expect(Vote::query()->sole()->votable_id)->toBe($elsewhere->id)
        ->and($userNote->user->refresh()->reputation)->toBe(11)
        ->and($evidence->user->refresh()->reputation)->toBe(1)
        ->and($comment->user->refresh()->reputation)->toBe(1);
});
