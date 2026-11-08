<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReputationReason;
use Carbon\CarbonInterface;
use Database\Factories\ReputationEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One change to a user's reputation, caused by a vote.
 *
 * @property-read int $id
 * @property-read string $user_id
 * @property-read int $vote_id
 * @property-read ReputationReason $reason
 * @property-read int $amount
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read User $user
 * @property-read Vote $vote
 */
final class ReputationEvent extends Model
{
    /** @use HasFactory<ReputationEventFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'user_id' => 'string',
            'vote_id' => 'integer',
            'reason' => ReputationReason::class,
            'amount' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Vote, $this>
     */
    public function vote(): BelongsTo
    {
        return $this->belongsTo(Vote::class);
    }
}
