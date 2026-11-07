<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubmissionKind;
use App\Enums\SubmissionState;
use Carbon\CarbonInterface;
use Database\Factories\SubmissionFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property-read int $id
 * @property-read string $user_id
 * @property-read string|null $title
 * @property-read string $body
 * @property-read string|null $source
 * @property-read string|null $normalized_url
 * @property-read string|null $url_hash
 * @property-read string $content_hash
 * @property-read SubmissionState $state
 * @property-read SubmissionKind|null $kind
 * @property-read array<string, mixed>|null $argument_map
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read User $user
 * @property-read Collection<int, SubmissionAnalysis> $analyses
 * @property-read Collection<int, UserNote> $userNotes
 */
final class Submission extends Model
{
    /** @use HasFactory<SubmissionFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'state' => 'pending',
        'kind' => null,
        'argument_map' => null,
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'user_id' => 'string',
            'title' => 'string',
            'body' => 'string',
            'source' => 'string',
            'normalized_url' => 'string',
            'url_hash' => 'string',
            'content_hash' => 'string',
            'state' => SubmissionState::class,
            'kind' => SubmissionKind::class,
            'argument_map' => 'array',
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
     * @return HasMany<SubmissionAnalysis, $this>
     */
    public function analyses(): HasMany
    {
        return $this->hasMany(SubmissionAnalysis::class);
    }

    /**
     * @return HasMany<UserNote, $this>
     */
    public function userNotes(): HasMany
    {
        return $this->hasMany(UserNote::class);
    }

    /**
     * @return MorphMany<Report, $this>
     */
    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }
}
