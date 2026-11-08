<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\NewsletterFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * An email an admin composes from analyses, polls and open questions and sends to chosen users.
 *
 * @property-read int $id
 * @property-read string $subject
 * @property-read string|null $intro
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read Collection<int, Submission> $submissions
 * @property-read Collection<int, Poll> $polls
 * @property-read Collection<int, AnalysisNote> $questions
 * @property-read Collection<int, User> $recipients
 */
final class Newsletter extends Model
{
    /** @use HasFactory<NewsletterFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'intro' => null,
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'subject' => 'string',
            'intro' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsToMany<Submission, $this>
     */
    public function submissions(): BelongsToMany
    {
        return $this->belongsToMany(Submission::class)->orderBy('submissions.id');
    }

    /**
     * @return BelongsToMany<Poll, $this>
     */
    public function polls(): BelongsToMany
    {
        return $this->belongsToMany(Poll::class)->orderBy('polls.id');
    }

    /**
     * Open factual questions the newsletter asks readers to help settle.
     *
     * @return BelongsToMany<AnalysisNote, $this>
     */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(AnalysisNote::class)->orderBy('analysis_notes.id');
    }

    /**
     * Users the newsletter was sent to, with when it was first sent.
     *
     * @return BelongsToMany<User, $this>
     */
    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
}
