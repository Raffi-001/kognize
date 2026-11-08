<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasVotes;
use Carbon\CarbonInterface;
use Database\Factories\UserNoteFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property-read int $id
 * @property-read int $submission_id
 * @property-read string $user_id
 * @property-read int $score
 * @property-read string $annotation_id
 * @property-read string|null $label
 * @property-read string $text
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read Submission $submission
 * @property-read User $user
 * @property-read Collection<int, Vote> $votes
 * @property-read Collection<int, Comment> $comments
 */
final class UserNote extends Model
{
    /** @use HasFactory<UserNoteFactory> */
    use HasFactory;

    use HasVotes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'score' => 0,
        'label' => null,
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'submission_id' => 'integer',
            'user_id' => 'string',
            'score' => 'integer',
            'annotation_id' => 'string',
            'label' => 'string',
            'text' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Submission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphMany<Comment, $this>
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
