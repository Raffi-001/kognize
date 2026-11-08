<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasVotes;
use Carbon\CarbonInterface;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property-read int $id
 * @property-read string $commentable_type
 * @property-read int $commentable_id
 * @property-read string $user_id
 * @property-read int $score
 * @property-read string $text
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read AnalysisNote|UserNote $commentable
 * @property-read User $user
 * @property-read Collection<int, Vote> $votes
 */
final class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    use HasVotes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'score' => 0,
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'commentable_type' => 'string',
            'commentable_id' => 'integer',
            'user_id' => 'string',
            'score' => 'integer',
            'text' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
