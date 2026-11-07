<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PollStatus;
use Carbon\CarbonInterface;
use Database\Factories\PollFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read int $id
 * @property-read int|null $analysis_note_id
 * @property-read int|null $mirror_of_id
 * @property-read string $question
 * @property-read PollStatus $status
 * @property-read string|null $approved_by
 * @property-read CarbonInterface|null $published_at
 * @property-read CarbonInterface|null $closed_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read AnalysisNote|null $source
 * @property-read Poll|null $mirrorOf
 * @property-read Poll|null $mirror
 * @property-read Collection<int, PollOption> $options
 * @property-read Collection<int, PollVote> $votes
 */
final class Poll extends Model
{
    /** @use HasFactory<PollFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
        'analysis_note_id' => null,
        'mirror_of_id' => null,
        'approved_by' => null,
        'published_at' => null,
        'closed_at' => null,
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'analysis_note_id' => 'integer',
            'mirror_of_id' => 'integer',
            'question' => 'string',
            'status' => PollStatus::class,
            'approved_by' => 'string',
            'published_at' => 'datetime',
            'closed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * The value question this poll was extracted from.
     *
     * @return BelongsTo<AnalysisNote, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(AnalysisNote::class, 'analysis_note_id');
    }

    /**
     * @return BelongsTo<Poll, $this>
     */
    public function mirrorOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'mirror_of_id');
    }

    /**
     * The same question with the political direction flipped.
     *
     * @return HasOne<Poll, $this>
     */
    public function mirror(): HasOne
    {
        return $this->hasOne(self::class, 'mirror_of_id');
    }

    /**
     * @return HasMany<PollOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class)->orderBy('position');
    }

    /**
     * @return HasMany<PollVote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }
}
