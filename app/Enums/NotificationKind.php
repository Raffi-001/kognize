<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Notifications\DatabaseNotification;

enum NotificationKind: string
{
    case AnalysisCompleted = 'analysis_completed';
    case AnalysisFailed = 'analysis_failed';
    case CommentPosted = 'comment_posted';
    case CommunityNoteAdded = 'community_note_added';
    case EvidenceChecked = 'evidence_checked';
    case QuestionResolved = 'question_resolved';
    case InviteeJoined = 'invitee_joined';
    case BadgeEarned = 'badge_earned';
    case PrivilegeUnlocked = 'privilege_unlocked';

    /**
     * Turn a stored notification into the text people see.
     *
     * @return array{kind: self|null, headline: string, excerpt: string|null, actors: list<string>}
     */
    public static function summarize(DatabaseNotification $notification): array
    {
        /** @var array<string, mixed> $data */
        $data = $notification->data;
        $subject = is_array($data['subject'] ?? null) ? $data['subject'] : [];
        $title = is_string($subject['title'] ?? null) ? $subject['title'] : '';
        $actors = array_values(array_filter(array_map(
            fn (mixed $actor): ?string => is_array($actor) && is_string($actor['name'] ?? null) ? $actor['name'] : null,
            is_array($data['actors'] ?? null) ? $data['actors'] : [],
        )));
        $kind = self::tryFrom($notification->type);

        return [
            'kind' => $kind,
            'headline' => $kind?->headline($actors, $title) ?? $title,
            'excerpt' => is_string($data['excerpt'] ?? null) ? $data['excerpt'] : null,
            'actors' => $actors,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::AnalysisCompleted => 'Analysis ready',
            self::AnalysisFailed => 'Analysis failed',
            self::CommentPosted => 'Comments',
            self::CommunityNoteAdded => 'Community notes',
            self::EvidenceChecked => 'Evidence checks',
            self::QuestionResolved => 'Resolved questions',
            self::InviteeJoined => 'Invitations',
            self::BadgeEarned => 'Badges',
            self::PrivilegeUnlocked => 'Privileges',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::AnalysisCompleted => 'When an analysis of your text finishes.',
            self::AnalysisFailed => 'When an analysis of your text could not be completed.',
            self::CommentPosted => 'When someone replies on your notes, your analyses, or a thread you joined.',
            self::CommunityNoteAdded => 'When someone adds a community note to your analysis.',
            self::EvidenceChecked => 'When a source you cited has been checked.',
            self::QuestionResolved => 'When a question you added evidence to is resolved.',
            self::InviteeJoined => 'When someone joins with your invitation code.',
            self::BadgeEarned => 'When you earn a badge.',
            self::PrivilegeUnlocked => 'When your reputation unlocks something new.',
        };
    }

    /**
     * Repeat activity on the same subject is folded into one notification.
     */
    public function groups(): bool
    {
        return match ($this) {
            self::CommentPosted, self::CommunityNoteAdded => true,
            default => false,
        };
    }

    /**
     * @param  list<string>  $actors
     */
    public function headline(array $actors, string $subject): string
    {
        $who = match (count($actors)) {
            0 => 'Someone',
            1 => $actors[0],
            2 => sprintf('%s and %s', $actors[0], $actors[1]),
            default => sprintf('%s and %d others', $actors[0], count($actors) - 1),
        };

        return match ($this) {
            self::AnalysisCompleted => sprintf('Your analysis of “%s” is ready', $subject),
            self::AnalysisFailed => sprintf('The analysis of “%s” could not be completed', $subject),
            self::CommentPosted => sprintf('%s commented on “%s”', $who, $subject),
            self::CommunityNoteAdded => sprintf('%s added a community note to “%s”', $who, $subject),
            self::EvidenceChecked => sprintf('Your source for “%s” was checked', $subject),
            self::QuestionResolved => sprintf('“%s” was resolved', $subject),
            self::InviteeJoined => sprintf('%s joined with your invitation', $who),
            self::BadgeEarned => sprintf('You earned the “%s” badge', $subject),
            self::PrivilegeUnlocked => sprintf('New privilege unlocked: %s', $subject),
        };
    }
}
