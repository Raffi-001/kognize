<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\NotificationKind;
use App\Enums\QuestionStatus;
use App\Enums\Verdict;
use App\Models\AnalysisNote;
use App\Models\Evidence;
use App\Models\User;

final readonly class ResolveQuestion
{
    public function __construct(private NotifyActivity $notify) {}

    public function handle(AnalysisNote $question, User $moderator, Verdict $verdict, string $resolution): AnalysisNote
    {
        $question->update([
            'status' => QuestionStatus::Resolved,
            'verdict' => $verdict,
            'resolution' => mb_trim($resolution),
            'resolved_by' => $moderator->id,
            'resolved_at' => now(),
        ]);

        $this->notify->handle(
            $question->evidence()->with('user')->get()->map(fn (Evidence $evidence): User => $evidence->user),
            NotificationKind::QuestionResolved,
            $moderator,
            ['type' => 'analysis_note', 'id' => $question->id, 'title' => $question->aspect_label ?? $question->text],
            route('questions.show', $question),
            $verdict->label(),
        );

        return $question;
    }
}
