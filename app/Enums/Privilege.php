<?php

declare(strict_types=1);

namespace App\Enums;

enum Privilege: string
{
    case VoteUp = 'vote_up';
    case VoteDown = 'vote_down';
    case ExtraAnalysis = 'extra_analysis';
    case ExtraInvitations = 'extra_invitations';
    case Trusted = 'trusted';

    /**
     * Privileges in the order they are earned.
     *
     * @return list<self>
     */
    public static function ladder(): array
    {
        $privileges = self::cases();

        usort($privileges, fn (self $a, self $b): int => $a->threshold() <=> $b->threshold());

        return $privileges;
    }

    public function threshold(): int
    {
        return config()->integer('kognize.privileges.'.$this->value);
    }

    public function label(): string
    {
        return match ($this) {
            self::VoteUp => 'Vote up',
            self::VoteDown => 'Vote down',
            self::ExtraAnalysis => 'Extra daily analysis',
            self::ExtraInvitations => 'More invitations',
            self::Trusted => 'Trusted reader',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::VoteUp => 'Upvote community notes, sources and replies that are useful.',
            self::VoteDown => 'Downvote content that is wrong or unhelpful. Costs 1 reputation on notes and sources.',
            self::ExtraAnalysis => 'Analyze one more text every day.',
            self::ExtraInvitations => 'Invite three more people.',
            self::Trusted => 'Analyze two more texts every day.',
        };
    }

    public function bonusDailySubmissions(): int
    {
        return match ($this) {
            self::ExtraAnalysis => 1,
            self::Trusted => 2,
            default => 0,
        };
    }

    public function bonusInvitations(): int
    {
        return $this === self::ExtraInvitations ? 3 : 0;
    }
}
