<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ReputationReason: string implements HasLabel
{
    case NoteUpvoted = 'note_upvoted';
    case NoteDownvoted = 'note_downvoted';
    case EvidenceUpvoted = 'evidence_upvoted';
    case EvidenceDownvoted = 'evidence_downvoted';
    case CommentUpvoted = 'comment_upvoted';
    case CommentDownvoted = 'comment_downvoted';
    case DownvoteCast = 'downvote_cast';

    /**
     * The reason the author of voted content gains or loses reputation.
     */
    public static function forVote(string $votableType, int $value): self
    {
        return match ($votableType) {
            'user_note' => $value > 0 ? self::NoteUpvoted : self::NoteDownvoted,
            'evidence' => $value > 0 ? self::EvidenceUpvoted : self::EvidenceDownvoted,
            default => $value > 0 ? self::CommentUpvoted : self::CommentDownvoted,
        };
    }

    public function points(): int
    {
        return config()->integer('kognize.reputation.points.'.$this->value);
    }

    /**
     * Upvote earnings count towards the daily cap.
     */
    public function isCapped(): bool
    {
        return in_array($this, [self::NoteUpvoted, self::EvidenceUpvoted, self::CommentUpvoted], true);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::NoteUpvoted => 'Community note upvoted',
            self::NoteDownvoted => 'Community note downvoted',
            self::EvidenceUpvoted => 'Source upvoted',
            self::EvidenceDownvoted => 'Source downvoted',
            self::CommentUpvoted => 'Reply upvoted',
            self::CommentDownvoted => 'Reply downvoted',
            self::DownvoteCast => 'Downvoted something',
        };
    }
}
