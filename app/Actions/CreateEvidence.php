<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Stance;
use App\Jobs\VerifyEvidenceJob;
use App\Models\AnalysisNote;
use App\Models\Evidence;
use App\Models\User;

final readonly class CreateEvidence
{
    public function handle(AnalysisNote $question, User $user, Stance $stance, string $url, string $quote, ?string $explanation = null): Evidence
    {
        $evidence = $question->evidence()->create([
            'user_id' => $user->id,
            'stance' => $stance,
            'url' => $url,
            'quote' => mb_trim($quote),
            'explanation' => $explanation === null || mb_trim($explanation) === '' ? null : mb_trim($explanation),
        ]);

        dispatch(new VerifyEvidenceJob($evidence));

        return $evidence;
    }
}
