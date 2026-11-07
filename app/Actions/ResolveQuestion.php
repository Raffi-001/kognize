<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\QuestionStatus;
use App\Enums\Verdict;
use App\Models\AnalysisNote;
use App\Models\User;

final readonly class ResolveQuestion
{
    public function handle(AnalysisNote $question, User $moderator, Verdict $verdict, string $resolution): AnalysisNote
    {
        $question->update([
            'status' => QuestionStatus::Resolved,
            'verdict' => $verdict,
            'resolution' => mb_trim($resolution),
            'resolved_by' => $moderator->id,
            'resolved_at' => now(),
        ]);

        return $question;
    }
}
