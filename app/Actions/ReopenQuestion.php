<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\QuestionStatus;
use App\Models\AnalysisNote;

final readonly class ReopenQuestion
{
    public function handle(AnalysisNote $question): AnalysisNote
    {
        $question->update([
            'status' => QuestionStatus::Open,
            'verdict' => null,
            'resolution' => null,
            'resolved_by' => null,
            'resolved_at' => null,
        ]);

        return $question;
    }
}
