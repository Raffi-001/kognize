<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UrlSubmissionStatus;
use Carbon\CarbonInterface;
use Database\Factories\UrlSubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read string $user_id
 * @property-read int|null $submission_id
 * @property-read string $url
 * @property-read UrlSubmissionStatus $status
 * @property-read string|null $error_message
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read User $user
 * @property-read Submission|null $submission
 */
final class UrlSubmission extends Model
{
    /** @use HasFactory<UrlSubmissionFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'submission_id' => null,
        'error_message' => null,
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'user_id' => 'string',
            'submission_id' => 'integer',
            'url' => 'string',
            'status' => UrlSubmissionStatus::class,
            'error_message' => 'string',
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
     * @return BelongsTo<Submission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
