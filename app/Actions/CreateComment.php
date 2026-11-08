<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\NotificationKind;
use App\Models\AnalysisNote;
use App\Models\Comment;
use App\Models\User;
use App\Models\UserNote;

final readonly class CreateComment
{
    public function __construct(
        private NotifyActivity $notify,
        private AwardBadges $badges,
    ) {}

    public function handle(AnalysisNote|UserNote $commentable, User $user, string $text): Comment
    {
        $comment = $commentable->comments()->create([
            'user_id' => $user->id,
            'text' => mb_trim($text),
        ]);

        $submission = $commentable instanceof UserNote
            ? $commentable->submission
            : $commentable->analysis->submission;

        // The author of the note (or the analyzed text) and everyone already in the thread.
        $recipients = $commentable->comments()
            ->with('user')
            ->whereKeyNot($comment->id)
            ->get()
            ->map(fn (Comment $previous): User => $previous->user)
            ->prepend($commentable instanceof UserNote ? $commentable->user : $submission->user);

        $this->notify->handle(
            $recipients,
            NotificationKind::CommentPosted,
            $user,
            [
                'type' => $commentable instanceof UserNote ? 'user_note' : 'analysis_note',
                'id' => $commentable->id,
                'title' => $submission->displayTitle(),
            ],
            route('submissions.show', $submission),
            $comment->text,
        );

        $this->badges->handle($user);

        return $comment;
    }
}
