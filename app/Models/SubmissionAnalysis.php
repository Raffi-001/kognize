<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AnalysisStatus;
use App\Enums\AnalysisType;
use Carbon\CarbonInterface;
use Database\Factories\SubmissionAnalysisFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read int $submission_id
 * @property-read AnalysisType $type
 * @property-read AnalysisStatus $status
 * @property-read array<string, mixed>|null $result
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read Submission $submission
 * @property-read Collection<int, AnalysisNote> $notes
 */
final class SubmissionAnalysis extends Model
{
    /** @use HasFactory<SubmissionAnalysisFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'not_started',
        'result' => null,
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'submission_id' => 'integer',
            'type' => AnalysisType::class,
            'status' => AnalysisStatus::class,
            'result' => 'array',
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
     * @return HasMany<AnalysisNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(AnalysisNote::class);
    }
}
