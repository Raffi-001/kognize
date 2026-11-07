<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Ethics;
use App\Enums\EvidenceAssessment;
use App\Enums\EvidenceVerification;
use App\Enums\QuestionKind;
use App\Enums\QuestionStatus;
use App\Enums\Verdict;
use Carbon\CarbonInterface;
use Database\Factories\AnalysisNoteFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property-read int $id
 * @property-read int $submission_analysis_id
 * @property-read string|null $annotation_id
 * @property-read string|null $label
 * @property-read string|null $aspect_label
 * @property-read string $text
 * @property-read Ethics $ethics
 * @property-read int $strength
 * @property-read QuestionKind|null $question_kind
 * @property-read QuestionStatus|null $status
 * @property-read Verdict|null $verdict
 * @property-read string|null $resolution
 * @property-read string|null $resolved_by
 * @property-read CarbonInterface|null $resolved_at
 * @property-read int|null $evidence_count
 * @property-read int|null $comments_count
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read SubmissionAnalysis $analysis
 * @property-read Collection<int, Comment> $comments
 * @property-read Collection<int, Evidence> $evidence
 * @property-read Poll|null $poll
 * @property-read User|null $resolver
 */
final class AnalysisNote extends Model
{
    /** @use HasFactory<AnalysisNoteFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'ethics' => 'neutral',
        'strength' => 5,
        'question_kind' => null,
        'status' => null,
        'verdict' => null,
        'resolution' => null,
        'resolved_by' => null,
        'resolved_at' => null,
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'submission_analysis_id' => 'integer',
            'annotation_id' => 'string',
            'label' => 'string',
            'aspect_label' => 'string',
            'text' => 'string',
            'ethics' => Ethics::class,
            'strength' => 'integer',
            'question_kind' => QuestionKind::class,
            'status' => QuestionStatus::class,
            'verdict' => Verdict::class,
            'resolution' => 'string',
            'resolved_by' => 'string',
            'resolved_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SubmissionAnalysis, $this>
     */
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(SubmissionAnalysis::class, 'submission_analysis_id');
    }

    /**
     * @return MorphMany<Comment, $this>
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Evidence readers submitted to answer this (factual) question.
     *
     * @return HasMany<Evidence, $this>
     */
    public function evidence(): HasMany
    {
        return $this->hasMany(Evidence::class)->latest('id');
    }

    /**
     * The public poll extracted from this (value) question.
     *
     * @return HasOne<Poll, $this>
     */
    public function poll(): HasOne
    {
        return $this->hasOne(Poll::class)->whereNull('mirror_of_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * The verdict the verified evidence points to, for a moderator to confirm.
     */
    public function suggestedVerdict(): ?Verdict
    {
        $assessments = $this->evidence
            ->filter(fn (Evidence $evidence): bool => $evidence->verification === EvidenceVerification::Verified)
            ->map(fn (Evidence $evidence): ?EvidenceAssessment => $evidence->assessment);

        $supports = $assessments->contains(EvidenceAssessment::Supports);
        $refutes = $assessments->contains(EvidenceAssessment::Refutes);

        return match (true) {
            $supports && $refutes => Verdict::Contested,
            $supports => Verdict::Supported,
            $refutes => Verdict::Refuted,
            default => null,
        };
    }
}
