<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EvidenceAssessment;
use App\Enums\EvidenceVerification;
use App\Enums\Stance;
use Carbon\CarbonInterface;
use Database\Factories\EvidenceFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $analysis_note_id
 * @property-read string $user_id
 * @property-read Stance $stance
 * @property-read string $url
 * @property-read string $quote
 * @property-read string|null $explanation
 * @property-read EvidenceVerification $verification
 * @property-read string|null $page_title
 * @property-read string|null $snapshot
 * @property-read EvidenceAssessment|null $assessment
 * @property-read string|null $assessment_reason
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read AnalysisNote $question
 * @property-read User $user
 */
#[Table(name: 'evidence')]
final class Evidence extends Model
{
    /** @use HasFactory<EvidenceFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'explanation' => null,
        'verification' => 'pending',
        'page_title' => null,
        'snapshot' => null,
        'assessment' => null,
        'assessment_reason' => null,
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'analysis_note_id' => 'integer',
            'user_id' => 'string',
            'stance' => Stance::class,
            'url' => 'string',
            'quote' => 'string',
            'explanation' => 'string',
            'verification' => EvidenceVerification::class,
            'page_title' => 'string',
            'snapshot' => 'string',
            'assessment' => EvidenceAssessment::class,
            'assessment_reason' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AnalysisNote, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(AnalysisNote::class, 'analysis_note_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
