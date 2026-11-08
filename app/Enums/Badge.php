<?php

declare(strict_types=1);

namespace App\Enums;

enum Badge: string
{
    case Supporter = 'supporter';
    case Critic = 'critic';
    case Annotator = 'annotator';
    case Commentator = 'commentator';
    case Researcher = 'researcher';
    case FactChecker = 'fact_checker';
    case Recruiter = 'recruiter';
    case GoodNote = 'good_note';
    case GreatNote = 'great_note';
    case ExcellentNote = 'excellent_note';
    case Respected = 'respected';
    case Pillar = 'pillar';

    public function tier(): BadgeTier
    {
        return match ($this) {
            self::FactChecker, self::Recruiter, self::GreatNote, self::Respected => BadgeTier::Silver,
            self::ExcellentNote, self::Pillar => BadgeTier::Gold,
            default => BadgeTier::Bronze,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Supporter => 'Supporter',
            self::Critic => 'Critic',
            self::Annotator => 'Annotator',
            self::Commentator => 'Commentator',
            self::Researcher => 'Researcher',
            self::FactChecker => 'Fact checker',
            self::Recruiter => 'Recruiter',
            self::GoodNote => 'Good note',
            self::GreatNote => 'Great note',
            self::ExcellentNote => 'Excellent note',
            self::Respected => 'Respected',
            self::Pillar => 'Pillar',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Supporter => 'Cast your first upvote.',
            self::Critic => 'Cast your first downvote.',
            self::Annotator => 'Added your first community note.',
            self::Commentator => 'Wrote 10 replies.',
            self::Researcher => 'Cited a source whose quote was found on the page.',
            self::FactChecker => 'Cited 10 sources whose quotes were found on the page.',
            self::Recruiter => '5 people joined with your invitation codes.',
            self::GoodNote => sprintf('A community note reached a score of %d.', $this->noteScore()),
            self::GreatNote => sprintf('A community note reached a score of %d.', $this->noteScore()),
            self::ExcellentNote => sprintf('A community note reached a score of %d.', $this->noteScore()),
            self::Respected => 'Reached 500 reputation.',
            self::Pillar => 'Reached 2,000 reputation.',
        };
    }

    /**
     * Badges earned again for every community note that qualifies.
     */
    public function isPerNote(): bool
    {
        return $this->noteScore() > 0;
    }

    public function noteScore(): int
    {
        return match ($this) {
            self::GoodNote => 5,
            self::GreatNote => 15,
            self::ExcellentNote => 40,
            default => 0,
        };
    }
}
