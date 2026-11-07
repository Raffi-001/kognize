<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\VerifyEvidence;
use App\Enums\EvidenceVerification;
use App\Models\Evidence;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;

#[Timeout(120)]
#[Tries(1)]
final class VerifyEvidenceJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Evidence $evidence) {}

    public function handle(VerifyEvidence $action): void
    {
        $action->handle($this->evidence);
    }

    public function failed(): void
    {
        $this->evidence->update(['verification' => EvidenceVerification::Unreachable]);
    }
}
