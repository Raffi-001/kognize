<?php

declare(strict_types=1);

use App\Enums\ReputationReason;

it('picks the reason for a vote on each kind of content', function (string $type, int $value, ReputationReason $reason): void {
    expect(ReputationReason::forVote($type, $value))->toBe($reason);
})->with([
    ['user_note', 1, ReputationReason::NoteUpvoted],
    ['user_note', -1, ReputationReason::NoteDownvoted],
    ['evidence', 1, ReputationReason::EvidenceUpvoted],
    ['evidence', -1, ReputationReason::EvidenceDownvoted],
    ['comment', 1, ReputationReason::CommentUpvoted],
    ['comment', -1, ReputationReason::CommentDownvoted],
]);

it('reads its points from config and has a label', function (ReputationReason $reason): void {
    config(['kognize.reputation.points.'.$reason->value => 7]);

    expect($reason->points())->toBe(7)
        ->and($reason->getLabel())->toBeString()->not->toBeEmpty();
})->with(ReputationReason::cases());

it('caps only upvote earnings', function (): void {
    $capped = array_values(array_filter(ReputationReason::cases(), fn (ReputationReason $reason): bool => $reason->isCapped()));

    expect($capped)->toBe([ReputationReason::NoteUpvoted, ReputationReason::EvidenceUpvoted, ReputationReason::CommentUpvoted]);
});

it('rewards upvotes and costs for downvotes by default', function (): void {
    expect(ReputationReason::NoteUpvoted->points())->toBe(10)
        ->and(ReputationReason::CommentUpvoted->points())->toBe(2)
        ->and(ReputationReason::NoteDownvoted->points())->toBe(-2)
        ->and(ReputationReason::DownvoteCast->points())->toBe(-1);
});
